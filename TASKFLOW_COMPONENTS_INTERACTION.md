# 🔄 Taskflow - Interacción de Componentes

Este documento explica cómo cada parte del sistema interactúa entre sí.

---

## 1. RELACIÓN: Controller ↔ Model ↔ Base de Datos

### Ejemplo: Crear una Tarea

```
┌──────────────────────────────────────────────────────────────────┐
│                    FRONTEND (Vue 3)                              │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │ TaskModal.vue                                              │  │
│  │ - Formulario para crear tarea                              │  │
│  │ - Valida en el cliente                                     │  │
│  │ - Hace POST /api/v1/tasks con datos                        │  │
│  └────────────────────────────────────────────────────────────┘  │
└──────────────────────┬───────────────────────────────────────────┘
                       │ HTTP POST
                       │ Body: { title, description, flow_id, ... }
                       │
┌──────────────────────▼───────────────────────────────────────────┐
│                    BACKEND (Laravel)                             │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │ routes/api.php                                             │  │
│  │ POST /tasks → TaskController@store                         │  │
│  └────────────────┬───────────────────────────────────────────┘  │
│                   │                                               │
│  ┌────────────────▼───────────────────────────────────────────┐  │
│  │ TaskController (app/Http/Controllers/Api/TaskController)   │  │
│  │                                                             │  │
│  │ public function store(StoreTaskRequest $request)           │  │
│  │ {                                                           │  │
│  │   $validated = $request->validated();  ✓ Valida según     │  │
│  │                                         reglas de Form      │  │
│  │   $task = Task::create($validated);                        │  │
│  │   return response()->json($task);                          │  │
│  │ }                                                           │  │
│  └────────────────┬───────────────────────────────────────────┘  │
│                   │                                               │
│  ┌────────────────▼───────────────────────────────────────────┐  │
│  │ StoreTaskRequest (app/Http/Requests)                       │  │
│  │                                                             │  │
│  │ public function rules()                                    │  │
│  │ {                                                           │  │
│  │   return [                                                 │  │
│  │     'title' => 'required|string|max:255',                 │  │
│  │     'flow_id' => 'required|exists:flows,id',              │  │
│  │     'priority' => 'in:low,medium,high,critical'           │  │
│  │   ];                                                        │  │
│  │ }                                                           │  │
│  └────────────────┬───────────────────────────────────────────┘  │
│                   │ Datos validados                              │
│  ┌────────────────▼───────────────────────────────────────────┐  │
│  │ Task Model (app/Models/Task.php)                           │  │
│  │                                                             │  │
│  │ class Task extends Model                                   │  │
│  │ {                                                           │  │
│  │   protected $fillable = [                                  │  │
│  │     'title', 'description', 'flow_id', 'priority', ...    │  │
│  │   ];                                                        │  │
│  │                                                             │  │
│  │   // Relaciones a otros modelos                            │  │
│  │   public function flow() {                                 │  │
│  │     return $this->belongsTo(Flow::class);                 │  │
│  │   }                                                        │  │
│  │ }                                                           │  │
│  │                                                             │  │
│  │ Task::create() → Eloquent ORM interpreta                  │  │
│  │    ↓                                                        │  │
│  │ INSERT INTO tasks (...) VALUES (...)                      │  │
│  └────────────────┬───────────────────────────────────────────┘  │
│                   │ Ejecuta Query SQL                            │
└───────────────────┼────────────────────────────────────────────┬─┘
                    │                                            │
        ┌───────────▼──────────────┐                            │
        │   MySQL Database          │                            │
        │                           │                            │
        │ INSERT INTO tasks         │                            │
        │ (title, description, ...) │────────────────────────────┘
        │ VALUES ('Mi tarea', ...) │ Retorna ID creada
        │                           │
        └───────────────────────────┘
```

### Flujo Detallado:

