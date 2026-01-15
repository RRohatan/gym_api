<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class GimnasioScope implements Scope
{
    /**
     * Aplica el filtro automáticamente a todas las consultas.
     */
    public function apply(Builder $builder, Model $model)
    {
        // Solo aplicamos el filtro si hay un usuario logueado
        if (Auth::check()) {
            // Filtramos para que SOLO traiga datos donde 'gimnasio_id'
            // coincida con el gimnasio del usuario logueado.
            $builder->where('gimnasio_id', Auth::user()->gimnasio_id);
        }
    }
}
