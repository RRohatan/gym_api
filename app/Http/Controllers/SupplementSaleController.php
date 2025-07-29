<?php

namespace App\Http\Controllers;

use App\Models\SupplementSale;
use App\Models\SupplementProduct;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupplementSaleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => SupplementSale::with(['product', 'member'])->get()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'product_id' => 'required|exists:supplement_products,id',
            'quantity' => 'required|integer|min:1',
            'paid_at' => 'required|date',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_method' => 'required|string|max:255',
        ]);

        $product = SupplementProduct::findOrFail($validated['product_id']);

        if ($product->stock < $validated['quantity']) {
            return response()->json(['message' => 'Insufficient stock'], 400);
        }

        $total = $product->price * $validated['quantity'];

        $sale = SupplementSale::create([
            'member_id' => $validated['member_id'],
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'total' => $total,
            'paid_at' => $validated['paid_at'],
        ]);

        Payment::create([
            'amount' => $total,
            'paymentable_type' => SupplementSale::class,
            'paymentable_id' => $sale->id,
            'payment_method_id' => $validated['payment_method_id'],
            'payment_method' => $validated['payment_method'],
            'paid_at' => $validated['paid_at'],
        ]);

        $product->decrement('stock', $validated['quantity']);

        return response()->json($sale, 201);
    }

    public function show($id): JsonResponse
    {
        $sale = SupplementSale::with(['product', 'member'])->findOrFail($id);
        return response()->json($sale);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $sale = SupplementSale::findOrFail($id);

        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'paid_at' => 'sometimes|date',
        ]);

        if (isset($validated['quantity'])) {
            $product = SupplementProduct::findOrFail($sale->product_id);

            if ($product->stock + $sale->quantity < $validated['quantity']) {
                return response()->json(['message' => 'Insufficient stock to increase quantity'], 400);
            }

            // restore stock difference
            $diff = $validated['quantity'] - $sale->quantity;
            $product->decrement('stock', $diff);

            $validated['total'] = $product->price * $validated['quantity'];
        }

        $sale->update($validated);

        return response()->json($sale);
    }

    public function destroy($id): JsonResponse
    {
        $sale = SupplementSale::findOrFail($id);

        // restore stock before delete
        $product = SupplementProduct::find($sale->product_id);
        if ($product) {
            $product->increment('stock', $sale->quantity);
        }

        $sale->delete();

        return response()->json(['message' => 'Supplement sale deleted']);
    }
}
