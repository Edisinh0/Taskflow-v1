<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FlowBuilderController;

/**
 * Rutas del módulo Flow Builder
 *
 * Estas rutas están protegidas por:
 * 1. Middleware de autenticación (sanctum)
 * 2. Laravel Policies (FlowPolicy, TaskPolicy)
 *
 * Solo accesibles por roles: admin, project_manager, pm
 */

Route::prefix('flow-builder')->middleware('auth:sanctum')->group(function () {

    // === GESTIÓN DE FLUJOS ===
    Route::prefix('flows')->group(function () {
        Route::post('/', [FlowBuilderController::class, 'createFlow']);
        Route::put('/{id}', [FlowBuilderController::class, 'updateFlow']);
        Route::delete('/{id}', [FlowBuilderController::class, 'deleteFlow']);
    });

    // === GESTIÓN DE TAREAS (ESTRUCTURA) ===
    Route::prefix('tasks')->group(function () {
        Route::post('/', [FlowBuilderController::class, 'createTask']);
        Route::put('/{id}', [FlowBuilderController::class, 'updateTaskStructure']);
        Route::delete('/{id}', [FlowBuilderController::class, 'deleteTask']);

        // Configuración de dependencias
        Route::put('/{id}/dependencies', [FlowBuilderController::class, 'configureDependencies']);
    });
});
