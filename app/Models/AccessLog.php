<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    protected $fillable = [
        'member_id',
        'method',
        'status',
        'accessed_at'
    ];

    public function member()
    {
         return $this->belongsTo(Member::class);
    }
}
