<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Task extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'title',
        'description',
        'flow_id',
        'parent_task_id',
        'assignee_id',
        'priority',
        'status',
        'is_milestone',
        'allow_attachments', // <-- AGREGAR
        'is_blocked',           // <-- AGREGAR
        'depends_on_task_id',   // <-- AGREGAR
        'depends_on_milestone_id',
        'milestone_auto_complete',
        'milestone_requires_validation',
        'milestone_validated_by',
        'milestone_target_date',
        'order',
        'estimated_start_at',
        'estimated_end_at',
        'actual_start_at',
        'actual_end_at',
        'progress',
        'blocked_reason',
        'notes', // <-- Nuevo campo para notas
        'last_updated_by', // Nuevo
        // SLA fields
        'sla_due_date',
        'sla_breached',
        'sla_breach_at',
        'sla_days_overdue',
        'sla_notified_assignee',
        'sla_escalated',
        'sla_notified_at',
        'sla_escalated_at',
    ];

    protected $casts = [
        'is_milestone' => 'boolean',
        'allow_attachments' => 'boolean',
        'milestone_auto_complete' => 'boolean',
        'milestone_requires_validation' => 'boolean',
        'milestone_target_date' => 'datetime',
        'estimated_start_at' => 'datetime',
        'estimated_end_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_end_at' => 'datetime',
        'progress' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_blocked' => 'boolean',
        // SLA casts
        'sla_due_date' => 'datetime',
        'sla_breached' => 'boolean',
        'sla_breach_at' => 'datetime',
        'sla_days_overdue' => 'integer',
        'sla_notified_assignee' => 'boolean',
        'sla_escalated' => 'boolean',
        'sla_notified_at' => 'datetime',
        'sla_escalated_at' => 'datetime',
    ];

    /**
     * Relación: Una tarea pertenece a un flujo
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    /**
     * Relación: Una tarea puede tener una tarea padre
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Relación: Una tarea puede tener muchas subtareas
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * Relación: Una tarea tiene un responsable (assignee)
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Relación: Usuario que validó el milestone
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'milestone_validated_by');
    }

    /**
     * Relación: Dependencias - Tareas de las que esta depende
     */
    public function dependencies(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'task_id');
    }

    /**
     * Relación: Tareas que dependen de esta
     */
    public function dependents(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'depends_on_task_id');
    }

    /**
     * Verificar si la tarea está bloqueada por alguna dependencia
     */
    public function checkIsBlocked(): bool
    {
        // Si ya está completada, no está bloqueada
        if ($this->status === 'completed') {
            return false;
        }

        // 1. Verificar dependencias directas (columnas en tabla tasks)
        if ($this->depends_on_task_id) {
            $precedentTask = self::find($this->depends_on_task_id);
            if ($precedentTask && $precedentTask->status !== 'completed') {
                return true;
            }
        }

        if ($this->depends_on_milestone_id) {
            $milestone = self::find($this->depends_on_milestone_id);
            if ($milestone && $milestone->status !== 'completed') {
                return true;
            }
        }

        // 2. Verificar dependencias en tabla pivot (si existen)
        foreach ($this->dependencies as $dependency) {
            $dependsOnTask = $dependency->dependsOnTask; 
            if ($dependsOnTask && $dependsOnTask->status !== 'completed') {
                return true;
            }
        }

        return false;
    }

    /**
     * Calcular el progreso basado en subtareas
     */
    public function calculateProgress(): int
    {
        $subtasks = $this->subtasks;
        
        if ($subtasks->isEmpty()) {
            return $this->progress;
        }

        $totalProgress = $subtasks->sum('progress');
        $count = $subtasks->count();

        return $count > 0 ? (int) ($totalProgress / $count) : 0;
    }

    /**
     * Relación: Tarea de la que esta tarea depende (dependencia de flujo)
     */
    public function dependsOnTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'depends_on_task_id');
    }

    /**
     * Relación: Milestone del que esta tarea depende
     */
    public function dependsOnMilestone(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'depends_on_milestone_id');
    }

    /**
     * Relación inversa: Tareas que dependen de esta tarea
     */
    public function dependentTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'depends_on_task_id');
    }

    /**
     * Relación inversa: Tareas que dependen de este milestone
     */
    public function dependentOnMilestone(): HasMany
    {
        return $this->hasMany(Task::class, 'depends_on_milestone_id');
    }

    /**
     * Relación: Archivos adjuntos
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * Verificar y actualizar el estado del SLA
     */
    public function checkSlaStatus(): void
    {
        // Solo verificar si no está completada o cancelada
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return;
        }

        // Si no hay fecha de SLA definida, usar estimated_end_at
        if (!$this->sla_due_date && $this->estimated_end_at) {
            $this->sla_due_date = $this->estimated_end_at;
            $this->save();
        }

        // Si no hay fecha de SLA, no hacer nada
        if (!$this->sla_due_date) {
            return;
        }

        $now = now();
        $dueDate = $this->sla_due_date;

        // Verificar si se ha superado el SLA
        if ($now->isAfter($dueDate)) {
            // Calcular días de retraso (positivo)
            $daysOverdue = (int) $dueDate->diffInDays($now);

            if (!$this->sla_breached) {
                $this->sla_breached = true;
                $this->sla_breach_at = $now;
            }

            $this->sla_days_overdue = $daysOverdue;
            $this->save();
        }
    }

    /**
     * Verificar si la tarea está retrasada
     */
    public function isOverdue(): bool
    {
        if (!$this->sla_due_date) {
            return false;
        }

        return now()->isAfter($this->sla_due_date) &&
               !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Recalcular estado actual de SLA
     * Retorna: 'none', 'warning', 'critical'
     */
    public function recalculateSLAStatus(): string
    {
        // Si está completada o cancelada, no hay alerta
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return 'none';
        }

        // Si no hay fecha de SLA, no hay alerta
        if (!$this->sla_due_date) {
            return 'none';
        }

        $now = now();
        $dueDate = \Carbon\Carbon::parse($this->sla_due_date);

        // Si la fecha límite es futura, no hay alerta
        if ($now->isBefore($dueDate)) {
            return 'none';
        }

        // Calcular horas de retraso
        $hoursOverdue = $now->diffInHours($dueDate, false);

        // Critical: 48+ horas de retraso
        if ($hoursOverdue >= 48) {
            return 'critical';
        }

        // Warning: 24+ horas de retraso
        if ($hoursOverdue >= 24) {
            return 'warning';
        }

        return 'none';
    }

    /**
     * Obtener el supervisor/PM del flujo para escalamiento
     */
    public function getSupervisor()
    {
        // Primero intentar obtener el responsable del flujo
        if ($this->flow && $this->flow->responsible_id) {
            return User::find($this->flow->responsible_id);
        }

        // Si no hay responsable, obtener el creador del flujo
        if ($this->flow && $this->flow->created_by) {
            return User::find($this->flow->created_by);
        }

        // Como última opción, buscar un admin o project_manager
        return User::where('role', 'admin')
            ->orWhere('role', 'project_manager')
            ->first();
    }

    /**
     * Verificar el estado de alerta SLA
     * Retorna: 'none' | 'warning' | 'escalation'
     */
    public function getSLAStatus(): string
    {
        if ($this->status === 'completed' || $this->status === 'cancelled') {
            return 'none';
        }

        if (!$this->sla_due_date) {
            return 'none';
        }

        $hoursOverdue = now()->diffInHours($this->sla_due_date, false);

        // Si es negativo, aún no ha vencido
        if ($hoursOverdue < 0) {
            return 'none';
        }

        if ($hoursOverdue >= 48) {
            return 'escalation';
        }

        if ($hoursOverdue >= 24) {
            return 'warning';
        }

        return 'none';
    }

    /**
     * Calcular días de atraso (para notificaciones)
     */
    public function getDaysOverdue(): int
    {
        if (!$this->sla_due_date) {
            return 0;
        }

        $daysOverdue = now()->diffInDays($this->sla_due_date, false);

        // Si es negativo, aún no ha vencido
        return max(0, (int) $daysOverdue);
    }

    /**
     * Obtener PM/Supervisor responsable (alias de getSupervisor)
     */
    public function getResponsible(): ?User
    {
        return $this->getSupervisor();
    }

    /**
     * Scope para tareas con SLA vencido
     */
    public function scopeSlaBreach($query)
    {
        return $query->where('sla_breached', true)
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope para tareas que necesitan notificación (+1 día)
     */
    public function scopeNeedsAssigneeNotification($query)
    {
        return $query->where('sla_breached', true)
                    ->where('sla_days_overdue', '>=', 1)
                    ->where('sla_notified_assignee', false)
                    ->whereNotNull('assignee_id')
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope para tareas que necesitan escalamiento (+2 días)
     */
    public function scopeNeedsEscalation($query)
    {
        return $query->where('sla_breached', true)
                    ->where('sla_days_overdue', '>=', 2)
                    ->where('sla_escalated', false)
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }
}