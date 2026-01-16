<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\Notification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckSlaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar SLA de tareas y enviar notificaciones por vencimientos y advertencias';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando verificación de SLA de tareas...');
        $this->newLine();

        $now = Carbon::now();
        $warningsCount = 0;
        $overdueCount = 0;

        // Buscar tareas con status 'pending' o 'in_progress' que tengan estimated_end_at definido
        $tasksToCheck = Task::whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('estimated_end_at')
            ->whereNotNull('assignee_id') // Solo tareas con responsable asignado
            ->with(['assignee', 'flow'])
            ->get();

        $this->info("📋 Tareas a verificar: {$tasksToCheck->count()}");
        $this->newLine();

        foreach ($tasksToCheck as $task) {
            $estimatedEnd = Carbon::parse($task->estimated_end_at);
            $hoursUntilDeadline = $now->diffInHours($estimatedEnd, false);

            // Si hoursUntilDeadline es negativo, la fecha ya pasó
            if ($hoursUntilDeadline < 0) {
                // Tarea vencida
                if ($this->createOverdueNotification($task, abs($hoursUntilDeadline))) {
                    $overdueCount++;
                    $this->warn("  🚨 Tarea vencida: [{$task->id}] {$task->title} (hace " . round(abs($hoursUntilDeadline)) . " horas)");
                }
            } elseif ($hoursUntilDeadline <= 24 && $hoursUntilDeadline > 0) {
                // Tarea próxima a vencer (dentro de 24 horas)
                if ($this->createWarningNotification($task, $hoursUntilDeadline)) {
                    $warningsCount++;
                    $this->info("  ⚠️  Advertencia: [{$task->id}] {$task->title} (vence en " . round($hoursUntilDeadline) . " horas)");
                }
            }
        }

        $this->newLine();
        $this->info('✅ Verificación completada:');
        $this->line("   - Tareas verificadas: {$tasksToCheck->count()}");
        $this->line("   - Advertencias enviadas: {$warningsCount}");
        $this->line("   - Notificaciones de vencimiento: {$overdueCount}");

        return Command::SUCCESS;
    }

    /**
     * Crear notificación de advertencia (tarea próxima a vencer)
     *
     * @param Task $task
     * @param float $hoursRemaining
     * @return bool True si se creó la notificación, false si ya existía
     */
    private function createWarningNotification(Task $task, float $hoursRemaining): bool
    {
        // Evitar notificaciones duplicadas verificando si ya existe una notificación
        // del mismo tipo para esta tarea en las últimas 24 horas
        $exists = Notification::where('task_id', $task->id)
            ->where('type', 'sla_warning')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->exists();

        if ($exists) {
            return false;
        }

        Notification::create([
            'user_id' => $task->assignee_id,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'sla_warning',
            'title' => '⚠️ Tarea próxima a vencer',
            'message' => "La tarea '{$task->title}' vence en " . round($hoursRemaining) . " horas",
            'priority' => 'high',
            'data' => [
                'hours_remaining' => round($hoursRemaining, 1),
                'deadline' => $task->estimated_end_at,
            ],
            'is_read' => false,
        ]);

        return true;
    }

    /**
     * Crear notificación de tarea vencida y marcar sla_breached
     *
     * @param Task $task
     * @param float $hoursOverdue
     * @return bool True si se creó la notificación, false si ya existía
     */
    private function createOverdueNotification(Task $task, float $hoursOverdue): bool
    {
        // Evitar notificaciones duplicadas verificando si ya existe una notificación
        // del mismo tipo para esta tarea en las últimas 24 horas
        $exists = Notification::where('task_id', $task->id)
            ->where('type', 'task_overdue')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->exists();

        if ($exists) {
            return false;
        }

        // Marcar la tarea como sla_breached = true
        $task->update([
            'sla_breached' => true,
        ]);

        Notification::create([
            'user_id' => $task->assignee_id,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'task_overdue',
            'title' => '🚨 Tarea vencida',
            'message' => "La tarea '{$task->title}' está vencida hace " . round($hoursOverdue) . " horas",
            'priority' => 'urgent',
            'data' => [
                'hours_overdue' => round($hoursOverdue, 1),
                'deadline' => $task->estimated_end_at,
            ],
            'is_read' => false,
        ]);

        return true;
    }
}
