<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Progress;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProgressController extends Controller
{
    /**
     * Get all progress for a specific task
     */
    public function index($taskId)
    {
        $progresses = Progress::where('task_id', $taskId)
            ->with('createdBy', 'attachments')
            ->latest('created_at')
            ->get();

        return response()->json($progresses);
    }

    /**
     * Store a newly created progress record
     */
    public function store(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'description' => 'required|string',
            'files' => 'nullable|array',
            'files.*' => 'file|max:10240', // Max 10MB per file
        ]);

        // Crear el progreso
        $progress = Progress::create([
            'task_id' => $request->task_id,
            'description' => $request->description,
            'created_by' => auth()->id()
        ]);

        // Procesar archivos adjuntos
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                // Guardar archivo en disco
                $path = $file->store('progress-attachments', 'public');

                // Crear registro en BD
                $progress->attachments()->create([
                    'user_id' => auth()->id(),
                    'name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'attachmentable_type' => Progress::class,
                    'attachmentable_id' => $progress->id,
                ]);
            }
        }

        return response()->json($progress->load(['createdBy', 'attachments']), 201);
    }

    /**
     * Display the specified progress record
     */
    public function show(Progress $progress)
    {
        return response()->json($progress->load(['createdBy', 'attachments']));
    }

    /**
     * Update the specified progress record
     */
    public function update(Request $request, Progress $progress)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'sometimes|required|date'
        ]);

        $progress->update($request->only(['name', 'description', 'date']));

        return response()->json($progress->load('createdBy'));
    }

    /**
     * Remove the specified progress record
     */
    public function destroy(Progress $progress)
    {
        $progress->delete();
        return response()->json(null, 204);
    }
}

