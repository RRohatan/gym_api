<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gimnasio extends Model
{

    use HasFactory;

    protected $fillable = ['nombre', 'uses_acces_control','horarios','politicas',  'url_grupo_whatsapp']; 


     public function users()
    {
        return $this->hasMany(User::class);
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function products()
    {
        return $this->hasMany(SupplementProduct::class);
    }

    public function planes()
    {
        return $this->hasMany(MembershipPlan::class, 'gym_id');
    }
}
