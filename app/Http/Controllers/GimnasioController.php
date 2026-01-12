<?php

namespace App\Http\Controllers;

use App\Models\Gimnasio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class GimnasioController extends Controller
{
     public function index()
    {
        return Gimnasio::all();
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'uses_access_control'=> 'boolean'
            // Agrega más validaciones si tienes más columnas
        ]);

        $gimnasio = Gimnasio::create($validated);
        return response()->json($gimnasio, 201);
    }


   public function show(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json(['error' => 'No autenticado'], 401);
    }

    if (!$user->gimnasio) {
        return response()->json(['error' => 'Usuario no tiene gimnasio asociado'], 404);
    }

    return response()->json($user->gimnasio);
}

    public function update(Request $request, Gimnasio $gimnasio)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            // Validar campos opcionales
        ]);

        $gimnasio->update($validated);
        return response()->json($gimnasio);
    }

    public function destroy(Gimnasio $gimnasio)
    {
        $gimnasio->delete();
        return response()->json(null, 204);
    }

    // Función para actualizar la configuración del Gym
    public function updateConfig(Request $request)
    {
        // 1. Validamos los datos que vienen del Front
        $request->validate([
            'horarios' => 'nullable|string|max:1000',
            'politicas' => 'nullable|string|max:2000',
            'url_grupo_whatsapp' => 'nullable|url', // Validamos que sea un link real
        ]);

        // 2. Identificamos el gimnasio del usuario logueado
        // (Asumiendo que el usuario es Admin y tiene 'gimnasio_id')
        $user = Auth::user();

        if (!$user->gimnasio_id) {
            return response()->json(['error' => 'No tienes un gimnasio asignado'], 403);
        }

        $gym = Gimnasio::find($user->gimnasio_id);

        // 3. Guardamos los cambios
        $gym->update([
            'horarios' => $request->horarios,
            'politicas' => $request->politicas,
            'url_grupo_whatsapp' => $request->url_grupo_whatsapp,
        ]);

        return response()->json([
            'message' => '¡Configuración guardada exitosamente!',
            'gym' => $gym
        ]);
    }
}
