<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\Notification;
use Carbon\Carbon;

class CheckOverdueTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:check-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica tareas vencidas y genera notificaciones';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando verificación de tareas vencidas...');

        // Buscar tareas que:
        // 1. No están completadas ni canceladas
        // 2. Tienen fecha estimada de fin definida
        // 3. Su fecha estimada expiró (es menor a hoy)
        // 4. No son milestones (opcional, pero los milestones suelen tener otra lógica)
        
        $now = Carbon::now();
        
        $overdueTasks = Task::whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('estimated_end_at')
            ->where('estimated_end_at', '<', $now)
            ->whereNotNull('assignee_id') // Solo notificar si hay responsable
            ->get();

        $count = 0;

        foreach ($overdueTasks as $task) {
            // Verificar si ya notificamos HOY sobre esta tarea para evitar spam
            // Buscamos una notificación del tipo 'task_overdue' para esta tarea creada en las últimas 24h
            $alreadyNotified = Notification::where('task_id', $task->id)
                ->where('type', 'task_overdue')
                ->where('created_at', '>=', Carbon::today())
                ->exists();

            if (!$alreadyNotified) {
                // Generar notificación
                Notification::create([
                    'user_id' => $task->assignee_id,
                    'task_id' => $task->id,
                    'flow_id' => $task->flow_id,
                    'type' => 'task_overdue',
                    'title' => 'Tarea Vencida',
                    'message' => "La tarea '{$task->title}' venció el " . Carbon::parse($task->estimated_end_at)->format('d/m/Y H:i'),
                    'priority' => 'urgent', // Alta prioridad
                    'is_read' => false
                ]);

                $this->info("Notificación enviada para tarea ID {$task->id}: {$task->title}");
                $count++;
            }
        }

        $this->info("Proceso finalizado. {$count} notificaciones generadas.");
    }
}
