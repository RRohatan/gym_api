<?php

namespace App\Http\Controllers;

use App\Models\MembershipType;
use Illuminate\Http\Request;

class MembershipTypeController extends Controller
{
    public function index()
    {
        return response()->json(MembershipType::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $type = MembershipType::create($validated);

        return response()->json($type, 201);
    }

    public function show($id)
    {
        $type = MembershipType::findOrFail($id);
        return response()->json($type);
    }

    public function update(Request $request, $id)
    {
        $type = MembershipType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $type->update($validated);

        return response()->json($type);
    }

    public function destroy($id)
    {
        $type = MembershipType::findOrFail($id);
        $type->delete();

        return response()->json(['message' => 'Membership type deleted']);
    }
}
