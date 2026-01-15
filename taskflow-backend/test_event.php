<?php

use App\Models\Task;
use App\Models\User;
use App\Models\Flow;

$user = User::first();
$flow = Flow::create(['name' => 'Test', 'created_by' => $user->id]);

$task = Task::create([
    'title' => 'Test Task',
    'status' => 'pending',
    'flow_id' => $flow->id
]);

// Registramos un listener temporal para el evento updated
Task::updated(function($task) {
    echo "--- EVENTO UPDATED ---\n";
    echo "Status: " . $task->status . "\n";
    echo "isDirty('status'): " . ($task->isDirty('status') ? 'SI' : 'NO') . "\n";
    echo "wasChanged('status'): " . ($task->wasChanged('status') ? 'SI' : 'NO') . "\n";
    echo "--------------------\n";
});

echo "Actualizando a completed...\n";
$task->status = 'completed';
$task->save();

$flow->delete();
