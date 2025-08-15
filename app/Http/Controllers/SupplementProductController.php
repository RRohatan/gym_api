<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\SupplementProduct;
use Illuminate\Http\Request;

class SupplementProductController extends Controller
{
    public function index()
    {
        return response()->json(SupplementProduct::all());
    }

    public function store(Request $request)
    {
         $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

         SupplementProduct::create([
            'name' => $request->name,
            'description' => $request->description,
             'price' => $request->price,
             'stock' => $request->stock,
             'gimnasio_id' => Auth::user()->gimnasio_id,

        ]);

        return response()->json( ['message' => 'Producto creado con éxito'], 201);
    }

    public function show($id)
    {
        $product = SupplementProduct::findOrFail($id);
        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $product = SupplementProduct::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
        ]);

        $product->update($validated);

        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = SupplementProduct::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Supplement product deleted']);
    }
}

