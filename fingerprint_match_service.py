#!/usr/bin/env python3
"""
Fingerprint Match Service para gym_api
========================================
Servicio HTTP local que expone POST /match para matching biometrico 1:N
usando dpfj.dll (DigitalPersona U.are.U SDK).

El frontend Vue.js captura la huella directamente via DpHostW.exe (WebSDK)
y delega el matching a este servicio en http://127.0.0.1:3002/match.

Requisitos:
  - DigitalPersona U.are.U SDK instalado (incluye dpfj.dll)
  - Python 3.8+  (sin dependencias extra)

Uso:
  python fingerprint_match_service.py
"""

import ctypes
import json
import base64
import os
import sys
import logging
from http.server import BaseHTTPRequestHandler, HTTPServer

# ── Logging a archivo (print() no funciona en servicios Windows) ───────────────

_LOG_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), "fingerprint_service.log")
logging.basicConfig(
    filename=_LOG_FILE,
    level=logging.DEBUG,
    format="%(asctime)s [%(levelname)s] %(message)s",
)
log = logging.getLogger(__name__)

# Redirigir print() al log para no cambiar el resto del codigo
class _PrintToLog:
    def write(self, msg):
        msg = msg.strip()
        if msg:
            log.info(msg)
    def flush(self):
        pass

sys.stdout = _PrintToLog()
sys.stderr = _PrintToLog()

# ── Configuracion ──────────────────────────────────────────────────────────────

HOST = "127.0.0.1"
PORT = 3002

_SDK_SEARCH_DIRS = [
    os.path.dirname(os.path.abspath(__file__)),
    r"C:\Program Files\DigitalPersona\U.are.U SDK\Windows\Lib\x64",
    r"C:\Program Files\DigitalPersona\U.are.U SDK\Windows\Lib\Win32",
    r"C:\Program Files (x86)\DigitalPersona\U.are.U SDK\Windows\Lib\x86",
]

# Agregar todos los directorios SDK al search path de DLLs de Windows.
# Esto es critico cuando corre como servicio: el PATH es minimo y
# las DLLs dependientes de dpfj.dll no se encuentran de otra forma.
for _d in _SDK_SEARCH_DIRS:
    if os.path.isdir(_d):
        try:
            os.add_dll_directory(_d)
            log.debug(f"DLL dir agregado: {_d}")
        except Exception as _e:
            log.warning(f"No se pudo agregar DLL dir {_d}: {_e}")

ANSI_FMD       = 0x001B0001  # DPFJ_FMD_ANSI_378_2004
MAX_FMD_SIZE   = 1600
MATCH_THRESHOLD = 21474836   # dpfj_compare: menor score = mejor match


# ── Cargar dpfj.dll ────────────────────────────────────────────────────────────

def _find_dll(name: str) -> str:
    for directory in _SDK_SEARCH_DIRS:
        path = os.path.join(directory, name)
        if os.path.exists(path):
            return path
    raise FileNotFoundError(
        f"{name} no encontrado. Instala el DigitalPersona U.are.U SDK."
    )


dpfj = ctypes.CDLL(_find_dll("dpfj.dll"))


# ── Conversion imagen raw → FMD ────────────────────────────────────────────────

def raw_to_fmd(image_b64: str, width: int, height: int, dpi: int) -> bytes | None:
    """
    Convierte una imagen raw (base64) a FMD ANSI 378 usando dpfj_create_fmd_from_raw.
    Retorna los bytes del FMD o None si falla.
    """
    try:
        img_bytes = base64.b64decode(image_b64)
    except Exception:
        return None

    img_size = len(img_bytes)
    if img_size == 0 or width == 0 or height == 0:
        return None

    c_img    = (ctypes.c_ubyte * img_size)(*img_bytes)
    c_fmd    = (ctypes.c_ubyte * MAX_FMD_SIZE)()
    c_size   = ctypes.c_uint(MAX_FMD_SIZE)

    ret = dpfj.dpfj_create_fmd_from_raw(
        c_img, img_size,
        width, height, dpi,
        0,          # finger_pos = unknown
        0,          # cbeff_id
        ANSI_FMD,
        c_fmd, ctypes.byref(c_size),
    )

    if ret != 0:
        print(f"[WARN] dpfj_create_fmd_from_raw ret={ret:#010x} ({width}x{height}@{dpi}dpi)")
        return None

    return bytes(c_fmd[: c_size.value])


