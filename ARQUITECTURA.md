# Arquitectura de Taskflow v1

## Tabla de Contenidos
1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Estructura del Proyecto](#estructura-del-proyecto)
3. [Modelo de Datos](#modelo-de-datos)
4. [Backend (Laravel)](#backend-laravel)
5. [Frontend (Vue 3)](#frontend-vue-3)
6. [Sistema de Tiempo Real](#sistema-de-tiempo-real)
7. [Flujo de Datos](#flujo-de-datos)
8. [Seguridad](#seguridad)

---

## Resumen Ejecutivo

**Taskflow** es un sistema empresarial de gestión de flujos de trabajo construido con:
- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Vue 3 + Vite + Tailwind CSS
- **Base de Datos**: MySQL
- **Tiempo Real**: Laravel Reverb (WebSocket)
- **Autenticación**: Laravel Sanctum (API Tokens)

### Características Principales
✅ Gestión jerárquica de tareas con dependencias
✅ Sistema SLA con alertas y escalamiento automático
✅ Notificaciones en tiempo real vía WebSocket
✅ Plantillas reutilizables de flujos
✅ Control de permisos basado en roles
✅ Auditoría completa de cambios
✅ Exportación de reportes (CSV, PDF)
✅ Múltiples vistas: Lista, Diagrama, Gantt

---

## Estructura del Proyecto

```
Taskflow-v1/
├── taskflow-backend/          # API Laravel 11
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Observers/
│   │   ├── Events/
│   │   ├── Policies/
│   │   └── Providers/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   │   ├── api.php
│   │   ├── flow-builder.php
│   │   └── task-center.php
│   └── config/
│
└── taskflow-frontend/         # SPA Vue 3
    ├── src/
    │   ├── components/        # 18 componentes
    │   ├── views/            # 9 vistas
    │   ├── stores/           # Pinia stores
    │   ├── services/         # API y WebSocket
    │   ├── composables/      # Lógica reutilizable
    │   └── router/
    └── public/
```

---

## Modelo de Datos

### Entidades Principales

#### 1. Users (Usuarios)
```
Campos:
- id, name, email, password
- role: admin | project_manager | pm | user
- timestamps

Relaciones:
- hasMany: Flow (creador)
- hasMany: Flow (responsable)
- hasMany: Task (asignado)
- hasMany: Notification
- hasMany: Progress
```

#### 2. Flows (Flujos/Proyectos)
```
Campos:
- id, name, description
- status: active | paused | completed | cancelled
- template_id, client_id
- created_by, responsible_id, last_updated_by
- progress (0-100%)
- started_at, completed_at
- timestamps

Relaciones:
- belongsTo: Template
- belongsTo: Client
- belongsTo: User (creator)
- belongsTo: User (responsible)
- hasMany: Task
- hasMany: Notification
```

#### 3. Tasks (Tareas)
```
Campos Principales:
- id, title, description, flow_id
- parent_task_id (auto-relación jerárquica)
- assignee_id, created_by, last_updated_by
- status: pending | in_progress | completed | paused | cancelled
- priority: low | medium | high | urgent
- progress (0-100%)

Campos de Dependencias:
- depends_on_task_id
- depends_on_milestone_id
- is_blocked (boolean calculado automáticamente)
- blocked_reason

Campos de Milestone:
- is_milestone
- milestone_auto_complete
- milestone_requires_validation
- milestone_validated_by
- milestone_target_date

Campos de SLA:
- sla_due_date
- sla_breached
- sla_breach_at
- sla_days_overdue
- sla_notified_assignee
- sla_escalated
- sla_notified_at
- sla_escalated_at

Campos de Tiempo:
- estimated_start_at, estimated_end_at
- actual_start_at, actual_end_at

Otros:
- allow_attachments
- notes
- order

Relaciones:
- belongsTo: Flow
- belongsTo: User (assignee)
- belongsTo: Task (parent)
- hasMany: Task (subtasks)
- hasMany: Progress
- hasMany: TaskAttachment
- hasMany: TaskDependency
```

#### 4. Progress (Actualizaciones de Progreso)
```
Campos:
- id, task_id, description
- created_by
- timestamps

Relaciones:
- belongsTo: Task
- belongsTo: User (creator)
- morphMany: TaskAttachment
```

#### 5. TaskDependency (Dependencias M:N)
```
Campos:
- task_id, depends_on_task_id
- dependency_type: finish_to_start | start_to_start |
                   finish_to_finish | start_to_finish
- lag_days

Relaciones:
- belongsTo: Task (task)
- belongsTo: Task (depends_on)
```

#### 6. TaskAttachment (Archivos Adjuntos - Polimórfico)
```
Campos:
- id, attachmentable_id, attachmentable_type
- user_id, name, file_path, file_type, file_size
- timestamps

Relaciones:
- morphTo: Task | Progress
- belongsTo: User
```

#### 7. Notification (Notificaciones)
```
Campos:
- id, user_id, task_id, flow_id
- type: task_assigned | sla_warning | sla_escalation | ...
- title, message
- priority: low | medium | high | urgent
- is_read, read_at
- data (JSON)
- timestamps

Relaciones:
- belongsTo: User
- belongsTo: Task (opcional)
- belongsTo: Flow (opcional)
```

#### 8. Template (Plantillas)
```
Campos:
- id, name, description, version
- is_active, created_by
- config (JSON con estructura de tareas)
- timestamps

Relaciones:
- belongsTo: User (creator)
- hasMany: Flow
```

#### 9. Client (Clientes)
```
Campos:
- id, name, email, phone, address, industry
- status: active | inactive
- sweetcrm_id
- timestamps

Relaciones:
- hasMany: Flow
```

### Diagrama de Relaciones

```
┌──────────┐       ┌──────────┐
│   User   │◄─────►│   Flow   │
└────┬─────┘       └────┬─────┘
     │                  │
     │                  │
     ▼                  ▼
┌──────────┐       ┌──────────┐
│   Task   │       │Template  │
└────┬─────┘       └──────────┘
     │
     ├──► Progress
     ├──► TaskAttachment
     ├──► TaskDependency
     └──► Notification
```

---

## Backend (Laravel)

### Controladores API (13 Total)

#### 1. AuthController
```php
POST   /api/v1/auth/login       - Login con email/password
POST   /api/v1/auth/register    - Registro de usuario
POST   /api/v1/auth/logout      - Cerrar sesión
GET    /api/v1/auth/me          - Usuario actual
```

#### 2. FlowController
```php
GET    /api/v1/flows            - Listar flujos
GET    /api/v1/flows/{id}       - Detalle de flujo
POST   /api/v1/flows            - Crear flujo
PUT    /api/v1/flows/{id}       - Actualizar flujo
DELETE /api/v1/flows/{id}       - Eliminar flujo
```

#### 3. TaskController
```php
GET    /api/v1/tasks            - Listar tareas
GET    /api/v1/tasks/{id}       - Detalle de tarea
POST   /api/v1/tasks            - Crear tarea
PUT    /api/v1/tasks/{id}       - Actualizar tarea
DELETE /api/v1/tasks/{id}       - Eliminar tarea
POST   /api/v1/tasks/reorder    - Reordenar tareas
POST   /api/v1/tasks/{id}/move  - Mover tarea entre flujos
```

#### 4. TaskDependencyController
```php
GET    /api/v1/tasks/{id}/dependencies        - Dependencias de tarea
POST   /api/v1/tasks/{id}/dependencies        - Agregar dependencia
DELETE /api/v1/dependencies/{id}              - Eliminar dependencia
GET    /api/v1/tasks/{id}/check-blocked       - Verificar si está bloqueada
```

#### 5. ProgressController
```php
GET    /api/v1/tasks/{id}/progress    - Listar progreso
POST   /api/v1/tasks/{id}/progress    - Agregar progreso (con archivos)
PUT    /api/v1/progress/{id}          - Actualizar progreso
DELETE /api/v1/progress/{id}          - Eliminar progreso
```

#### 6. NotificationController
```php
GET    /api/v1/notifications              - Listar notificaciones
GET    /api/v1/notifications/stats        - Estadísticas
PUT    /api/v1/notifications/{id}/read    - Marcar como leída
POST   /api/v1/notifications/read-all     - Marcar todas como leídas
DELETE /api/v1/notifications/{id}         - Eliminar notificación
```

#### 7. TemplateController
```php
GET    /api/v1/templates                      - Listar plantillas
POST   /api/v1/templates                      - Crear plantilla
PUT    /api/v1/templates/{id}                 - Actualizar plantilla
DELETE /api/v1/templates/{id}                 - Eliminar plantilla
POST   /api/v1/templates/from-flow/{flowId}  - Crear desde flujo
```

#### 8. ClientController
```php
GET    /api/v1/clients         - Listar clientes
POST   /api/v1/clients         - Crear cliente
PUT    /api/v1/clients/{id}    - Actualizar cliente
DELETE /api/v1/clients/{id}    - Eliminar cliente
```

#### 9. ReportController
```php
GET    /api/v1/reports/stats          - Estadísticas agregadas
GET    /api/v1/reports/analytics      - Análisis de tendencias
GET    /api/v1/reports/export/csv     - Exportar CSV
GET    /api/v1/reports/export/pdf     - Exportar PDF
```

#### 10-13. Módulos Especializados
- **FlowBuilderController**: Operaciones complejas para PM/Admin
- **TaskCenterController**: Dashboard de tareas del usuario
- **UserController**: Gestión de usuarios
- **TaskAttachmentController**: Subida/descarga de archivos

### Servicios de Negocio

#### SlaNotificationService
```php
Métodos:
- notifyAssignee(Task $task)
  → Envía alerta al asignado después de 1 día de atraso

- escalateToSupervisor(Task $task)
  → Escala al supervisor después de 2 días de atraso

- checkAllTasks()
  → Revisa todas las tareas con SLA vencido
```

#### NotificationService
```php
Métodos:
- taskAssigned(Task $task)
  → Notifica cuando se asigna una tarea

- flowAssigned(Flow $flow)
  → Notifica cuando se asigna un flujo

- create(array $data)
  → Crea notificación y la transmite via WebSocket
```

#### ReportService
```php
Métodos:
- getStats(array $filters)
  → Retorna estadísticas agregadas

- exportCsv(array $filters)
  → Genera archivo CSV

- exportPdf(array $filters)
  → Genera archivo PDF
```

### Observers (Automatización)

#### TaskObserver
```php
Hooks:
- saving():
  • Calcula is_blocked basado en dependencias
  • Establece last_updated_by automáticamente
  • Valida reglas de negocio

- created():
  • Envía notificación de asignación
  • Actualiza progreso del flujo padre

- updated():
  • Detecta cambios de asignado → envía notificación
  • Detecta cambio de status → desbloquea tareas dependientes
  • Actualiza progreso del flujo padre
```

#### FlowObserver
```php
Hooks:
- created():
  • Envía notificación al responsable

- updated():
  • Recalcula progreso basado en tareas
```

### Eventos y Broadcasting

#### Eventos que se transmiten via WebSocket

```php
TaskUpdated
├─ Channel: flows.{flowId}
└─ Data: Task completo con relaciones

NotificationSent
├─ Channel: users.{userId}  (privado)
└─ Data: Notification object

SlaBreached
├─ Channel: users.{userId}  (privado)
└─ Data: {task, days_overdue, escalated}

FlowProgressUpdated
├─ Channel: flows.{flowId}
└─ Data: {flow_id, progress}
```

### Políticas de Autorización

#### FlowPolicy
```php
- viewAny(): todos los usuarios autenticados
- view(): todos los usuarios autenticados
- create(): admin, pm, project_manager
- update(): creador o admin
- delete(): solo admin
```

#### TaskPolicy
```php
- viewAny(): todos los usuarios autenticados
- view(): todos los usuarios autenticados
- create(): admin, pm, project_manager
- update(): creador, asignado, o admin
- delete(): solo admin
- execute(): asignado de la tarea
```

---

## Frontend (Vue 3)

### Pinia Stores (Estado Global)

#### auth.js
```javascript
State:
  - user: null | {id, name, email, role}
  - token: string | null
  - isLoading: boolean
  - error: string | null

Getters:
  - isAuthenticated: boolean
  - currentUser: User | null
  - isAdmin: boolean
  - isPM: boolean

Actions:
  - login(credentials)
  - logout()
  - fetchCurrentUser()
  - loadFromStorage()
```

#### notifications.js
```javascript
State:
  - notifications: Notification[]
  - toasts: Toast[]
  - isLoading: boolean

Getters:
  - unreadCount: number
  - unreadNotifications: Notification[]
  - slaNotifications: Notification[]

Actions:
  - fetchNotifications()
  - markAsRead(id)
  - markAllAsRead()
  - addNotification(notification)  // WebSocket
  - showToast(toast)
  - removeToast(id)
  - clearAll()
```

#### theme.js
```javascript
State:
  - isDark: boolean

Actions:
  - toggleTheme()
  - setTheme(isDark)
  - loadTheme()
```

### Componentes Principales

#### Modales (Formularios)
```
TaskModal.vue           - Crear/editar tareas
  • Campos: título, descripción, asignado, prioridad
  • Soporte para milestone/subtareas
  • Selector de dependencias

ProgressModal.vue       - Agregar progreso
  • Textarea para descripción
  • Subida de archivos adjuntos
  • Historial de progreso

FlowModal.vue          - Crear/editar flujos
ClientModal.vue        - Gestión de clientes
TemplateModal.vue      - Gestión de plantillas
```

#### Componentes de Navegación
```
AppNavbar.vue
  • Logo y título
  • Toggle dark/light mode
  • Notification bell con badge
  • User menu (perfil, logout)
  • Links de navegación

NotificationBell.vue
  • Dropdown de notificaciones
  • Contador de no leídas
  • Lista de notificaciones recientes
  • Botón "marcar todas como leídas"
```

#### Componentes de Visualización
```
TaskTreeItem.vue       - Ítem de tarea en árbol jerárquico
  • Expand/collapse subtareas
  • Drag & drop reordering
  • Botones de acción (editar, eliminar)
  • Badges de estado, prioridad, SLA
  • Indicador de tarea bloqueada

FlowDiagram.vue        - Diagrama visual del flujo
TaskGantt.vue          - Vista Gantt de tareas
DependencyManager.vue  - Gestor de dependencias
```

#### Componentes de Notificación
```
NotificationToast.vue  - Toast emergente
  • Auto-remove después de 5s
  • Color según prioridad
  • Clickeable para ir a tarea/flujo
  • Sonido para notificaciones urgentes

NotificationCenter.vue - Centro de notificaciones
  • Lista completa de notificaciones
  • Filtros por tipo/prioridad
  • Historial
```

### Vistas (Páginas)

```
LoginView.vue          - Pantalla de login
DashboardView.vue      - Dashboard principal con estadísticas
FlowsView.vue          - Lista de flujos
FlowDetailView.vue     - Detalle de flujo con 3 vistas:
                         • Lista (árbol de tareas)
                         • Diagrama (visual)
                         • Gantt (timeline)
ClientsView.vue        - Lista de clientes
ClientDetailView.vue   - Detalle de cliente
NotificationsView.vue  - Centro de notificaciones completo
TemplatesView.vue      - Biblioteca de plantillas
ReportsView.vue        - Reportes y exportaciones
```

### Servicios API

#### api.js (Axios Configuration)
```javascript
baseURL: /api/v1
headers: {
  'Content-Type': 'application/json',
  'Authorization': 'Bearer {token}'
}

Interceptors:
  Request: Agregar token automáticamente
  Response: Manejar 401 → redirect a login

Exported APIs:
  - authAPI: login, register, me, logout
  - flowsAPI: CRUD + filters
  - tasksAPI: CRUD + upload
  - progressAPI: CRUD + attachments
  - notificationsAPI: list, read, delete
  - templatesAPI: CRUD
  - clientsAPI: CRUD
  - reportsAPI: stats, export
```

#### echo.js (WebSocket)
```javascript
Inicialización:
  - broadcaster: 'pusher'
  - key: VITE_REVERB_APP_KEY
  - wsHost: VITE_REVERB_HOST
  - wsPort: VITE_REVERB_PORT
  - authEndpoint: /broadcasting/auth

Métodos:
  - initializeEcho(token): Iniciar conexión
  - getEcho(): Obtener instancia
  - disconnectEcho(): Cerrar conexión

Eventos de Conexión:
  - connecting: Log intento
  - connected: Log éxito
  - error: Log error
  - disconnected: Auto-reconnect después de 3s
```

### Composables (Lógica Reutilizable)

#### useRealtime.js
```javascript
Parámetros:
  - channelName: string
  - events: {eventName: handler}

Return:
  - isConnected: Ref<boolean>
  - channel: Ref<Channel>
  - error: Ref<string>
  - connect(): Function
  - disconnect(): Function

Características:
  • Cache global de canales
  • Listeners específicos por componente
  • Auto-disconnect en onUnmounted
  • No destruye el canal, solo los listeners
```

#### useUserNotifications.js
```javascript
Parámetros:
  - userId: number
  - onNotification: Function

Return:
  - isConnected: boolean
  - disconnect: Function

Funcionalidad:
  • Suscribe a canal privado users.{userId}
  • Escucha evento NotificationSent
  • Llama callback con la notificación
```

---

## Sistema de Tiempo Real

### Arquitectura de Broadcasting

```
┌──────────────┐
│   Laravel    │
│   Backend    │
└──────┬───────┘
       │ HTTP POST
       ▼
┌──────────────┐
│   Reverb     │ WebSocket Server
│   (Puerto    │ (Laravel Package)
│   8080)      │
└──────┬───────┘
       │ WebSocket
       ▼
┌──────────────┐
│   Laravel    │
│   Echo       │ JavaScript Client
│   (Frontend) │
└──────────────┘
```

### Configuración Backend

**config/broadcasting.php**
```php
'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
    ],
]
```

**.env**
```
BROADCAST_CONNECTION=reverb
BROADCAST_DRIVER=reverb
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Configuración Frontend

**echo.js**
```javascript
const echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
  wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
  forceTLS: false,
  enabledTransports: ['ws', 'wss'],
  authEndpoint: 'http://localhost/broadcasting/auth',
  auth: {
    headers: {
      Authorization: `Bearer ${token}`
    }
  }
})
```

### Canales y Eventos

#### Canal Público: flows.{flowId}
```javascript
// Suscripción
echo.channel(`flows.${flowId}`)
    .listen('TaskUpdated', (event) => {
        console.log('Tarea actualizada:', event.task)
        // Actualizar lista de tareas
    })
    .listen('FlowProgressUpdated', (event) => {
        console.log('Progreso del flujo:', event.progress)
        // Actualizar barra de progreso
    })
```

#### Canal Privado: users.{userId}
```javascript
// Suscripción (requiere autenticación)
echo.private(`users.${userId}`)
    .listen('NotificationSent', (event) => {
        console.log('Nueva notificación:', event.notification)
        // Agregar a badge y mostrar toast
        notificationsStore.addNotification(event.notification)
        notificationsStore.showToast(event.notification)
    })
    .listen('SlaBreached', (event) => {
        console.log('SLA incumplido:', event)
        // Mostrar alerta urgente
    })
```

### Casos de Uso en Tiempo Real

#### 1. Actualización de Tarea
```
Usuario A edita tarea → TaskController@update
  ↓
TaskObserver@updated dispara evento TaskUpdated
  ↓
Reverb transmite a canal flows.{flowId}
  ↓
Usuario B (viendo mismo flujo) recibe evento
  ↓
Frontend actualiza lista sin refresh
```

#### 2. Notificación de Asignación
```
PM asigna tarea a Usuario → TaskController@store
  ↓
TaskObserver@created crea Notification
  ↓
Evento NotificationSent broadcast a users.{userId}
  ↓
Usuario recibe notificación en tiempo real
  ↓
Badge actualiza contador + Toast aparece
```

#### 3. Alerta SLA
```
Scheduler ejecuta SlaNotificationService
  ↓
Detecta tarea con 1 día de atraso
  ↓
Crea Notification con priority=urgent
  ↓
Broadcast NotificationSent a users.{assigneeId}
  ↓
Usuario recibe alerta + sonido de emergencia
```

---

## Flujo de Datos

### Ejemplo 1: Crear una Tarea

```
┌─────────────┐
│   Usuario   │
│  (Frontend) │
└──────┬──────┘
       │ 1. Completa TaskModal
       │ 2. Click "Guardar"
       ▼
┌─────────────┐
│   Vue API   │
│  Service    │
└──────┬──────┘
       │ 3. POST /api/v1/tasks
       ▼
┌─────────────┐
│   Laravel   │
│  Middleware │ auth:sanctum
└──────┬──────┘
       │ 4. Valida token
       ▼
┌─────────────┐
│Task         │
│Controller   │
│@store       │
└──────┬──────┘
       │ 5. Valida datos (FormRequest)
       │ 6. Task::create()
       ▼
┌─────────────┐
│Task         │
│Observer     │
│@saving      │
└──────┬──────┘
       │ 7. Calcula is_blocked
       │ 8. Set last_updated_by
       ▼
┌─────────────┐
│  Database   │
│   INSERT    │
└──────┬──────┘
       │ 9. Tarea guardada
       ▼
┌─────────────┐
│Task         │
│Observer     │
│@created     │
└──────┬──────┘
       │ 10. Crear Notification
       │ 11. Broadcast TaskUpdated
       ▼
┌─────────────┐
│   Reverb    │
│  WebSocket  │
└──────┬──────┘
       │ 12. Envía a clientes suscritos
       ▼
┌─────────────┐
│  Frontend   │
│  Echo       │
└──────┬──────┘
       │ 13. Recibe evento
       │ 14. Actualiza store
       │ 15. Re-render componente
       ▼
┌─────────────┐
│   Usuario   │
│ Ve la tarea │
│sin refresh  │
└─────────────┘
```

### Ejemplo 2: Completar Tarea y Desbloquear Dependientes

```
Usuario marca tarea como "completada"
  ↓
PUT /api/v1/tasks/{id} {status: 'completed'}
  ↓
TaskController@update
  ↓
Task::update(['status' => 'completed'])
  ↓
TaskObserver@updated detecta cambio de status
  ↓
Busca tareas que dependen de esta (TaskDependency)
  ↓
Para cada tarea dependiente:
  • Recalcula is_blocked con checkIsBlocked()
  • Si ya no está bloqueada:
    - is_blocked = false
    - blocked_reason = null
    - Crear notificación "Tarea desbloqueada"
    - Broadcast TaskUpdated
  ↓
Frontend recibe eventos en tiempo real
  ↓
Actualiza is_blocked en el árbol de tareas
  ↓
Usuario ve tareas desbloqueadas sin refresh
```

---

## Seguridad

### Autenticación

**Laravel Sanctum (Token-Based)**
```php
1. Usuario envía email + password
2. Backend valida credenciales
3. Genera token: $user->createToken('auth_token')
4. Frontend guarda token en localStorage
5. Todas las requests incluyen:
   Authorization: Bearer {token}
6. Middleware auth:sanctum valida token
7. Request->user() disponible en controlador
```

**Protección de Rutas**
```php
// routes/api.php
Route::group(['middleware' => 'auth:sanctum'], function () {
    // Todas las rutas requieren autenticación
});
```

### Autorización

**Policies (Control Granular)**
```php
// FlowController@destroy
public function destroy(Flow $flow) {
    // Verifica FlowPolicy@delete
    $this->authorize('delete', $flow);

    $flow->delete();
    return response()->json(['message' => 'Flujo eliminado']);
}

// FlowPolicy
public function delete(User $user, Flow $flow) {
    // Solo admins pueden eliminar
    return $user->role === 'admin';
}
```

**Role-Based Access Control**
```php
Roles:
  - admin: Acceso total
  - project_manager: Gestión de flujos y tareas
  - pm: Gestión de flujos y tareas
  - user: Solo ejecutar tareas asignadas

Implementación:
  - Middleware checkRole
  - Policies por recurso
  - Guards en frontend (router)
```

### Validación de Entrada

**Form Requests**
```php
// StoreTaskRequest
public function rules() {
    return [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'assignee_id' => 'required|exists:users,id',
        'status' => 'in:pending,in_progress,completed,paused,cancelled',
        'priority' => 'in:low,medium,high,urgent',
        'estimated_start_at' => 'required|date',
        'estimated_end_at' => 'required|date|after:estimated_start_at',
    ];
}
```

### Protección CSRF & CORS

**CORS (Cross-Origin Resource Sharing)**
```php
// config/cors.php
'allowed_origins' => [
    env('FRONTEND_URL', 'http://localhost:5173')
],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

### Subida de Archivos Segura

**Validación de Archivos**
```php
$request->validate([
    'file' => 'required|file|mimes:pdf,doc,docx,jpg,png|max:10240'
]);

// Guardar fuera del webroot
$path = $request->file('file')->store('attachments', 'private');
```

**Descarga Controlada**
```php
public function download(TaskAttachment $attachment) {
    $this->authorize('view', $attachment->task);

    return Storage::disk('private')->download($attachment->file_path);
}
```

### Auditoría

**OwenIt/Auditing Package**
```php
// Todos los modelos tienen auditoría automática
use OwenIt\Auditing\Contracts\Auditable;

class Task extends Model implements Auditable {
    use \OwenIt\Auditing\Auditable;
}

// Historial de cambios
$task->audits; // Todos los cambios registrados
```

---

## Conclusión

Taskflow es un sistema empresarial robusto con:

✅ **Arquitectura limpia**: Separación clara de responsabilidades
✅ **Tiempo real**: WebSocket con Reverb para actualizaciones instantáneas
✅ **Automatización inteligente**: Observers manejan lógica de negocio
✅ **Seguridad robusta**: Sanctum + Policies + Validación exhaustiva
✅ **Escalabilidad**: Diseño modular y patrones enterprise
✅ **UX moderna**: Vue 3 con Composition API + Pinia
✅ **Auditoría completa**: Registro automático de todos los cambios

El sistema está listo para producción y puede escalar horizontalmente agregando más servidores Reverb con Redis como backend de broadcasting.
