<?php

namespace App\Http\Controllers;

use App\Models\DailyCashbox;
use Illuminate\Http\Request;

class DailyCashboxController extends Controller
{
    public function index()
    {
        return DailyCashbox::with(['payments', 'supplementSales', 'gastos'])->get();
    }

    public function show($id)
    {
        return DailyCashbox::with(['payments', 'supplementSales', 'gastos'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date|unique:daily_cashboxes,date',
            'opening_balance' => 'required|numeric'
        ]);

        $cashbox = DailyCashbox::create($request->only('date', 'opening_balance'));

        return response()->json($cashbox, 201);
    }
}
