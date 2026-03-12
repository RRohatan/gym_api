#!/usr/bin/env python3
"""
Fingerprint Bridge para gym_api
================================
Servidor WebSocket local que conecta el frontend Vue.js con el lector
DigitalPersona U.are.U via libfprint.

Requisitos:
  sudo apt-get install gir1.2-fprint-2.0
  pip3 install websockets requests

Uso:
  python3 fingerprint_bridge.py
"""

import asyncio
import json
import base64
import requests
import websockets
import gi
import threading

gi.require_version("FPrint", "2.0")
gi.require_version("Gio", "2.0")
from gi.repository import FPrint, GLib, Gio

WS_HOST = "localhost"
WS_PORT = 3001

# Liberar puerto si ya está en uso por una instancia anterior
import socket as _socket
def _free_port():
    with _socket.socket(_socket.AF_INET, _socket.SOCK_STREAM) as s:
        if s.connect_ex((WS_HOST, WS_PORT)) == 0:
            import os, signal
            import subprocess
            result = subprocess.run(["fuser", f"{WS_PORT}/tcp"], capture_output=True, text=True)
            for pid in result.stdout.split():
                try:
                    os.kill(int(pid), signal.SIGKILL)
                except Exception:
                    pass
_free_port()


# ──────────────────────────────────────────────────────────
# Manejo del dispositivo libfprint (bloqueante, corre en thread)
# ──────────────────────────────────────────────────────────

class FingerprintDevice:
    def __init__(self):
        self._ctx = None
        self._dev = None

    def open(self) -> str:
        self._ctx = FPrint.Context()
        devices = self._ctx.get_devices()
        if not devices:
            raise RuntimeError("No se encontró ningún lector de huellas conectado.")
        self._dev = devices[0]
        self._dev.open_sync()
        return self._dev.get_name()

    def close(self):
        if self._dev:
            try:
                self._dev.close_sync()
            except Exception:
                pass

    def enroll(self, on_progress=None) -> bytes:
        """Captura una huella y devuelve el template serializado como bytes."""
        template = FPrint.Print.new(self._dev)
        enrolled = self._dev.enroll_sync(template, None, on_progress, None)
        return bytes(enrolled.serialize())

    def _reopen(self):
        """Cierra y reabre el dispositivo para limpiar su estado."""
        try:
            self._dev.close_sync()
        except Exception:
            pass
        import time
        time.sleep(1)
        self._dev.open_sync()

    def identify(self, stored_prints: list, timeout: int = 20):
        """
        Escanea un dedo y lo compara contra la lista de FPrint.Print.
        Devuelve el índice del match o None. Cancela tras `timeout` segundos.
        """
        if not stored_prints:
            return None

        cancellable = Gio.Cancellable()
        timer = threading.Timer(timeout, cancellable.cancel)
        timer.start()

        try:
            matched, _ = self._dev.identify_sync(stored_prints, cancellable, None, None)
            timer.cancel()
        except Exception:
            timer.cancel()
            self._reopen()
            raise

        if matched is None:
            return None
        for i, p in enumerate(stored_prints):
            if p == matched:
                return i
        return None


# ──────────────────────────────────────────────────────────
# Llamadas al API de gym_api
# ──────────────────────────────────────────────────────────

def api_save_fingerprint(api_url: str, token: str, member_id: int, template_b64: str):
    resp = requests.post(
        f"{api_url}/members/{member_id}/fingerprint",
        json={"fingerprint_data": template_b64},
        headers={"Authorization": f"Bearer {token}", "Accept": "application/json"},
        timeout=10,
    )
    resp.raise_for_status()
    return resp.json()


def api_get_fingerprints(api_url: str, gimnasio_id: int) -> list:
    """Devuelve [{id, name, fingerprint_data}, ...] del gimnasio."""
    resp = requests.get(
        f"{api_url}/access/fingerprints/{gimnasio_id}",
        headers={"Accept": "application/json"},
        timeout=10,
    )
    resp.raise_for_status()
    return resp.json()


def api_log_access(api_url: str, gimnasio_id: int, member_id: int) -> dict:
    resp = requests.post(
        f"{api_url}/access/fingerprint",
        json={"member_id": member_id, "gimnasio_id": gimnasio_id},
        headers={"Accept": "application/json"},
        timeout=10,
    )
    # 403 = membresía vencida — es un resultado válido, no un error
    if resp.status_code in (200, 403):
        return resp.json()
    resp.raise_for_status()
    return resp.json()


# ──────────────────────────────────────────────────────────
# Handlers de acciones WebSocket
# ──────────────────────────────────────────────────────────

_main_loop = None
_device_lock: asyncio.Lock | None = None


async def _send(ws, data: dict):
    await ws.send(json.dumps(data))


