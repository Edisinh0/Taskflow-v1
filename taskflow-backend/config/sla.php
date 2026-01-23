<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SLA System Enabled
    |--------------------------------------------------------------------------
    |
    | Habilitar o deshabilitar el sistema completo de alertas SLA
    |
    */

    'enabled' => env('SLA_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | SLA Thresholds
    |--------------------------------------------------------------------------
    |
    | Umbrales de tiempo para generar alertas SLA
    | - warning_hours: Horas después del vencimiento para enviar advertencia
    | - escalation_hours: Horas después del vencimiento para escalar al supervisor
    |
    */

    'thresholds' => [
        'warning_hours' => (int) env('SLA_WARNING_HOURS', 24),      // +1 día = 24 horas
        'escalation_hours' => (int) env('SLA_ESCALATION_HOURS', 48), // +2 días = 48 horas
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Canales habilitados para enviar notificaciones SLA
    |
    */

    'channels' => [
        'in_app' => env('SLA_NOTIFY_IN_APP', true),      // Notificaciones en la aplicación
        'email' => env('SLA_NOTIFY_EMAIL', true),         // Enviar emails
        'slack' => env('SLA_NOTIFY_SLACK', false),        // Integración con Slack (futuro)
        'webhook' => env('SLA_NOTIFY_WEBHOOK', false),    // Webhooks personalizados (futuro)
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración específica para emails de escalación SLA
    |
    */

    'email' => [
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@taskflow.com'),
            'name' => env('MAIL_FROM_NAME', 'Taskflow Alerts'),
        ],
        'cc_assignee' => env('SLA_EMAIL_CC_ASSIGNEE', true), // CC al asignado en escalaciones
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduler Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración del comando programado
    |
    */

    'scheduler' => [
        'frequency' => env('SLA_CHECK_FREQUENCY', 'hourly'), // hourly, everyFifteenMinutes, daily
        'verbose' => env('SLA_CHECK_VERBOSE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Duplicate Prevention
    |--------------------------------------------------------------------------
    |
    | Tiempo en minutos para prevenir notificaciones duplicadas del mismo tipo
    |
    */

    'duplicate_prevention_minutes' => (int) env('SLA_DUPLICATE_PREVENTION_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Auto-Resolution
    |--------------------------------------------------------------------------
    |
    | Resolver automáticamente alertas cuando la tarea se completa
    |
    */

    'auto_resolve' => env('SLA_AUTO_RESOLVE', true),

];
