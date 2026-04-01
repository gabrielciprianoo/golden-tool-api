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
            'assigned_quantity' => 'required|integer|min:1', // 🔥 NUEVO
            'state' => 'required|in:nuevo,buen estado,regular,mal estado,obsoleto',
            'date' => 'required|date',
        ]);

        $tool = Tool::findOrFail($request->tool_id);

        // 🚨 VALIDACIÓN CORRECTA (aquí estaba el error)
        if ($tool->unassigned_quantity < $request->assigned_quantity) {
            return response()->json([
                'message' => 'No hay suficiente stock disponible'
            ], 400);
        }

        try {
            $asignation = DB::transaction(function () use ($request, $tool) {

                $asignation = Asignation::create([
                    'tool_id' => $request->tool_id,
                    'worker_id' => $request->worker_id,
                    'assigned_quantity' => $request->assigned_quantity,
                    'state' => $request->state,
                    'date' => $request->date,
                ]);

                // 🔻 DESCONTAR CORRECTAMENTE
                $tool->decrement('unassigned_quantity', $request->assigned_quantity);

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
        'assigned_quantity' => 'sometimes|integer|min:1',
    ]);

    $asignation->update(
        $request->only(['state', 'date', 'assigned_quantity'])
    );

    return response()->json([
        'data' => $asignation->load(['tool', 'worker'])
    ]);
}
    // ❌ Eliminar (y devolver stock)
    public function destroy($id)
    {
        $asignation = Asignation::findOrFail($id);

        try {
            DB::transaction(function () use ($asignation) {

                // 🔺 DEVOLVER CORRECTAMENTE
                $asignation->tool->increment(
                    'unassigned_quantity',
                    $asignation->assigned_quantity
                );

                $asignation->delete();
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

    public function getByWorker($workerId)
{
    $assignations = Asignation::with(['tool', 'worker'])
        ->where('worker_id', $workerId)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $assignations
    ]);
}
}
