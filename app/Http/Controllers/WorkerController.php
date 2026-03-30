<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->get('search', '');

        $workers = Worker::when($search, function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('lastname', 'like', "%{$search}%")
                ->orWhere('worker_code', 'like', "%{$search}%")
                ->orWhere('area', 'like', "%{$search}%");
        })->orderBy('created_at', 'desc')->get();

        return Inertia::render('Workers/Index', [
            'workers' => $workers,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|regex:/^[\pL\s]+$/u',
            'lastname' => 'required|string|max:255|regex:/^[\pL\s]+$/u',
            'area' => 'required|in:montaje/desmontaje,armado/desarmado',
        ]);

        $validated['worker_code'] = Worker::generateWorkerCode();
        $validated['created_by'] = $request->user()->id;

        Worker::create($validated);

        return redirect()->route('workers.index')->with('success', 'Trabajador creado exitosamente.');
    }

    public function update(Request $request, Worker $worker): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|regex:/^[\pL\s]+$/u',
            'lastname' => 'required|string|max:255|regex:/^[\pL\s]+$/u',
            'area' => 'required|in:montaje/desmontaje,armado/desarmado',
        ]);

        $worker->update($validated);

        return redirect()->route('workers.index')->with('success', 'Trabajador actualizado exitosamente.');
    }

    public function destroy(Worker $worker): RedirectResponse
    {
        $worker->delete();

        return redirect()->route('workers.index')->with('success', 'Trabajador eliminado exitosamente.');
    }
}