```javascript
// 1️⃣ FRONTEND
// TaskModal.vue - Usuario llena formulario y da clic a guardar
const handleCreateTask = async () => {
  const data = {
    title: 'Nueva Tarea',
    description: 'Detalles',
    flow_id: 1,
    priority: 'high'
  }
  const response = await api.post('/tasks', data)
  // Response contiene { id: 5, title: 'Nueva Tarea', ... }
}

// 2️⃣ BACKEND - HTTP Layer
// routes/api.php
Route::post('/tasks', [TaskController::class, 'store'])->middleware('auth:sanctum');

// 3️⃣ BACKEND - Controller
// TaskController.php
public function store(StoreTaskRequest $request)
{
  // StoreTaskRequest validó automáticamente
  $validated = $request->validated(); // ✓ Datos seguros

  $task = Task::create($validated);
  // Eloquent ejecuta: INSERT INTO tasks (...) VALUES (...)

  return response()->json($task, 201);
}

// 4️⃣ BACKEND - Model
// Task.php
class Task extends Model
{
  protected $fillable = ['title', 'description', 'flow_id', 'priority'];

  // Protected contra Mass Assignment
  // Solo permite asignar estos campos
}

// 5️⃣ DATABASE
// MySQL
INSERT INTO tasks (title, description, flow_id, priority, created_at, updated_at)
VALUES ('Nueva Tarea', 'Detalles', 1, 'high', NOW(), NOW());

// 6️⃣ BACKEND - Model obtiene ID
// Eloquent retorna el modelo con ID asignado
// Task { id: 5, title: 'Nueva Tarea', ... }

// 7️⃣ BACKEND - Controller responde
return response()->json($task, 201);
// Status 201 Created
// Body: { id: 5, title: 'Nueva Tarea', ... }

// 8️⃣ FRONTEND - Recibe y actualiza
const response = await api.post('/tasks', data)
const newTask = response.data // { id: 5, ... }
tasks.value.push(newTask) // Actualiza lista local
closeModal() // Cierra modal
showToast('Tarea creada') // Mensaje de éxito
```

---

## 2. RELACIÓN: Modal ↔ Form ↔ API ↔ Store

### Ejemplo: ProgressModal.vue

