<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use LucasDotVin\Soulbscription\Models\Plan;

class SubscriptionController extends Controller
{
    public function plans()
    {
        return response()->json(Plan::with('features')->get());
    }

    public function current(Request $request)
    {
        $gimnasio = $request->user()->gimnasio;

        if (!$gimnasio) {
            return response()->json(['error' => 'No tienes un gimnasio asociado'], 404);
        }

        $subscription = $gimnasio->subscription;

        if (!$subscription) {
            return response()->json(['message' => 'Sin suscripción activa', 'subscription' => null]);
        }

        return response()->json(['subscription' => $subscription->load('plan.features')]);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $gimnasio = $request->user()->gimnasio;

        if (!$gimnasio) {
            return response()->json(['error' => 'No tienes un gimnasio asociado'], 404);
        }

        $plan = Plan::findOrFail($request->plan_id);

        $subscription = $gimnasio->subscribeTo($plan);

        return response()->json([
            'message' => 'Suscripción creada exitosamente',
            'subscription' => $subscription->load('plan'),
        ], 201);
    }

    public function switchPlan(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $gimnasio = $request->user()->gimnasio;

        if (!$gimnasio) {
            return response()->json(['error' => 'No tienes un gimnasio asociado'], 404);
        }

        $plan = Plan::findOrFail($request->plan_id);

        $subscription = $gimnasio->switchTo($plan);

        return response()->json([
            'message' => 'Plan actualizado exitosamente',
            'subscription' => $subscription->load('plan'),
        ]);
    }

    public function cancel(Request $request)
    {
        $gimnasio = $request->user()->gimnasio;

        if (!$gimnasio) {
            return response()->json(['error' => 'No tienes un gimnasio asociado'], 404);
        }

        $gimnasio->cancel();

        return response()->json(['message' => 'Suscripción cancelada']);
    }
}
