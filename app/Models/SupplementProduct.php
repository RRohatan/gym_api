<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Scopes\GimnasioScope;
use Illuminate\Support\Facades\Auth;
class SupplementProduct extends Model
{
    /** @use HasFactory<\Database\Factories\SupplementProductFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'stock', 'gimnasio_id'];


    public function gimnasio()
    {
        return $this->belongsTo(Gimnasio::class);
    }

    public function sales()
    {
        return $this->hasMany(SupplementSale::class, 'product_id');
    }

    protected static function booted()
    {
        static::addGlobalScope(new GimnasioScope);
    }

    /**
     * El método "boot" (sin ed) sirve para GUARDAR datos automáticamente.
     */
    protected static function boot()
    {
        parent::boot();

        // Antes de crear un producto, le asignamos el gimnasio_id automáticamente
        static::creating(function ($product) {
            if (Auth::check()) {
                $product->gimnasio_id = Auth::user()->gimnasio_id;
            }
        });
    }
}
