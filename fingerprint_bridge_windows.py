#!/usr/bin/env python3
"""
Fingerprint Bridge Windows para gym_api
========================================
Servidor WebSocket local para Windows que conecta el frontend Vue.js
con el lector DigitalPersona U.are.U via dpfpdd.dll + dpfj.dll.

Requisitos:
  - DigitalPersona U.are.U SDK instalado
      (incluye dpfpdd.dll y dpfj.dll en C:\\Program Files\\DigitalPersona\\...)
  - pip install websockets requests

Uso:
  python fingerprint_bridge_windows.py
"""

import asyncio
import json
import base64
import ctypes
import os
import socket as _socket
import subprocess
import threading

import requests
import websockets

# ── Configuracion ──────────────────────────────────────────────────────────────

WS_HOST = "localhost"
WS_PORT = 3001

_SDK_SEARCH_DIRS = [
    os.path.dirname(os.path.abspath(__file__)),  # mismo directorio del script
    r"C:\Program Files\DigitalPersona\U.are.U SDK\Windows\Lib\x64",
    r"C:\Program Files\DigitalPersona\U.are.U SDK\Windows\Lib\Win32",
    r"C:\Program Files (x86)\DigitalPersona\U.are.U SDK\Windows\Lib\x86",
]

ANSI_FMD      = 0x001B0001  # DPFJ_FMD_ANSI_378_2004
MAX_FMD_SIZE  = 1600        # bytes maximos para un FMD
MATCH_THRESHOLD = 21474836  # dpfj_compare: menor score = mejor match

# Liberar puerto si ya esta en uso por una instancia anterior
def _free_port():
    with _socket.socket(_socket.AF_INET, _socket.SOCK_STREAM) as s:
        if s.connect_ex((WS_HOST, WS_PORT)) == 0:
            result = subprocess.run(
                ["netstat", "-ano"],
                capture_output=True, text=True
            )
            for line in result.stdout.splitlines():
                if f":{WS_PORT}" in line and "LISTENING" in line:
                    parts = line.split()
                    pid = parts[-1]
                    try:
                        subprocess.run(["taskkill", "/PID", pid, "/F"],
                                       capture_output=True)
                    except Exception:
                        pass

_free_port()


# ── Helpers ────────────────────────────────────────────────────────────────────

def _find_dll(name: str) -> str:
    for directory in _SDK_SEARCH_DIRS:
        path = os.path.join(directory, name)
        if os.path.exists(path):
            return path
    raise FileNotFoundError(
        f"{name} no encontrado. "
        f"Instala el DigitalPersona U.are.U SDK."
    )


# ── ctypes structures para dpfpdd.dll ─────────────────────────────────────────

DPFPDD_MAX_STR_LEN = 1024
DPFPDD_SUCCESS     = 0x00000000


class DPFPDD_HW_DESCR(ctypes.Structure):
    _fields_ = [
        ("vendor_id",      ctypes.c_uint),
        ("product_id",     ctypes.c_uint),
        ("serial_num",     ctypes.c_char * DPFPDD_MAX_STR_LEN),
        ("hw_rev",         ctypes.c_char * DPFPDD_MAX_STR_LEN),
        ("fw_rev",         ctypes.c_char * DPFPDD_MAX_STR_LEN),
        ("sb_rev",         ctypes.c_char * DPFPDD_MAX_STR_LEN),
        ("has_fp_storage", ctypes.c_uint),
    ]


class DPFPDD_DEV_INFO(ctypes.Structure):
    _fields_ = [
        ("size",       ctypes.c_uint),
        ("name",       ctypes.c_char * DPFPDD_MAX_STR_LEN),
        ("descr",      ctypes.c_char * DPFPDD_MAX_STR_LEN),
        ("modality",   ctypes.c_int),
        ("technology", ctypes.c_int),
        ("hw_descr",   DPFPDD_HW_DESCR),
    ]


