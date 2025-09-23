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
                'method' => 'identification',
                'success' => false,
            ]);
            return response()->json(['success' => false, 'message' => 'Membresía expirada'], 403);
        }

        AccessLog::create([
            'member_id' => $member->id,
            'method' => 'identification',
            'success' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Acceso permitido', 'member' => $member]);
    }

    /**
     * Acceso por huella
     */
    public function accessByFingerprint(Request $request)
    {
        $request->validate([
            'fingerprint' => 'required|string',
        ]);

        // Aquí asumes que fingerprint es un hash/plantilla y lo comparas
        $member = Member::where('fingerprint', $request->fingerprint)->first();

        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Huella no reconocida'], 404);
        }

        if ($member->is_expired) {
            AccessLog::create([
                'member_id' => $member->id,
                'method' => 'fingerprint',
                'success' => false,
            ]);
            return response()->json(['success' => false, 'message' => 'Membresía expirada'], 403);
        }

        AccessLog::create([
            'member_id' => $member->id,
            'method' => 'fingerprint',
            'success' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Acceso permitido', 'member' => $member]);
    }
}
