<?php

namespace App\Http\Controllers;

use App\Models\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Validator;

class RequestController extends Controller
{
    public function store(HttpRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'worker_id' => 'required|exists:workers,id',
            'type_request' => 'required|in:PRIMERA_VEZ,SE_ROMPIO,DESGASTE,SE_PERDIO',
            'details_tool' => 'required|string',
            'preferred_brand' => 'nullable|string',
            'signa_applicant' => 'required|string',
            'signa_authorization' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $requestData = Request::create([
            'worker_id' => $request->worker_id,
            'type_request' => $request->type_request,
            'details_tool' => $request->details_tool,
            'preferred_brand' => $request->preferred_brand,
            'signa_applicant' => $request->signa_applicant,
            'signa_authorization' => $request->signa_authorization,
            'state' => 'pendiente',
        ]);

        return response()->json([
            'success' => true,
            'data' => $requestData,
            'message' => 'Solicitud creada correctamente',
        ], 201);
    }
}