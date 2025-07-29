<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DailyCashbox extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'opening_balance',
    ];

    protected $dates = ['date'];

    // 👉 Relación con pagos
    public function payments()
    {
        return $this->hasMany(Payment::class, 'cashbox_id');
    }

    // 👉 Relación con ventas de suplementos
    public function supplementSales()
    {
        return $this->hasMany(SupplementSale::class, 'cashbox_id');
    }

    // 👉 Relación con gastos
    public function expenses()
    {
        return $this->hasMany(Gasto::class, 'cashbox_id'); // Asegúrate de tener el modelo Gasto
    }

    // 💰 Total ingresos (dinámico)
    public function getTotalIncomeAttribute()
    {
        return $this->payments()->sum('amount') + $this->supplementSales()->sum('total');
    }

    // 💸 Total gastos (dinámico)
    public function getTotalExpenseAttribute()
    {
        return $this->expenses()->sum('amount');
    }

    // 🧮 Balance de cierre (dinámico)
    public function getClosingBalanceAttribute()
    {
        return $this->opening_balance + $this->total_income - $this->total_expense;
    }
}