class DPFPDD_IMAGE_INFO(ctypes.Structure):
    _fields_ = [
        ("size",   ctypes.c_uint),
        ("width",  ctypes.c_uint),
        ("height", ctypes.c_uint),
        ("res",    ctypes.c_uint),  # DPI
        ("bpp",    ctypes.c_uint),  # bits per pixel
    ]


class DPFPDD_CAPTURE_PARAM(ctypes.Structure):
    _fields_ = [
        ("size",       ctypes.c_uint),
        ("prio",       ctypes.c_int),   # 0 = NORMAL
        ("timeout",    ctypes.c_uint),  # ms; 0 = esperar indefinidamente
        ("image_fmt",  ctypes.c_int),   # 0 = PIXEL_BUFFER (raw grayscale)
        ("image_proc", ctypes.c_int),   # 1 = DEFAULT
        ("image_res",  ctypes.c_uint),  # 0 = resolucion nativa
    ]


class DPFPDD_CAPTURE_RESULT(ctypes.Structure):
    """
    Header del resultado de captura (image_data sigue inmediatamente en
    el buffer raw; no se incluye aqui por ser de longitud variable).
    """
    _fields_ = [
        ("size",       ctypes.c_uint),
        ("success",    ctypes.c_int),
        ("quality",    ctypes.c_int),
        ("info",       DPFPDD_IMAGE_INFO),
        ("image_size", ctypes.c_uint),
    ]


# ── Dispositivo ────────────────────────────────────────────────────────────────