def parse_template(template_str: str) -> bytes | None:
    """
    Parsea un template guardado en DB.
    Soporta dos formatos:
      - JSON string: '{"data":"<b64>","width":N,"height":N,"dpi":N}'
      - Base64 puro (FMD directo)
    """
    try:
        parsed = json.loads(template_str)
        return raw_to_fmd(
            parsed["data"],
            int(parsed.get("width",  0)),
            int(parsed.get("height", 0)),
            int(parsed.get("dpi",  500)),
        )
    except Exception:
        # Intento legacy: base64 directo de FMD
        try:
            return base64.b64decode(template_str)
        except Exception:
            return None


# ── Matching 1:N ──────────────────────────────────────────────────────────────

def match_1n(sample_str: str, candidates: list) -> str | None:
    """
    Compara la muestra contra todos los candidatos.
    Retorna el id del mejor match o None.

    sample_str:  JSON string '{"data":"...","width":N,"height":N,"dpi":N}'
    candidates:  [{"id": "1", "template": "<fingerprint_data>", ...}, ...]
    """
    try:
        sample = json.loads(sample_str)
    except Exception:
        return None

    probe_fmd = raw_to_fmd(
        sample["data"],
        int(sample.get("width",  0)),
        int(sample.get("height", 0)),
        int(sample.get("dpi",  500)),
    )
    if probe_fmd is None:
        return None

    probe_size = len(probe_fmd)
    c_probe    = (ctypes.c_ubyte * probe_size)(*probe_fmd)

    best_score = MATCH_THRESHOLD
    best_id    = None

    for candidate in candidates:
        tmpl_fmd = parse_template(candidate.get("template", ""))
        if not tmpl_fmd:
            continue

        tmpl_size = len(tmpl_fmd)
        c_tmpl    = (ctypes.c_ubyte * tmpl_size)(*tmpl_fmd)
        c_score   = ctypes.c_uint(0)

        ret = dpfj.dpfj_compare(
            ANSI_FMD, c_probe, probe_size, 0,
            ANSI_FMD, c_tmpl,  tmpl_size,  0,
            ctypes.byref(c_score),
        )

        if ret == 0 and c_score.value < best_score:
            best_score = c_score.value
            best_id    = str(candidate["id"])

    return best_id


# ── HTTP Handler ───────────────────────────────────────────────────────────────

class MatchHandler(BaseHTTPRequestHandler):

    def log_message(self, fmt, *args):
        print(f"[{self.address_string()}] {fmt % args}")

    def _send_json(self, status: int, data: dict):
        body = json.dumps(data).encode()
        self.send_response(status)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        # CORS: el navegador hace la peticion desde localhost
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        self.end_headers()
        self.wfile.write(body)

    def do_OPTIONS(self):
        # Preflight CORS
        self.send_response(204)
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        self.end_headers()

    def do_POST(self):
        if self.path != "/match":
            self._send_json(404, {"error": "Not found"})
            return

        try:
            length  = int(self.headers.get("Content-Length", 0))
            payload = json.loads(self.rfile.read(length))
        except Exception as e:
            self._send_json(400, {"error": f"JSON invalido: {e}"})
            return

        sample     = payload.get("sample", "")
        candidates = payload.get("candidates", [])

        if not sample or not candidates:
            self._send_json(400, {"error": "Faltan sample o candidates"})
            return

        member_id = match_1n(sample, candidates)
        print(f"[MATCH] candidatos={len(candidates)} resultado={member_id}")
        self._send_json(200, {"member_id": member_id})


# ── Arranque ───────────────────────────────────────────────────────────────────

if __name__ == "__main__":
    server = HTTPServer((HOST, PORT), MatchHandler)
    print(f"[OK] dpfj.dll cargado")
    print(f"[OK] Servicio de matching en http://{HOST}:{PORT}/match")
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\n[·] Servicio detenido.")
