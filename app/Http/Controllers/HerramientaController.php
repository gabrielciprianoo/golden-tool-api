<?php

namespace App\Http\Controllers;

use App\Models\Herramienta;
use Illuminate\Http\Request;

class HerramientaController extends Controller
{
    // 🔹 Obtener todas las herramientas
    public function index()
    {
        return response()->json(Herramienta::all());
    }

    // 🔹 Guardar herramienta
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'categoria' => 'required|string',
            'precio' => 'required|numeric',
            'proveedor' => 'required|string',
            'fecha_ingreso' => 'required|date',
            'cantidad' => 'required|integer',
            'cantidad_no_asignada' => 'required|integer'
        ]);

        $herramienta = Herramienta::create($request->all());

        return response()->json([
            'mensaje' => 'Herramienta guardada correctamente',
            'data' => $herramienta
        ], 201);
    }

    // 🔹 Obtener una herramienta por ID
    public function show($id)
    {
        $herramienta = Herramienta::find($id);

        if (!$herramienta) {
            return response()->json([
                'mensaje' => 'Herramienta no encontrada'
            ], 404);
        }

        return response()->json($herramienta);
    }

    // 🔹 Actualizar herramienta
    public function update(Request $request, $id)
    {
        $herramienta = Herramienta::find($id);

        if (!$herramienta) {
            return response()->json([
                'mensaje' => 'Herramienta no encontrada'
            ], 404);
        }

        $request->validate([
            'nombre' => 'sometimes|string',
            'categoria' => 'sometimes|string',
            'precio' => 'sometimes|numeric',
            'proveedor' => 'sometimes|string',
            'fecha_ingreso' => 'sometimes|date',
            'cantidad' => 'sometimes|integer',
            'cantidad_no_asignada' => 'sometimes|integer'
        ]);

        $herramienta->update($request->all());

        return response()->json([
            'mensaje' => 'Herramienta actualizada correctamente',
            'data' => $herramienta
        ]);
    }

    // 🔹 Eliminar herramienta
    public function destroy($id)
    {
        $herramienta = Herramienta::find($id);

        if (!$herramienta) {
            return response()->json([
                'mensaje' => 'Herramienta no encontrada'
            ], 404);
        }

        $herramienta->delete();

        return response()->json([
            'mensaje' => 'Herramienta eliminada correctamente'
        ]);
    }
}