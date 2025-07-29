<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplementProduct extends Model
{
    /** @use HasFactory<\Database\Factories\SupplementProductFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'stock'];


    public function gimnasio()
    {
        return $this->belongsTo(Gimnasio::class);
    }

    public function sales()
    {
        return $this->hasMany(SupplementSale::class, 'product_id');
    }
}
