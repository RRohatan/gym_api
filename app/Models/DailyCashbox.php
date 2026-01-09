<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// 👇 IMPORTANTE: Importar los modelos necesarios para que no de error 500
use App\Models\Payment;
use App\Models\SupplementSale;
use App\Models\Gasto;
use Carbon\Carbon;

class DailyCashbox extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'opening_balance',
        'gimnasio_id',
    ];

    protected $dates = ['date'];

    // Estos atributos se calculan automáticamente y se envían al JSON
    protected $appends = ['total_income', 'total_expense', 'closing_balance'];

    // --- CÁLCULOS POR FECHA (Para incluir pagos con cashbox_id NULL) ---

    // 1. Calcular INGRESOS
    public function getTotalIncomeAttribute()
    {
        // Sumar pagos que coincidan con la fecha de esta caja y el gimnasio
        return Payment::whereDate('paid_at', $this->date)
            ->whereHasMorph('paymentable', '*', function ($query) {
                // Filtramos por gimnasio a través del miembro
                if (method_exists($query->getModel(), 'member')) {
                    $query->whereHas('member', function ($q) {
                        $q->where('gimnasio_id', $this->gimnasio_id);
                    });
                }
            })
            ->sum('amount');
    }

    // 2. Calcular GASTOS
    public function getTotalExpenseAttribute()
    {
        // Verificar si existe la clase Gasto antes de sumar
        if (class_exists(Gasto::class)) {
            return Gasto::where('gimnasio_id', $this->gimnasio_id)
                ->whereDate('fecha', $this->date)
                ->sum('monto');
        }
        return 0;
    }

    // 3. Balance final
    public function getClosingBalanceAttribute()
    {
        return $this->opening_balance + $this->total_income - $this->total_expense;
    }

    // Relaciones (Las mantenemos por si acaso, aunque no las usemos para el cálculo directo)
    public function payments()
    {
        return $this->hasMany(Payment::class, 'cashbox_id');
    }

    public function supplementSales()
    {
        return $this->hasMany(SupplementSale::class, 'cashbox_id');
    }

    public function expenses()
    {
        return $this->hasMany(Gasto::class, 'cashbox_id');
    }
}
