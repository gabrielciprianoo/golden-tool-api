<?php

namespace App\Http\Controllers;

use App\Models\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Validator;

class RequestController extends Controller
{
    public function index($workerId)
    {
        $requests = Request::with('tool')
            ->where('worker_id', $workerId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function store(HttpRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'worker_id' => 'required|exists:workers,id',
            'tool_id' => 'nullable|exists:tools,id',
            'type_request' => 'required|in:PRIMERA_VEZ,SE_ROMPIO,DESGASTE,SE_PERDIO',
            'details_tool' => 'required|string',
            'preferred_brand' => 'nullable|string',
            'signa_applicant' => 'nullable|string',
            'signa_authorization' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $hasApplicant = !empty($request->signa_applicant);
        $hasAuth = !empty($request->signa_authorization);

        if ($hasApplicant && $hasAuth) {
            $state = 'finalizado';
        } elseif ($hasApplicant || $hasAuth) {
            $state = 'en_proceso';
        } else {
            $state = 'pendiente';
        }

        $requestData = Request::create([
            'worker_id' => $request->worker_id,
            'tool_id' => $request->tool_id ?? null,
            'type_request' => $request->type_request,
            'details_tool' => $request->details_tool,
            'preferred_brand' => $request->preferred_brand,
            'signa_applicant' => $request->signa_applicant ?: null,
            'signa_authorization' => $request->signa_authorization ?: null,
            'state' => $state,
        ]);

        return response()->json([
            'success' => true,
            'data' => $requestData,
            'message' => 'Solicitud creada correctamente',
        ], 201);
    }
}
