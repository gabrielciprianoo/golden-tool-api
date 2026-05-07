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

    public function indexCreatedBy(HttpRequest $request)
    {
        $requests = Request::with('tool', 'worker')
            ->where(function($q) use ($request) {
                $q->where('created_by', $request->user()->id)
                  ->orWhereNull('created_by');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function update(HttpRequest $httpRequest, $id)
    {
        $requestModel = Request::find($id);

        if (! $requestModel) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitud no encontrada',
            ], 404);
        }

        $signa_applicant = $httpRequest->input('signa_applicant');
        $signa_authorization = $httpRequest->input('signa_authorization');
        $state = $httpRequest->input('state');

        if ($signa_applicant !== null) {
            $requestModel->signa_applicant = $signa_applicant ?: null;
        }
        if ($signa_authorization !== null) {
            $requestModel->signa_authorization = $signa_authorization ?: null;
        }
        if ($state !== null) {
            $requestModel->state = $state;
        }

        if ($signa_applicant !== null || $signa_authorization !== null) {
            $hasApplicant = ! empty($requestModel->signa_applicant);
            $hasAuth = ! empty($requestModel->signa_authorization);
            $signatureCount = (int) $hasApplicant + (int) $hasAuth;

            if ($signatureCount >= 2 && $state === null) {
                $requestModel->state = 'pendiente_compra';
            } elseif ($state === null) {
                $requestModel->state = 'incompleta';
            }
        }

        $requestModel->save();

        return response()->json([
            'success' => true,
            'data' => $requestModel,
            'message' => 'Solicitud actualizada correctamente',
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

        $hasApplicant = ! empty($request->signa_applicant);
        $hasAuth = ! empty($request->signa_authorization);
        $signatureCount = (int) $hasApplicant + (int) $hasAuth;

        if ($signatureCount >= 2) {
            $state = 'pendiente_compra';
        } else {
            $state = 'incompleta';
        }

        $requestData = Request::create([
            'worker_id' => $request->worker_id,
            'created_by' => $request->user()->id,
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

    public function destroy($id)
    {
        $requestModel = Request::find($id);

        if (! $requestModel) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitud no encontrada',
            ], 404);
        }

        $requestModel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Solicitud eliminada correctamente',
        ]);
    }
}