async def handle_capture(ws):
    """Solo captura el template sin guardarlo. Útil al crear un miembro nuevo."""
    await _send(ws, {"event": "status", "message": "Coloca el dedo en el lector..."})

    def progress_cb(device, completed_stages, print_, error, user_data):
        asyncio.run_coroutine_threadsafe(
            _send(ws, {"event": "progress", "stage": completed_stages}),
            _main_loop,
        )

    async with _device_lock:
        try:
            raw = await asyncio.get_event_loop().run_in_executor(
                None, lambda: fp_device.enroll(on_progress=progress_cb)
            )
            template_b64 = base64.b64encode(raw).decode()
            await _send(ws, {"event": "captured", "success": True, "template": template_b64})
        except Exception as e:
            await _send(ws, {"event": "captured", "success": False, "message": str(e)})


async def handle_enroll(ws, payload: dict):
    """
    Enrola la huella de un miembro y la guarda en el API.
    Payload: { action: "enroll", member_id: int, api_url: str, token: str }
    """
    member_id = payload["member_id"]
    api_url   = payload["api_url"].rstrip("/")
    token     = payload["token"]

    await _send(ws, {"event": "status", "message": "Coloca el dedo en el lector..."})

    def progress_cb(device, completed_stages, print_, error, user_data):
        asyncio.run_coroutine_threadsafe(
            _send(ws, {"event": "progress", "stage": completed_stages}),
            _main_loop,
        )

    async with _device_lock:
        try:
            raw = await asyncio.get_event_loop().run_in_executor(
                None, lambda: fp_device.enroll(on_progress=progress_cb)
            )
            template_b64 = base64.b64encode(raw).decode()
            api_save_fingerprint(api_url, token, member_id, template_b64)
            await _send(ws, {
                "event": "enrolled",
                "success": True,
                "message": "Huella registrada correctamente.",
            })
        except Exception as e:
            await _send(ws, {"event": "enrolled", "success": False, "message": str(e)})


async def handle_identify(ws, payload: dict):
    """
    Identifica quién pone el dedo y registra el acceso en el API.
    Payload: { action: "identify", gimnasio_id: int, api_url: str }
    """
    if "gimnasio_id" not in payload or not payload["gimnasio_id"]:
        await _send(ws, {"event": "identified", "success": False, "message": "Falta gimnasio_id en la peticion."})
        return
    gimnasio_id = int(payload["gimnasio_id"])
    api_url     = payload["api_url"].rstrip("/")

    await _send(ws, {"event": "status", "message": "Coloca el dedo en el lector..."})

    async with _device_lock:
        try:
            members = api_get_fingerprints(api_url, gimnasio_id)
            if not members:
                await _send(ws, {
                    "event": "identified",
                    "success": False,
                    "message": "No hay huellas enroladas en este gimnasio.",
                })
                return

            stored_prints = []
            for m in members:
                raw = base64.b64decode(m["fingerprint_data"])
                fp_print = FPrint.Print.deserialize(bytearray(raw))
                stored_prints.append(fp_print)

            idx = await asyncio.get_event_loop().run_in_executor(
                None, lambda: fp_device.identify(stored_prints)
            )

            if idx is None:
                await _send(ws, {
                    "event": "identified",
                    "success": False,
                    "message": "Huella no reconocida.",
                })
                return

            matched = members[idx]
            result  = api_log_access(api_url, gimnasio_id, matched["id"])

            await _send(ws, {
                "event": "identified",
                "success": result.get("success", False),
                "message": result.get("message", ""),
                "member": result.get("member", matched),
            })

        except Exception as e:
            msg = str(e)
            if "overheat" in msg.lower():
                msg = "Lector en reposo por temperatura. Espera un momento..."
            await _send(ws, {"event": "identified", "success": False, "message": msg})


# ──────────────────────────────────────────────────────────
# WebSocket principal
# ──────────────────────────────────────────────────────────

async def ws_handler(websocket):
    print(f"[+] Cliente conectado: {websocket.remote_address}")
    try:
        async for raw in websocket:
            try:
                payload = json.loads(raw)
            except json.JSONDecodeError:
                await _send(websocket, {"event": "error", "message": "JSON invalido."})
                continue

            action = payload.get("action")
            if action == "capture":
                await handle_capture(websocket)
            elif action == "enroll":
                await handle_enroll(websocket, payload)
            elif action == "identify":
                await handle_identify(websocket, payload)
            elif action == "ping":
                await _send(websocket, {"event": "pong"})
            else:
                await _send(websocket, {"event": "error", "message": f"Accion desconocida: {action}"})
    except websockets.exceptions.ConnectionClosed:
        print("[-] Cliente desconectado.")


# ──────────────────────────────────────────────────────────
# Arranque
# ──────────────────────────────────────────────────────────

fp_device = FingerprintDevice()


async def main():
    global _main_loop, _device_lock
    _main_loop   = asyncio.get_event_loop()
    _device_lock = asyncio.Lock()

    try:
        name = fp_device.open()
        print(f"[OK] Lector abierto: {name}")
    except Exception as e:
        print(f"[ERROR] No se pudo abrir el lector: {e}")
        return

    print(f"[OK] WebSocket escuchando en ws://{WS_HOST}:{WS_PORT}")
    async with websockets.serve(ws_handler, WS_HOST, WS_PORT):
        await asyncio.Future()


if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        fp_device.close()
        print("\n[·] Servicio detenido.")
