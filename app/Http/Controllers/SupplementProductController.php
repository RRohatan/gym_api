<?php

namespace App\Http\Controllers;

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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        $product = SupplementProduct::create($validated);

        return response()->json($product, 201);
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

