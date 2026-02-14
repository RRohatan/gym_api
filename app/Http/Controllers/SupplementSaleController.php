<?php

namespace App\Http\Controllers;

// 👇 ESTAS SON LAS IMPORTACIONES QUE SUELEN FALTAR
use App\Models\SupplementSale;
use App\Models\SupplementProduct;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB; // <--- ¡ESTA ES CLAVE!
use Illuminate\Support\Facades\Log;

class SupplementSaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $gimnasioId = $request->user()->gimnasio_id;

        return response()->json([
            'data' => SupplementSale::with(['product', 'member'])
                ->whereHas('member', function ($query) use ($gimnasioId) {
                    $query->where('gimnasio_id', $gimnasioId);
                })
                ->get()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // (Tu lógica de venta individual existente...)
        return response()->json(['message' => 'Use storeBulk for POS'], 200);
    }

    // ⭐ FUNCIÓN DE VENTA MASIVA (POS) BLINDADA ⭐
    public function storeBulk(Request $request): JsonResponse
    {
        try {
            // 1. Validar datos
            $validated = $request->validate([
                'member_id' => 'required|exists:members,id',
                'cart' => 'required|array',
                'payment_method_id' => 'required|exists:payment_methods,id',
                'paid_at' => 'required|date',
            ]);

            return DB::transaction(function () use ($validated) {
                $salesCreated = [];

                foreach ($validated['cart'] as $item) {
                    // Buscar producto
                    $product = SupplementProduct::lockForUpdate()->find($item['id']);

                    // Validación extra por si el producto se borró mientras vendían
                    if (!$product) {
                        throw new \Exception("El producto con ID {$item['id']} no existe.");
                    }

                    // Validación de Stock
                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("No hay suficiente stock de: " . $product->name);
                    }

                    $totalItem = $product->price * $item['quantity'];

                    // Crear Venta
                    $sale = SupplementSale::create([
                        'member_id' => $validated['member_id'],
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'total' => $totalItem,
                        'paid_at' => $validated['paid_at'],
                    ]);

                    // Crear Pago
                    Payment::create([
                        'amount' => $totalItem,
                        'paymentable_type' => SupplementSale::class,
                        'paymentable_id' => $sale->id,
                        'payment_method_id' => $validated['payment_method_id'],
                        'paid_at' => $validated['paid_at'],
                    ]);

                    // Descontar Stock
                    $product->decrement('stock', $item['quantity']);

                    $salesCreated[] = $sale;
                }

                return response()->json([
                    'message' => 'Venta registrada correctamente',
                    'data' => $salesCreated
                ], 201);
            });

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Error de validación (datos incompletos)
            return response()->json(['message' => 'Datos inválidos', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Error del servidor o de lógica (Stock, DB, etc)
            Log::error('Error en POS: ' . $e->getMessage()); // Guardar en log
            return response()->json([
                'message' => 'Error al procesar la venta: ' . $e->getMessage()
            ], 500); // Enviamos el mensaje real al Frontend
        }
    }

    // ... (El resto de tus funciones show, update, destroy déjalas igual o bórralas si no las usas)
    public function show($id): JsonResponse { return response()->json(SupplementSale::find($id)); }
    public function destroy($id): JsonResponse { return response()->json(null); }
}
