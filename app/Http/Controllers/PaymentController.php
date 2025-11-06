<?php

namespace App\Http\Controllers;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\SupplementSale; // <-- 1. IMPORTAR SupplementSale
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

    // --- INICIO DE MODIFICACIÓN (Lógica de Reactivación) ---

    // Buscar la membresía activa, vencida O PENDIENTE DE PAGO
    $membership = Membership::with('plan') // Eager load plan
        ->where('member_id', $validated['member_id'])
        ->whereIn('status', ['active', 'expired', 'inactive_unpaid']) // <-- 2. MODIFICADO
        ->latest('end_date')
        ->first();

    if (!$membership) {
        // <-- 3. MENSAJE ACTUALIZADO
        return response()->json(['error' => 'No se encontró membresía activa, vencida o pendiente de pago.'], 404);
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

    $plan = $membership->plan; // Obtenemos el plan

    // 4. Reactivar membresía si estaba 'expired' O 'inactive_unpaid' Y el pago cubre todo
    if (in_array($membership->status, ['expired', 'inactive_unpaid']) && $membership->outstanding_balance == 0) {
        $membership->status = 'active';

        // El nuevo ciclo de la membresía inicia HOY (fecha de pago)
        $fechaBase = Carbon::now();

        // Si era un registro nuevo (del QR), actualizamos su fecha de inicio
        if ($membership->status == 'inactive_unpaid') {
            $membership->start_date = $fechaBase->copy();
        }

        switch ($plan->frequency) {
            case 'diario':    $membership->end_date = $fechaBase->copy()->addDay(); break;
            case 'semanal':   $membership->end_date = $fechaBase->copy()->addWeek(); break;
            case 'biweekly':  $membership->end_date = $fechaBase->copy()->addDays(15); break;
            case 'mensual':   $membership->end_date = $fechaBase->copy()->addMonthNoOverflow(); break;
        }

        // Resetear saldo pendiente al precio del plan para el PRÓXIMO ciclo
        $membership->outstanding_balance = $plan->price;
    }

    // 5. Renovar si la membresía YA ESTABA activa y el saldo es cero
    else if ($membership->status == 'active' && $membership->outstanding_balance == 0) {
        // Esta lógica extiende la fecha de fin (Renovación normal)
        $fechaBase = Carbon::parse($membership->end_date);
        $frecuencia = $plan->frequency;

        switch ($frecuencia) {
            case 'diario':     $fechaBase->addDay(); break;
            case 'semanal':    $fechaBase->addWeek(); break;
            case 'biweekly':   $fechaBase->addDays(15); break;
            case 'mensual':    $fechaBase->addMonth(); break;
        }

        $membership->end_date = $fechaBase;
        $membership->status = 'active';
        $membership->outstanding_balance = $plan->price;
    }

    $membership->save();

    // --- FIN DE MODIFICACIÓN ---

    // TODO: Aquí puedes añadir la lógica para generar y enviar el recibo por email
    // Mail::to($membership->member->email)->send(new PaymentReceipt($payment));

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

    // --- 6. MÉTODO NUEVO PARA HISTORIAL DE PAGOS (DASHBOARD) ---
    /**
     * Obtiene historial de ingresos (Membresías Y Suplementos)
     * para un rango de fechas.
     */
    public function getHistory(Request $request)
    {
        $gimnasioId = $request->user()->gimnasio_id;

        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        // Usar inicio de mes por defecto si no se provee fecha
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        // Usar hoy por defecto
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // Carbon necesita 'endOfDay' para incluir transacciones de hoy
        $endDateCarbon = Carbon::parse($endDate)->endOfDay();
        $startDateCarbon = Carbon::parse($startDate)->startOfDay();


        // 1. Pagos de Membresías
        $queryPayments = Payment::with(['paymentable.member', 'payment_method'])
            ->whereBetween('paid_at', [$startDateCarbon, $endDateCarbon])
            ->whereHasMorph(
                'paymentable',
                [Membership::class],
                function ($query) use ($gimnasioId) {
                    $query->whereHas('member', function ($q) use ($gimnasioId) {
                        $q->where('gimnasio_id', $gimnasioId);
                    });
                }
            );

        // 2. Pagos de Suplementos (Basado en SupplementSaleController)
        $querySales = Payment::with(['paymentable.product', 'paymentable.member', 'payment_method'])
            ->whereBetween('paid_at', [$startDateCarbon, $endDateCarbon])
            ->whereHasMorph(
                'paymentable',
                [SupplementSale::class], // Modelo de venta
                function ($query) use ($gimnasioId) {
                    // La venta de suplemento tiene 'member'
                    // y el member tiene gimnasio_id
                    $query->whereHas('member', function ($q) use ($gimnasioId) {
                        $q->where('gimnasio_id', $gimnasioId);
                    });
                }
            );

        $payments = $queryPayments->get();
        $sales = $querySales->get();

        // 3. Combinar todo y ordenar
        $allTransactions = $payments->concat($sales)->sortByDesc('paid_at');

        return response()->json([
            'total_ingresos' => $allTransactions->sum('amount'),
            'total_transacciones' => $allTransactions->count(),
            'historial' => $allTransactions->values(), // re-indexar array
        ]);
    }
}
