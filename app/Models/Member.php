<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class Member extends Model
{
    /** @use HasFactory<\Database\Factories\MemberFactory> */
    use HasFactory;

    protected $fillable = [
    'identification',
    'fingerprint_data',
    'gimnasio_id',
    'name',
    'email',
    'phone',
    'birth_date',
    'medical_history',
    'sexo',
    'estatura',
    'peso',
];

     public function gimnasio()
    {
        return $this->belongsTo(Gimnasio::class);
    }

    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function sales()
    {
        return $this->hasMany(SupplementSale::class);
    }



public function getEdadAttribute()
{
    return $this->birth_date ? Carbon::parse($this->birth_date)->age : null;
}

public function getIndiceMasaCorporalAttribute()
{
    if ($this->peso && $this->estatura && $this->estatura > 0) {
        return round($this->peso / ($this->estatura ** 2), 2);
    }

    return null;
}

protected $appends = ['is_expired'];

public function getIsExpiredAttribute()
{
    $lastMembership = $this->memberships()->latest('end_date')->first();

    if(!$lastMembership){
        return false; //no tiene membresia -> se considera vencida
    }

      return $lastMembership->status === 'expired' ||
           now()->greaterThan($lastMembership->end_date);

}


public function accessLogs()
{
    return $this->hasMany(AccessLog::class);
}



}
