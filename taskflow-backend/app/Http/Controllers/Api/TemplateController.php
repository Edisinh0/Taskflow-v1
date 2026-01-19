<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\Flow;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    /**
     * Listar todas las plantillas
     * GET /api/v1/templates
     */
    public function index()
    {
        $templates = Template::with('creator')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ], 200);
    }

    /**
     * Crear nueva plantilla
     * POST /api/v1/templates
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'version' => 'nullable|string|max:50',
            'config' => 'nullable|array',
        ]);

        $template = Template::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plantilla creada exitosamente',
            'data' => $template->load('creator'),
        ], 201);
    }

    /**
     * Ver una plantilla específica
     * GET /api/v1/templates/{id}
     */
    public function show($id)
    {
        $template = Template::with(['creator', 'flows'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $template,
        ], 200);
    }

    /**
     * Actualizar plantilla
     * PUT /api/v1/templates/{id}
     */
    public function update(Request $request, $id)
    {
        $template = Template::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'version' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
            'config' => 'nullable|array',
        ]);

        $template->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Plantilla actualizada exitosamente',
            'data' => $template->load('creator'),
        ], 200);
    }

    /**
     * Eliminar plantilla (soft delete)
     * DELETE /api/v1/templates/{id}
     */
    public function destroy($id)
    {
        $template = Template::findOrFail($id);
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plantilla eliminada exitosamente',
        ], 200);
    }

    /**
     * Crear una plantilla desde un flujo existente
     * POST /api/v1/templates/from-flow/{flowId}
     */
    public function createFromFlow(Request $request, $flowId)
    {
        // Cargar tareas con sus dependencias
        $flow = Flow::with(['tasks.dependencies'])->findOrFail($flowId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'version' => 'nullable|string|max:50',
        ]);

        // Construir configuración de tareas
        $tasksConfig = [];

        // Mapa para rastrear IDs originales -> temp_refs para dependencias
        // Usaremos el ID original como referencia temporal

        // 1. Identificar hitos
        $milestones = $flow->tasks->where('is_milestone', true);

        // Obtener la fecha de inicio más temprana del flujo para calcular offsets relativos
        $flowStartDate = $flow->tasks
            ->filter(fn($t) => $t->estimated_start_at)
            ->min('estimated_start_at');

        foreach ($milestones as $milestone) {
            $milestoneConfig = [
                'temp_ref_id' => $milestone->id,
                'title' => $milestone->title,
                'description' => $milestone->description,
                'is_milestone' => true,
                'subtasks' => []
            ];

            // 2. Encontrar subtareas para este hito
            $subtasks = $flow->tasks->filter(function ($task) use ($milestone) {
                return !$task->is_milestone &&
                       ($task->parent_task_id == $milestone->id || $task->depends_on_milestone_id == $milestone->id);
            });

            // Ordenar subtareas por fecha de inicio para mantener el orden secuencial
            $subtasks = $subtasks->sortBy('estimated_start_at');

            foreach ($subtasks as $subtask) {
                // Recopilar IDs de tareas de las que depende esta subtarea (Sistema M:N)
                $dependencyRefs = [];
                foreach ($subtask->dependencies as $dep) {
                    $dependencyRefs[] = $dep->depends_on_task_id;
                }

                // Calcular offset relativo desde el inicio del flujo
                $startDayOffset = 0;
                if ($flowStartDate && $subtask->estimated_start_at) {
                    $startDayOffset = $flowStartDate->diffInDays($subtask->estimated_start_at);
                }

                // Calcular duración en días
                $durationDays = 1; // Default
                if ($subtask->estimated_end_at && $subtask->estimated_start_at) {
                    $durationDays = max(1, $subtask->estimated_start_at->diffInDays($subtask->estimated_end_at));
                }

                $milestoneConfig['subtasks'][] = [
                    'temp_ref_id' => $subtask->id, // Guardamos ID original para mapear
                    'title' => $subtask->title,
                    'description' => $subtask->description,
                    'priority' => $subtask->priority,
                    'dependencies' => $dependencyRefs, // Sistema complejo (pivote)
                    'depends_on_task_ref' => $subtask->depends_on_task_id, // Sistema simple (columna, usado por frontend)
                    'start_day_offset' => $startDayOffset,
                    'duration_days' => $durationDays
                ];
            }

            $tasksConfig[] = $milestoneConfig;
        }

        // Crear la plantilla
        $template = Template::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? "Plantilla generada desde el flujo: {$flow->name}",
            'version' => $validated['version'] ?? '1.0',
            'created_by' => $request->user()->id,
            'is_active' => true,
            'config' => [
                'tasks' => $tasksConfig,
                'estimated_duration_days' => 7,
                'priority' => 'medium'
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plantilla generada correctamente desde el flujo. IMPORTANTE: Las dependencias se han guardado.',
            'data' => $template
        ], 201);
    }
}