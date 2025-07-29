<?php


namespace App\Http\Controllers;

use App\Models\Member;

use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $gimnasioId = $request->user()->gimnasio_id;
            return Member::where('gimnasio_id', $gimnasioId)->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([

            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:members,email',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'medical_history' => 'nullable|string', // ✅ NUEVO CAMPO
            'sexo' => 'nullable|string|in:masculino,femenino,no_binario,otro,preferir_no_decir',
            'estatura' => 'nullable|numeric|min:0',
            'peso' => 'nullable|numeric|min:0',
        ]);

         $validated['gimnasio_id'] = $request->user()->gimnasio_id;

            $member = Member::create($validated);
            return response()->json($member, 201);

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

        $validated = $request->validate([
            'gimnasio_id' => 'sometimes|exists:gimnasios,id',
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:members,email,' . $member->id,
            'phone' => 'sometimes|string|max:20',
            'birth_date' => 'sometimes|date',
            'medical_history' => 'nullable|string', // ✅ NUEVO CAMPO
            'sexo' => 'nullable|string|in:masculino,femenino,no_binario,otro,preferir_no_decir',
            'estatura' => 'nullable|numeric|min:0',
            'peso' => 'nullable|numeric|min:0',
        ]);

        $member->update($validated);

        return response()->json($member);
    }

    public function destroy($id)
    {
        $member = Member::findOrFail($id);
        $member->delete();

        return response()->json(['message' => 'Miembro eliminado con éxito']);
    }
}