```
┌─────────────────────────────────────────────────────────────┐
│           ProgressModal.vue (Componente Vue)                │
│                                                              │
│  Props recibidos:                                           │
│  - isOpen: boolean (visible o no)                          │
│  - task: object (la tarea actual)                          │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Template (HTML)                                      │   │
│  │ ┌────────────────────────────────────────────────┐   │   │
│  │ │ <form @submit="handleAddProgress">             │   │   │
│  │ │   <textarea v-model="formData.description">    │   │   │
│  │ │   <input type="file" @change="handleFileSelect"   │   │
│  │ │   <button type="submit" :disabled="loading">  │   │   │
│  │ │ </form>                                        │   │   │
│  │ │                                                 │   │   │
│  │ │ <div v-for="progress in progressList">        │   │   │
│  │ │   {{ progress.created_by?.name }}             │   │   │
│  │ │   {{ formatDateTime(progress.created_at) }}   │   │   │
│  │ │   {{ progress.description }}                  │   │   │
│  │ │   <a v-for="att in progress.attachments">     │   │   │
│  │ │     {{ att.name }}                            │   │   │
│  │ │ </div>                                         │   │   │
│  │ └────────────────────────────────────────────────┘   │   │
│  │                                                         │   │
│  │ Script (lógica)                                        │   │
│  │ ┌────────────────────────────────────────────────┐   │   │
│  │ │ const formData = ref({                         │   │   │
│  │ │   description: '',                             │   │   │
│  │ │   files: []                                    │   │   │
│  │ │ })                                             │   │   │
│  │ │                                                │   │   │
│  │ │ const progressList = ref([])                  │   │   │
│  │ │ const loading = ref(false)                    │   │   │
│  │ │ const error = ref(null)                       │   │   │
│  │ │                                                │   │   │
│  │ │ // Cuando abre el modal                       │   │   │
│  │ │ watch(() => props.isOpen, (newVal) => {      │   │   │
│  │ │   if (newVal) loadProgressList()              │   │   │
│  │ │ })                                             │   │   │
│  │ │                                                │   │   │
│  │ │ // Cargar lista de avances                    │   │   │
│  │ │ const loadProgressList = async () => {        │   │   │
│  │ │   const response = await api.get(             │   │   │
│  │ │     `/tasks/${props.task.id}/progress`        │   │   │
│  │ │   )                                            │   │   │
│  │ │   progressList.value = response.data           │   │   │
│  │ │ }                                              │   │   │
│  │ │                                                │   │   │
│  │ │ // Agregar nuevo avance                       │   │   │
│  │ │ const handleAddProgress = async () => {       │   │   │
│  │ │   loading.value = true                        │   │   │
│  │ │                                                │   │   │
│  │ │   const progressFormData = new FormData()     │   │   │
│  │ │   progressFormData.append('task_id', ...)     │   │   │
│  │ │   progressFormData.append('description', ...) │   │   │
│  │ │   files.forEach(f => progressFormData.append(...))   │   │
│  │ │                                                │   │   │
│  │ │   try {                                        │   │   │
│  │ │     const response = await api.post(          │   │   │
│  │ │       '/progress',                            │   │   │
│  │ │       progressFormData,                       │   │   │
│  │ │       { headers: {...} }                      │   │   │
│  │ │     )                                          │   │   │
│  │ │     progressList.value.unshift(response.data) │   │   │
│  │ │     resetForm()                               │   │   │
│  │ │     emit('saved') // Emite evento al padre    │   │   │
│  │ │   } catch (err) {                             │   │   │
│  │ │     error.value = err.message                 │   │   │
│  │ │   } finally {                                 │   │   │
│  │ │     loading.value = false                     │   │   │
│  │ │   }                                            │   │   │
│  │ │ }                                              │   │   │
│  │ └────────────────────────────────────────────────┘   │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
         │                             │
         │ emit('saved')               │ GET /tasks/{id}/progress
         │                             │ POST /progress
         │                             │
    ┌────▼──────────────────────────────▼──────┐
    │  Parent Component (FlowDetailView.vue)    │
    │                                           │
    │  @saved="handleProgressSaved"             │
    │    → Refrescar datos del flujo            │
    └───────────────────────────────────────────┘
```

---

## 3. RELACIÓN: API Service ↔ HTTP Client ↔ Backend Routes

### Diagrama de Capas

```
┌─────────────────────────────────────────────────────┐
│            FRONTEND LAYER                           │
├─────────────────────────────────────────────────────┤
│                                                      │
│  Vue Component (ProgressModal.vue)                  │
│  - Maneja UI                                        │
│  - Maneja estado local del componente               │
│                                                      │
│  await api.post('/progress', ...)                  │
│         │                                           │
│         ▼                                           │
│  services/api.js (Axios instance)                  │
│  - Configuración: baseURL, timeout, headers        │
│  - Interceptores: token auth, error handling       │
│  - Defaults: Content-Type: application/json        │
│         │                                           │
│         ▼                                           │
│  HTTP POST /api/v1/progress                        │
│  Headers: Authorization: Bearer {token}            │
│           Content-Type: multipart/form-data        │
│  Body: FormData { task_id, description, files }    │
│                                                      │
└─────────────────────────────────────────────────────┘
                     │
                HTTP/HTTPS
                     │
┌─────────────────────────────────────────────────────┐
│            BACKEND LAYER                            │
├─────────────────────────────────────────────────────┤
│                                                      │
│  Nginx/Apache (Web Server)                          │
│  - Recibe HTTP request                              │
│  - Pasa a Laravel                                   │
│         │                                           │
│         ▼                                           │
│  routes/api.php                                    │
│  - Coincide ruta: POST /progress                   │
│  - Ejecuta: ProgressController@store               │
│  - Aplica middleware: auth:sanctum                 │
│         │                                           │
│         ▼                                           │
│  Middleware auth:sanctum                           │
│  - Valida token del header Authorization           │
│  - Si inválido → 401 Unauthorized                  │
│  - Si válido → Continúa                            │
│         │                                           │
│         ▼                                           │
│  ProgressController@store()                        │
│  - Accede a: $request->validated()                │
│  - Crea registro: Progress::create(...)           │
│  - Procesa archivos                                │
│  - Retorna response JSON                           │
│         │                                           │
│         ▼                                           │
│  Models & Database Layer                           │
│  - Progress model maneja lógica                    │
│  - Eloquent ORM ejecuta INSERT SQL                 │
│  - Archivos se guardan en storage                  │
│  - TaskAttachment se crea morphMany                │
│         │                                           │
│         ▼                                           │
│  Response JSON                                      │
│  Status: 201 Created                               │
│  Body: { id, task_id, description, created_by,    │
│          created_at, attachments [...] }           │
│                                                      │
└─────────────────────────────────────────────────────┘
                     │
                HTTP 201 Response
                     │
┌─────────────────────────────────────────────────────┐
│            FRONTEND LAYER (Respuesta)               │
├─────────────────────────────────────────────────────┤
│                                                      │
│  Api.js interceptor recibe response                │
│  - Status 201 ✓                                     │
│  - Headers: Content-Type: application/json         │
│  - Body: objeto Progress con todos los datos       │
│         │                                           │
│         ▼                                           │
│  Vue Component maneja response                     │
│  const response = await api.post(...)              │
│  progressList.value.unshift(response.data)         │
│  Template se re-renderiza con nuevo avance         │
│                                                      │
│  Usuarios ven:                                      │
│  - Nombre del usuario: Daniel Tapia                │
│  - Fecha/hora: 14/01/2026 15:50                    │
│  - Descripción: del textarea                       │
│  - Documentos: archivos adjuntos como links        │
│                                                      │
└─────────────────────────────────────────────────────┘
```

