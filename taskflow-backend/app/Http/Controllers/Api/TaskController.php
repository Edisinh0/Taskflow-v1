<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Events\TaskUpdated;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    /**
     * Listar tareas (con filtros opcionales)
     * GET /api/v1/tasks
     */
    public function index(Request $request)
    {
        $query = Task::with(['flow', 'assignee', 'parentTask', 'subtasks']);

        // Filtrar por flujo
        if ($request->has('flow_id')) {
            $query->where('flow_id', $request->flow_id);
        }

        // Filtrar por usuario asignado
        if ($request->has('assignee_id')) {
            $query->where('assignee_id', $request->assignee_id);
        }

        // Filtrar por estado
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Solo milestones
        if ($request->has('milestones_only') && $request->milestones_only) {
            $query->where('is_milestone', true);
        }

        // Solo tareas raíz (sin padre)
        if ($request->has('root_only') && $request->root_only) {
            $query->whereNull('parent_task_id');
        }

        $tasks = $query->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ], 200);
    }

    /**
     * Crear nueva tarea
     * POST /api/v1/tasks
     */
    public function store(Request $request)
    {
        // Autorización: Solo PM/Admin pueden crear tareas (modificar estructura)
        \Illuminate\Support\Facades\Gate::authorize('create', Task::class);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'flow_id' => 'required|exists:flows,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'assignee_id' => 'nullable|exists:users,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:pending,blocked,in_progress,paused,completed,cancelled',
            'is_milestone' => 'nullable|boolean',
            'allow_attachments' => 'nullable|boolean', // <-- Permitir adjuntos
            'estimated_start_at' => 'nullable|date',
            'estimated_end_at' => 'nullable|date',
            // ⚠️ NO permitir que el frontend controle is_blocked
            // Este campo se calcula automáticamente en el Observer
            'depends_on_task_id' => 'nullable|exists:tasks,id',
            'depends_on_milestone_id' => 'nullable|exists:tasks,id',
        ]);

        // Validar dependencias circulares y auto-referencia
        if (isset($validated['depends_on_task_id'])) {
            // Verificar que no sea la misma tarea (aunque aún no tiene ID, prevenir en updates)
            if (isset($validated['depends_on_milestone_id']) &&
                $validated['depends_on_task_id'] === $validated['depends_on_milestone_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Una tarea no puede depender de la misma tarea como precedente y milestone.',
                ], 422);
            }
        }

        // ✅ NO establecer is_blocked aquí - el Observer lo maneja automáticamente
        // El Observer::creating() verificará las dependencias y establecerá is_blocked correctamente

        // 🔧 Lógica especial para subtareas de milestones
        if (isset($validated['parent_task_id'])) {
            // Verificar si hay otras subtareas hermanas
            $siblingSubtasks = Task::where('parent_task_id', $validated['parent_task_id'])
                ->orderBy('order', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            // Si es la primera subtarea, debe estar en "in_progress"
            if ($siblingSubtasks->isEmpty()) {
                if (!isset($validated['status'])) {
                    $validated['status'] = 'in_progress';
                }
            } else {
                // Si no es la primera, debe depender de la última subtarea creada
                // (solo si no se especificó otra dependencia manualmente)
                if (!isset($validated['depends_on_task_id'])) {
                    $lastSubtask = $siblingSubtasks->last();
                    $validated['depends_on_task_id'] = $lastSubtask->id;
                }
                // Si no se especificó estado, dejarla en "pending" (por defecto)
            }
        }

        $task = Task::create($validated);


        return response()->json([
            'success' => true,
            'message' => 'Tarea creada exitosamente',
            'data' => $task->load(['flow', 'assignee']),
        ], 201);
    }

    /**
     * Ver una tarea específica
     * GET /api/v1/tasks/{id}
     */
    public function show($id)
    {
        try {
            $task = Task::with([
                'flow',
                'assignee',
                'lastEditor',
                'parentTask',
                'subtasks.assignee',
                'dependencies.dependsOnTask',
                'dependents.task',
                'attachments.uploader' // Cargar adjuntos
            ])->findOrFail($id);

            // Verificar si está bloqueada
            // Usamos try-catch interno por si hay errores de recursión o datos corruptos
            try {
                $task->is_blocked = $task->checkIsBlocked();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Error calculando isBlocked para tarea $id: " . $e->getMessage());
                // Fallback seguro
                $task->is_blocked = false; 
            }

            return response()->json([
                'success' => true,
                'data' => $task,
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error mostrando tarea $id: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno al cargar la tarea.',
                'error_debug' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Actualizar tarea
     * PUT /api/v1/tasks/{id}
     */
    public function update(Request $request, $id)
{
    // Log CRÍTICO para debugging usando error_log que va directo a stderr
    error_log('🚨🚨🚨 TaskController::update() EJECUTÁNDOSE - ID: ' . $id);
    error_log('🚨🚨🚨 Request data: ' . json_encode($request->all()));

    \Log::channel('single')->critical('🚨 TaskController::update() INICIO', [
        'task_id' => $id,
        'request_data' => $request->all()
    ]);

    $task = Task::findOrFail($id);

    // Determinar si es una actualización de estructura o de ejecución
    $isStructuralChange = $request->hasAny([
        'title', 'description', 'parent_task_id', 'is_milestone', 
        'depends_on_task_id', 'depends_on_milestone_id', 'priority', 
        'estimated_start_at', 'estimated_end_at', 'assignee_id'
    ]);

    if ($isStructuralChange) {
        \Illuminate\Support\Facades\Gate::authorize('updateStructure', $task);
    } else {
        // Si solo es estado/progreso/adjuntos, es ejecución
        \Illuminate\Support\Facades\Gate::authorize('execute', $task);
    }
    
    $validated = $request->validate([
        'flow_id' => 'sometimes|exists:flows,id',
        'title' => 'sometimes|string|max:255',
        'description' => 'nullable|string',
        'status' => ['sometimes', 'string', Rule::in(['pending', 'in_progress', 'completed', 'paused', 'cancelled'])],
        'assignee_id' => 'nullable|exists:users,id',
        'estimated_start_at' => 'nullable|date',
        'estimated_end_at' => 'nullable|date',
        'is_milestone' => 'sometimes|boolean',
        'allow_attachments' => 'sometimes|boolean',
        'order' => 'sometimes|integer|min:0',
        'depends_on_task_id' => 'nullable|exists:tasks,id',
        'depends_on_milestone_id' => 'nullable|exists:tasks,id',
        'parent_task_id' => 'nullable|exists:tasks,id',
        'progress' => 'sometimes|integer|min:0|max:100',
        'priority' => ['sometimes', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
        'notes' => 'nullable|string',
        'blocked_reason' => 'nullable|string',
    ]);

    // 🎯 MOTOR DE CONTROL DE FLUJOS (Lógica de Bloqueo y Requisitos)
    if (isset($validated['status'])) {
        // ⚠️ IMPORTANTE: Refrescar desde BD para obtener el valor actualizado de is_blocked
        $task->refresh();
        $newStatus = $validated['status'];
        $currentStatus = $task->status;

        // 1. Verificar Bloqueo - SOLO si intenta CAMBIAR el estado a in_progress o completed
        if ($task->is_blocked && $currentStatus !== $newStatus) {
            // Si intenta iniciarla o finalizarla estando bloqueada Y el estado es diferente al actual
            if (in_array($newStatus, ['in_progress', 'completed'])) {

                // Generar mensaje detallado del bloqueo
                $blockingReasons = [];

                if ($task->depends_on_task_id) {
                    $precedentTask = Task::find($task->depends_on_task_id);
                    if ($precedentTask && $precedentTask->status !== 'completed') {
                        $blockingReasons[] = "la tarea '{$precedentTask->title}' (ID: {$precedentTask->id})";
                    }
                }

                if ($task->depends_on_milestone_id) {
                    $milestone = Task::find($task->depends_on_milestone_id);
                    if ($milestone && $milestone->status !== 'completed') {
                        $blockingReasons[] = "el milestone '{$milestone->title}' (ID: {$milestone->id})";
                    }
                }

                $blockMessage = !empty($blockingReasons)
                    ? "Esta tarea está bloqueada. Debe completarse " . implode(' y ', $blockingReasons) . " primero."
                    : "Esta tarea está bloqueada por dependencias no cumplidas.";

                return response()->json([
                    'success' => false,
                    'message' => "🔒 Acción prohibida: {$blockMessage}",
                ], 403);
            }
        }
        
        // 2. Validar adjuntos obligatorios al completar
        if ($newStatus === 'completed' && $task->allow_attachments) {
            if ($task->attachments()->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => "⚠️ Requisito faltante: Debes adjuntar al menos un documento para completar esta tarea.",
                ], 422);
            }
        }
    }
    
    // Continuar con la actualización normal
    try {
        if (isset($validated['assignee_id']) && !isset($task->assigned_at)) {
            $validated['assigned_at'] = now();
        }

        // Guardar los cambios realizados
        $originalAttributes = $task->getOriginal();

        \Log::info('🎯 TaskController::update() - ANTES de actualizar', [
            'task_id' => $task->id,
            'validated' => $validated,
            'original_assignee_id' => $originalAttributes['assignee_id'] ?? null,
            'new_assignee_id' => $validated['assignee_id'] ?? null,
            'has_assignee_change' => isset($validated['assignee_id']) && ($originalAttributes['assignee_id'] ?? null) !== $validated['assignee_id']
        ]);

        $task->update($validated);

        // Refrescar desde BD para obtener valores actualizados por triggers
        $task->refresh();

        \Log::info('🎯 TaskController::update() - DESPUÉS de actualizar', [
            'task_id' => $task->id,
            'current_assignee_id' => $task->assignee_id
        ]);

        // Calcular qué campos cambiaron
        $changes = [];
        foreach ($validated as $key => $value) {
            if (isset($originalAttributes[$key]) && $originalAttributes[$key] != $value) {
                $changes[$key] = [
                    'old' => $originalAttributes[$key],
                    'new' => $value,
                ];
            }
        }

        // Disparar evento en tiempo real
        if (!empty($changes)) {
            broadcast(new TaskUpdated($task, $changes))->toOthers();
        }

        // 🚨 VERIFICAR SI CAMBIÓ EL ESTADO SLA (fechas o estado de tarea)
        $dateChanged = isset($changes['estimated_end_at']) ||
                       isset($changes['estimated_start_at']) ||
                       isset($changes['status']);

        if ($dateChanged) {
            \Log::info('📅 Fecha o estado cambió, verificando SLA', [
                'task_id' => $task->id,
                'changes' => array_keys($changes)
            ]);

            // Calcular estado SLA ANTERIOR
            $oldStatus = 'none';
            if (isset($originalAttributes['sla_due_date']) &&
                !in_array($originalAttributes['status'] ?? 'pending', ['completed', 'cancelled'])) {

                $oldDueDate = \Carbon\Carbon::parse($originalAttributes['sla_due_date']);
                $now = now();

                if ($now->isAfter($oldDueDate)) {
                    $hoursOverdue = $now->diffInHours($oldDueDate, false);
                    if ($hoursOverdue >= 48) {
                        $oldStatus = 'critical';
                    } elseif ($hoursOverdue >= 24) {
                        $oldStatus = 'warning';
                    }
                }
            }

            // Calcular estado SLA NUEVO (refresh ya hizo la consulta)
            $newStatus = $task->recalculateSLAStatus();

            \Log::info('🔄 Comparando estados SLA en Controller', [
                'task_id' => $task->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            // Disparar evento si cambió el estado SLA
            if ($oldStatus !== $newStatus) {
                \Log::info('🚨 Disparando evento SLAStatusChanged desde Controller', [
                    'task_id' => $task->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]);

                event(new \App\Events\SLAStatusChanged($task, $oldStatus, $newStatus));

                // Limpiar alertas obsoletas
                $slaService = app(\App\Services\SLAService::class);
                $slaService->clearStaleAlerts($task);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tarea actualizada exitosamente',
            'data' => $task->load(['flow', 'assignee', 'parentTask', 'subtasks']),
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar tarea: ' . $e->getMessage(),
        ], 500);
    }
}

    /**
     * Eliminar tarea
     * DELETE /api/v1/tasks/{id}
     */
    public function destroy($id)
    {
        $task = Task::findOrFail($id);

        // Autorización: Solo PM/Admin pueden eliminar
        \Illuminate\Support\Facades\Gate::authorize('delete', $task);
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tarea eliminada exitosamente',
        ], 200);
    }

    public function reorder(Request $request)
    {
        // Autorización: Modificar orden es estructural (PM/Admin)
        \Illuminate\Support\Facades\Gate::authorize('create', Task::class); // Usamos create o una permission genérica de estructura
        $validated = $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:tasks,id',
            'tasks.*.order' => 'required|integer|min:0',
            'tasks.*.parent_task_id' => 'nullable|exists:tasks,id',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['tasks'] as $taskData) {
                Task::where('id', $taskData['id'])->update([
                    'order' => $taskData['order'],
                    'parent_task_id' => $taskData['parent_task_id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tareas reordenadas exitosamente',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al reordenar tareas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mover tarea a otro milestone/parent
     * POST /api/v1/tasks/{id}/move
     */
    public function move(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        
        // Autorización: Mover es cambio estructural
        \Illuminate\Support\Facades\Gate::authorize('updateStructure', $task);

        $validated = $request->validate([
            'parent_task_id' => 'nullable|exists:tasks,id',
            'order' => 'nullable|integer|min:0',
        ]);

        try {
            $task->update([
                'parent_task_id' => $validated['parent_task_id'] ?? null,
                'order' => $validated['order'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tarea movida exitosamente',
                'data' => $task->load(['flow', 'assignee', 'parentTask']),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mover tarea: ' . $e->getMessage(),
            ], 500);
        }
    }
}