<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    /** @use HasFactory<\Database\Factories\MembershipPlanFactory> */
    use HasFactory;

    protected $fillable = [
    'gym_id', 'membership_type_id', 'frequency', 'price'
];

     public function type()
    {
        return $this->belongsTo(MembershipType::class, 'membership_type_id');
    }

    public function memberships()
    {
        return $this->hasMany(Membership::class, 'plan_id');
    }

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasio::class, 'gym_id');
    }

    public function membershipType()
    {
        return $this->belongsTo(MembershipType::class,'membership_type_id');
    }

}