---

## 4. RELACIÓN: Store (Pinia) ↔ Component ↔ API

### Ejemplo: Notificaciones en Tiempo Real

```javascript
// ┌─────────────────────────────────────────┐
// │  stores/notifications.js                │
// │  (Pinia State Management)               │
// └─────────────────────────────────────────┘

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import echo from '@/services/echo'

export const useNotificationStore = defineStore('notifications', () => {

  // STATE
  const notifications = ref([])
  const unreadCount = ref(0)
  const isLoading = ref(false)

  // GETTERS (computed)
  const unreadNotifications = computed(
    () => notifications.value.filter(n => !n.is_read)
  )

  // ACTIONS
  const fetchNotifications = async () => {
    isLoading.value = true
    try {
      // Llamada API al backend
      const response = await api.get('/notifications')
      notifications.value = response.data
      updateUnreadCount()
    } finally {
      isLoading.value = false
    }
  }

  const markAsRead = async (notificationId) => {
    try {
      // Llamada API
      await api.post(`/notifications/${notificationId}/read`)

      // Actualizar estado local
      const notification = notifications.value.find(n => n.id === notificationId)
      if (notification) {
        notification.is_read = true
        updateUnreadCount()
      }
    } catch (error) {
      console.error('Error marking as read:', error)
    }
  }

  const deleteNotification = async (notificationId) => {
    try {
      // Llamada API
      await api.delete(`/notifications/${notificationId}`)

      // Actualizar estado local
      notifications.value = notifications.value.filter(n => n.id !== notificationId)
      updateUnreadCount()
    } catch (error) {
      console.error('Error deleting:', error)
    }
  }

  const updateUnreadCount = () => {
    unreadCount.value = unreadNotifications.value.length
  }

  const subscribeToRealtime = () => {
    // Escuchar eventos WebSocket
    echo.private(`users.${userId}`)
      .listen('NotificationEvent', (data) => {
        // Agregar notificación en tiempo real
        notifications.value.unshift(data.notification)
        updateUnreadCount()
      })
  }

  return {
    notifications,
    unreadCount,
    isLoading,
    unreadNotifications,
    fetchNotifications,
    markAsRead,
    deleteNotification,
    subscribeToRealtime
  }
})

// ┌─────────────────────────────────────────┐
// │  components/NotificationBell.vue         │
// │  (Vue Component)                         │
// └─────────────────────────────────────────┘

<template>
  <div class="notification-bell">
    <!-- Badge con contador -->
    <button @click="showPanel" class="relative">
      🔔
      <span v-if="unreadCount > 0" class="badge">
        {{ unreadCount }}
      </span>
    </button>

    <!-- Panel de notificaciones -->
    <div v-if="isOpen" class="notification-panel">
      <div v-if="isLoading" class="spinner">Cargando...</div>

      <div v-else-if="notifications.length === 0" class="empty">
        No hay notificaciones
      </div>

      <div v-else class="notification-list">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          class="notification-item"
          :class="{ unread: !notification.is_read }"
        >
          <div class="header">
            <h4>{{ notification.title }}</h4>
            <button @click="deleteNotification(notification.id)">✕</button>
          </div>
          <p>{{ notification.message }}</p>
          <small>{{ formatDateTime(notification.created_at) }}</small>
          <button
            v-if="!notification.is_read"
            @click="markAsRead(notification.id)"
            class="read-btn"
          >
            Marcar como leída
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useNotificationStore } from '@/stores/notifications'

const notificationStore = useNotificationStore()

const isOpen = ref(false)

// Obtener datos del store con computed
const notifications = computed(() => notificationStore.notifications)
const unreadCount = computed(() => notificationStore.unreadCount)
const isLoading = computed(() => notificationStore.isLoading)

// Métodos del store
const deleteNotification = (id) => notificationStore.deleteNotification(id)
const markAsRead = (id) => notificationStore.markAsRead(id)

const showPanel = () => {
  isOpen.value = !isOpen.value
}

const formatDateTime = (date) => {
  return new Date(date).toLocaleString('es-ES')
}

// Al montar el componente
onMounted(() => {
  notificationStore.fetchNotifications()
  notificationStore.subscribeToRealtime()
})
</script>
```

