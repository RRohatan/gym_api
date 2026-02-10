<?php

namespace App\Http\Controllers;

use App\Models\ProductPurchase;
use App\Models\SupplementProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductPurchaseController extends Controller
{
    public function index()
    {
        return response()->json(ProductPurchase::with('product')->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:supplement_products,id',
            'quantity' => 'required|integer|min:1',
            'total_cost' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'new_price' => 'nullable|numeric|min:0', // Validar precio opcional
        ]);

        return DB::transaction(function () use ($validated) {
            // 1. Registrar Compra
            $purchase = ProductPurchase::create([
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'total_cost' => $validated['total_cost'],
                'supplier' => $validated['supplier'],
                'purchase_date' => now(),
            ]);

            // 2. Incrementar Stock y Actualizar Precio si es necesario
            $product = SupplementProduct::findOrFail($validated['product_id']);
            $product->increment('stock', $validated['quantity']);

            if (isset($validated['new_price']) && $validated['new_price'] > 0) {
                 $product->update(['price' => $validated['new_price']]);
            }

            return response()->json([
                'message' => 'Compra registrada y stock actualizado',
                'data' => $purchase
            ], 201);
        });
    }
}
