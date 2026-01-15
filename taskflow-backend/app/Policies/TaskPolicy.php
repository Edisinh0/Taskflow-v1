<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Task Policy
 *
 * Separación de permisos entre:
 * - Flow Builder (Diseño): Crear/editar estructura
 * - Task Center (Ejecución): Solo completar y actualizar progreso
 */
class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determinar si el usuario puede ver tareas
     */
    public function viewAny(User $user): bool
    {
        // Todos pueden ver tareas
        return true;
    }

    /**
     * Determinar si el usuario puede ver una tarea específica
     */
    public function view(User $user, Task $task): bool
    {
        // Puede ver si:
        // - Es el asignado
        // - Es PM/Admin
        // - Es parte del equipo del flujo
        return $task->assignee_id === $user->id
            || $this->isFlowBuilder($user)
            || $task->flow->created_by === $user->id;
    }

    /**
     * Determinar si el usuario puede crear tareas
     *
     * FLOW BUILDER: Solo PM/Admin pueden crear tareas
     * (estructura del flujo)
     */
    public function create(User $user): bool
    {
        return $this->isFlowBuilder($user);
    }

    /**
     * Determinar si el usuario puede actualizar la ESTRUCTURA de una tarea
     *
     * FLOW BUILDER: Solo PM/Admin pueden modificar:
     * - Título, descripción
     * - Dependencias (depends_on_task_id, depends_on_milestone_id)
     * - Jerarquía (parent_task_id)
     * - Configuración de milestone
     * - Asignación inicial
     */
    public function updateStructure(User $user, Task $task): bool
    {
        return $this->isFlowBuilder($user);
    }

    /**
     * Determinar si el usuario puede EJECUTAR una tarea
     *
     * TASK CENTER: Usuario asignado puede:
     * - Cambiar status (pending → in_progress → completed)
     * - Actualizar progress (0-100%)
     * - Registrar tiempo (actual_start_at, actual_end_at)
     * - Subir archivos adjuntos
     */
    public function execute(User $user, Task $task): bool
    {
        // Solo el usuario asignado o PM/Admin
        return $task->assignee_id === $user->id
            || $this->isFlowBuilder($user);
    }

    /**
     * Determinar si el usuario puede eliminar tareas
     *
     * FLOW BUILDER: Solo PM/Admin
     */
    public function delete(User $user, Task $task): bool
    {
        return $this->isFlowBuilder($user);
    }

    /**
     * Determinar si el usuario puede modificar dependencias
     *
     * FLOW BUILDER: Solo PM/Admin
     */
    public function manageDependencies(User $user, Task $task): bool
    {
        return $this->isFlowBuilder($user);
    }

    /**
     * Determinar si el usuario puede validar un milestone
     *
     * FLOW BUILDER: Solo PM/Admin pueden validar milestones
     */
    public function validateMilestone(User $user, Task $task): bool
    {
        if (!$task->is_milestone) {
            return false;
        }

        return $this->isFlowBuilder($user);
    }

    /**
     * Verificar si el usuario tiene rol de Flow Builder
     */
    private function isFlowBuilder(User $user): bool
    {
        return in_array($user->role, ['admin', 'project_manager', 'pm']);
    }

    /**
     * Verificar si el usuario es operativo (Task Center)
     */
    private function isOperator(User $user): bool
    {
        return in_array($user->role, ['user', 'operator', 'employee']);
    }
}