class FingerprintDevice:
    def __init__(self):
        self._dpfpdd: ctypes.CDLL | None = None
        self._dpfj:   ctypes.CDLL | None = None
        self._dev = ctypes.c_void_p(None)

    # ── ciclo de vida ──

    def open(self) -> str:
        """Carga las DLLs y abre el primer lector disponible. Retorna su descripcion."""
        self._dpfpdd = ctypes.CDLL(_find_dll("dpfpdd.dll"))
        self._dpfj   = ctypes.CDLL(_find_dll("dpfj.dll"))

        cnt = ctypes.c_uint(0)
        self._dpfpdd.dpfpdd_query_devices(ctypes.byref(cnt), None)
        if cnt.value == 0:
            raise RuntimeError("No se encontro ningun lector de huellas conectado.")

        devs = (DPFPDD_DEV_INFO * cnt.value)()
        for i in range(cnt.value):
            devs[i].size = ctypes.sizeof(DPFPDD_DEV_INFO)
        self._dpfpdd.dpfpdd_query_devices(ctypes.byref(cnt), devs)

        ret = self._dpfpdd.dpfpdd_open(devs[0].name, ctypes.byref(self._dev))
        if ret != DPFPDD_SUCCESS:
            raise RuntimeError(f"No se pudo abrir el lector: error {ret:#010x}")

        descr = devs[0].descr.decode(errors="replace").strip()
        return descr or devs[0].name.decode(errors="replace").strip()

    def close(self):
        if self._dev and self._dpfpdd:
            try:
                self._dpfpdd.dpfpdd_close(self._dev)
            except Exception:
                pass
        self._dev = ctypes.c_void_p(None)

    def _reopen(self):
        self.close()
        import time; time.sleep(1)
        self.open()

    # ── captura ──

    def _capture_raw(self) -> tuple[bytes, int, int, int]:
        """
        Captura una imagen del dedo en formato PIXEL_BUFFER (grayscale 8bpp).
        Retorna (image_bytes, width, height, dpi).
        """
        param = DPFPDD_CAPTURE_PARAM(
            size       = ctypes.sizeof(DPFPDD_CAPTURE_PARAM),
            prio       = 0,      # NORMAL
            timeout    = 20000,  # 20 segundos
            image_fmt  = 0,      # PIXEL_BUFFER
            image_proc = 1,      # DEFAULT
            image_res  = 0,      # nativa
        )

        # Primera llamada: obtener tamano requerido del buffer
        result_size = ctypes.c_uint(0)
        self._dpfpdd.dpfpdd_capture(
            self._dev, ctypes.byref(param), ctypes.byref(result_size), None
        )

        if result_size.value == 0:
            result_size = ctypes.c_uint(512 * 1024)  # fallback 512 KB

        # Segunda llamada: captura real
        buf = ctypes.create_string_buffer(result_size.value)
        hdr = DPFPDD_CAPTURE_RESULT.from_buffer(buf)
        hdr.size = result_size.value

        ret = self._dpfpdd.dpfpdd_capture(
            self._dev, ctypes.byref(param), ctypes.byref(result_size), buf
        )
        if ret != DPFPDD_SUCCESS:
            raise RuntimeError(f"Captura fallida: error {ret:#010x}")
        if not hdr.success:
            raise RuntimeError("Calidad de imagen insuficiente. Intenta de nuevo.")

        offset      = ctypes.sizeof(DPFPDD_CAPTURE_RESULT)
        image_bytes = bytes(buf[offset : offset + hdr.image_size])

        return image_bytes, hdr.info.width, hdr.info.height, hdr.info.res

    # ── extraccion de FMD ──

    def _raw_to_fmd(self, image: bytes, width: int, height: int, dpi: int) -> bytes:
        """Convierte imagen raw a ANSI 378 FMD usando dpfj_create_fmd_from_raw."""
        img_size = len(image)
        c_img    = (ctypes.c_ubyte * img_size)(*image)
        c_fmd    = (ctypes.c_ubyte * MAX_FMD_SIZE)()
        c_size   = ctypes.c_uint(MAX_FMD_SIZE)

        ret = self._dpfj.dpfj_create_fmd_from_raw(
            c_img, img_size,
            width, height, dpi,
            0,        # finger_pos = unknown
            0,        # cbeff_id
            ANSI_FMD,
            c_fmd, ctypes.byref(c_size),
        )
        if ret != 0:
            raise RuntimeError(f"FMD extraction fallida: {ret:#010x}")

        return bytes(c_fmd[: c_size.value])

    # ── API publica ──

    def enroll(self) -> bytes:
        """Captura una huella y retorna el FMD en bytes (para guardar en API)."""
        img, w, h, dpi = self._capture_raw()
        return self._raw_to_fmd(img, w, h, dpi)

    def identify(self, stored_fmds: list[bytes]) -> int | None:
        """
        Captura una huella y la compara 1:N contra la lista de FMDs almacenados.
        Retorna el indice del mejor match (score < MATCH_THRESHOLD) o None.
        """
        img, w, h, dpi = self._capture_raw()
        probe_fmd  = self._raw_to_fmd(img, w, h, dpi)
        probe_size = len(probe_fmd)
        c_probe    = (ctypes.c_ubyte * probe_size)(*probe_fmd)

        best_score = MATCH_THRESHOLD
        best_idx   = None

        for i, tmpl_fmd in enumerate(stored_fmds):
            if not tmpl_fmd:
                continue
            tmpl_size = len(tmpl_fmd)
            c_tmpl    = (ctypes.c_ubyte * tmpl_size)(*tmpl_fmd)
            c_score   = ctypes.c_uint(0)

            ret = self._dpfj.dpfj_compare(
                ANSI_FMD, c_probe, probe_size, 0,
                ANSI_FMD, c_tmpl,  tmpl_size,  0,
                ctypes.byref(c_score),
            )
            if ret == 0 and c_score.value < best_score:
                best_score = c_score.value
                best_idx   = i

        return best_idx


# ── Llamadas al API de gym_api ─────────────────────────────────────────────────

def api_save_fingerprint(api_url: str, token: str, member_id: int, fmd_b64: str):
    resp = requests.post(
        f"{api_url}/members/{member_id}/fingerprint",
        json={"fingerprint_data": fmd_b64},
        headers={"Authorization": f"Bearer {token}", "Accept": "application/json"},
        timeout=10,
    )
    resp.raise_for_status()
    return resp.json()


