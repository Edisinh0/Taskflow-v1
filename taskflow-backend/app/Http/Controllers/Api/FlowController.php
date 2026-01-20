<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flow;
use App\Models\Template;
use App\Models\Task;
use Illuminate\Http\Request;

class FlowController extends Controller
{
    /**
     * Listar todos los flujos
     * GET /api/v1/flows
     */
    public function index(Request $request)
    {
        $query = Flow::with(['template', 'creator', 'responsible', 'tasks']);

        // Filtrar por estado si se envía
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrar por plantilla
        if ($request->has('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        $flows = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $flows,
        ], 200);
    }

    /**
     * Crear nuevo flujo
     * POST /api/v1/flows
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'template_id' => 'nullable|exists:templates,id',
                'client_id' => 'nullable|exists:clients,id',
                'responsible_id' => 'nullable|exists:users,id',
                'status' => 'nullable|in:active,paused,completed,cancelled',
            ]);

            // Verificar que el usuario esté autenticado
            if (!$request->user()) {
                \Illuminate\Support\Facades\Log::error('Flow creation failed: User not authenticated');
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            // Autorización mediante Policy (Solo PM/Admin pueden crear)
            \Illuminate\Support\Facades\Gate::authorize('create', Flow::class);

            $flow = Flow::create([
                ...$validated,
                'created_by' => $request->user()->id,
                'started_at' => now(),
            ]);

        // Instanciar tareas desde la plantilla si existe
        if ($request->template_id) {
            $template = Template::find($request->template_id);
            if ($template && isset($template->config['tasks']) && is_array($template->config['tasks'])) {
                $idMap = [];
                $pendingDependencies = [];

                $this->createTasksFromTemplate($flow->id, $template->config['tasks'], null, $idMap, $pendingDependencies);

                \Illuminate\Support\Facades\Log::info("Flow ID {$flow->id}: Tareas creadas. ID Map: " . json_encode($idMap));
                \Illuminate\Support\Facades\Log::info("Flow ID {$flow->id}: Dependencias pendientes: " . json_encode($pendingDependencies));

                // Procesar dependencias pendientes
                foreach ($pendingDependencies as $pending) {
                    $taskModified = false;
                    $task = null;

                    // 1. Sistema complejo (TaskDependency M:N)
                    if (isset($pending['dependency_refs']) && is_array($pending['dependency_refs'])) {
                        foreach ($pending['dependency_refs'] as $refId) {
                            if (isset($idMap[$refId])) {
                                \Illuminate\Support\Facades\Log::info("Creando dependencia Pivot: Tarea {$pending['new_task_id']} depende de {$idMap[$refId]}");
                                \App\Models\TaskDependency::create([
                                    'task_id' => $pending['new_task_id'],
                                    'depends_on_task_id' => $idMap[$refId],
                                    'dependency_type' => 'finish_to_start'
                                ]);
                            }
                        }
                    }

                    // 2. Sistema simple (Columna depends_on_task_id 1:N)
                    if (isset($pending['depends_on_task_ref'])) {
                        $refId = $pending['depends_on_task_ref'];
                        if (isset($idMap[$refId])) {
                            if (!$task) $task = Task::find($pending['new_task_id']);
                            
                            if ($task) {
                                \Illuminate\Support\Facades\Log::info("Asignando depends_on_task_id: Tarea {$task->id} depende de {$idMap[$refId]}");
                                $task->depends_on_task_id = $idMap[$refId];
                                $taskModified = true;
                            }
                        } else {
                            \Illuminate\Support\Facades\Log::warning("No se encontró ref en mapa para depends_on_task_ref: $refId");
                        }
                    }

                    if ($taskModified && $task) {
                        $task->save(); // Dispara Observer updating -> recalcula bloqueo
                    }
                }
                
                // Recalcular estado de bloqueo final para asegurar consistencia
                foreach ($idMap as $newTaskId) {
                    $task = Task::find($newTaskId);
                    if ($task) {
                        // Forzar refresco por si el observer no corrió o si usamos sistema pivot
                        $task->is_blocked = $task->checkIsBlocked();
                        if ($task->is_blocked && $task->status !== 'completed' && $task->status !== 'blocked') {
                            $task->status = 'blocked';
                            $task->blocked_reason = 'Esperando tareas precedentes';
                            $task->saveQuietly();
                        }
                    }
                }
            }
        }

        // El Observer se encargará de notificar automáticamente (FlowObserver::created)
        // No necesitamos llamar manualmente al servicio aquí

        return response()->json([
            'success' => true,
            'message' => 'Flujo creado exitosamente',
            'data' => $flow->load(['template', 'creator', 'responsible', 'tasks']),
        ], 201);
        
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Flow creation validation failed: ' . json_encode($e->errors()));
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Flow creation failed: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el flujo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recursively create tasks from template configuration
     */
    private function createTasksFromTemplate($flowId, $tasks, $parentId = null, &$idMap = [], &$pendingDependencies = [], $cumulativeOffset = 0)
    {
        $previousSubtaskId = null;
        $isFirstSubtask = true;
        $currentOffset = $cumulativeOffset; // Track cumulative offset for sequential tasks

        foreach ($tasks as $taskData) {
            // Determinar el estado inicial de la tarea
            $initialStatus = 'pending';

            // Si es una subtarea (tiene parent_task_id) y es la primera, debe estar "in_progress"
            if ($parentId !== null && $isFirstSubtask) {
                $initialStatus = 'in_progress';
                $isFirstSubtask = false;
            }

            // Calcular fechas estimadas
            // Si tiene start_day_offset explícito, usarlo. Si no, usar el offset acumulado
            $startOffset = $taskData['start_day_offset'] ?? $currentOffset;
            $durationDays = $taskData['duration_days'] ?? 1; // Duración por defecto: 1 día

            $estimatedStartAt = now()->addDays($startOffset);
            $estimatedEndAt = now()->addDays($startOffset + $durationDays);

            $task = Task::create([
                'flow_id' => $flowId,
                'parent_task_id' => $parentId,
                'title' => $taskData['title'],
                'description' => $taskData['description'] ?? null,
                'is_milestone' => $taskData['is_milestone'] ?? false,
                'priority' => $taskData['priority'] ?? 'medium',
                'status' => $initialStatus,
                'estimated_start_at' => $estimatedStartAt,
                'estimated_end_at' => $estimatedEndAt,
            ]);

            // Incrementar el offset acumulado para la siguiente tarea
            $currentOffset += $durationDays;

            // Guardar mapeo si existe referencia
            if (isset($taskData['temp_ref_id'])) {
                $idMap[$taskData['temp_ref_id']] = $task->id;
            }

            // Guardar dependencias para procesar después
            $pendingData = [
                'new_task_id' => $task->id
            ];
            $hasPending = false;

            if (isset($taskData['dependencies']) && is_array($taskData['dependencies']) && !empty($taskData['dependencies'])) {
                $pendingData['dependency_refs'] = $taskData['dependencies'];
                $hasPending = true;
            }

            if (isset($taskData['depends_on_task_ref']) && $taskData['depends_on_task_ref']) {
                $pendingData['depends_on_task_ref'] = $taskData['depends_on_task_ref'];
                $hasPending = true;
            }

            // Si es una subtarea (no la primera) y hay una subtarea anterior, debe depender de ella
            if ($parentId !== null && $previousSubtaskId !== null) {
                $pendingData['depends_on_task_ref'] = 'prev_subtask_' . $previousSubtaskId;
                $idMap['prev_subtask_' . $previousSubtaskId] = $previousSubtaskId;
                $hasPending = true;
            }

            if ($hasPending) {
                $pendingDependencies[] = $pendingData;
            }

            // Actualizar el ID de la subtarea anterior para la próxima iteración
            if ($parentId !== null) {
                $previousSubtaskId = $task->id;
            }

            // Si tiene subtareas, crearlas recursivamente
            // Las subtareas siempre empiezan desde offset 0 relativo a su milestone
            if (isset($taskData['subtasks']) && is_array($taskData['subtasks'])) {
                $this->createTasksFromTemplate($flowId, $taskData['subtasks'], $task->id, $idMap, $pendingDependencies, 0);
            }
        }
    }

