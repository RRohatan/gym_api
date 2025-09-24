<?php


namespace App\Http\Controllers;

use App\Models\Gimnasio;
use App\Models\Member;

use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $gimnasioId = $request->user()->gimnasio_id;
            return Member::where('gimnasio_id', $gimnasioId)
            ->with('memberships')
            ->get();
    }

    public function store(Request $request)
    {
        $gimnasioId = $request->user()->gimnasio_id;
        $gimnasio = Gimnasio::findOrFail($gimnasioId);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:members,email',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'medical_history' => 'nullable|string', // ✅ NUEVO CAMPO
            'sexo' => 'nullable|string|in:masculino,femenino,no_binario,otro,preferir_no_decir',
            'estatura' => 'nullable|numeric|min:0',
            'peso' => 'nullable|numeric|min:0',
            'identification' => 'nullable|string|unique:members,identification',
            'fingerprint_data' => 'nullable|string',
        ];

        if ($gimnasio->uses_access_control) {
            $rules['identification'] = 'required|string|unique:members,identification';
            $rules['fingerprint_data'] = 'required|string';
        }

        $validated = $request->validate($rules);
        $validated['gimnasio_id'] = $gimnasioId;

        $member = Member::create([
            ...$validated,
        ]);

        return response()->json($member,201);
    }

    public function show($id)
    {
        $member = Member::with(['memberships' => function ($q)
        {
        $q->where('status', 'active');
        }, 'memberships.plan'])->findOrFail($id);

    return response()->json($member);


    }

    public function update(Request $request, $id)
    {
        $member = Member::findOrFail($id);

        // Determinar el gimnasio objetivo (puede venir en el request o mantenerse)
        $targetGimnasioId = $request->input('gimnasio_id', $member->gimnasio_id);
        $gimnasio = Gimnasio::findOrFail($targetGimnasioId);

        $rules = [
            'gimnasio_id' => 'sometimes|exists:gimnasios,id',
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:members,email,' . $member->id,
            'phone' => 'sometimes|string|max:20',
            'birth_date' => 'sometimes|date',
            'medical_history' => 'nullable|string', // ✅ NUEVO CAMPO
            'sexo' => 'nullable|string|in:masculino,femenino,no_binario,otro,preferir_no_decir',
            'estatura' => 'nullable|numeric|min:0',
            'peso' => 'nullable|numeric|min:0',
            'identification' => 'sometimes|string|unique:members,identification,' . $member->id,
            'fingerprint_data' => 'nullable|string',
        ];

        if ($gimnasio->uses_access_control) {
            // Requerir ambos si el gimnasio usa control de acceso
            $rules['identification'] = 'required|string|unique:members,identification,' . $member->id;
            $rules['fingerprint_data'] = 'required|string';
        }

        $validated = $request->validate($rules);

        // Asegurar el gimnasio_id final
        $validated['gimnasio_id'] = $validated['gimnasio_id'] ?? $member->gimnasio_id;

        $member->update([
            ...$validated,
        ]);

        return response()->json($member);
    }


    public function destroy($id)
    {
        $member = Member::findOrFail($id);
        $member->delete();

        return response()->json(['message' => 'Miembro eliminado con éxito']);
    }
}