def api_get_fingerprints(api_url: str, gimnasio_id: int) -> list:
    """Retorna [{id, name, fingerprint_data}, ...] del gimnasio."""
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
    # 403 = membresia vencida — resultado valido, no un error de red
    if resp.status_code in (200, 403):
        return resp.json()
    resp.raise_for_status()
    return resp.json()


# ── Handlers WebSocket ─────────────────────────────────────────────────────────

_main_loop: asyncio.AbstractEventLoop | None = None
_device_lock: asyncio.Lock | None = None


async def _send(ws, data: dict):
    await ws.send(json.dumps(data))


async def handle_capture(ws):
    """
    Captura el template sin guardarlo.
    Util al crear un miembro nuevo desde el frontend.
    """
    await _send(ws, {"event": "status", "message": "Coloca el dedo en el lector..."})

    async with _device_lock:
        try:
            fmd_bytes = await asyncio.get_event_loop().run_in_executor(
                None, fp_device.enroll
            )
            fmd_b64 = base64.b64encode(fmd_bytes).decode()
            await _send(ws, {"event": "captured", "success": True, "template": fmd_b64})
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

    async with _device_lock:
        try:
            fmd_bytes = await asyncio.get_event_loop().run_in_executor(
                None, fp_device.enroll
            )
            fmd_b64 = base64.b64encode(fmd_bytes).decode()
            api_save_fingerprint(api_url, token, member_id, fmd_b64)
            await _send(ws, {
                "event":   "enrolled",
                "success": True,
                "message": "Huella registrada correctamente.",
            })
        except Exception as e:
            await _send(ws, {"event": "enrolled", "success": False, "message": str(e)})


async def handle_identify(ws, payload: dict):
    """
    Identifica quien pone el dedo y registra el acceso en el API.
    Payload: { action: "identify", gimnasio_id: int, api_url: str }
    """
    if not payload.get("gimnasio_id"):
        await _send(ws, {
            "event": "identified", "success": False,
            "message": "Falta gimnasio_id en la peticion."
        })
        return

    gimnasio_id = int(payload["gimnasio_id"])
    api_url     = payload["api_url"].rstrip("/")

    await _send(ws, {"event": "status", "message": "Coloca el dedo en el lector..."})

    async with _device_lock:
        try:
            members = api_get_fingerprints(api_url, gimnasio_id)
            if not members:
                await _send(ws, {
                    "event": "identified", "success": False,
                    "message": "No hay huellas enroladas en este gimnasio.",
                })
                return

            # Decodificar templates almacenados a bytes FMD
            stored_fmds = []
            for m in members:
                try:
                    raw = base64.b64decode(m["fingerprint_data"])
                    # Soporte para templates legacy guardados como JSON
                    # {"data":"<b64>","width":N,"height":N,"dpi":N}
                    try:
                        parsed = json.loads(raw)
                        raw = base64.b64decode(parsed["data"])
                    except Exception:
                        pass
                    stored_fmds.append(raw)
                except Exception:
                    stored_fmds.append(b"")

            idx = await asyncio.get_event_loop().run_in_executor(
                None, lambda: fp_device.identify(stored_fmds)
            )

            if idx is None:
                await _send(ws, {
                    "event": "identified", "success": False,
                    "message": "Huella no reconocida.",
                })
                return

            matched = members[idx]
            result  = api_log_access(api_url, gimnasio_id, matched["id"])

            await _send(ws, {
                "event":   "identified",
                "success": result.get("success", False),
                "message": result.get("message", ""),
                "member":  result.get("member", matched),
            })

        except Exception as e:
            msg = str(e)
            await _send(ws, {"event": "identified", "success": False, "message": msg})
            try:
                fp_device._reopen()
            except Exception:
                pass


# ── WebSocket principal ────────────────────────────────────────────────────────

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
                await _send(websocket, {
                    "event": "error",
                    "message": f"Accion desconocida: {action}"
                })
    except websockets.exceptions.ConnectionClosed:
        print("[-] Cliente desconectado.")


# ── Arranque ───────────────────────────────────────────────────────────────────

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
