<?php

namespace App\Http\Controllers;

use App\Models\Asignation;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsignationController extends Controller
{
    // 🔍 Obtener todas las asignaciones (con filtros + paginación)
    public function index(Request $request)
    {
        $query = Asignation::with(['tool', 'worker']);

        if ($request->worker_id) {
            $query->where('worker_id', $request->worker_id);
        }

        if ($request->state) {
            $query->where('state', $request->state);
        }

        if ($request->tool_id) {
            $query->where('tool_id', $request->tool_id);
        }

        return response()->json(
            $query->latest()->paginate(10)
        );
    }

    // ➕ Crear asignación
    public function store(Request $request)
    {
        $request->validate([
            'tool_id' => 'required|exists:tools,id',
            'worker_id' => 'required|exists:workers,id',
            'assigned_quantity' => 'required|integer|min:1',
            'state' => 'required|in:nuevo,buen estado,regular,mal estado,obsoleto',
            'date' => 'required|date',
        ]);

        try {
            $asignation = DB::transaction(function () use ($request) {

                // 🔒 Lock para concurrencia
                $tool = Tool::lockForUpdate()->findOrFail($request->tool_id);

                // 🚨 Validar stock
                if ($tool->unassigned_quantity < $request->assigned_quantity) {
                    throw new \Exception('No hay suficiente stock disponible');
                }

                // ➕ Crear asignación
                $asignation = Asignation::create([
                    'tool_id' => $request->tool_id,
                    'worker_id' => $request->worker_id,
                    'assigned_quantity' => $request->assigned_quantity,
                    'state' => $request->state,
                    'date' => $request->date,
                ]);

                // 🔻 Descontar inventario
                $tool->decrement('unassigned_quantity', $request->assigned_quantity);

                // 🧠 Validar consistencia
                $this->validateInventory($tool);

                return $asignation;
            });

            return response()->json([
                'data' => $asignation->load(['tool', 'worker'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la asignación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 🔍 Ver una asignación
    public function show($id)
    {
        $asignation = Asignation::with(['tool', 'worker'])->findOrFail($id);

        return response()->json([
            'data' => $asignation
        ]);
    }

    // ✏️ Actualizar asignación
    public function update(Request $request, $id)
    {
        $asignation = Asignation::findOrFail($id);

        $request->validate([
            'state' => 'sometimes|in:nuevo,buen estado,regular,mal estado,obsoleto',
            'date' => 'sometimes|date',
        ]);

        $asignation->update($request->only(['state', 'date']));

        return response()->json([
            'data' => $asignation->load(['tool', 'worker'])
        ]);
    }

    // ❌ Eliminar asignación (y devolver stock)
    public function destroy($id)
    {
        $asignation = Asignation::findOrFail($id);

        try {
            DB::transaction(function () use ($asignation) {

                // 🔒 Lock tool
                $tool = Tool::lockForUpdate()->findOrFail($asignation->tool_id);

                // 🔺 Devolver stock
                $tool->increment(
                    'unassigned_quantity',
                    $asignation->assigned_quantity
                );

                $asignation->delete();

                // 🧠 Validar consistencia
                $this->validateInventory($tool);
            });

            return response()->json([
                'message' => 'Asignación eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la asignación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 🧠 VALIDACIÓN INTERNA
    private function validateInventory($tool)
    {
        $assigned = Asignation::where('tool_id', $tool->id)
            ->sum('assigned_quantity');

        if ($tool->quantity !== ($assigned + $tool->unassigned_quantity)) {
            throw new \Exception('Inventario desincronizado');
        }
    }

    // 🔍 INVENTORY CHECK (diagnóstico)
    public function inventoryCheck()
    {
        $tools = Tool::all();

        $results = [];

        foreach ($tools as $tool) {

            $assigned = Asignation::where('tool_id', $tool->id)
                ->sum('assigned_quantity');

            $expected = $tool->quantity - $assigned;

            $isValid = $expected == $tool->unassigned_quantity;

            $results[] = [
                'tool_id' => $tool->id,
                'tool_name' => $tool->name,
                'quantity' => $tool->quantity,
                'assigned' => $assigned,
                'unassigned' => $tool->unassigned_quantity,
                'expected_unassigned' => $expected,
                'status' => $isValid ? 'OK' : 'ERROR',
                'difference' => $tool->unassigned_quantity - $expected
            ];
        }

        return response()->json([
            'status' => collect($results)->contains('status', 'ERROR') ? 'ERROR' : 'OK',
            'data' => $results
        ]);
    }

    // 🔧 FIX INVENTORY (corrige automáticamente)
    public function fixInventory($toolId)
    {
        $tool = Tool::findOrFail($toolId);

        $assigned = Asignation::where('tool_id', $toolId)
            ->sum('assigned_quantity');

        $tool->unassigned_quantity = $tool->quantity - $assigned;
        $tool->save();

        return response()->json([
            'message' => 'Inventario corregido correctamente',
            'tool' => $tool
        ]);
    }
}