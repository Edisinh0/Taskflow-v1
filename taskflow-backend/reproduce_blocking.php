<?php

use App\Models\Flow;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Encontrar un usuario
$user = User::first();
if (!$user) {
    echo "No hay usuarios en la base de datos.\n";
    exit;
}

// 1. Crear un flujo
$flow = Flow::create([
    'name' => 'Flow de Prueba Reproducción',
    'created_by' => $user->id,
    'status' => 'active',
]);

echo "Flow creado: {$flow->id}\n";

// 2. Crear una tarea y completarla
$task1 = Task::create([
    'title' => 'Tarea Precedente (Completada)',
    'flow_id' => $flow->id,
    'status' => 'completed',
    'progress' => 100,
]);

echo "Tarea 1 creada y completada: {$task1->id}, status: {$task1->status}\n";

// 3. Crear una nueva tarea que dependa de la Tarea 1
$task2 = Task::create([
    'title' => 'Tarea Dependiente (Debería estar Libre)',
    'flow_id' => $flow->id,
    'depends_on_task_id' => $task1->id,
    'status' => 'pending',
]);

echo "Tarea 2 creada con depend_on_task_id = {$task1->id}\n";
echo "Resultado - is_blocked: " . ($task2->is_blocked ? 'BLOQUEADA (MAL)' : 'LIBRE (BIEN)') . "\n";

if ($task2->is_blocked) {
    echo "ERROR: La tarea 2 se bloqueó a pesar de que la tarea 1 está completada.\n";
} else {
    echo "ÉXITO: La tarea 2 está libre.\n";
}

// 4. Probar con Milestone
$milestone = Task::create([
    'title' => 'Milestone (Completado)',
    'flow_id' => $flow->id,
    'is_milestone' => true,
    'status' => 'completed',
    'progress' => 100,
]);

echo "Milestone creado y completado: {$milestone->id}, status: {$milestone->status}\n";

$task3 = Task::create([
    'title' => 'Tarea Dependiente de Milestone',
    'flow_id' => $flow->id,
    'depends_on_milestone_id' => $milestone->id,
    'status' => 'pending',
]);

echo "Tarea 3 creada con depends_on_milestone_id = {$milestone->id}\n";
echo "Resultado - is_blocked: " . ($task3->is_blocked ? 'BLOQUEADA (MAL)' : 'LIBRE (BIEN)') . "\n";

// Limpiar
$flow->delete(); // Elimina tareas en cascada por el observer
