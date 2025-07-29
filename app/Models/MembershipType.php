<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipType extends Model
{
    /** @use HasFactory<\Database\Factories\MembershipTypeFactory> */
    use HasFactory;

    public function plans()
    {
        return $this->hasMany(MembershipPlan::class);
    }
}
