# 🔧 Ejemplos de Integración: TaskFlow SRP Modules

## 📋 Índice

1. [Actualizar routes/api.php](#1-actualizar-routesapiphp)
2. [Configurar Vue Router](#2-configurar-vue-router)
3. [Crear Store de Auth](#3-crear-store-de-auth)
4. [Ejemplos de Uso de Componentes](#4-ejemplos-de-uso-de-componentes)
5. [Tests de Ejemplo](#5-tests-de-ejemplo)

---

## 1. Actualizar routes/api.php

**Archivo:** `taskflow-backend/routes/api.php`

```php
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ===== NUEVOS MÓDULOS SEPARADOS =====
    // Requieren autenticación y verifican roles mediante Policies

    // Flow Builder (PM/Admin)
    require __DIR__.'/flow-builder.php';

    // Task Center (Usuarios)
    require __DIR__.'/task-center.php';

    // ===== RUTAS LEGACY (Compatibilidad) =====
    // Mantener temporalmente durante migración

    Route::middleware('auth:sanctum')->group(function () {
        // Autenticación
        Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/me', [App\Http\Controllers\Api\AuthController::class, 'me']);

        // Flujos (usar FlowBuilderController en su lugar)
        Route::apiResource('flows', App\Http\Controllers\Api\FlowController::class);

        // Tareas (usar FlowBuilderController o TaskCenterController según rol)
        Route::apiResource('tasks', App\Http\Controllers\Api\TaskController::class);
        Route::post('tasks/reorder', [App\Http\Controllers\Api\TaskController::class, 'reorder']);
        Route::post('tasks/{id}/move', [App\Http\Controllers\Api\TaskController::class, 'move']);

        // Dependencias
        Route::apiResource('task-dependencies', App\Http\Controllers\Api\TaskDependencyController::class);

        // Adjuntos
        Route::apiResource('task-attachments', App\Http\Controllers\Api\TaskAttachmentController::class);

        // Notificaciones
        Route::prefix('notifications')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\NotificationController::class, 'index']);
            Route::put('/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
            Route::post('/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
            Route::delete('/{id}', [App\Http\Controllers\Api\NotificationController::class, 'destroy']);
            Route::get('/stats', [App\Http\Controllers\Api\NotificationController::class, 'stats']);
        });

        // Reportes
        Route::get('reports/dashboard', [App\Http\Controllers\Api\ReportController::class, 'dashboard']);

        // Plantillas
        Route::apiResource('templates', App\Http\Controllers\Api\TemplateController::class);

        // Usuarios
        Route::get('users', [App\Http\Controllers\Api\UserController::class, 'index']);
    });

    // Autenticación (sin middleware)
    Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
});
```

---

## 2. Configurar Vue Router

**Archivo:** `taskflow-frontend/src/router/index.js`

```javascript
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  // ===== LOGIN =====
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresAuth: false }
  },

  // ===== DASHBOARD =====
  {
    path: '/',
    name: 'Dashboard',
    component: () => import('@/views/DashboardView.vue'),
    meta: { requiresAuth: true }
  },

  // ===== FLOW BUILDER (PM/Admin) =====
  {
    path: '/flow-builder',
    name: 'FlowBuilder',
    component: () => import('@/modules/flow-builder/views/FlowBuilderView.vue'),
    meta: {
      requiresAuth: true,
      requiresRole: ['admin', 'project_manager', 'pm']
    }
  },
  {
    path: '/flow-builder/flows/:id',
    name: 'FlowDesign',
    component: () => import('@/modules/flow-builder/views/FlowDesignView.vue'),
    meta: {
      requiresAuth: true,
      requiresRole: ['admin', 'project_manager', 'pm']
    }
  },

  // ===== TASK CENTER (Usuarios) =====
  {
    path: '/task-center',
    name: 'TaskCenter',
    component: () => import('@/modules/task-center/views/TaskCenterView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/task-center/tasks/:id',
    name: 'TaskDetail',
    component: () => import('@/modules/task-center/views/TaskDetailView.vue'),
    meta: { requiresAuth: true }
  },

  // ===== LEGACY ROUTES (Compatibilidad) =====
  {
    path: '/flows',
    name: 'Flows',
    component: () => import('@/views/FlowsView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/flows/:id',
    name: 'FlowDetail',
    component: () => import('@/views/FlowDetailView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/tasks',
    name: 'Tasks',
    component: () => import('@/views/TasksView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/notifications',
    name: 'Notifications',
    component: () => import('@/views/NotificationsView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/reports',
    name: 'Reports',
    component: () => import('@/views/ReportsView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/templates',
    name: 'Templates',
    component: () => import('@/views/TemplatesView.vue'),
    meta: { requiresAuth: true }
  },

  // ===== 404 =====
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/NotFoundView.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// ===== GUARDS DE NAVEGACIÓN =====
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // 1. Verificar autenticación
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'Login', query: { redirect: to.fullPath } })
  }

  // 2. Si ya está autenticado e intenta ir a login, redirigir
  if (to.name === 'Login' && authStore.isAuthenticated) {
    return next({ name: 'Dashboard' })
  }

  // 3. Verificar roles requeridos
  if (to.meta.requiresRole && authStore.user) {
    const hasRequiredRole = to.meta.requiresRole.includes(authStore.user.role)

    if (!hasRequiredRole) {
      // Redirigir a Task Center si no tiene permisos
      console.warn(`User ${authStore.user.email} attempted to access ${to.path} without required role`)
      return next({ name: 'TaskCenter' })
    }
  }

  next()
})

export default router
```

---

## 3. Crear Store de Auth

**Archivo:** `taskflow-frontend/src/stores/auth.js`

```javascript
import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false,
    error: null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,

    isFlowBuilder: (state) => {
      if (!state.user) return false
      return ['admin', 'project_manager', 'pm'].includes(state.user.role)
    },

    isOperator: (state) => {
      if (!state.user) return false
      return ['user', 'operator', 'employee'].includes(state.user.role)
    },

    isAdmin: (state) => {
      return state.user?.role === 'admin'
    }
  },

  actions: {
    async login(credentials) {
      this.loading = true
      this.error = null

      try {
        const response = await axios.post('/api/v1/login', credentials)

        this.token = response.data.token
        this.user = response.data.user

        // Guardar token en localStorage
        localStorage.setItem('token', this.token)

        // Configurar header de axios
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Error al iniciar sesión'
        throw error
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await axios.post('/api/v1/logout')
      } catch (error) {
        console.error('Error during logout:', error)
      } finally {
        this.user = null
        this.token = null
        localStorage.removeItem('token')
        delete axios.defaults.headers.common['Authorization']
      }
    },

    async fetchUser() {
      if (!this.token) return

      try {
        const response = await axios.get('/api/v1/me')
        this.user = response.data.user
      } catch (error) {
        console.error('Error fetching user:', error)
        // Si falla, limpiar sesión
        this.logout()
      }
    },

    initializeAuth() {
      if (this.token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        this.fetchUser()
      }
    }
  }
})
```

---

## 4. Ejemplos de Uso de Componentes

### 4.1. TaskCenterView con TaskExecutionCard

**Archivo:** `MyTasksPage.vue`

```vue
<script setup>
import { ref, onMounted } from 'vue'
import TaskExecutionCard from '@/modules/task-center/components/TaskExecutionCard.vue'
import { useTaskExecution } from '@/modules/task-center/composables/useTaskExecution'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const { tasks, loading, fetchMyTasks } = useTaskExecution()
const errorMessage = ref(null)

onMounted(async () => {
  await fetchMyTasks()
})

function handleTaskUpdate(updatedTask) {
  console.log('Task updated:', updatedTask)

  // Actualizar en la lista
  const index = tasks.value.findIndex(t => t.id === updatedTask.id)
  if (index !== -1) {
    tasks.value[index] = updatedTask
  }

  // Mostrar notificación
  alert('Tarea actualizada correctamente')
}

function handleError(error) {
  errorMessage.value = error
  setTimeout(() => {
    errorMessage.value = null
  }, 5000)
}
</script>

<template>
  <div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">Mis Tareas</h1>

    <!-- Error Alert -->
    <div
      v-if="errorMessage"
      class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg"
    >
      {{ errorMessage }}
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <p>Cargando tareas...</p>
    </div>

    <!-- Tasks Grid -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <TaskExecutionCard
        v-for="task in tasks"
        :key="task.id"
        :task="task"
        @taskUpdated="handleTaskUpdate"
        @error="handleError"
      />
    </div>

    <!-- Empty State -->
    <div v-if="!loading && tasks.length === 0" class="text-center py-12">
      <p class="text-gray-500">No tienes tareas asignadas</p>
    </div>
  </div>
</template>
```

### 4.2. Uso del Composable useTaskExecution

```javascript
import { useTaskExecution } from '@/modules/task-center/composables/useTaskExecution'

export default {
  setup() {
    const {
      tasks,
      loading,
      error,
      fetchMyTasks,
      startTask,
      pauseTask,
      completeTask,
      updateProgress,
      inProgressTasks,
      pendingTasks,
      overdueTasks
    } = useTaskExecution()

    // Cargar tareas al montar
    onMounted(async () => {
      await fetchMyTasks({ status: 'in_progress' })
    })

    // Iniciar una tarea
    async function handleStartTask(taskId) {
      try {
        await startTask(taskId)
        alert('Tarea iniciada correctamente')
      } catch (err) {
        alert(`Error: ${err.message}`)
      }
    }

    // Completar una tarea
    async function handleCompleteTask(taskId) {
      try {
        await completeTask(taskId)
        alert('¡Tarea completada!')
      } catch (err) {
        alert(`Error: ${err.message}`)
      }
    }

    // Actualizar progreso
    async function handleProgressChange(taskId, progress) {
      try {
        await updateProgress(taskId, progress)
      } catch (err) {
        console.error('Error updating progress:', err)
      }
    }

    return {
      tasks,
      loading,
      inProgressTasks,
      overdueTasks,
      handleStartTask,
      handleCompleteTask,
      handleProgressChange
    }
  }
}
```

### 4.3. Navbar con Permisos de Rol

```vue
<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

function logout() {
  authStore.logout()
  router.push({ name: 'Login' })
}
</script>

<template>
  <nav class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex justify-between h-16">
        <div class="flex space-x-4">
          <!-- Logo -->
          <router-link to="/" class="flex items-center">
            <span class="text-xl font-bold">TaskFlow</span>
          </router-link>

          <!-- Navigation Links -->
          <router-link
            to="/"
            class="flex items-center px-3 text-gray-700 hover:text-blue-600"
          >
            Dashboard
          </router-link>

          <!-- Flow Builder (Solo PM/Admin) -->
          <router-link
            v-if="authStore.isFlowBuilder"
            to="/flow-builder"
            class="flex items-center px-3 text-gray-700 hover:text-blue-600"
          >
            🏗️ Flow Builder
          </router-link>

          <!-- Task Center (Todos) -->
          <router-link
            to="/task-center"
            class="flex items-center px-3 text-gray-700 hover:text-blue-600"
          >
            ⚙️ Task Center
          </router-link>

          <!-- Legacy Links -->
          <router-link
            to="/notifications"
            class="flex items-center px-3 text-gray-700 hover:text-blue-600"
          >
            🔔 Notificaciones
          </router-link>

          <router-link
            to="/reports"
            class="flex items-center px-3 text-gray-700 hover:text-blue-600"
          >
            📊 Reportes
          </router-link>
        </div>

        <!-- User Menu -->
        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-600">
            {{ authStore.user?.name }}
            <span class="text-xs text-gray-400">({{ authStore.user?.role }})</span>
          </span>

          <button
            @click="logout"
            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
          >
            Cerrar Sesión
          </button>
        </div>
      </div>
    </div>
  </nav>
</template>
```

---

## 5. Tests de Ejemplo

### 5.1. Test de FlowPolicy

**Archivo:** `taskflow-backend/tests/Feature/FlowPolicyTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Flow;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FlowPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_flows()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson('/api/v1/flow-builder/flows', [
                'name' => 'Test Flow',
                'description' => 'Test Description'
            ])
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'description']
            ]);
    }

    public function test_pm_can_create_flows()
    {
        $pm = User::factory()->create(['role' => 'project_manager']);

        $this->actingAs($pm)
            ->postJson('/api/v1/flow-builder/flows', [
                'name' => 'Test Flow'
            ])
            ->assertStatus(201);
    }

    public function test_user_cannot_create_flows()
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->postJson('/api/v1/flow-builder/flows', [
                'name' => 'Test Flow'
            ])
            ->assertStatus(403);
    }

    public function test_pm_can_update_flows()
    {
        $pm = User::factory()->create(['role' => 'project_manager']);
        $flow = Flow::factory()->create(['created_by' => $pm->id]);

        $this->actingAs($pm)
            ->putJson("/api/v1/flow-builder/flows/{$flow->id}", [
                'name' => 'Updated Flow Name'
            ])
            ->assertStatus(200)
            ->assertJson([
                'data' => ['name' => 'Updated Flow Name']
            ]);
    }

    public function test_user_cannot_delete_flows()
    {
        $user = User::factory()->create(['role' => 'user']);
        $flow = Flow::factory()->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/flow-builder/flows/{$flow->id}")
            ->assertStatus(403);
    }
}
```

### 5.2. Test de TaskCenterController

**Archivo:** `taskflow-backend/tests/Feature/TaskCenterTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use App\Models\Flow;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_tasks()
    {
        $user = User::factory()->create(['role' => 'user']);
        $flow = Flow::factory()->create();
        $task = Task::factory()->create([
            'flow_id' => $flow->id,
            'assignee_id' => $user->id
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/task-center/my-tasks')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $task->id);
    }

    public function test_user_can_start_own_task()
    {
        $user = User::factory()->create(['role' => 'user']);
        $flow = Flow::factory()->create();
        $task = Task::factory()->create([
            'flow_id' => $flow->id,
            'assignee_id' => $user->id,
            'status' => 'pending',
            'is_blocked' => false
        ]);

        $this->actingAs($user)
            ->putJson("/api/v1/task-center/tasks/{$task->id}/execute", [
                'status' => 'in_progress'
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress'
        ]);
    }

    public function test_user_can_complete_own_task()
    {
        $user = User::factory()->create(['role' => 'user']);
        $flow = Flow::factory()->create();
        $task = Task::factory()->create([
            'flow_id' => $flow->id,
            'assignee_id' => $user->id,
            'status' => 'in_progress',
            'is_blocked' => false,
            'allow_attachments' => false
        ]);

        $this->actingAs($user)
            ->putJson("/api/v1/task-center/tasks/{$task->id}/execute", [
                'status' => 'completed'
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_user_cannot_complete_blocked_task()
    {
        $user = User::factory()->create(['role' => 'user']);
        $flow = Flow::factory()->create();
        $task = Task::factory()->create([
            'flow_id' => $flow->id,
            'assignee_id' => $user->id,
            'status' => 'blocked',
            'is_blocked' => true
        ]);

        $this->actingAs($user)
            ->putJson("/api/v1/task-center/tasks/{$task->id}/execute", [
                'status' => 'completed'
            ])
            ->assertStatus(403)
            ->assertJsonFragment(['message' => '🔒 Acción prohibida']);
    }

    public function test_user_cannot_complete_task_without_attachments()
    {
        $user = User::factory()->create(['role' => 'user']);
        $flow = Flow::factory()->create();
        $task = Task::factory()->create([
            'flow_id' => $flow->id,
            'assignee_id' => $user->id,
            'status' => 'in_progress',
            'is_blocked' => false,
            'allow_attachments' => true
        ]);

        $this->actingAs($user)
            ->putJson("/api/v1/task-center/tasks/{$task->id}/execute", [
                'status' => 'completed'
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => '⚠️ Requisito faltante']);
    }

    public function test_user_cannot_execute_others_tasks()
    {
        $user1 = User::factory()->create(['role' => 'user']);
        $user2 = User::factory()->create(['role' => 'user']);
        $flow = Flow::factory()->create();
        $task = Task::factory()->create([
            'flow_id' => $flow->id,
            'assignee_id' => $user2->id
        ]);

        $this->actingAs($user1)
            ->putJson("/api/v1/task-center/tasks/{$task->id}/execute", [
                'status' => 'in_progress'
            ])
            ->assertStatus(403);
    }

    public function test_user_can_update_progress()
    {
        $user = User::factory()->create(['role' => 'user']);
        $flow = Flow::factory()->create();
        $task = Task::factory()->create([
            'flow_id' => $flow->id,
            'assignee_id' => $user->id,
            'status' => 'in_progress',
            'progress' => 0
        ]);

        $this->actingAs($user)
            ->putJson("/api/v1/task-center/tasks/{$task->id}/execute", [
                'progress' => 50
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.progress', 50);
    }
}
```

---

## 🎉 Conclusión

Estos ejemplos proporcionan todo lo necesario para integrar los nuevos módulos TaskFlow en tu aplicación:

1. ✅ Rutas API actualizadas
2. ✅ Vue Router con guards de rol
3. ✅ Auth Store con verificación de permisos
4. ✅ Ejemplos de uso de componentes
5. ✅ Tests completos de seguridad

**La integración es incremental y no rompe funcionalidad existente.**

---

**Fecha:** 2025-12-17
**Versión:** 1.0