### Flujo de Datos:

```
1. Component monta (onMounted)
   ↓
2. Llama notificationStore.fetchNotifications()
   ↓
3. Store hace: await api.get('/notifications')
   ↓
4. Backend retorna lista de notificaciones
   ↓
5. Store actualiza: notifications.value = response.data
   ↓
6. Component accede vía computed: notifications
   ↓
7. Template se re-renderiza con v-for
   ↓
8. Usuario ve lista en NotificationBell
   ↓
9. Backend emite evento WebSocket: NotificationEvent
   ↓
10. Store escucha: echo.private(...).listen(...)
   ↓
11. Store agrega notificación: notifications.value.unshift(...)
   ↓
12. unreadCount computed se actualiza automáticamente
   ↓
13. Badge en el botón muestra nuevos no leídos
   ↓
14. Usuario ve cambio en tiempo real sin recargar
```

---

## 5. RELACIÓN: Model ↔ Relationships ↔ Database

### Ejemplo: Task con múltiples relaciones

```php
// ┌──────────────────────────────────────────┐
// │  app/Models/Task.php                      │
// └──────────────────────────────────────────┘

class Task extends Model
{
    protected $fillable = [
        'title', 'description', 'flow_id', 'parent_task_id',
        'status', 'priority', 'assigned_to', 'created_by'
    ];

    // UNO-A-MUCHOS (Task → Flow)
    // Una tarea pertenece a un flujo
    public function flow()
    {
        return $this->belongsTo(Flow::class);
    }

    // UNO-A-MUCHOS (User → Task como creador)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // UNO-A-MUCHOS (User → Task como asignado)
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // AUTO-RELACIÓN (padre-hijo para subtareas)
    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function children()
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    // UNO-A-MUCHOS (Task → Progress)
    public function progress()
    {
        return $this->hasMany(Progress::class);
    }

    // UNO-A-MUCHOS (Task → TaskDependency)
    public function dependencies()
    {
        return $this->hasMany(TaskDependency::class);
    }

    // UNO-A-MUCHOS (Task → TaskAttachment polymorphic)
    public function attachments()
    {
        return $this->morphMany(TaskAttachment::class, 'attachmentable');
    }
}

// ┌──────────────────────────────────────────┐
// │  Database Structure (MySQL)               │
// └──────────────────────────────────────────┘

/*
tasks table:
├─ id (PK)
├─ title
├─ description
├─ flow_id (FK → flows.id)
├─ parent_task_id (FK → tasks.id) [auto-relación]
├─ status (pending/in_progress/completed/blocked)
├─ priority (low/medium/high/critical)
├─ assigned_to (FK → users.id)
├─ created_by (FK → users.id)
├─ position
├─ created_at
└─ updated_at

flows table:
├─ id (PK)
├─ name
├─ description
├─ status
├─ client_id (FK → clients.id)
├─ created_by (FK → users.id)
└─ ...

progress table:
├─ id (PK)
├─ task_id (FK → tasks.id)
├─ description
├─ created_by (FK → users.id)
└─ created_at

task_dependencies table:
├─ id (PK)
├─ task_id (FK → tasks.id)
├─ depends_on_task_id (FK → tasks.id)
├─ dependency_type
└─ lag_days

task_attachments table (polymorphic):
├─ id (PK)
├─ attachmentable_type (Task|Progress)
├─ attachmentable_id
├─ file_path
└─ name
*/

// ┌──────────────────────────────────────────┐
// │  Using Relationships in Controller        │
// └──────────────────────────────────────────┘

class TaskController extends Controller
{
    public function show(Task $task)
    {
        // Eager loading para evitar N+1 queries
        $task->load([
            'flow',          // 1 query
            'creator',       // 1 query
            'assignee',      // 1 query
            'parent',        // 1 query
            'children',      // 1 query (retorna array)
            'progress',      // 1 query (retorna array)
            'attachments'    // 1 query (retorna array)
        ]);

        return response()->json($task);
    }

    public function getSubtasks(Task $task)
    {
        // Acceder a subtareas
        $subtasks = $task->children; // Retorna Collection

        return response()->json($subtasks);
    }

    public function getProgress(Task $task)
    {
        // Acceder a progreso
        $progressRecords = $task->progress() // Query builder
            ->orderByDesc('created_at')
            ->with('createdBy')
            ->get();

        return response()->json($progressRecords);
    }
}

// ┌──────────────────────────────────────────┐
// │  SQL Queries Ejecutadas                   │
// └──────────────────────────────────────────┘

// Cuando se ejecuta: $task->load(['flow', 'creator', ...])

SELECT * FROM tasks WHERE id = 1; -- 1 query

SELECT * FROM flows WHERE id = (valor de task.flow_id); -- 1 query

SELECT * FROM users WHERE id = (valor de task.created_by); -- 1 query

SELECT * FROM users WHERE id = (valor de task.assigned_to); -- 1 query

SELECT * FROM tasks WHERE parent_task_id = 1; -- 1 query (children)

SELECT * FROM progress WHERE task_id = 1; -- 1 query

SELECT * FROM task_attachments WHERE attachmentable_id = 1 AND attachmentable_type = 'Task'; -- 1 query

// Total: 8 queries optimizadas
// Sin eager loading sería: N+1 queries (muy ineficiente)
```

