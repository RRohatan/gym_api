<?php

namespace App\Http\Controllers;

use App\Models\DailyCashbox;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; 

class DailyCashboxController extends Controller
{
    public function index(Request $request)
    {
        $gimnasioId = $request->user()->gimnasio_id;

        return DailyCashbox::where('gimnasio_id', $gimnasioId)
            ->with(['payments', 'supplementSales', 'expenses'])
            ->orderBy('date', 'desc')
            ->get();
    }

    public function show($id)
    {
        return DailyCashbox::with(['payments', 'supplementSales', 'expenses'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $gimnasioId = $request->user()->gimnasio_id;

        $request->validate([
            // 2. CORRECCIÓN AQUÍ: Usamos Rule en lugar de la cadena de texto larga
            'date' => [
                'required',
                'date',
                // Esta regla dice: "La fecha debe ser única en la tabla daily_cashboxes,
                // PERO solo revisando las filas donde gimnasio_id sea el mío".
                Rule::unique('daily_cashboxes')->where(function ($query) use ($gimnasioId) {
                    return $query->where('gimnasio_id', $gimnasioId);
                }),
            ],
            'opening_balance' => 'required|numeric'
        ]);

        $cashbox = DailyCashbox::create([
            'date' => $request->date,
            'opening_balance' => $request->opening_balance,
            'gimnasio_id' => $gimnasioId,
        ]);

        return response()->json($cashbox, 201);
    }
}
