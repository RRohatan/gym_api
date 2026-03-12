<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\AccessLog;
class AccesController extends Controller
{
    /**
     * Acceso por identificación (cédula)
     */
    public function accessByIdentification(Request $request)
    {
        $request->validate([
            'identification' => 'required|string',
        ]);

        $member = Member::where('identification', $request->identification)->first();

        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Miembro no encontrado'], 404);
        }

        if ($member->is_expired) {
            AccessLog::create([
                'member_id' => $member->id,
                'method'    => 'cedula',
                'status'    => 'denegado',
            ]);
            return response()->json(['success' => false, 'message' => 'Membresía expirada'], 403);
        }

        AccessLog::create([
            'member_id' => $member->id,
            'method'    => 'cedula',
            'status'    => 'permitido',
        ]);

        return response()->json(['success' => true, 'message' => 'Acceso permitido', 'member' => $member]);
    }

    /**
     * Acceso por huella (DigitalPersona).
     *
     * El matching biométrico 1:N se realiza en el cliente (kiosco) usando el SDK
     * de DigitalPersona. Una vez identificado el miembro, el kiosco envía el
     * member_id y gimnasio_id al servidor para registrar el acceso.
     */
    public function accessByFingerprint(Request $request)
    {
        $request->validate([
            'member_id'   => 'required|integer',
            'gimnasio_id' => 'required|integer',
        ]);

        $member = Member::where('id', $request->member_id)
                        ->where('gimnasio_id', $request->gimnasio_id)
                        ->first();

        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Miembro no encontrado'], 404);
        }

        if ($member->is_expired) {
            AccessLog::create([
                'member_id' => $member->id,
                'method'    => 'huella',
                'status'    => 'denegado',
            ]);
            return response()->json(['success' => false, 'message' => 'Membresía expirada'], 403);
        }

        AccessLog::create([
            'member_id' => $member->id,
            'method'    => 'huella',
            'status'    => 'permitido',
        ]);

        return response()->json(['success' => true, 'message' => 'Acceso permitido', 'member' => $member]);
    }

    /**
     * Devuelve todos los FMD (templates) de huella del gimnasio.
     *
     * El kiosco llama este endpoint para obtener los templates almacenados,
     * realiza la comparación local con el SDK de DigitalPersona y luego
     * llama a /access/fingerprint con el member_id identificado.
     */
    public function getLogs(Request $request)
    {
        $gimnasioId = $request->user()->gimnasio_id;

        $logs = AccessLog::whereHas('member', function ($q) use ($gimnasioId) {
                    $q->where('gimnasio_id', $gimnasioId);
                })
                ->with('member:id,name,identification')
                ->orderBy('accessed_at', 'desc')
                ->paginate(50);

        return response()->json($logs);
    }

    public function getFingerprintsForGym($gimnasio_id)
    {
        $fingerprints = Member::where('gimnasio_id', $gimnasio_id)
                              ->whereNotNull('fingerprint_data')
                              ->select('id', 'name', 'fingerprint_data')
                              ->get();

        return response()->json($fingerprints);
    }
}
