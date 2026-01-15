<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\Notification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckSlaCommand extends Command
{
    protected $signature = 'sla:check';
    protected $description = 'Verificar SLA de tareas y enviar notificaciones';

    public function handle()
    {
        $this->info('🔍 Verificando SLA de tareas...');

        $tasksToCheck = Task::whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('estimated_end_at')
            ->with(['assignee', 'flow'])
            ->get();

        $warnings = 0;
        $criticals = 0;

        foreach ($tasksToCheck as $task) {
            $hoursUntilDeadline = Carbon::parse($task->estimated_end_at)
                ->diffInHours(now(), false);

            // Si es negativo, ya pasó la fecha
            if ($hoursUntilDeadline < 0) {
                $this->createOverdueNotification($task, abs($hoursUntilDeadline));
                $criticals++;
            } 
            // Alerta 24 horas antes
            elseif ($hoursUntilDeadline <= 24 && $hoursUntilDeadline > 0) {
                $this->createWarningNotification($task, $hoursUntilDeadline);
                $warnings++;
            }
        }

        $this->info("✅ Verificación completada:");
        $this->info("   - {$warnings} advertencias enviadas");
        $this->info("   - {$criticals} tareas vencidas detectadas");

        return 0;
    }

    private function createWarningNotification($task, $hours)
    {
        // Evitar duplicados
        $exists = Notification::where('task_id', $task->id)
            ->where('type', 'sla_warning')
            ->where('created_at', '>', now()->subHours(12))
            ->exists();

        if ($exists) return;

        Notification::create([
            'user_id' => $task->assignee_id,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'sla_warning',
            'title' => '⚠️ Tarea próxima a vencer',
            'message' => "La tarea '{$task->title}' vence en " . round($hours) . " horas",
            'priority' => 'high',
            'data' => [
                'hours_remaining' => round($hours, 1),
                'deadline' => $task->estimated_end_at,
            ],
        ]);
    }

    private function createOverdueNotification($task, $hours)
    {
        // Evitar duplicados
        $exists = Notification::where('task_id', $task->id)
            ->where('type', 'task_overdue')
            ->where('created_at', '>', now()->subDay())
            ->exists();

        if ($exists) return;

        Notification::create([
            'user_id' => $task->assignee_id,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'task_overdue',
            'title' => '🚨 Tarea vencida',
            'message' => "La tarea '{$task->title}' está vencida hace " . round($hours) . " horas",
            'priority' => 'urgent',
            'data' => [
                'hours_overdue' => round($hours, 1),
                'deadline' => $task->estimated_end_at,
            ],
        ]);
    }
}