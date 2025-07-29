<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use Illuminate\Http\Request;

use function Pest\Laravel\json;

class MembershipPlanController extends Controller
{
    public function index(Request $request)
    {
        //obten el usuario que hizo la peticion (autenticado) accede al campo gimnasio_id y guarda ese valor en $gimnasioId
        $gimnasioId = $request->user()->gimnasio_id;

        //devuelveme todos los planes que pertenezcan al gimnasio del usurio autenticado, icluyendo tambien su relacion con el tipo de membresia
        $planes = MembershipPlan::where('gym_id', $gimnasioId)
        ->with('membershipType')
        ->get();

        return response()->json($planes);
    }


    public function store(Request $request)
    {
    $validated = $request->validate([
        'membership_type_id' => 'required|exists:membership_types,id',
        'frequency'          => 'required|in:daily,weekly,biweekly,monthly',
        'price'              => 'required|numeric|min:0',
    ]);

    $gymId = $request->user()->gimnasio_id;

    $plan = MembershipPlan::create([
        'gym_id'             => $gymId,
        'membership_type_id' => $validated['membership_type_id'],
        'frequency'          => $validated['frequency'],
        'price'              => $validated['price'],
    ]);

    return response()->json($plan, 201);
    }

    public function show($id)
    {
        $plan = MembershipPlan::with('membershipType')->findOrFail($id);
        return response()->json($plan);
    }

    public function update(Request $request, $id)
    {
        $plan = MembershipPlan::findOrFail($id);

        $validated = $request->validate([
            'membership_type_id' => 'sometimes|exists:membership_types,id',
            'frequency' => 'sometimes|in:semanal,quincenal,mensual',
            'price' => 'sometimes|numeric|min:0',
        ]);

        $plan->update($validated);

        return response()->json($plan);
    }

    public function destroy($id)
    {
        $plan = MembershipPlan::findOrFail($id);
        $plan->delete();

        return response()->json(['message' => 'Membership plan deleted']);
    }
}
