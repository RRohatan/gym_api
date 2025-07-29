<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplementSale extends Model
{
    protected $fillable = [
        'member_id',
        'product_id',
        'quantity',
        'total',
        'paid_at',
    ];

    /**
     * Relación con el producto vendido
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(SupplementProduct::class);
    }

    /**
     * Relación con el miembro que compró
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Relación polimórfica con Payment
     */
    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'paymentable');
    }
}
