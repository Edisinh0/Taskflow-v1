<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\SlaNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSLAAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:check
                            {--details : Mostrar detalles por tarea}
                            {--task-id= : Verificar solo una tarea específica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar tareas con SLA vencido y disparar alertas automáticas (+1 día warning, +2 días escalación)';

    /**
     * SLA Notification Service
     */
    protected SlaNotificationService $slaService;

    /**
     * Constructor
     */
    public function __construct(SlaNotificationService $slaService)
    {
        parent::__construct();
        $this->slaService = $slaService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando verificación de alertas SLA...');
        $this->newLine();

        // Verificar si se especificó una tarea
        $taskId = $this->option('task-id');

        if ($taskId) {
            return $this->checkSingleTask($taskId);
        }

        return $this->checkAllTasks();
    }

    /**
     * Verificar una sola tarea
     */
    private function checkSingleTask(int $taskId): int
    {
        $task = Task::find($taskId);

        if (!$task) {
            $this->error("❌ Tarea #{$taskId} no encontrada");
            return Command::FAILURE;
        }

        $this->info("📋 Verificando tarea #{$taskId}: {$task->title}");
        $this->newLine();

        // Mostrar información de la tarea
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Status', $task->status],
                ['Assignee', $task->assignee?->name ?? 'Sin asignar'],
                ['SLA Due Date', $task->sla_due_date ?? 'No definido'],
                ['SLA Breached', $task->sla_breached ? 'Sí' : 'No'],
                ['Days Overdue', $task->sla_days_overdue ?? 0],
                ['Notified', $task->sla_notified_assignee ? 'Sí' : 'No'],
                ['Escalated', $task->sla_escalated ? 'Sí' : 'No'],
            ]
        );

        $this->newLine();

        // Verificar la tarea
        $alertType = $this->slaService->checkTask($task);

        if ($alertType) {
            $this->info("✅ Alerta generada: " . strtoupper($alertType));
        } else {
            $this->info("✅ No se requieren alertas para esta tarea");
        }

        return Command::SUCCESS;
    }

    /**
     * Verificar todas las tareas
     */
    private function checkAllTasks(): int
    {
        $details = $this->option('details');

        // Ejecutar verificación completa
        $stats = $this->slaService->checkAllTasks();

        if ($details) {
            $this->showDetailedResults();
        }

        // Mostrar resumen
        $this->newLine();
        $this->info('✅ Verificación de SLA completada:');
        $this->newLine();

        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Tareas verificadas', $stats['checked']],
                ['Alertas de advertencia (+1 día)', $stats['warnings_count']],
                ['Escalaciones críticas (+2 días)', $stats['escalations_count']],
                ['Total de alertas procesadas', $stats['processed_count']],
            ]
        );

        // Log para historial
        Log::info('Comando sla:check ejecutado', $stats);

        return Command::SUCCESS;
    }

    /**
     * Mostrar resultados detallados
     */
    private function showDetailedResults(): void
    {
        $this->newLine();
        $this->info('📊 Detalles de tareas con alertas SLA:');
        $this->newLine();

        // Tareas que necesitan notificación (+1 día)
        $tasksToNotify = Task::needsAssigneeNotification()->with(['assignee', 'flow'])->get();

        if ($tasksToNotify->count() > 0) {
            $this->warn("⚠️  Tareas con advertencia (+1 día):");
            $this->newLine();

            $rows = $tasksToNotify->map(function ($task) {
                return [
                    $task->id,
                    $task->title,
                    $task->assignee?->name ?? 'Sin asignar',
                    $task->flow?->name ?? 'Sin flujo',
                    $task->sla_days_overdue . ' días',
                    $task->sla_due_date?->format('Y-m-d H:i'),
                ];
            });

            $this->table(
                ['ID', 'Título', 'Asignado', 'Flujo', 'Atraso', 'Vencimiento'],
                $rows
            );
        }

        // Tareas que necesitan escalación (+2 días)
        $tasksToEscalate = Task::needsEscalation()->with(['assignee', 'flow'])->get();

        if ($tasksToEscalate->count() > 0) {
            $this->newLine();
            $this->error("🚨 Tareas críticas (+2 días) - ESCALADAS:");
            $this->newLine();

            $rows = $tasksToEscalate->map(function ($task) {
                return [
                    $task->id,
                    $task->title,
                    $task->assignee?->name ?? 'Sin asignar',
                    $task->flow?->name ?? 'Sin flujo',
                    $task->sla_days_overdue . ' días',
                    $task->sla_due_date?->format('Y-m-d H:i'),
                ];
            });

            $this->table(
                ['ID', 'Título', 'Asignado', 'Flujo', 'Atraso', 'Vencimiento'],
                $rows
            );
        }

        if ($tasksToNotify->count() === 0 && $tasksToEscalate->count() === 0) {
            $this->info('✅ No hay tareas con alertas SLA pendientes');
        }
    }
}
