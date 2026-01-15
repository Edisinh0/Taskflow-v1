<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal para notificaciones de usuario
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Canal para actualizaciones de tareas
Broadcast::channel('task.{taskId}', function ($user, $taskId) {
    // Verificar si el usuario tiene acceso a la tarea
    $task = \App\Models\Task::find($taskId);
    if (!$task) {
        return false;
    }

    // El usuario puede suscribirse si es el assignee o si pertenece al flujo
    return $user->id === $task->assignee_id ||
           $task->flow->created_by === $user->id;
});

// Canal para actualizaciones de flujos
Broadcast::channel('flow.{flowId}', function ($user, $flowId) {
    // Verificar si el usuario tiene acceso al flujo
    $flow = \App\Models\Flow::find($flowId);
    if (!$flow) {
        return false;
    }

    // El usuario puede suscribirse si creó el flujo o tiene tareas asignadas en él
    return $flow->created_by === $user->id ||
           $flow->tasks()->where('assignee_id', $user->id)->exists();
});
