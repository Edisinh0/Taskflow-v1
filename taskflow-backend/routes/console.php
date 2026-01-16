<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar verificación de SLA de tareas
// Ejecuta cada hora para verificar tareas vencidas y próximas a vencer
Schedule::command('sla:check')
    ->hourly()
    ->withoutOverlapping() // Evita ejecuciones simultáneas
    ->runInBackground(); // Ejecuta en segundo plano