---

## 6. RELACIÓN: Policy (Autorización) ↔ Controller ↔ Request

### Ejemplo: Autorizar actualización de tarea

```php
// ┌────────────────────────────────────────┐
// │  app/Policies/TaskPolicy.php            │
// └────────────────────────────────────────┘

class TaskPolicy
{
    // Solo el creador o admin pueden actualizar
    public function update(User $user, Task $task): bool
    {
        return $user->is_admin || $user->id === $task->created_by;
    }

    // Solo el asignado puede cambiar estado
    public function changeStatus(User $user, Task $task): bool
    {
        return $user->id === $task->assigned_to;
    }

    // Solo admin puede eliminar
    public function delete(User $user, Task $task): bool
    {
        return $user->is_admin;
    }
}

// ┌────────────────────────────────────────┐
// │  app/Providers/AuthServiceProvider      │
// └────────────────────────────────────────┘

protected $policies = [
    Task::class => TaskPolicy::class,
];

// ┌────────────────────────────────────────┐
// │  app/Http/Controllers/TaskController    │
// └────────────────────────────────────────┘

class TaskController extends Controller
{
    public function update(Request $request, Task $task)
    {
        // Autorizar: Laravel llama TaskPolicy@update
        $this->authorize('update', $task);
        // Si retorna false → 403 Forbidden

        // Si autorización pasó, continuar
        $task->update($request->validated());

        return response()->json($task);
    }

    public function destroy(Task $task)
    {
        // Autorizar
        $this->authorize('delete', $task);

        $task->delete();

        return response()->noContent();
    }
}

// ┌────────────────────────────────────────┐
// │  Flow de Autorización                   │
// └────────────────────────────────────────┘

/*
1. Frontend: PUT /api/v1/tasks/5
   ↓
2. Backend: TaskController@update(5)
   ↓
3. $this->authorize('update', $task)
   ↓
4. Laravel llama: TaskPolicy::update($user, $task)
   ↓
5. Policy evalúa: $user->is_admin || $user->id === $task->created_by
   ↓
6. SI TRUE:
   - Continúa en controller
   - Actualiza tarea
   - Retorna 200 OK
   ↓
7. SI FALSE:
   - Lanza AuthorizationException
   - Retorna 403 Forbidden
   - Mensaje: "No autorizado"
*/
```

