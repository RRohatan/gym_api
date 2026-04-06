<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LucasDotVin\Soulbscription\Models\Concerns\HasSubscriptions;

class Gimnasio extends Model
{

    use HasFactory, HasSubscriptions;

    protected $fillable = ['nombre', 'uses_acces_control','horarios','politicas',  'url_grupo_whatsapp']; 


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
