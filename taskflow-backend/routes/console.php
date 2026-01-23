<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar verificación de SLA de tareas
// Ejecuta cada hora para verificar tareas vencidas (+1 día warning, +2 días escalación)
Schedule::command('sla:check --details')
    ->hourly()
    ->withoutOverlapping() // Evita ejecuciones simultáneas
    ->name('sla-alerts-check') // Nombre identificador
    ->runInBackground(); // Ejecuta en segundo plano
