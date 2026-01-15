<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskCenterController;

/**
 * Rutas del módulo Task Center
 *
 * Estas rutas están protegidas por:
 * 1. Middleware de autenticación (sanctum)
 * 2. Laravel Policies (TaskPolicy)
 *
 * Accesibles por usuarios asignados a tareas
 */

Route::prefix('task-center')->middleware('auth:sanctum')->group(function () {

    // Ver tareas asignadas al usuario actual
    Route::get('/my-tasks', [TaskCenterController::class, 'myTasks']);

    // Ver detalle de una tarea
    Route::get('/tasks/{id}', [TaskCenterController::class, 'show']);

    // Ejecutar tarea (cambiar estado, actualizar progreso)
    Route::put('/tasks/{id}/execute', [TaskCenterController::class, 'executeTask']);
});
