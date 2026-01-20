<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\FlowController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskDependencyController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes - TaskFlow v1
|--------------------------------------------------------------------------
|
| Incluye módulos SRP:
| - Flow Builder: Diseño de flujos (PM/Admin)
| - Task Center: Ejecución de tareas (Users)
|
*/

// Ruta de bienvenida de la API
Route::get('/', function () {
    return response()->json([
        'name' => 'TaskFlow API',
        'version' => 'v1',
        'status' => 'active',
        'endpoints' => [
            'auth' => '/api/v1/auth/*',
            'users' => '/api/v1/users',
            'templates' => '/api/v1/templates',
            'flows' => '/api/v1/flows',
            'tasks' => '/api/v1/tasks',
            'notifications' => '/api/v1/notifications',
            'reports' => '/api/v1/reports',
        ],
        'documentation' => '/api/v1/docs',
    ]);
});

// Rutas públicas (sin autenticación)
Route::prefix('v1')->group(function () {
    // Autenticación
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
});

// Broadcasting authentication routes
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// DEBUG: Ruta temporal para ver qué genera Laravel en broadcasting auth
Route::post('v1/debug/broadcast-auth', function (\Illuminate\Http\Request $request) {
    $socketId = $request->input('socket_id');
    $channelName = $request->input('channel_name');

    $appKey = config('broadcasting.connections.reverb.key');
    $appSecret = config('broadcasting.connections.reverb.secret');
    $host = config('broadcasting.connections.reverb.options.host');

    // Generar firma como lo hace Laravel
    $signature = $socketId . ':' . $channelName;
    $auth = $appKey . ':' . hash_hmac('sha256', $signature, $appSecret);

    return response()->json([
        'debug_info' => [
            'socket_id' => $socketId,
            'channel_name' => $channelName,
            'app_key' => $appKey,
            'host_config' => $host,
            'signature_string' => $signature,
            'generated_auth' => $auth,
            'reverb_config' => config('reverb'),
        ],
    ]);
})->middleware('auth:sanctum');

// ===== NUEVOS MÓDULOS SRP =====
// Requieren autenticación y verifican roles mediante Policies
Route::prefix('v1')->group(function () {
    // Flow Builder (PM/Admin)
    require __DIR__.'/flow-builder.php';

    // Task Center (Usuarios)
    require __DIR__.'/task-center.php';
});

// Rutas protegidas (requieren autenticación)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    // Usuarios
    Route::get('/users', [App\Http\Controllers\Api\UserController::class, 'index']);

    // Templates
    Route::post('/templates/from-flow/{flowId}', [TemplateController::class, 'createFromFlow']);
    Route::apiResource('templates', TemplateController::class);

    // Clients
    Route::apiResource('clients', ClientController::class);

    // Flows
    Route::apiResource('flows', FlowController::class);

    // Tasks
    Route::post('/tasks/reorder', [TaskController::class, 'reorder']);
    Route::post('/tasks/{id}/move', [TaskController::class, 'move']);
    Route::apiResource('tasks', TaskController::class);
    // Dependencias de tareas
    Route::get('/tasks/{taskId}/dependencies', [App\Http\Controllers\Api\TaskDependencyController::class, 'index']);
    Route::post('/tasks/{taskId}/dependencies', [App\Http\Controllers\Api\TaskDependencyController::class, 'store']);
    Route::delete('/dependencies/{id}', [App\Http\Controllers\Api\TaskDependencyController::class, 'destroy']);
    Route::get('/tasks/{taskId}/check-blocked', [App\Http\Controllers\Api\TaskDependencyController::class, 'checkBlocked']);

    // Progreso de tareas
    Route::get('/tasks/{taskId}/progress', [App\Http\Controllers\Api\ProgressController::class, 'index']);
    Route::post('/progress', [App\Http\Controllers\Api\ProgressController::class, 'store']);
    Route::get('/progress/{progress}', [App\Http\Controllers\Api\ProgressController::class, 'show']);
    Route::put('/progress/{progress}', [App\Http\Controllers\Api\ProgressController::class, 'update']);
    Route::delete('/progress/{progress}', [App\Http\Controllers\Api\ProgressController::class, 'destroy']);
    
    // Notificaciones
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('/notifications/stats', [NotificationController::class, 'stats']);

    // Reportes
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/stats', [ReportController::class, 'stats']);
    Route::get('/reports/analytics', [ReportController::class, 'analytics']);
    Route::get('/reports/export/csv', [ReportController::class, 'exportCsv']);
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf']);

    // Adjuntos de Tareas
    Route::post('/tasks/{task}/attachments', [App\Http\Controllers\Api\TaskAttachmentController::class, 'store']);
    Route::delete('/attachments/{attachment}', [App\Http\Controllers\Api\TaskAttachmentController::class, 'destroy']);
});