---

## 7. RELACIÓN: Event ↔ Listener ↔ Broadcasting

### Ejemplo: Cuando se crea una tarea

```php
// ┌────────────────────────────────────────────┐
// │  app/Events/TaskCreatedEvent.php            │
// └────────────────────────────────────────────┘

class TaskCreatedEvent implements ShouldBroadcast
{
    public function __construct(public Task $task) {}

    public function broadcastOn()
    {
        // Broadcast en canal público del flujo
        return new Channel("flows.{$this->task->flow_id}");
    }

    public function broadcastAs()
    {
        return 'TaskCreatedEvent';
    }
}

// ┌────────────────────────────────────────────┐
// │  app/Http/Controllers/TaskController        │
// └────────────────────────────────────────────┘

public function store(StoreTaskRequest $request)
{
    $task = Task::create($request->validated());

    // Disparar evento
    TaskCreatedEvent::dispatch($task);

    return response()->json($task, 201);
}

// ┌────────────────────────────────────────────┐
// │  Frontend: services/echo.js                │
// └────────────────────────────────────────────┘

// Escuchar evento
echo.channel(`flows.${flowId}`)
    .listen('TaskCreatedEvent', (data) => {
        // data.task contiene la nueva tarea
        console.log('Nueva tarea creada:', data.task)

        // Actualizar estado
        tasksStore.addTask(data.task)

        // UI se re-renderiza automáticamente
    })

// ┌────────────────────────────────────────────┐
// │  Flow Completo                              │
// └────────────────────────────────────────────┘

/*
1. Usuario A abre FlowDetailView del flujo #5
   - Escucha: echo.channel('flows.5').listen(...)

2. Usuario A crea tarea → POST /api/v1/tasks

3. Backend ejecuta:
   - Task::create(...)
   - TaskCreatedEvent::dispatch($task)

4. Laravel Broadcasting:
   - Envía evento a Redis/Pusher
   - Con datos de la tarea

5. Echo WebSocket en cliente:
   - Recibe evento de canal flows.5
   - Llama callback

6. Callback actualiza estado local:
   - Agrega tarea a tasksStore.tasks
   - Computed se actualiza
   - Template se re-renderiza con v-for

7. Usuario A ve nuevatarea aparecida en árbol

8. Usuario B (también en flujo #5):
   - Recibe mismo evento
   - Su vista también se actualiza automáticamente
   - ¡Sin recargar página!
*/
```

---

## Resumen Visual Completo

