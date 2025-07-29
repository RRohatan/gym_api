<?php

namespace App\Http\Controllers;

use App\Models\Gimnasio;
use Illuminate\Http\Request;

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
}
