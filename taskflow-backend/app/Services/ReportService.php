<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Construir query con filtros aplicados
     */
    public function buildQuery(array $filters): Builder
    {
        // Importante: whereHas('flow') filtra tareas cuyo flujo ha sido eliminado (soft deleted)
        // Esto evita que aparezcan tareas "fantasmas" de flujos borrados
        $query = Task::with(['assignee', 'flow', 'dependsOnTask', 'dependsOnMilestone'])
            ->whereHas('flow');

        // Filtro por estado
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Filtro por usuario asignado
        if (!empty($filters['assignee_id'])) {
            $query->where('assignee_id', $filters['assignee_id']);
        }

        // Filtro por prioridad
        if (!empty($filters['priority'])) {
            if (is_array($filters['priority'])) {
                $query->whereIn('priority', $filters['priority']);
            } else {
                $query->where('priority', $filters['priority']);
            }
        }

        // Filtro por flujo
        if (!empty($filters['flow_id'])) {
            $query->where('flow_id', $filters['flow_id']);
        }

        // Filtro por milestone
        if (isset($filters['is_milestone'])) {
            $query->where('is_milestone', $filters['is_milestone']);
        }

        // Filtro por rango de fechas (created_at)
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Ordenar por fecha de creación descendente
        $query->orderBy('created_at', 'desc');

        return $query;
    }

    /**
     * Obtener estadísticas del reporte
     */
    public function getStats(array $filters): array
    {
        $query = $this->buildQuery($filters);
        $tasks = $query->get();

        // Asegurar que avg_progress siempre devuelva un número válido
        $avgProgress = $tasks->count() > 0 ? round($tasks->avg('progress'), 2) : 0;

        return [
            'total' => $tasks->count() ?? 0,
            'by_status' => [
                'pending' => $tasks->where('status', 'pending')->count() ?? 0,
                'in_progress' => $tasks->where('status', 'in_progress')->count() ?? 0,
                'completed' => $tasks->where('status', 'completed')->count() ?? 0,
                'paused' => $tasks->where('status', 'paused')->count() ?? 0,
                'cancelled' => $tasks->where('status', 'cancelled')->count() ?? 0,
            ],
            'by_priority' => [
                'low' => $tasks->where('priority', 'low')->count() ?? 0,
                'medium' => $tasks->where('priority', 'medium')->count() ?? 0,
                'high' => $tasks->where('priority', 'high')->count() ?? 0,
                'urgent' => $tasks->where('priority', 'urgent')->count() ?? 0,
            ],
            'avg_progress' => $avgProgress ?? 0,
            'milestones' => $tasks->where('is_milestone', true)->count() ?? 0,
            'blocked' => $tasks->where('is_blocked', true)->count() ?? 0,
        ];
    }

    /**
     * Obtener métricas de analytics con SLA
     */
    public function getAnalytics(array $filters): array
    {
        $query = $this->buildQuery($filters);
        $tasks = $query->get();

        // Tareas completadas
        $completedTasks = $tasks->where('status', 'completed');

        // Tareas completadas a tiempo (sin SLA breach)
        $completedOnTime = $completedTasks->where('sla_breached', false)->count();
        $completedLate = $completedTasks->where('sla_breached', true)->count();

        // Tareas activas con SLA breach
        $activeTasks = $tasks->whereNotIn('status', ['completed', 'cancelled']);
        $activeWithSLABreach = $activeTasks->where('sla_breached', true)->count();

        // Promedio de días de retraso
        $avgDaysOverdue = $tasks->where('sla_breached', true)
            ->where('sla_days_overdue', '>', 0)
            ->avg('sla_days_overdue');

        // Tareas por estado
        $tasksByStatus = [
            'pending' => $tasks->where('status', 'pending')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'paused' => $tasks->where('status', 'paused')->count(),
            'cancelled' => $tasks->where('status', 'cancelled')->count(),
            'blocked' => $tasks->where('status', 'blocked')->count(),
        ];

        // Tareas por prioridad
        $tasksByPriority = [
            'low' => $tasks->where('priority', 'low')->count(),
            'medium' => $tasks->where('priority', 'medium')->count(),
            'high' => $tasks->where('priority', 'high')->count(),
            'urgent' => $tasks->where('priority', 'urgent')->count(),
        ];

        // Rendimiento temporal (últimos 30 días)
        $performanceByDay = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayTasks = $tasks->filter(function ($task) use ($date) {
                return $task->completed_at && $task->completed_at->format('Y-m-d') === $date;
            });

            $completedToday = $dayTasks->where('status', 'completed');

            $performanceByDay[] = [
                'date' => $date,
                'completed' => $completedToday->count(),
                'on_time' => $completedToday->where('sla_breached', false)->count(),
                'late' => $completedToday->where('sla_breached', true)->count(),
                'created' => $tasks->filter(function ($task) use ($date) {
                    return $task->created_at->format('Y-m-d') === $date;
                })->count(),
            ];
        }

        // Tasa de cumplimiento de SLA
        $totalCompleted = $completedTasks->count();
        $slaComplianceRate = $totalCompleted > 0
            ? round(($completedOnTime / $totalCompleted) * 100, 2)
            : 0;

        // Asegurar que avg_progress siempre devuelva un número válido
        $avgProgressAnalytics = $tasks->count() > 0 ? round($tasks->avg('progress'), 2) : 0;

        return [
            'summary' => [
                'total_tasks' => $tasks->count() ?? 0,
                'completed_tasks' => $completedTasks->count() ?? 0,
                'total_completed' => $completedTasks->count() ?? 0, // Alias para compatibilidad frontend
                'completed_on_time' => $completedOnTime ?? 0,
                'completed_late' => $completedLate ?? 0,
                'active_with_sla_breach' => $activeWithSLABreach ?? 0,
                'avg_days_overdue' => round($avgDaysOverdue ?? 0, 1),
                'avg_progress' => $avgProgressAnalytics ?? 0,
                'sla_compliance_rate' => $slaComplianceRate ?? 0,
            ],
            'tasks_by_status' => $tasksByStatus,
            'tasks_by_priority' => $tasksByPriority,
            'performance_by_day' => $performanceByDay ?? [],
            'milestones' => [
                'total' => $tasks->where('is_milestone', true)->count() ?? 0,
                'completed' => $tasks->where('is_milestone', true)->where('status', 'completed')->count() ?? 0,
            ],
        ];
    }

    /**
     * Formatear tareas para exportación
     */
    public function formatForExport(Collection $tasks): array
    {
        return $tasks->map(function ($task) {
            return [
                'ID' => $task->id,
                'Título' => $task->title,
                'Descripción' => $task->description ?? '-',
                'Estado' => $this->translateStatus($task->status),
                'Prioridad' => $this->translatePriority($task->priority),
                'Asignado' => $task->assignee?->name ?? 'Sin asignar',
                'Flujo' => $task->flow?->name ?? '-',
                'Progreso' => $task->progress . '%',
                'Es Milestone' => $task->is_milestone ? 'Sí' : 'No',
                'Bloqueada' => $task->is_blocked ? 'Sí' : 'No',
                'Fecha Inicio Est.' => $task->estimated_start_at ?? '-',
                'Fecha Fin Est.' => $task->estimated_end_at ?? '-',
                'Creada' => $task->created_at->format('Y-m-d H:i'),
                'Actualizada' => $task->updated_at->format('Y-m-d H:i'),
            ];
        })->toArray();
    }

    /**
     * Traducir estado al español
     */
    private function translateStatus(string $status): string
    {
        $translations = [
            'pending' => 'Pendiente',
            'in_progress' => 'En Progreso',
            'completed' => 'Completada',
            'paused' => 'Pausada',
            'cancelled' => 'Cancelada',
            'blocked' => 'Bloqueada',
        ];

        return $translations[$status] ?? $status;
    }

    /**
     * Traducir prioridad al español
     */
    private function translatePriority(string $priority): string
    {
        $translations = [
            'low' => 'Baja',
            'medium' => 'Media',
            'high' => 'Alta',
            'urgent' => 'Urgente',
        ];

        return $translations[$priority] ?? $priority;
    }
}
