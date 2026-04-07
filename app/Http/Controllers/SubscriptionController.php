<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Gimnasio;
use LucasDotVin\Soulbscription\Models\Plan;

class SubscriptionController extends Controller
{
    public function plans()
    {
        return response()->json(Plan::with('features')->get());
    }

    // -------------------------------------------------------
    // Métodos server-to-server (protegidos por ServiceKey)
    // Operan directamente por gimnasio_id sin sesión de usuario
    // -------------------------------------------------------

    public function getGymSubscription(int $gimnasioId)
    {
        $gimnasio = Gimnasio::find($gimnasioId);

        if (!$gimnasio) {
            return response()->json(['error' => 'Gimnasio no encontrado'], 404);
        }

        $subscription = $gimnasio->subscription;

        if (!$subscription) {
            return response()->json(['message' => 'Sin suscripción activa', 'subscription' => null]);
        }

        return response()->json(['subscription' => $subscription->load('plan.features')]);
    }

    public function subscribeGym(Request $request, int $gimnasioId)
    {
        $request->validate(['plan_id' => 'required|exists:plans,id']);

        $gimnasio = Gimnasio::find($gimnasioId);

        if (!$gimnasio) {
            return response()->json(['error' => 'Gimnasio no encontrado'], 404);
        }

        $plan = Plan::findOrFail($request->plan_id);
        $subscription = $gimnasio->subscribeTo($plan);

        return response()->json([
            'message' => 'Suscripción creada exitosamente',
            'subscription' => $subscription->load('plan'),
        ], 201);
    }

    public function switchGymPlan(Request $request, int $gimnasioId)
    {
        $request->validate(['plan_id' => 'required|exists:plans,id']);

        $gimnasio = Gimnasio::find($gimnasioId);

        if (!$gimnasio) {
            return response()->json(['error' => 'Gimnasio no encontrado'], 404);
        }

        $plan = Plan::findOrFail($request->plan_id);
        $subscription = $gimnasio->switchTo($plan);

        return response()->json([
            'message' => 'Plan actualizado exitosamente',
            'subscription' => $subscription->load('plan'),
        ]);
    }

    public function cancelGymSubscription(int $gimnasioId)
    {
        $gimnasio = Gimnasio::find($gimnasioId);

        if (!$gimnasio) {
            return response()->json(['error' => 'Gimnasio no encontrado'], 404);
        }

        $gimnasio->cancel();

        return response()->json(['message' => 'Suscripción cancelada']);
    }

    public function listGymsSubscriptions()
    {
        $gimnasios = Gimnasio::with(['subscription.plan.features'])->get();

        $emails = DB::table('users')
            ->whereIn('gimnasio_id', $gimnasios->pluck('id'))
            ->orderBy('id')
            ->pluck('email', 'gimnasio_id');

        return response()->json($gimnasios->map(function ($gimnasio) use ($emails) {
            return [
                'id'           => $gimnasio->id,
                'nombre'       => $gimnasio->nombre,
                'email'        => $emails[$gimnasio->id] ?? null,
                'subscription' => $gimnasio->subscription?->load('plan.features'),
            ];
        }));
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
