<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gasto;
class GastoController extends Controller
{
    public function index(Request $request)
    {
        $gimnasioId = $request->user()->gimnasio_id;
        $gastos = Gasto::where('gimmnasio_id', $gimnasioId)->orderBy('fecha','desc')->get();

        return response()->json($gastos);
    }


    public function store(Request $request)
    {
        $dalidated = $request->validate([
            'descripcion' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
        ]);

        $validated['gimnasio_id'] = $request->user()->gimnasio_id;

        $gasto = Gasto::create($validated);

        return response()->json($gasto, 201);
    }

     // Mostrar un gasto específico (opcional)
    public function show($id)
    {
        $gasto = Gasto::findOrFail($id);
        return response()->json($gasto);
    }

    // Actualizar un gasto existente
    public function update(Request $request, $id)
    {
        $gasto = Gasto::findOrFail($id);

        $validated = $request->validate([
            'descripcion' => 'sometimes|string|max:255',
            'monto' => 'sometimes|numeric|min:0',
            'fecha' => 'sometimes|date',
        ]);

        $gasto->update($validated);

        return response()->json($gasto);
    }

    // Eliminar un gasto
    public function destroy($id)
    {
        $gasto = Gasto::findOrFail($id);
        $gasto->delete();

        return response()->json(['message' => 'Gasto eliminado con éxito.']);
    }
}