```
┌─────────────────────────────────────────────────────────────────┐
│                      USUARIO EN BROWSER                         │
└─────────────┬───────────────────────────────────────────────────┘
              │
              │ Interactúa (click, type, submit)
              ▼
┌─────────────────────────────────────────────────────────────────┐
│  Vue Component (ProgressModal.vue)                              │
│  - Template HTML                                                │
│  - Script JavaScript (setup)                                    │
│  - Validación en cliente                                        │
└─────────────┬───────────────────────────────────────────────────┘
              │
              │ await api.post('/progress', data)
              ▼
┌─────────────────────────────────────────────────────────────────┐
│  services/api.js (Axios)                                        │
│  - Agrega headers (Authorization)                               │
│  - Serializa FormData                                           │
└─────────────┬───────────────────────────────────────────────────┘
              │
              │ HTTP POST /api/v1/progress
              ▼
┌─────────────────────────────────────────────────────────────────┐
│  Backend: routes/api.php                                        │
│  - Coincide ruta                                                │
│  - Aplica middleware (auth:sanctum)                             │
└─────────────┬───────────────────────────────────────────────────┘
              │
              │ ProgressController@store()
              ▼
┌─────────────────────────────────────────────────────────────────┐
│  ProgressController                                             │
│  - Recibe request validado                                      │
│  - Llama Progress::create()                                     │
│  - Procesa archivos                                             │
└─────────────┬───────────────────────────────────────────────────┘
              │
              │ Model → Eloquent ORM
              ▼
┌─────────────────────────────────────────────────────────────────┐
│  Progress Model                                                 │
│  - Validaciones                                                 │
│  - Casts                                                        │
│  - Relationships                                                │
│  - Firing Events                                                │
└─────────────┬───────────────────────────────────────────────────┘
              │
              │ SQL INSERT
              ▼
┌─────────────────────────────────────────────────────────────────┐
│  MySQL Database                                                 │
│  - progress table                                               │
│  - task_attachments table                                       │
└─────────────┬───────────────────────────────────────────────────┘
              │
              │ Retorna modelo con ID
              ▼
┌─────────────────────────────────────────────────────────────────┐
│  Backend: Dispara Events                                        │
│  - ProgressCreatedEvent                                         │
└─────────────┬───────────────────────────────────────────────────┘
              │
              │ Broadcast a canal WebSocket
              ▼
┌─────────────────────────────────────────────────────────────────┐
│  Laravel Broadcasting (Redis/Pusher)                            │
└─────────────┬───────────────────────────────────────────────────┘
              │
              │ JSON Response HTTP 201 Created
              ▼
┌─────────────────────────────────────────────────────────────────┐
│  Frontend: recibe response                                      │
│  - progressList.value.unshift(response.data)                   │
│  - resetForm()                                                  │
│  - Emite @saved                                                 │
└─────────────┬───────────────────────────────────────────────────┘
              │
              │ WebSocket Event llega simultáneamente
              ▼
┌─────────────────────────────────────────────────────────────────┐
│  Frontend: echo.listen()                                        │
│  - Notificaciones actualizadas en tiempo real                  │
│  - Otros usuarios en flujo ven cambio automático                │
└─────────────┬───────────────────────────────────────────────────┘
              │
              │ Vue Reactivity
              ▼
┌─────────────────────────────────────────────────────────────────┐
│  Template HTML se re-renderiza                                  │
│  - Nuevo avance aparece en historial                            │
│  - Mostrador de archivo funciona                                │
│  - Nombre de usuario se muestra                                 │
│  - Fecha/hora se formatea                                       │
│  - USUARIO VE CAMBIOS INMEDIATAMENTE                            │
└─────────────────────────────────────────────────────────────────┘
```

---

## Conclusión

Cada componente del sistema está conectado de manera precisa:

1. **Componentes Vue** reciben datos del usuario y los preparan
2. **Services (Axios)** transportan datos al servidor
3. **Controllers** reciben y validan datos
4. **Models** aplican lógica de negocios y relacionan datos
5. **Database** almacena información de forma estructurada
6. **Events** notifican cambios en tiempo real
7. **Broadcasting** actualiza todos los clientes conectados
8. **Stores (Pinia)** mantienen estado central
9. **Componentes** se re-renderizán automáticamente con Reactivity

¡Este flujo es lo que hace que Taskflow sea una aplicación moderna, reactiva y en tiempo real!
