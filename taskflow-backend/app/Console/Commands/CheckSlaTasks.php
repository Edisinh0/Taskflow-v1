<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SlaNotificationService;

class CheckSlaTasks extends Command
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
    protected $description = 'Verificar tareas con SLA vencido y enviar notificaciones/escalamientos automáticos';

    /**
     * Execute the console command.
     */
    public function handle(SlaNotificationService $slaService)
    {
        $this->info('Iniciando verificación de SLA...');

        try {
            $stats = $slaService->processOverdueTasks();

            $this->info('Proceso completado exitosamente:');
            $this->line("  - Tareas verificadas: {$stats['checked']}");
            $this->line("  - Notificaciones enviadas: {$stats['notified']}");
            $this->line("  - Escalamientos realizados: {$stats['escalated']}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error al verificar SLA: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
