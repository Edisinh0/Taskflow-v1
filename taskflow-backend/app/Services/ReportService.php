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

        return [
            'total' => $tasks->count(),
            'by_status' => [
                'pending' => $tasks->where('status', 'pending')->count(),
                'in_progress' => $tasks->where('status', 'in_progress')->count(),
                'completed' => $tasks->where('status', 'completed')->count(),
                'paused' => $tasks->where('status', 'paused')->count(),
                'cancelled' => $tasks->where('status', 'cancelled')->count(),
            ],
            'by_priority' => [
                'low' => $tasks->where('priority', 'low')->count(),
                'medium' => $tasks->where('priority', 'medium')->count(),
                'high' => $tasks->where('priority', 'high')->count(),
                'urgent' => $tasks->where('priority', 'urgent')->count(),
            ],
            'avg_progress' => round($tasks->avg('progress'), 2),
            'milestones' => $tasks->where('is_milestone', true)->count(),
            'blocked' => $tasks->where('is_blocked', true)->count(),
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
