<?php


namespace App\Http\Controllers;

use App\Models\Gimnasio;
use App\Models\Member;
// --- AÑADIR IMPORTS ---
use App\Models\Membership;
use App\Models\MembershipPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $gimnasioId = $request->user()->gimnasio_id;
            return Member::where('gimnasio_id', $gimnasioId)
            ->with('memberships') // ->with('memberships.plan') es más pesado si solo quieres 'is_expired'
            ->get();
    }

    // --- MÉTODO STORE MODIFICADO ---
    public function store(Request $request)
    {
        $gimnasioId = $request->user()->gimnasio_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:members,email',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'medical_history' => 'nullable|string',
            'sexo' => 'nullable|string|in:masculino,femenino,no_binario,otro,preferir_no_decir',
            'estatura' => 'nullable|numeric|min:0',
            'peso' => 'nullable|numeric|min:0',
            'identification' => 'required|string|unique:members,identification',

            // --- CAMPO OPCIONAL AÑADIDO ---
            'plan_id' => [
                'nullable', // Permite que sea nulo o no se envíe
                'sometimes', // Solo valida si está presente
                // Validar que el plan exista Y pertenezca al gimnasio
                Rule::exists('membership_plans', 'id')->where(function ($query) use ($gimnasioId) {
                    return $query->where('gym_id', $gimnasioId);
                }),
            ],
        ]);

        $validated['gimnasio_id'] = $gimnasioId;
        $gimnasio = Gimnasio::findOrFail($validated['gimnasio_id']);

        //lógica de huella original
        if ($gimnasio->uses_access_control) {
            $request->validate([
                'fingerprint_data' => 'required|string',
            ]);
        }

        $member = null;

        // --- AÑADIR TRANSACCIÓN DE BASE DE DATOS ---
        // Esto asegura que si la membresía falla, el miembro tampoco se crea.
        try {
            DB::beginTransaction();

            // 1. Crear al Miembro
            $member = Member::create([
                ...$validated, // 'plan_id' se ignora aquí porque no está en $fillable de Member
                'gimnasio_id' => $validated['gimnasio_id'],
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'medical_history' => $validated['medical_history'] ?? null,
                'sexo' => $validated['sexo'] ?? null,
                'estatura' => $validated['estatura'] ?? null,
                'peso' => $validated['peso'] ?? null,
                'identification' => $validated['identification'],
                'fingerprint_data' => $request->fingerprint_data ?? null,
            ]);

            // 2. Crear Membresía (SI se envió un plan_id)
            if (isset($validated['plan_id']) && $validated['plan_id']) {
                $plan = MembershipPlan::findOrFail($validated['plan_id']);
                $startDate = Carbon::now();
                $endDate = $startDate->copy();

                // Calcular fecha de fin
                switch ($plan->frequency) {
                    case 'daily':    $endDate->addDay(); break;
                    case 'weekly':   $endDate->addWeek(); break;
                    case 'biweekly': $endDate->addDays(15); break;
                    case 'monthly':  $endDate->addMonthNoOverflow(); break;
                }

                // Crear la membresía asociada
                Membership::create([
                    'member_id' => $member->id,
                    'plan_id' => $plan->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'inactive_unpaid', // <-- Estado clave
                    'outstanding_balance' => $plan->price,
                ]);
            }

            DB::commit(); // Todo salió bien, guardar cambios

            return response()->json($member, 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Algo falló, deshacer todo
            return response()->json(['error' => 'No se pudo registrar al miembro.', 'details' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $member = Member::with(['memberships' => function ($q)
        {
        // Modificado para que también pueda encontrar la membresía pendiente
        $q->whereIn('status', ['active', 'inactive_unpaid', 'expired']);
        }, 'memberships.plan'])->findOrFail($id);

        return response()->json($member);
    }

    public function update(Request $request, $id)
    {
        $member = Member::findOrFail($id);

        $validated = $request->validate([
            // 'gimnasio_id' => 'sometimes|exists:gimnasios,id', // No deberías cambiar el gimnasio
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|unique:members,email,' . $member->id,
            'phone' => 'sometimes|nullable|string|max:20',
            'birth_date' => 'sometimes|nullable|date',
            'medical_history' => 'nullable|string',
            'sexo' => 'nullable|string|in:masculino,femenino,no_binario,otro,preferir_no_decir',
            'estatura' => 'nullable|numeric|min:0',
            'peso' => 'nullable|numeric|min:0',
            // La identificación no debería cambiar, pero si lo permites, debe ser única
            'identification' => 'sometimes|string|unique:members,identification,' . $member->id,
        ]);

       $gimnasio = $member->gimnasio; // Usar el gimnasio existente

       //si el gimnasio tiene activado el control de acceso, la huella es obligatoria
       if ($gimnasio->uses_access_control && $request->has('fingerprint_data')) {
            $request->validate([
                'fingerprint_data' => 'required|string',
            ]);
       }

       $member->update([
        ...$validated,
        'fingerprint_data' => $request->fingerprint_data ?? $member->fingerprint_data,
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
