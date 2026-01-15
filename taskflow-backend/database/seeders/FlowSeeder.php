<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Flow;
use App\Models\Task;
use App\Models\Template;
use App\Models\User;

class FlowSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@taskflow.com')->first();
        $pm = User::where('email', 'juan.perez@taskflow.com')->first();
        $tecnico1 = User::where('email', 'maria.gonzalez@taskflow.com')->first();
        $tecnico2 = User::where('email', 'carlos.rodriguez@taskflow.com')->first();

        $template = Template::where('name', 'Instalación 3CX')->first();

        // Crear un flujo de ejemplo
        $flow = Flow::create([
            'name' => 'Instalación 3CX - Cliente ABC Corp',
            'description' => 'Implementación de central telefónica para ABC Corp',
            'template_id' => $template->id,
            'created_by' => $admin->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Milestone 1: Contrato firmado
        $milestone1 = Task::create([
            'title' => 'Contrato firmado',
            'description' => 'Validar que el contrato esté firmado por ambas partes',
            'flow_id' => $flow->id,
            'assignee_id' => $pm->id,
            'priority' => 'high',
            'status' => 'completed',
            'is_milestone' => true,
            'milestone_requires_validation' => true,
            'order' => 1,
            'progress' => 100,
            'actual_start_at' => now()->subDays(5),
            'actual_end_at' => now()->subDays(4),
        ]);

        // Tareas del milestone 1
        Task::create([
            'title' => 'Crear cotización',
            'description' => 'Generar cotización detallada del proyecto',
            'flow_id' => $flow->id,
            'parent_task_id' => $milestone1->id,
            'assignee_id' => $pm->id,
            'priority' => 'high',
            'status' => 'completed',
            'order' => 1,
            'progress' => 100,
        ]);

        Task::create([
            'title' => 'Enviar documento a cliente',
            'description' => 'Enviar cotización y contrato por email',
            'flow_id' => $flow->id,
            'parent_task_id' => $milestone1->id,
            'assignee_id' => $pm->id,
            'priority' => 'high',
            'status' => 'completed',
            'order' => 2,
            'progress' => 100,
        ]);

        // Milestone 2: Instalación técnica
        $milestone2 = Task::create([
            'title' => 'Instalación técnica completada',
            'description' => 'Configuración completa del servidor y clientes',
            'flow_id' => $flow->id,
            'assignee_id' => $tecnico1->id,
            'priority' => 'urgent',
            'status' => 'in_progress',
            'is_milestone' => true,
            'order' => 2,
            'progress' => 60,
            'actual_start_at' => now()->subDays(3),
        ]);

        // Tareas del milestone 2
        Task::create([
            'title' => 'Configurar servidor 3CX',
            'description' => 'Instalación y configuración base del servidor',
            'flow_id' => $flow->id,
            'parent_task_id' => $milestone2->id,
            'assignee_id' => $tecnico1->id,
            'priority' => 'urgent',
            'status' => 'completed',
            'order' => 1,
            'progress' => 100,
        ]);

        Task::create([
            'title' => 'Instalar clientes Windows',
            'description' => 'Instalar softphone en equipos de usuarios',
            'flow_id' => $flow->id,
            'parent_task_id' => $milestone2->id,
            'assignee_id' => $tecnico2->id,
            'priority' => 'high',
            'status' => 'in_progress',
            'order' => 2,
            'progress' => 70,
        ]);

        Task::create([
            'title' => 'Probar llamadas internas',
            'description' => 'Verificar que las llamadas entre anexos funcionen',
            'flow_id' => $flow->id,
            'parent_task_id' => $milestone2->id,
            'assignee_id' => $tecnico1->id,
            'priority' => 'high',
            'status' => 'pending',
            'order' => 3,
            'progress' => 0,
        ]);

        Task::create([
            'title' => 'Configurar grabaciones',
            'description' => 'Activar y probar sistema de grabación de llamadas',
            'flow_id' => $flow->id,
            'parent_task_id' => $milestone2->id,
            'assignee_id' => $tecnico1->id,
            'priority' => 'medium',
            'status' => 'blocked',
            'blocked_reason' => 'Esperando que se completen las pruebas de llamadas internas',
            'order' => 4,
            'progress' => 0,
        ]);

        // Milestone 3: Capacitación y entrega
        $milestone3 = Task::create([
            'title' => 'Capacitación y entrega',
            'description' => 'Capacitar al cliente y cerrar el proyecto',
            'flow_id' => $flow->id,
            'assignee_id' => $pm->id,
            'priority' => 'high',
            'status' => 'pending',
            'is_milestone' => true,
            'order' => 3,
            'progress' => 0,
        ]);

        // Tareas del milestone 3
        Task::create([
            'title' => 'Agendar reunión de capacitación',
            'description' => 'Coordinar fecha y hora con el cliente',
            'flow_id' => $flow->id,
            'parent_task_id' => $milestone3->id,
            'assignee_id' => $pm->id,
            'priority' => 'medium',
            'status' => 'pending',
            'order' => 1,
            'progress' => 0,
        ]);

        Task::create([
            'title' => 'Enviar manual de usuario',
            'description' => 'Enviar documentación técnica al cliente',
            'flow_id' => $flow->id,
            'parent_task_id' => $milestone3->id,
            'assignee_id' => $pm->id,
            'priority' => 'low',
            'status' => 'pending',
            'order' => 2,
            'progress' => 0,
        ]);

        Task::create([
            'title' => 'Cerrar proyecto',
            'description' => 'Marcar proyecto como completado y archivar',
            'flow_id' => $flow->id,
            'parent_task_id' => $milestone3->id,
            'assignee_id' => $pm->id,
            'priority' => 'low',
            'status' => 'pending',
            'order' => 3,
            'progress' => 0,
        ]);

        echo "✅ Flujo y tareas creadas exitosamente\n";
    }
}