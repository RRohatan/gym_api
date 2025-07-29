<?php


namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Member;
use App\Models\MembershipPlan;
use Illuminate\Http\Request;
use Carbon\Carbon;
class MembershipController extends Controller
{


public function index(Request $request)
{
    $gimnasioId = $request->user()->gimnasio_id;

    // 1. Cambiar estado a 'expired' para las membresías vencidas
    Membership::where('status', 'active')
        ->where('end_date', '<', Carbon::now())
        ->update(['status' => 'expired']);

    // 2. Traer membresías del gimnasio actual
    $memberships = Membership::with(['member', 'plan.membershipType'])
        ->whereHas('member', function ($query) use ($gimnasioId) {
            $query->where('gimnasio_id', $gimnasioId);
        })
        ->get();

    return response()->json($memberships);
}

 public function store(Request $request)
{
    $validated = $request->validate([
        'member_id' => 'required|exists:members,id',
        'plan_id' => 'required|exists:membership_plans,id',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    // Obtener el plan para usar su precio como saldo pendiente
    $plan = \App\Models\MembershipPlan::findOrFail($validated['plan_id']);

    $validated['outstanding_balance'] = $plan->price;

    $membership = \App\Models\Membership::create($validated);

    return response()->json($membership, 201);
}


    public function show($id)
    {
        $membership = Membership::with(['member', 'plan'])->findOrFail($id);
        return response()->json($membership);
    }

    public function update(Request $request, $id)
    {
        $membership = Membership::findOrFail($id);

        $validated = $request->validate([
            'plan_id' => 'sometimes|exists:membership_plans,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|in:active,expired,cancelled',
        ]);

        $membership->update($validated);

        return response()->json($membership);
    }

    public function destroy($id)
    {
        $membership = Membership::findOrFail($id);
        $membership->delete();

        return response()->json(['message' => 'Membership deleted']);
    }
}
