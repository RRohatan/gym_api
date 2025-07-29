<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    use HasFactory;

    protected $fillable = [
        'gimnasio_id',
        'concepto',
        'monto',
        'fecha',
        'descripcion',
    ];

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasio::class);
    }
}
