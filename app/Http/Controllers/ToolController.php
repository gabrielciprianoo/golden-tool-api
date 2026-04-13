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
            'unassigned_quantity' => 'required|integer',
            'warranty' => 'required|string|in:con garantia,sin garantia',
        ]);

        $tool = Tool::create($request->all());

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
            'unassigned_quantity' => 'sometimes|integer',
            'warranty' => 'sometimes|string|in:con garantia,sin garantia',
        ]);

        $tool->update($request->all());

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
