<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Models\Asignation;
use App\Models\Tool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkerController extends Controller
{
   public function index()
{
    $workers = Worker::all();

    return response()->json($workers);
}

public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'lastname' => 'required|string|max:255',
        'area' => 'required',
    ]);

    $validated['worker_code'] = Worker::generateWorkerCode();
    $validated['created_by'] = $request->user()->id;

    $worker = Worker::create($validated);

    return response()->json($worker, 201);
}

    public function update(Request $request, Worker $worker)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255|regex:/^[\pL\s]+$/u',
        'lastname' => 'required|string|max:255|regex:/^[\pL\s]+$/u',
        'area' => 'required|in:montaje/desmontaje,armado/desarmado',
    ]);

    $worker->update($validated);

    return response()->json($worker);
}

public function destroy(Worker $worker)
   {
       $asignations = Asignation::where('worker_id', $worker->id)->get();

foreach ($asignations as $asignation) {
            $tool = Tool::find($asignation->tool_id);
            if ($tool) {
                $tool->update([
                    'unassigned_quantity' => $tool->unassigned_quantity + $asignation->assigned_quantity,
                ]);
            }

            $asignation->delete();
        }

       $worker->delete();

       return response()->json(['message' => 'Deleted']);
   }
}
