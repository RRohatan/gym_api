<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PaymentMethod;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

      protected $fillable = [
        'amount',
        'payment_method_id',
        'paymentable_id',
        'paymentable_type',
        'paid_at'
    ];

    public function paymentable()
    {
        return $this->morphTo();
    }


    public function payment_method()
{

     return $this->belongsTo(PaymentMethod::class);
}
}
