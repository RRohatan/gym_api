<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'total_cost',
        'supplier',
        'purchase_date',
    ];

    public function product()
    {
        return $this->belongsTo(SupplementProduct::class, 'product_id');
    }
}
