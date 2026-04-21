<?php

namespace App\Http\Controllers;

use App\Models\Asignation;
use App\Models\LostTool;
use App\Models\Obsolete;
use App\Models\Review;
use App\Models\ReviewItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index(): JsonResponse
    {
        $reviews = Review::with(['worker', 'reviewer', 'reviewItems.tool'])
            ->latest()
            ->get();

        return response()->json(['data' => $reviews]);
    }

    public function show(int $id): JsonResponse
    {
        $review = Review::with(['worker', 'reviewer', 'reviewItems.tool', 'lostTools.tool'])->findOrFail($id);

        return response()->json(['data' => $review]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'worker_id' => 'required|exists:workers,id',
            'name' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.asignation_id' => 'required|exists:asignations,id',
            'items.*.quantity_present' => 'required|integer|min:0',
            'items.*.new_state' => 'nullable|in:nuevo,buen estado,regular,mal estado,obsoleto',
        ]);

        try {
            $review = DB::transaction(function () use ($request) {
                $review = Review::create([
                    'worker_id' => $request->worker_id,
                    'reviewed_by' => $request->user()->id,
                    'name' => $request->name,
                ]);

                foreach ($request->items as $item) {
                    $asignation = Asignation::with('tool')->findOrFail($item['asignation_id']);

                    $quantityPresent = (int) $item['quantity_present'];
                    $quantityLost = $asignation->assigned_quantity - $quantityPresent;
                    $previousState = $asignation->state;

                    $newState = $item['new_state'];
                    $isObsolete = $newState === 'obsoleto';

                    if ($quantityLost > 0) {
                        LostTool::create([
                            'review_id' => $review->id,
                            'tool_id' => $asignation->tool_id,
                            'worker_id' => $asignation->worker_id,
                            'quantity_lost' => $quantityLost,
                            'lost_at' => now()->toDateString(),
                        ]);

                        $asignation->tool->decrement('quantity', $quantityLost);

                        if ($asignation->tool->unassigned_quantity > 0) {
                            $asignation->tool->decrement('unassigned_quantity', min($quantityLost, $asignation->tool->unassigned_quantity));
                        }
                    }

                    if ($isObsolete) {
                        Obsolete::create([
                            'tool_id' => $asignation->tool_id,
                            'worker_id' => $asignation->worker_id,
                            'date' => now()->toDateString(),
                        ]);

                        $asignation->tool->decrement('quantity', $quantityPresent);

                        $quantityBefore = $asignation->assigned_quantity;
                        if ($quantityBefore > 0 && $asignation->tool->unassigned_quantity > 0) {
                            $asignation->tool->decrement('unassigned_quantity', min($quantityBefore, $asignation->tool->unassigned_quantity));
                        }

                        $asignation->delete();
                    } elseif ($quantityPresent === 0) {
                        $asignation->delete();
                    } else {
                        $asignation->update([
                            'assigned_quantity' => $quantityPresent,
                            'state' => $newState,
                        ]);
                    }

                    ReviewItem::create([
                        'review_id' => $review->id,
                        'tool_id' => $asignation->tool_id,
                        'worker_id' => $asignation->worker_id,
                        'previous_state' => $previousState,
                        'new_state' => $isObsolete ? null : $newState,
                    ]);
                }

                return $review;
            });

            return response()->json([
                'data' => $review->load(['worker', 'reviewer', 'reviewItems.tool', 'lostTools.tool']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al guardar la revisión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
