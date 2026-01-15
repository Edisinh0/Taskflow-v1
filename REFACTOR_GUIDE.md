# 🏗️ Guía de Refactorización: TaskFlow SRP Modules

## 📋 Índice

1. [Introducción](#introducción)
2. [Arquitectura de Módulos](#arquitectura-de-módulos)
3. [Estructura de Backend](#estructura-de-backend)
4. [Estructura de Frontend](#estructura-de-frontend)
5. [Seguridad y Policies](#seguridad-y-policies)
6. [Motor de Control de Flujos](#motor-de-control-de-flujos)
7. [Guía de Implementación](#guía-de-implementación)
8. [Testing](#testing)

---

## 🎯 Introducción

Esta refactorización separa el sistema TaskFlow en dos módulos independientes siguiendo el **Principio de Responsabilidad Única (SRP)**:

### 📐 Flow Builder (Diseño)
- **Usuarios**: PM/Administradores
- **Propósito**: Crear y diseñar la estructura de flujos
- **Capacidades**:
  - Crear/editar/eliminar flujos
  - Diseñar jerarquía de tareas
  - Configurar dependencias y milestones
  - Establecer reglas de bloqueo
  - Asignar responsables

### ⚙️ Task Center (Ejecución)
- **Usuarios**: Usuarios asignados (operativos)
- **Propósito**: Ejecutar tareas asignadas
- **Capacidades**:
  - Ver tareas asignadas
  - Iniciar/pausar/completar tareas
  - Registrar progreso (0-100%)
  - Subir archivos adjuntos
  - Ver alertas de SLA

---

## 🏛️ Arquitectura de Módulos

```
┌─────────────────────────────────────────────────────────┐
│                    TaskFlow System                       │
├───────────────────────┬─────────────────────────────────┤
│   Flow Builder        │      Task Center                │
│   (Design Module)     │      (Execution Module)         │
├───────────────────────┼─────────────────────────────────┤
│ PM/Admin Only         │ Assigned Users                  │
│                       │                                 │
│ ✓ Create flows        │ ✓ View assigned tasks          │
│ ✓ Design structure    │ ✓ Update status                │
│ ✓ Set dependencies    │ ✓ Track progress               │
│ ✓ Configure milestones│ ✓ Upload attachments           │
│ ✓ Assign tasks        │ ✓ Log time                     │
│                       │                                 │
│ ✗ Cannot execute      │ ✗ Cannot modify structure      │
└───────────────────────┴─────────────────────────────────┘
```

---

## 🔧 Estructura de Backend

### 📂 Directorio de Controllers

```
app/Http/Controllers/Api/
├── FlowBuilderController.php    # Flow Builder (PM/Admin)
├── TaskCenterController.php     # Task Center (Users)
├── FlowController.php            # Mantenido para compatibilidad
└── TaskController.php            # Mantenido para compatibilidad
```

### 🔐 Policies

```
app/Policies/
├── FlowPolicy.php               # Protege operaciones de flujos
└── TaskPolicy.php               # Protege operaciones de tareas
```

**FlowPolicy:**
- `view()`: Todos pueden ver
- `create()`, `update()`, `delete()`: Solo PM/Admin
- Método: `isFlowBuilder(User $user)`

**TaskPolicy:**
- `view()`: Asignado o PM/Admin
- `create()`, `updateStructure()`, `delete()`: Solo PM/Admin
- `execute()`: Solo asignado o PM/Admin
- `manageDependencies()`: Solo PM/Admin
- Métodos: `isFlowBuilder()`, `isOperator()`

### 🛣️ Rutas Separadas

**routes/flow-builder.php:**
```php
Route::prefix('flow-builder')->middleware('auth:sanctum')->group(function () {
    Route::prefix('flows')->group(function () {
        Route::post('/', [FlowBuilderController::class, 'createFlow']);
        Route::put('/{id}', [FlowBuilderController::class, 'updateFlow']);
        Route::delete('/{id}', [FlowBuilderController::class, 'deleteFlow']);
    });

    Route::prefix('tasks')->group(function () {
        Route::post('/', [FlowBuilderController::class, 'createTask']);
        Route::put('/{id}', [FlowBuilderController::class, 'updateTaskStructure']);
        Route::delete('/{id}', [FlowBuilderController::class, 'deleteTask']);
        Route::put('/{id}/dependencies', [FlowBuilderController::class, 'configureDependencies']);
    });
});
```

**routes/task-center.php:**
```php
Route::prefix('task-center')->middleware('auth:sanctum')->group(function () {
    Route::get('/my-tasks', [TaskCenterController::class, 'myTasks']);
    Route::get('/tasks/{id}', [TaskCenterController::class, 'show']);
    Route::put('/tasks/{id}/execute', [TaskCenterController::class, 'executeTask']);
});
```

### 📊 Roles de Usuario

```php
// Migración: 2025_12_17_000001_add_role_to_users_table.php
enum('role', [
    'admin',            // Acceso total
    'project_manager',  // Flow Builder
    'pm',               // Flow Builder (alias)
    'user',             // Task Center
    'operator',         // Task Center
    'employee'          // Task Center
])->default('user')
```

---

## 🎨 Estructura de Frontend

### 📂 Directorio de Módulos

```
taskflow-frontend/src/
├── modules/
│   ├── flow-builder/              # Módulo de Diseño
│   │   ├── components/
│   │   │   ├── FlowEditor.vue
│   │   │   ├── TaskStructureEditor.vue
│   │   │   ├── MilestoneDesigner.vue
│   │   │   ├── DependencyConfigurator.vue
│   │   │   └── TaskHierarchyTree.vue
│   │   ├── views/
│   │   │   ├── FlowBuilderView.vue
│   │   │   └── FlowDesignView.vue
│   │   └── composables/
│   │       ├── useFlowBuilder.js
│   │       └── useTaskStructure.js
│   │
│   └── task-center/               # Módulo de Ejecución
│       ├── components/
│       │   ├── TaskExecutionCard.vue     # ⭐ Principal
│       │   ├── TaskProgressTracker.vue
│       │   ├── TimeLogger.vue
│       │   ├── TaskAttachmentUploader.vue
│       │   └── MilestoneStatusBadge.vue
│       ├── views/
│       │   ├── TaskCenterView.vue
│       │   └── MyTasksView.vue
│       └── composables/
│           ├── useTaskExecution.js      # ⭐ Creado
│           └── useTaskProgress.js
│
└── shared/                        # Componentes compartidos
    ├── components/
    │   ├── TaskStatusBadge.vue
    │   ├── UserAvatar.vue
    │   ├── DateDisplay.vue
    │   └── SLAIndicator.vue
    └── composables/
        ├── usePermissions.js
        └── useSLA.js
```

### 🧩 Componente TaskExecutionCard.vue

**Ubicación:** `src/modules/task-center/components/TaskExecutionCard.vue`

**Características:**
- ✅ 100% enfocado en ejecución
- ✅ Solo permite ver y completar tareas
- ✅ Respeta bloqueos de milestones (🔒)
- ✅ Valida adjuntos obligatorios
- ✅ Muestra alertas de SLA (+1 día, +2 días)
- ✅ Optimistic UI para mejor UX
- ✅ Deshabilitado para usuarios no asignados

**Props:**
```vue
<TaskExecutionCard
  :task="task"
  :readonly="false"
  @taskUpdated="handleUpdate"
  @error="handleError"
/>
```

**Lógica de Bloqueo:**
```javascript
const isBlocked = computed(() => {
  return props.task.is_blocked ||
         props.task.status === 'blocked' ||
         (props.task.depends_on_task_id && props.task.depends_on_task?.status !== 'completed') ||
         (props.task.depends_on_milestone_id && props.task.depends_on_milestone?.status !== 'completed')
})
```

### 🔧 Composable useTaskExecution.js

**Ubicación:** `src/modules/task-center/composables/useTaskExecution.js`

**Métodos:**
- `fetchMyTasks()` - Obtener tareas asignadas
- `fetchTaskDetail()` - Obtener detalle de tarea
- `startTask()` - Iniciar tarea
- `pauseTask()` - Pausar tarea
- `completeTask()` - Completar tarea
- `updateProgress()` - Actualizar progreso

**Computed Properties:**
- `pendingTasks` - Tareas pendientes
- `inProgressTasks` - Tareas en progreso
- `completedTasks` - Tareas completadas
- `blockedTasks` - Tareas bloqueadas
- `overdueTasks` - Tareas vencidas
- `urgentTasks` - Tareas urgentes

---

## 🔐 Seguridad y Policies

### Protección de Endpoints

```php
// FlowBuilderController.php
public function createFlow(Request $request)
{
    // ✅ Autorización mediante Policy
    Gate::authorize('create', Flow::class);

    // ... lógica de creación
}
```

```php
// TaskCenterController.php
public function executeTask(Request $request, $id)
{
    $task = Task::findOrFail($id);

    // ✅ Solo el asignado puede ejecutar
    Gate::authorize('execute', $task);

    // ... lógica de ejecución
}
```

### Verificación de Roles

```php
private function isFlowBuilder(User $user): bool
{
    return in_array($user->role, ['admin', 'project_manager', 'pm']);
}

private function isOperator(User $user): bool
{
    return in_array($user->role, ['user', 'operator', 'employee']);
}
```

### Bloqueos del Frontend

```javascript
// Calcular si el usuario puede ejecutar
const canExecute = computed(() => {
  if (props.readonly) return false
  const user = authStore.user
  return user && user.id === props.task.assignee_id
})
```

---

## ⚙️ Motor de Control de Flujos

### Lógica de Bloqueo de Milestones

**Backend (TaskCenterController.php:196-230):**
```php
if ($task->is_blocked && in_array($newStatus, ['in_progress', 'completed'])) {
    $blockingReasons = [];

    if ($task->depends_on_task_id) {
        $precedentTask = Task::find($task->depends_on_task_id);
        if ($precedentTask && $precedentTask->status !== 'completed') {
            $blockingReasons[] = "la tarea '{$precedentTask->title}'";
        }
    }

    if ($task->depends_on_milestone_id) {
        $milestone = Task::find($task->depends_on_milestone_id);
        if ($milestone && $milestone->status !== 'completed') {
            $blockingReasons[] = "el milestone '{$milestone->title}'";
        }
    }

    return response()->json([
        'success' => false,
        'message' => "🔒 Acción prohibida: {$blockMessage}",
    ], 403);
}
```

### Sistema de SLA (Alertas de Atraso)

**Backend (TaskCenterController.php:287-319):**
```php
private function calculateSLAStatus(Task $task): ?array
{
    $diffDays = $now->diffInDays($deadline, false);

    if ($diffDays < 0) {
        return [
            'level' => 'critical',
            'message' => "⚠️ Vencida hace " . abs($diffDays) . " día(s)",
        ];
    } elseif ($diffDays == 0) {
        return ['level' => 'warning', 'message' => '⏰ Vence HOY'];
    } elseif ($diffDays == 1) {
        return ['level' => 'warning', 'message' => '⏰ Vence MAÑANA'];
    }
}
```

**Frontend (TaskExecutionCard.vue:63-96):**
```javascript
const slaStatus = computed(() => {
  if (diffDays < 0) {
    return {
      level: 'critical',
      message: `⚠️ Vencida hace ${Math.abs(diffDays)} día(s)`,
      class: 'bg-red-100 text-red-800'
    }
  }
  // ... más niveles
})
```

### Validación de Adjuntos Obligatorios

**Backend (TaskCenterController.php:242-248):**
```php
if ($newStatus === 'completed' && $task->allow_attachments) {
    if ($task->attachments()->count() === 0) {
        return response()->json([
            'success' => false,
            'message' => "⚠️ Debes adjuntar al menos un documento",
        ], 422);
    }
}
```

---

## 📝 Guía de Implementación

### Paso 1: Preparar Base de Datos

```bash
# Ejecutar migración para agregar campo 'role'
cd taskflow-backend
php artisan migrate

# (Opcional) Actualizar usuarios existentes
php artisan tinker
```

```php
// En tinker:
User::where('email', 'admin@taskflow.com')->update(['role' => 'admin']);
User::where('email', 'pm@taskflow.com')->update(['role' => 'project_manager']);
User::whereNotIn('role', ['admin', 'project_manager'])->update(['role' => 'user']);
```

### Paso 2: Registrar Rutas en api.php

**taskflow-backend/routes/api.php:**
```php
// Registrar módulos separados
require __DIR__.'/flow-builder.php';
require __DIR__.'/task-center.php';

// Mantener rutas legacy para compatibilidad (opcional)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('flows', FlowController::class);
    Route::apiResource('tasks', TaskController::class);
});
```

### Paso 3: Configurar Frontend

**Crear estructura de módulos:**
```bash
cd taskflow-frontend/src
mkdir -p modules/flow-builder/{components,views,composables}
mkdir -p modules/task-center/{components,views,composables}
mkdir -p shared/{components,composables}
```

**Configurar Vue Router:**
```javascript
// router/index.js
const routes = [
  {
    path: '/flow-builder',
    name: 'FlowBuilder',
    component: () => import('@/modules/flow-builder/views/FlowBuilderView.vue'),
    meta: { requiresRole: ['admin', 'project_manager', 'pm'] }
  },
  {
    path: '/task-center',
    name: 'TaskCenter',
    component: () => import('@/modules/task-center/views/TaskCenterView.vue'),
    meta: { requiresAuth: true }
  }
]

// Guard de navegación
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresRole) {
    if (!to.meta.requiresRole.includes(authStore.user?.role)) {
      return next({ name: 'TaskCenter' })
    }
  }

  next()
})
```

### Paso 4: Integrar Componente TaskExecutionCard

```vue
<!-- MyTasksView.vue -->
<template>
  <div class="container">
    <h1>Mis Tareas</h1>

    <div class="tasks-grid">
      <TaskExecutionCard
        v-for="task in tasks"
        :key="task.id"
        :task="task"
        @taskUpdated="handleTaskUpdate"
        @error="handleError"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import TaskExecutionCard from '@/modules/task-center/components/TaskExecutionCard.vue'
import { useTaskExecution } from '@/modules/task-center/composables/useTaskExecution'

const { tasks, fetchMyTasks } = useTaskExecution()

onMounted(() => {
  fetchMyTasks()
})

function handleTaskUpdate(updatedTask) {
  // Actualizar la tarea en la lista
  const index = tasks.value.findIndex(t => t.id === updatedTask.id)
  if (index !== -1) {
    tasks.value[index] = updatedTask
  }
}

function handleError(errorMessage) {
  alert(errorMessage)
}
</script>
```

### Paso 5: Verificar Seguridad

**Prueba de seguridad:**
```bash
# Como usuario operativo (role: user), intentar crear un flujo
curl -X POST http://localhost:8000/api/v1/flow-builder/flows \
  -H "Authorization: Bearer USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "Test Flow"}'

# Respuesta esperada: 403 Forbidden
```

**Prueba de ejecución:**
```bash
# Como usuario asignado, intentar completar tarea bloqueada
curl -X PUT http://localhost:8000/api/v1/task-center/tasks/123/execute \
  -H "Authorization: Bearer USER_TOKEN" \
  -d '{"status": "completed"}'

# Respuesta esperada: 403 con mensaje de bloqueo
```

---

## 🧪 Testing

### Tests de Policies

**tests/Feature/FlowPolicyTest.php:**
```php
public function test_only_flow_builder_can_create_flows()
{
    $pm = User::factory()->create(['role' => 'project_manager']);
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($pm)
        ->postJson('/api/v1/flow-builder/flows', ['name' => 'Test'])
        ->assertStatus(201);

    $this->actingAs($user)
        ->postJson('/api/v1/flow-builder/flows', ['name' => 'Test'])
        ->assertStatus(403);
}
```

### Tests de Task Center

**tests/Feature/TaskCenterTest.php:**
```php
public function test_user_can_complete_own_task()
{
    $user = User::factory()->create(['role' => 'user']);
    $task = Task::factory()->create([
        'assignee_id' => $user->id,
        'status' => 'in_progress'
    ]);

    $this->actingAs($user)
        ->putJson("/api/v1/task-center/tasks/{$task->id}/execute", [
            'status' => 'completed'
        ])
        ->assertStatus(200);

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => 'completed'
    ]);
}

public function test_cannot_complete_blocked_task()
{
    $user = User::factory()->create(['role' => 'user']);
    $task = Task::factory()->create([
        'assignee_id' => $user->id,
        'is_blocked' => true,
        'status' => 'blocked'
    ]);

    $this->actingAs($user)
        ->putJson("/api/v1/task-center/tasks/{$task->id}/execute", [
            'status' => 'completed'
        ])
        ->assertStatus(403)
        ->assertJsonFragment(['message' => '🔒 Acción prohibida']);
}
```

---

## 📚 Referencias

- **Motor de Control de Flujos v0.4**: Lógica de bloqueo y dependencias
- **Recomendaciones Técnicas v1.1**: Mejores prácticas de Laravel y Vue
- **Laravel Policies**: https://laravel.com/docs/11.x/authorization
- **Vue 3 Composition API**: https://vuejs.org/guide/extras/composition-api-faq.html

---

## ✅ Checklist de Implementación

### Backend
- [x] Crear FlowPolicy y TaskPolicy
- [x] Crear FlowBuilderController
- [x] Crear TaskCenterController
- [x] Registrar Policies en AppServiceProvider
- [x] Crear rutas flow-builder.php y task-center.php
- [x] Crear migración add_role_to_users_table
- [ ] Ejecutar migraciones
- [ ] Actualizar roles de usuarios existentes
- [ ] Escribir tests de Policies

### Frontend
- [x] Crear estructura de carpetas de módulos
- [x] Crear componente TaskExecutionCard.vue
- [x] Crear composable useTaskExecution.js
- [ ] Crear TaskCenterView.vue
- [ ] Crear FlowBuilderView.vue
- [ ] Actualizar Vue Router con guards
- [ ] Integrar componentes en vistas existentes
- [ ] Escribir tests unitarios

### Seguridad
- [ ] Verificar que usuarios operativos no puedan acceder a Flow Builder
- [ ] Verificar que solo asignados puedan ejecutar tareas
- [ ] Verificar bloqueo de milestones
- [ ] Verificar validación de adjuntos obligatorios
- [ ] Verificar cálculo de SLA

---

**Fecha de creación:** 2025-12-17
**Autor:** Arquitecto de Software Senior
**Versión:** 1.0
