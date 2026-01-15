# 🏗️ Taskflow - Documentación Completa de Arquitectura y Funcionamiento

## 📋 Tabla de Contenidos
1. [Visión General](#visión-general)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Backend - Laravel](#backend---laravel)
4. [Frontend - Vue 3](#frontend---vue-3)
5. [Flujo de Datos](#flujo-de-datos)
6. [Módulos y Funcionalidades](#módulos-y-funcionalidades)
7. [Base de Datos](#base-de-datos)
8. [Guía de Desarrollo](#guía-de-desarrollo)

---

## Visión General

**Taskflow** es un sistema integral de gestión de flujos de trabajo empresariales diseñado para TNA Group. Permite:
- Crear flujos de trabajo automatizados
- Gestionar tareas jerárquicas y dependencias
- Asignar trabajo a usuarios
- Rastrear progreso en tiempo real
- Generar reportes y análisis
- Manejar SLA y alertas
- Integración con clientes externos

### Tecnologías Principales
- **Backend:** Laravel 11 (PHP) + MySQL
- **Frontend:** Vue 3 + Vite + Tailwind CSS
- **Autenticación:** Sanctum (JWT)
- **Real-time:** Laravel Echo + Broadcasting
- **Estado:** Pinia (Vue)

---

## Arquitectura del Sistema

### Diagrama de Alto Nivel

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENTE FINAL (Browser)                  │
│                      (Vue 3 + Tailwind CSS)                     │
└────────────────────────────┬────────────────────────────────────┘
                             │
                    HTTP/WebSocket
                             │
┌────────────────────────────▼────────────────────────────────────┐
│                    API REST (Laravel 11)                         │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │            Controllers (Api Controllers)                 │   │
│  │  - AuthController      - FlowController                  │   │
│  │  - TaskController      - ClientController               │   │
│  │  - ProgressController  - NotificationController         │   │
│  │  - TemplateController  - ReportController               │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │          Business Logic (Models & Services)              │   │
│  │  - User, Flow, Task, Progress, Notification              │   │
│  │  - Services: ReportService, DashboardService, etc        │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │         Events & Broadcasting (Real-time Updates)        │   │
│  │  - TaskCreatedEvent, TaskUpdatedEvent, etc               │   │
│  │  - WebSocket Broadcasting via Laravel Echo              │   │
│  └──────────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────────┘
                             │
                        Eloquent ORM
                             │
┌────────────────────────────▼────────────────────────────────────┐
│                    MySQL Database                               │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Tables: users, flows, tasks, progress, clients,         │   │
│  │          notifications, templates, task_attachments,     │   │
│  │          task_dependencies, audits, sla_rules            │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Backend - Laravel

### 1. Estructura de Directorios

```
taskflow-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/               # RESTful Controllers
│   │   │       ├── AuthController
│   │   │       ├── FlowController
│   │   │       ├── TaskController
│   │   │       ├── ProgressController
│   │   │       ├── ClientController
│   │   │       ├── TemplateController
│   │   │       ├── NotificationController
│   │   │       ├── ReportController
│   │   │       └── ... (más controllers)
│   │   ├── Middleware/             # Middleware (auth, cors, etc)
│   │   └── Requests/               # Form Requests (validación)
│   ├── Models/                     # Modelos Eloquent
│   │   ├── User.php
│   │   ├── Flow.php
│   │   ├── Task.php
│   │   ├── Progress.php
│   │   ├── Notification.php
│   │   ├── Client.php
│   │   ├── Template.php
│   │   ├── TaskDependency.php
│   │   ├── TaskAttachment.php
│   │   └── ... (más modelos)
│   ├── Services/                   # Lógica de negocios
│   │   ├── DashboardService.php
│   │   ├── ReportService.php
│   │   ├── FlowService.php
│   │   └── ... (más servicios)
│   ├── Events/                     # Eventos Laravel
│   ├── Observers/                  # Observers para modelos
│   ├── Policies/                   # Autorización (Policy)
│   └── Providers/                  # Service Providers
├── database/
│   ├── migrations/                 # Cambios de BD
│   ├── seeders/                    # Datos iniciales
│   └── factories/                  # Generadores de datos fake
├── routes/
│   ├── api.php                     # Rutas API principales
│   ├── flow-builder.php            # Rutas del módulo PM/Admin
│   └── task-center.php             # Rutas del módulo Usuario
├── config/                         # Archivos de configuración
├── storage/logs/                   # Logs de aplicación
└── tests/                          # Tests unitarios
```

### 2. Modelos Principales (Eloquent)

#### **User** - Usuarios del Sistema
```php
// app/Models/User.php
- id, name, email, password, role
- Relaciones:
  * flows() -> Many flows created by user
  * tasks() -> Many tasks assigned to user
  * notifications() -> Many notifications for user
  * createdProgress() -> Progress records created by user
```

#### **Flow** - Flujos de Trabajo
```php
// app/Models/Flow.php
- id, name, description, status (active/paused/completed)
- template_id, client_id, created_by
- progress (0-100%), due_date, completion_date
- Relaciones:
  * template() -> Belongs to Template
  * client() -> Belongs to Client
  * tasks() -> Has many Tasks
  * creator() -> Belongs to User
  * notifications() -> Has many Notifications
```

#### **Task** - Tareas Individuales
```php
// app/Models/Task.php
- id, title, description, flow_id
- parent_task_id (para subtareas)
- status (pending/in_progress/completed/blocked)
- priority (low/medium/high/critical)
- assigned_to (user_id), created_by
- SLA fields: sla_hours, sla_warning_hours, sla_critical_hours
- position (orden en la lista)
- Relaciones:
  * flow() -> Belongs to Flow
  * parent() -> Self relationship (parent task)
  * children() -> Self relationship (subtasks)
  * assignee() -> Belongs to User
  * creator() -> Belongs to User
  * dependencies() -> Has many TaskDependency
  * attachments() -> Has many TaskAttachment
  * progress() -> Has many Progress
  * notes() -> Has many Note
```

#### **Progress** - Registros de Avance
```php
// app/Models/Progress.php
- id, task_id, description
- created_by (user_id), created_at, updated_at
- Relaciones:
  * task() -> Belongs to Task
  * createdBy() -> Belongs to User
  * attachments() -> Morph many TaskAttachment
```

#### **TaskDependency** - Dependencias entre Tareas
```php
// app/Models/TaskDependency.php
- id, task_id, depends_on_task_id
- dependency_type (FS/SS/FF/SF)
  * FS = Finish-to-Start (tarea A debe terminar antes que B inicie)
  * SS = Start-to-Start (A y B inician juntas)
  * FF = Finish-to-Finish (A y B terminan juntas)
  * SF = Start-to-Finish (B debe terminar antes que A inicie)
- lag_days (días de retraso permitidos)
- Relaciones:
  * task() -> Belongs to Task
  * dependsOn() -> Belongs to Task
```

#### **Notification** - Notificaciones del Sistema
```php
// app/Models/Notification.php
- id, user_id, type (sla_warning/task_created/task_assigned/etc)
- title, message, priority
- is_read, data (JSON), created_at
- Relaciones:
  * user() -> Belongs to User
```

#### **Client** - Clientes
```php
// app/Models/Client.php
- id, name, email, phone, industry, website
- status (active/inactive), sweetcrm_id
- Relaciones:
  * flows() -> Has many Flow
  * contacts() -> Custom relationship
```

#### **Template** - Plantillas de Flujos
```php
// app/Models/Template.php
- id, name, description, version
- config (JSON con estructura de flujo)
- is_active, created_by
- Relaciones:
  * flows() -> Has many Flow
  * creator() -> Belongs to User
```

### 3. Controllers (API)

Los controllers siguen el patrón RESTful estándar:

#### **AuthController** - Autenticación
```
POST   /api/v1/auth/login        - Login (email, password)
POST   /api/v1/auth/register     - Registro (name, email, password)
POST   /api/v1/auth/logout       - Logout
GET    /api/v1/auth/me           - Datos del usuario actual
```

#### **FlowController** - Gestión de Flujos
```
GET    /api/v1/flows             - Listar todos los flujos
POST   /api/v1/flows             - Crear nuevo flujo
GET    /api/v1/flows/{id}        - Obtener detalle de flujo
PUT    /api/v1/flows/{id}        - Actualizar flujo
DELETE /api/v1/flows/{id}        - Eliminar flujo
POST   /api/v1/flows/{id}/pause  - Pausar flujo
POST   /api/v1/flows/{id}/resume - Reanudar flujo
```

#### **TaskController** - Gestión de Tareas
```
GET    /api/v1/tasks             - Listar tareas
POST   /api/v1/tasks             - Crear tarea
GET    /api/v1/tasks/{id}        - Obtener detalle
PUT    /api/v1/tasks/{id}        - Actualizar tarea
DELETE /api/v1/tasks/{id}        - Eliminar tarea
POST   /api/v1/tasks/reorder     - Reordenar tareas
POST   /api/v1/tasks/{id}/move   - Mover tarea a otro flujo
```

#### **ProgressController** - Registro de Avances
```
GET    /api/v1/tasks/{taskId}/progress   - Listar avances de tarea
POST   /api/v1/progress                   - Crear nuevo avance
GET    /api/v1/progress/{id}              - Obtener detalle
PUT    /api/v1/progress/{id}              - Actualizar avance
DELETE /api/v1/progress/{id}              - Eliminar avance
```

#### **ClientController** - Gestión de Clientes
```
GET    /api/v1/clients           - Listar clientes
POST   /api/v1/clients           - Crear cliente
GET    /api/v1/clients/{id}      - Obtener detalle
PUT    /api/v1/clients/{id}      - Actualizar cliente
DELETE /api/v1/clients/{id}      - Eliminar cliente
```

#### **NotificationController** - Notificaciones
```
GET    /api/v1/notifications     - Listar notificaciones
POST   /api/v1/notifications/{id}/read    - Marcar como leída
DELETE /api/v1/notifications/{id}         - Eliminar
GET    /api/v1/notifications/stats        - Estadísticas
```

#### **ReportController** - Reportes
```
GET    /api/v1/reports/dashboard          - Datos del dashboard
GET    /api/v1/reports/tasks-by-status    - Tareas por estado
GET    /api/v1/reports/export-csv         - Exportar CSV
GET    /api/v1/reports/export-pdf         - Exportar PDF
GET    /api/v1/reports/sla-breaches       - Incumplimientos SLA
```

### 4. Flujo de Autenticación

```
1. Usuario ingresa credenciales en el frontend
   ↓
2. Frontend hace POST a /api/v1/auth/login
   ↓
3. Backend valida y genera token Sanctum/JWT
   ↓
4. Frontend almacena token en localStorage/sessionStorage
   ↓
5. Todos los requests posteriores incluyen: Authorization: Bearer {token}
   ↓
6. Middleware auth:sanctum valida el token
   ↓
7. Request se ejecuta si es válido, si no → 401 Unauthorized
```

### 5. Middleware y Autenticación

```php
// Middleware disponibles en routes/api.php:
- auth:sanctum              // Autenticación requerida
- cors                      // CORS habilitado
- api                       // Rate limiting
- verified                  // Email verificado (si aplica)

// Policies (Autorización):
- Cada modelo tiene Policy que define quién puede:
  * view (ver)
  * create (crear)
  * update (actualizar)
  * delete (eliminar)

Ejemplo: Solo el creador o admin puede actualizar un Flow
```

---

## Frontend - Vue 3

### 1. Estructura de Directorios

```
taskflow-frontend/src/
├── components/              # Componentes reutilizables
│   ├── ProgressModal.vue
│   ├── TaskModal.vue
│   ├── FlowModal.vue
│   ├── ClientModal.vue
│   ├── AppNavbar.vue
│   ├── TaskTreeItem.vue
│   ├── NotificationBell.vue
│   └── ... (17 componentes)
├── views/                   # Páginas principales
│   ├── DashboardView.vue
│   ├── FlowsView.vue
│   ├── FlowDetailView.vue
│   ├── ClientsView.vue
│   ├── NotificationsView.vue
│   ├── ReportsView.vue
│   ├── TemplatesView.vue
│   ├── LoginView.vue
│   └── ... (9 vistas)
├── stores/                  # Pinia State Management
│   ├── auth.js             # Estado de autenticación
│   ├── notifications.js    # Estado de notificaciones
│   ├── theme.js            # Estado del tema
│   └── counter.js          # Demo/utility
├── services/                # Servicios (API calls)
│   ├── api.js              # Configuración de Axios
│   ├── echo.js             # Real-time (Echo/WebSocket)
│   ├── ClientService.js
│   └── reports.js
├── router/
│   └── index.js            # Definición de rutas
├── composables/            # Vue composables (lógica reutilizable)
│   ├── useDragAndDrop.js
│   └── useRealtime.js
├── modules/                # Módulos grandes
│   ├── flow-builder/       # Módulo PM/Admin
│   │   ├── components/
│   │   ├── views/
│   │   └── composables/
│   └── task-center/        # Módulo Usuario
│       ├── components/
│       ├── views/
│       └── composables/
├── App.vue                 # Componente raíz
├── main.js                 # Entry point
└── index.css              # Estilos globales (Tailwind)
```

### 2. Componentes Principales

#### **AppNavbar.vue** - Barra de Navegación
- Menú de navegación
- Logo y título
- Selector de tema (dark/light)
- Botón de notificaciones
- Menú de usuario (perfil, logout)

#### **ProgressModal.vue** - Modal de Avances
- Formulario para agregar progreso
- Textarea para descripción
- File upload para documentos
- Historial de avances con creador y fecha
- Mostrar documentos adjuntos

#### **TaskModal.vue** - Modal de Tareas
- Crear/editar tareas
- Asignar a usuario
- Establecer prioridad
- Configurar SLA
- Agregar descripción

#### **FlowModal.vue** - Modal de Flujos
- Crear/editar flujos
- Seleccionar template
- Asignar cliente
- Configurar detalles

#### **TaskTreeItem.vue** - Item del Árbol de Tareas
- Renderiza una tarea en el árbol jerárquico
- Muestra subtareas
- Botones de acción (editar, eliminar, expandir)
- Indicadores de estado y SLA

### 3. Vistas Principales

#### **DashboardView.vue** - Panel de Control
```
Muestra:
- Resumen de flujos (activos, completados, pausados)
- Tareas por estado (pendiente, en progreso, completado, bloqueado)
- Tareas urgentes (SLA crítico)
- Últimas actividades
- Gráficos de tendencias
```

#### **FlowsView.vue** - Lista de Flujos
```
Características:
- Listar todos los flujos
- Filtrar por estado, cliente, fecha
- Búsqueda por nombre
- Crear nuevo flujo
- Ver progreso (barra de progreso)
- Editar/eliminar flujos
```

#### **FlowDetailView.vue** - Detalle de Flujo
```
Características:
- Árbol jerárquico de tareas
- Crear/editar/eliminar tareas
- Arrastrar y soltar para reordenar
- Ver dependencias
- Expandir/contraer subtareas
- Modales para detalles
- Panel de propiedades de tarea
```

#### **ClientsView.vue** - Gestión de Clientes
```
Características:
- Listar clientes
- Ver flujos por cliente
- Crear/editar/eliminar clientes
- Filtrar y buscar
```

#### **NotificationsView.vue** - Centro de Notificaciones
```
Características:
- Listar notificaciones
- Filtrar por tipo y estado
- Marcar como leída/no leída
- Eliminar notificaciones
- Real-time updates (WebSocket)
```

#### **ReportsView.vue** - Reportes y Análisis
```
Características:
- Dashboard con gráficos
- Estadísticas de tareas
- Cumplimiento de SLA
- Exportar CSV/PDF
- Filtros personalizados
```

### 4. Pinia Stores (Estado Global)

#### **auth.js** - Autenticación
```javascript
State:
- token: string (JWT token)
- user: object (datos del usuario)
- isAuthenticated: boolean

Actions:
- login(email, password)
- register(name, email, password)
- logout()
- fetchUser()
```

#### **notifications.js** - Notificaciones
```javascript
State:
- notifications: array
- unreadCount: number
- isLoading: boolean

Actions:
- fetchNotifications()
- markAsRead(id)
- deleteNotification(id)
- subscribeToRealtime()

Getters:
- unreadNotifications
- notificationsByType
```

#### **theme.js** - Tema
```javascript
State:
- isDark: boolean

Actions:
- toggleTheme()
- setTheme(dark: boolean)

Getters:
- theme: 'dark' | 'light'
```

### 5. Router Vue

```javascript
// src/router/index.js
Rutas:
- / -> DashboardView
- /login -> LoginView
- /flows -> FlowsView
- /flows/:id -> FlowDetailView
- /clients -> ClientsView
- /clients/:id -> ClientDetailView
- /notifications -> NotificationsView
- /reports -> ReportsView
- /templates -> TemplatesView

Meta:
- requiresAuth: true (para rutas protegidas)
- role: 'admin'|'user'|'pm' (para control de acceso)
```

### 6. Servicios (API)

#### **api.js** - Axios Configuration
```javascript
// Configuración global de Axios
- Base URL: http://localhost:8000/api/v1
- Timeout: 30000ms
- Headers default: Content-Type, Authorization
- Interceptores para token y refresh
```

#### **echo.js** - Real-time Communication
```javascript
// Laravel Echo + Pusher/local broadcast
- Escucha eventos de servidor
- Actualiza estado en tiempo real
- Canales por usuario/flujo/tarea
```

---

## Flujo de Datos

### Ejemplo: Crear una Nueva Tarea

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. FRONTEND - Usuario interactúa                                │
│    - Usuario abre FlowDetailView                                │
│    - Hace clic en "Nueva Tarea"                                 │
│    - Se abre TaskModal.vue                                      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. FRONTEND - Recolecta datos                                   │
│    - Usuario llena formulario:                                  │
│      * title, description, priority, assigned_to, sla_hours    │
│    - Valida datos en el cliente                                 │
│    - Prepara payload JSON                                       │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. FRONTEND - HTTP Request                                      │
│    - POST /api/v1/tasks                                         │
│    - Headers: Authorization: Bearer {token}                     │
│    - Body: { title, description, priority, ... }               │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. BACKEND - Recibe Request                                     │
│    - TaskController@store() intercepta request                  │
│    - Valida token con middleware auth:sanctum                  │
│    - Valida datos con FormRequest rules                         │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 5. BACKEND - Procesa lógica                                     │
│    - TaskController::store()                                    │
│    - Crea instancia Task                                        │
│    - Asigna valores: title, description, flow_id, etc          │
│    - $task->save() → Eloquent inserta en BD                     │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 6. BACKEND - Dispara evento                                     │
│    - TaskCreatedEvent::dispatch($task)                          │
│    - Event Listener procesa:                                    │
│      * Crea notificaciones para usuarios asignados              │
│      * Emite evento WebSocket                                   │
│      * Actualiza audit log                                      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 7. BACKEND - HTTP Response                                      │
│    - Retorna 201 Created                                        │
│    - Body: { id, title, description, flow_id, ... }            │
│    - Con toda la tarea creada                                   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 8. FRONTEND - Procesa Response                                  │
│    - Recibe tarea con todos los datos                           │
│    - Actualiza estado Pinia (si lo usa)                         │
│    - Actualiza vista (TaskTreeItem se re-renderiza)             │
│    - Cierra modal                                               │
│    - Muestra toast "Tarea creada exitosamente"                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 9. FRONTEND - Real-time Update (WebSocket)                      │
│    - Echo recibe evento TaskCreatedEvent desde servidor         │
│    - Todos los usuarios viendo este flujo reciben evento        │
│    - Actualizan automáticamente su vista sin recargar           │
└─────────────────────────────────────────────────────────────────┘
```

### Ejemplo: Registrar un Avance de Tarea

```
Frontend (ProgressModal.vue):
1. Usuario abre modal de avance
2. Escribe descripción
3. (Opcional) Adjunta documentos
4. Hace clic en "Agregar Avance"
   ↓
5. Frontend valida descripción (obligatoria)
6. Crea FormData con: task_id, description, files[]
7. POST /api/v1/progress con multipart/form-data
   ↓
Backend (ProgressController):
8. store() recibe request
9. Valida: task_id existe, description requerida
10. Crea Progress record:
    - task_id: del request
    - description: del request
    - created_by: auth()->id() (automático)
    - created_at: ahora (automático)
   ↓
11. Procesa archivos adjuntos:
    - Loop por cada file en request
    - Guarda en storage
    - Crea TaskAttachment morphMany record
   ↓
12. Retorna 201 con Progress + createdBy + attachments
   ↓
Frontend:
13. Recibe respuesta
14. Agrega avance al inicio del historial
15. Historial muestra:
    - "Daniel Tapia • 14/01/2026 15:50"
    - Descripción del avance
    - Documentos adjuntos como links
16. Limpia formulario
17. Emite evento @saved para refrescar datos
```

---

## Módulos y Funcionalidades

### 1. Flow Builder (PM/Admin)

**Propósito:** Diseñar y configurar flujos de trabajo

**Rutas:**
```
GET  /flow-builder              - Vista principal
POST /flows                     - Crear flujo
PUT  /flows/{id}               - Editar flujo
DELETE /flows/{id}             - Eliminar flujo
POST /flows/{id}/pause         - Pausar ejecución
POST /flows/{id}/resume        - Reanudar ejecución
```

**Características:**
- Diseñador visual de flujos
- Crear tareas jerárquicas
- Configurar dependencias
- Definir SLA
- Asignar recursos
- Vista previa
- Versioning

### 2. Task Center (Usuario)

**Propósito:** Ejecutar tareas asignadas

**Rutas:**
```
GET  /task-center                    - Mi dashboard
GET  /task-center/my-tasks          - Mis tareas
GET  /task-center/tasks/{id}        - Detalle de tarea
POST /task-center/tasks/{id}/update - Actualizar estado
POST /progress                       - Registrar avance
```

**Características:**
- Ver tareas asignadas
- Cambiar estado de tarea
- Registrar progreso
- Adjuntar documentos
- Ver historial de avances
- Recibir notificaciones

### 3. Real-time Features

```javascript
// WebSocket channels via Echo
Channel: flows.{flowId}
  Event: TaskCreatedEvent
  Event: TaskUpdatedEvent
  Event: TaskDeletedEvent
  Event: ProgressCreatedEvent

Channel: users.{userId}
  Event: NotificationEvent
  Event: TaskAssignedEvent

// Escuchadores en el frontend:
echo.channel(`flows.${flowId}`)
    .listen('TaskCreatedEvent', (data) => {
      // Actualizar árbol de tareas en tiempo real
    })

echo.private(`users.${userId}`)
    .listen('NotificationEvent', (data) => {
      // Agregar notificación al store
      // Actualizar contador de no leídas
    })
```

---

## Base de Datos

### Diagrama de Relaciones

```
users
  ├─ flows (creador)
  ├─ tasks (asignado)
  ├─ progress (creador)
  └─ notifications

flows
  ├─ template_id → templates
  ├─ client_id → clients
  ├─ created_by → users
  ├─ tasks
  └─ notifications

tasks
  ├─ flow_id → flows
  ├─ parent_task_id → tasks (self-join para subtareas)
  ├─ assigned_to → users
  ├─ created_by → users
  ├─ task_dependencies
  ├─ task_attachments
  ├─ progress
  └─ notes

progress
  ├─ task_id → tasks
  ├─ created_by → users
  └─ task_attachments (morph)

task_attachments
  ├─ attachmentable_id (task_id o progress_id)
  ├─ attachmentable_type (Task o Progress)
  └─ file_path

task_dependencies
  ├─ task_id → tasks
  ├─ depends_on_task_id → tasks
  └─ dependency_type (FS/SS/FF/SF)

clients
  ├─ flows
  └─ contacts

templates
  ├─ flows
  ├─ created_by → users
  └─ config (JSON)

notifications
  ├─ user_id → users
  ├─ type (enum)
  └─ data (JSON)
```

### Tabla de Columnas Principales

```sql
-- users
id, name, email, password, role (admin/pm/user), email_verified_at, created_at

-- flows
id, name, description, status (active/paused/completed),
template_id, client_id, created_by, progress (%), due_date,
completion_date, created_at, updated_at

-- tasks
id, title, description, flow_id, parent_task_id, status (pending/in_progress/completed/blocked),
priority (low/medium/high/critical), assigned_to, created_by,
sla_hours, sla_warning_hours, sla_critical_hours,
position (orden), created_at, updated_at

-- progress
id, task_id, description, created_by, created_at, updated_at

-- task_dependencies
id, task_id, depends_on_task_id, dependency_type (FS/SS/FF/SF),
lag_days, created_at, updated_at

-- notifications
id, user_id, type (enum), title, message, priority (low/medium/high),
is_read, data (JSON), created_at, updated_at

-- clients
id, name, email, phone, industry, website, status (active/inactive),
sweetcrm_id, created_at, updated_at
```

---

## Guía de Desarrollo

### 1. Configuración Local

```bash
# Backend
cd taskflow-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve

# Frontend
cd taskflow-frontend
npm install
npm run dev
```

### 2. Crear una Nueva Funcionalidad

#### Pasos en Backend:
```
1. Crear Migration (si hay cambios de BD)
   php artisan make:migration create_xxx_table

2. Crear Model
   php artisan make:model Xxx

3. Crear Controller
   php artisan make:controller Api/XxxController --api

4. Crear FormRequest (validación)
   php artisan make:request StoreXxxRequest

5. Crear Event (si hay eventos)
   php artisan make:event XxxCreatedEvent

6. Crear Policy (autorización)
   php artisan make:policy XxxPolicy --model=Xxx

7. Agregar rutas en routes/api.php
   Route::apiResource('xxxs', 'Api\XxxController');

8. Registrar Policy en AuthServiceProvider
   protected $policies = [ Xxx::class => XxxPolicy::class ];
```

#### Pasos en Frontend:
```
1. Crear componente Vue
   src/components/XxxModal.vue

2. Crear vista (si es una página)
   src/views/XxxView.vue

3. Crear servicio API
   src/services/xxxService.js

4. Actualizar router
   src/router/index.js

5. Actualizar store si es necesario
   src/stores/xxx.js
```

### 3. Agregar una Ruta API

```php
// routes/api.php

// RESTful resource
Route::apiResource('tasks', TaskController::class)->middleware('auth:sanctum');

// Custom route
Route::post('flows/{flow}/pause', [FlowController::class, 'pause'])
    ->middleware('auth:sanctum')
    ->name('flows.pause');
```

### 4. Crear un Modelo con Relaciones

```php
// app/Models/Task.php
class Task extends Model
{
    protected $fillable = ['title', 'description', 'flow_id', 'status'];

    // Relación: muchas tareas pertenecen a un flujo
    public function flow() {
        return $this->belongsTo(Flow::class);
    }

    // Relación: una tarea tiene muchas subtareas
    public function children() {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    // Relación: una tarea pertenece a un usuario (asignado)
    public function assignee() {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
```

### 5. Validación en Backend

```php
// app/Http/Requests/StoreTaskRequest.php
class StoreTaskRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'flow_id' => 'required|exists:flows,id',
            'priority' => 'required|in:low,medium,high,critical',
            'assigned_to' => 'nullable|exists:users,id'
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'El título es obligatorio',
            'flow_id.exists' => 'El flujo no existe'
        ];
    }
}
```

### 6. Componente Vue con API

```vue
<template>
  <div>
    <button @click="loadData">Cargar</button>
    <div v-if="loading">Cargando...</div>
    <div v-if="error">{{ error }}</div>
    <ul v-if="items">
      <li v-for="item in items" :key="item.id">{{ item.name }}</li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const items = ref([])
const loading = ref(false)
const error = ref(null)

const loadData = async () => {
  try {
    loading.value = true
    error.value = null
    const response = await api.get('/tasks')
    items.value = response.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Error'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>
```

---

## Resumen de Flujos Importantes

### 1. Flujo de Autenticación
Usuario → Login → Backend valida → JWT token → Guard auth → Requests autenticados

### 2. Flujo de CRUD
Frontend modal → Valida → API POST/PUT/DELETE → Backend valida → BD → Event fired → WebSocket → Otros clientes actualizan

### 3. Flujo de Notificaciones
Evento en backend → Event listener → Crea Notification → Emite WebSocket → Store actualiza → Badge actualiza

### 4. Flujo de Reporte
ReportController → Ejecuta queries → Agrega datos → Exporta CSV/PDF → Frontend descarga

---

## Conclusión

Taskflow es un sistema robusto y escalable que combina:
- **Backend potente** con Laravel (modelos, eventos, políticas, servicios)
- **Frontend moderno** con Vue 3 (componentes, stores, real-time)
- **Base de datos relacional** bien diseñada (relaciones, índices)
- **Comunicación real-time** (WebSocket, Echo, Broadcasting)
- **Seguridad** (auth, policies, middleware)

Cada característica sigue patrones establecidos y puede ser fácilmente extendida.
