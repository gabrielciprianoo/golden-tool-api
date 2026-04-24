<?php

namespace App\Http\Controllers;

use App\Models\Tool;
use Illuminate\Http\Request;

class ToolController extends Controller
{
    public function index()
    {
        return response()->json(Tool::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'price' => 'required|numeric',
            'supplier' => 'required|string',
            'entry_date' => 'required|date',
            'quantity' => 'required|integer',
            'warranty' => 'required|string|in:con garantia,sin garantia',
        ]);

        $quantity = $request->quantity;

        $tool = Tool::create([
            'name' => $request->name,
            'category' => $request->category,
            'price' => $request->price,
            'supplier' => $request->supplier,
            'entry_date' => $request->entry_date,
            'quantity' => $quantity,
            'unassigned_quantity' => $quantity,
            'warranty' => $request->warranty,
        ]);

        return response()->json([
            'message' => 'Tool saved successfully',
            'data' => $tool,
        ], 201);
    }

    public function show($id)
    {
        $tool = Tool::find($id);

        if (! $tool) {
            return response()->json([
                'message' => 'Tool not found',
            ], 404);
        }

        return response()->json($tool);
    }

    public function update(Request $request, $id)
    {
        $tool = Tool::find($id);

        if (! $tool) {
            return response()->json([
                'message' => 'Tool not found',
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string',
            'category' => 'sometimes|string',
            'price' => 'sometimes|numeric',
            'supplier' => 'sometimes|string',
            'entry_date' => 'sometimes|date',
            'quantity' => 'sometimes|integer',
            'warranty' => 'sometimes|string|in:con garantia,sin garantia',
        ]);

        $oldQuantity = $tool->quantity;
        $newQuantity = $request->quantity ?? $oldQuantity;
        $difference = $newQuantity - $oldQuantity;

        $newUnassigned = $tool->unassigned_quantity + $difference;

        $tool->update(array_merge(
            $request->except(['unassigned_quantity']),
            [
                'quantity' => $newQuantity,
                'unassigned_quantity' => max(0, $newUnassigned),
            ]
        ));

        return response()->json([
            'message' => 'Tool updated successfully',
            'data' => $tool,
        ]);
    }

    public function destroy($id)
    {
        $tool = Tool::find($id);

        if (! $tool) {
            return response()->json([
                'message' => 'Tool not found',
            ], 404);
        }

        $tool->delete();

        return response()->json([
            'message' => 'Tool deleted successfully',
        ]);
    }
}
