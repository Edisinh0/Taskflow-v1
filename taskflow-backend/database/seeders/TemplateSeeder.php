<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;
use App\Models\User;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@taskflow.com')->first();

        // Plantilla 1: Instalación 3CX
        Template::create([
            'name' => 'Instalación 3CX',
            'description' => 'Plantilla estándar para instalación de central telefónica 3CX',
            'version' => '1.0',
            'is_active' => true,
            'created_by' => $admin->id,
            'config' => [
                'estimated_duration_days' => 5,
                'required_roles' => ['Técnico', 'Project Manager'],
                'priority' => 'high',
                'tasks' => [
                    [
                        'title' => 'Fase 1: Preparación',
                        'is_milestone' => true,
                        'start_day_offset' => 0,
                        'subtasks' => [
                            ['title' => 'Relevamiento de requisitos', 'duration_days' => 1, 'priority' => 'high'],
                            ['title' => 'Adquisición de Licencia 3CX', 'duration_days' => 1, 'priority' => 'urgent'],
                            ['title' => 'Validación de requisitos de red', 'duration_days' => 1, 'priority' => 'high']
                        ]
                    ],
                    [
                        'title' => 'Fase 2: Instalación y Configuración',
                        'is_milestone' => true,
                        'start_day_offset' => 2,
                        'subtasks' => [
                            ['title' => 'Despliegue de Servidor (Cloud/On-Premise)', 'duration_days' => 1, 'priority' => 'high'],
                            ['title' => 'Configuración de Reglas de Entrada/Salida', 'duration_days' => 1],
                            ['title' => 'Creación de Extensiones', 'duration_days' => 1],
                            ['title' => 'Configuración de Troncal SIP', 'duration_days' => 1, 'priority' => 'high']
                        ]
                    ],
                    [
                        'title' => 'Fase 3: Pruebas y Cierre',
                        'is_milestone' => true,
                        'start_day_offset' => 4,
                        'subtasks' => [
                            ['title' => 'Pruebas de llamadas internas/externas', 'duration_days' => 1, 'priority' => 'medium'],
                            ['title' => 'Capacitación al cliente', 'duration_days' => 1],
                            ['title' => 'Entrega de documentación', 'duration_days' => 0]
                        ]
                    ]
                ]
            ]
        ]);

        // Plantilla 2: Soporte Técnico
        Template::create([
            'name' => 'Soporte Técnico',
            'description' => 'Flujo estándar para atención de tickets de soporte',
            'version' => '1.2',
            'is_active' => true,
            'created_by' => $admin->id,
            'config' => [
                'estimated_duration_days' => 2,
                'required_roles' => ['Soporte'],
                'priority' => 'medium',
                'tasks' => [
                    [
                        'title' => 'Diagnóstico Inicial',
                        'is_milestone' => true,
                        'start_day_offset' => 0,
                        'subtasks' => [
                            ['title' => 'Revisión de logs', 'duration_days' => 1, 'priority' => 'high'],
                            ['title' => 'Reproducción del error', 'duration_days' => 1]
                        ]
                    ],
                    [
                        'title' => 'Resolución',
                        'is_milestone' => true,
                        'start_day_offset' => 1,
                        'subtasks' => [
                            ['title' => 'Aplicación de parche/fix', 'duration_days' => 1, 'priority' => 'high'],
                            ['title' => 'Validación con usuario', 'duration_days' => 1]
                        ]
                    ]
                ]
            ]
        ]);

        // Plantilla 3: Alta de Cliente
        Template::create([
            'name' => 'Alta de Cliente',
            'description' => 'Proceso de onboarding para nuevos clientes',
            'version' => '1.0',
            'is_active' => true,
            'created_by' => $admin->id,
            'config' => [
                'estimated_duration_days' => 3,
                'required_roles' => ['Ventas', 'Administración'],
                'priority' => 'high',
                'tasks' => [
                    [
                        'title' => 'Documentación Legal',
                        'is_milestone' => true,
                        'subtasks' => [
                            ['title' => 'Firma de Contrato', 'priority' => 'urgent'],
                            ['title' => 'Alta en sistema de facturación']
                        ]
                    ],
                    [
                        'title' => 'Onboarding',
                        'is_milestone' => true,
                        'subtasks' => [
                            ['title' => 'Reunión de bienvenida'],
                            ['title' => 'Entrega de credenciales']
                        ]
                    ]
                ]
            ]
        ]);

        echo "✅ Plantillas creadas exitosamente\n";
    }
}