    /**
     * Ver un flujo específico con todas sus tareas
     * GET /api/v1/flows/{id}
     */
    public function show($id)
    {
        $flow = Flow::with([
            'template',
            'creator',
            'responsible',
            'lastEditor',
            'tasks.assignee',
            'tasks.lastEditor',
            'tasks.subtasks',
            'tasks.subtasks.lastEditor',
            'tasks.dependsOnTask',
            'tasks.dependsOnMilestone',
            'tasks.subtasks.dependsOnTask',
            'tasks.subtasks.dependsOnMilestone',
            'tasks.attachments.uploader',
            'tasks.subtasks.attachments.uploader',
            'tasks.subtasks.assignee',
            'milestones.lastEditor',
            'milestones.assignee'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $flow,
        ], 200);
    }

    /**
     * Actualizar flujo
     * PUT /api/v1/flows/{id}
     */
    public function update(Request $request, $id)
    {
        $flow = Flow::findOrFail($id);

        // Autorización mediante Policy (Solo PM/Admin pueden actualizar)
        \Illuminate\Support\Facades\Gate::authorize('update', $flow);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
            'responsible_id' => 'nullable|exists:users,id',
            'status' => 'sometimes|in:active,paused,completed,cancelled',
        ]);

        // Si se marca como completado, guardar la fecha
        if (isset($validated['status']) && $validated['status'] === 'completed' && !$flow->completed_at) {
            $validated['completed_at'] = now();
        }

        // El Observer se encarga automáticamente de:
        // - Notificar cambio de responsable (FlowObserver::updating)
        // - Notificar flujo completado (FlowObserver::updating)
        $flow->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Flujo actualizado exitosamente',
            'data' => $flow->load(['template', 'creator', 'responsible']),
        ], 200);
    }

    /**
     * Eliminar flujo
     * DELETE /api/v1/flows/{id}
     */
    public function destroy($id)
    {
        $flow = Flow::findOrFail($id);
        
        // Autorización mediante Policy (Solo PM/Admin pueden eliminar)
        \Illuminate\Support\Facades\Gate::authorize('delete', $flow);
        
        // Eliminar tareas asociadas (Soft Delete) explícitamente
        // Esto previene que queden tareas huérfanas si el Observer falla
        $flow->tasks()->delete();
        
        $flow->delete();

        return response()->json([
            'success' => true,
            'message' => 'Flujo eliminado exitosamente',
        ], 200);
    }
}