<?php

namespace App\Http\Controllers;
use App\Models\Membership;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
class PaymentController extends Controller
{
   public function index(Request $request)
{
    $gimnasioId = $request->user()->gimnasio_id;

    $payments = Payment::with(['paymentable.member', 'payment_method'])
        ->whereHasMorph(
            'paymentable',
            [\App\Models\Membership::class],
            function ($query) use ($gimnasioId) {
                $query->whereHas('member', function ($q) use ($gimnasioId) {
                    $q->where('gimnasio_id', $gimnasioId);
                });
            }
        )
        ->get();

    return response()->json($payments);
}

public function store(Request $request)
{
    $validated = $request->validate([
        'member_id'         => 'required|exists:members,id',
        'amount'            => 'required|numeric|min:0',
        'payment_method_id' => 'required|exists:payment_methods,id',
    ]);

    // Buscar membresía activa
    $membership = Membership::where('member_id', $validated['member_id'])
        ->where('status', 'active')
        ->latest('end_date')
        ->first();

    if (!$membership) {
        return response()->json(['error' => 'No se encontró membresía activa.'], 404);
    }

    // Crear el pago
    $payment = Payment::create([
        'amount'            => $validated['amount'],
        'paymentable_id'    => $membership->id,
        'paymentable_type'  => Membership::class,
        'payment_method_id' => $validated['payment_method_id'],
        'paid_at'           => now(),
    ]);

    // Descontar saldo
    $membership->outstanding_balance -= $validated['amount'];
    if ($membership->outstanding_balance < 0) {
        $membership->outstanding_balance = 0;
    }

    // Verificar si ya venció, marcar como inactiva
    if (Carbon::now()->gt(Carbon::parse($membership->end_date))) {
        $membership->status = 'inactive';
    }

    // Solo renovar si el saldo pendiente es cero
    if ($membership->outstanding_balance == 0) {
        $fechaBase = Carbon::parse($membership->end_date);
        $frecuencia = $membership->plan->frequency;

        switch ($frecuencia) {
            case 'diario':     $fechaBase->addDay(); break;
            case 'semanal':    $fechaBase->addWeek(); break;
            case 'quincenal':  $fechaBase->addDays(15); break;
            case 'mensual':    $fechaBase->addMonth(); break;
        }

        $membership->end_date = $fechaBase;
        $membership->status = 'active'; // reactiva si estaba inactiva
    }

    $membership->save();

    return response()->json([
        'message' => 'Pago registrado correctamente.',
        'payment' => $payment
    ], 201);
}

    public function show($id)
    {
        $payment = Payment::with('paymentable')->findOrFail($id);
        return response()->json($payment);
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0',
            'payment_method_id' => 'sometimes|exists:payment_methods,id',
            'paymentable_type' => 'sometimes|string',
            'paymentable_id' => 'sometimes|integer',
            'paid_at' => 'sometimes|date',
        ]);

        $payment->update($validated);

        return response()->json($payment);
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();

        return response()->json(['message' => 'Payment deleted']);
    }

    public function totalToday()
{
    $hoy = Carbon::today();

    $total = DB::table('payments')
        ->whereDate('created_at', $hoy)
        ->sum('amount');

    return response()->json(['total' => $total]);
}

}
