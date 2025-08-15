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
        'amount'            => 'required|numeric|min:1000',
        'payment_method_id' => 'required|exists:payment_methods,id',
    ]);

    // Buscar la membresía activa o vencida más reciente
    $membership = Membership::where('member_id', $validated['member_id'])
        ->whereIn('status', ['active', 'expired'])
        ->latest('end_date')
        ->first();

    if (!$membership) {
        return response()->json(['error' => 'No se encontró membresía activa o expirada.'], 404);
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

    // Reactivar membresía si estaba vencida y el pago cubre todo
    if ($membership->status == 'expired' && $membership->outstanding_balance == 0) {
        $membership->status = 'active';

        // Calcular nueva fecha de vencimiento manteniendo el día de inicio
        $startDate = Carbon::parse($membership->start_date); // día original de inscripción
        $planFrequency = $membership->plan->frequency;

        switch ($planFrequency) {
            case 'diario':    $membership->end_date = Carbon::now()->addDay(); break;
            case 'semanal':   $membership->end_date = Carbon::now()->addWeek(); break;
            case 'biweekly':  $membership->end_date = Carbon::now()->addDays(15); break;
            case 'mensual':   $membership->end_date = Carbon::parse($membership->start_date)->addMonthNoOverflow(); break;
        }

        // Resetear saldo pendiente al precio del plan
        $membership->outstanding_balance = $membership->plan->price;
    }

    // Si la membresía está activa y el saldo es cero, renovar normalmente
    if ($membership->status == 'active' && $membership->outstanding_balance == 0) {
        $fechaBase = Carbon::parse($membership->end_date);
        $frecuencia = $membership->plan->frequency;

        switch ($frecuencia) {
            case 'diario':     $fechaBase->addDay(); break;
            case 'semanal':    $fechaBase->addWeek(); break;
            case 'biweekly':   $fechaBase->addDays(15); break;
            case 'mensual':    $fechaBase->addMonth(); break;
        }

        $membership->end_date = $fechaBase;
        $membership->status = 'active';
        $membership->outstanding_balance = $membership->plan->price;
    }

    $membership->save();

    return response()->json([
        'message'    => 'Pago registrado correctamente.',
        'payment'    => $payment,
        'membership' => $membership
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
