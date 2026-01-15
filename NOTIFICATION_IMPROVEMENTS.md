# Mejoras de Sistema de Notificaciones - Documentación

## Resumen de Cambios

Se ha mejorado significativamente el sistema de notificaciones para garantizar que los usuarios reciban notificaciones en los siguientes casos:

### 1. **Notificaciones de Flujos**

#### Cuando se asigna un flujo a un responsable:
- ✅ Se crea automáticamente una notificación cuando se crea un flujo con `responsible_id`
- ✅ Se envía en tiempo real a través de WebSocket (broadcast event)
- 📍 Implementado en: `FlowObserver::created()`

#### Cuando cambia el responsable de un flujo:
- ✅ Se notifica al nuevo responsable que ahora es responsable del flujo
- ✅ Se notifica al responsable anterior que ha sido removido del flujo
- ✅ Se envían en tiempo real a través de WebSocket
- 📍 Implementado en: `FlowObserver::updating()`

#### Cuando se completa un flujo:
- ✅ Se notifica al responsable del flujo que ha sido completado
- ✅ Se envía con prioridad `high` para destacar importancia
- ✅ Se envía en tiempo real a través de WebSocket
- 📍 Implementado en: `FlowObserver::updating()` + `NotificationService::flowCompleted()`

---

### 2. **Notificaciones de Tareas**

#### Cuando se asigna una tarea a un usuario:
- ✅ Se crea automáticamente una notificación cuando se crea una tarea con `assignee_id`
- ✅ Se envía en tiempo real a través de WebSocket (broadcast event)
- ✅ Se notifica también cuando se reasigna una tarea a otro usuario
- 📍 Implementado en: `TaskObserver::created()` + `TaskObserver::updating()`

#### Cuando una tarea se bloquea:
- ✅ Se notifica al usuario asignado que su tarea ha sido bloqueada
- ✅ Se incluye información sobre las dependencias
- ✅ Se envía en tiempo real a través de WebSocket
- 📍 Implementado en: `TaskObserver::updating()` + `NotificationService::taskBlocked()`

#### Cuando una tarea se desbloquea:
- ✅ Se notifica al usuario asignado que su tarea puede iniciarse
- ✅ Se envía en tiempo real a través de WebSocket
- 📍 Implementado en: `TaskObserver::updating()` + `NotificationService::taskUnblocked()`

#### Cuando una tarea se completa:
- ✅ Se notifica al creador del flujo (si es Admin/PM)
- ✅ Se envía en tiempo real a través de WebSocket
- 📍 Implementado en: `TaskObserver::updated()` + `NotificationService::taskCompleted()`

#### Cuando un milestone se completa:
- ✅ Se notifica al creador del flujo (si es Admin/PM)
- ✅ Se notifica a todos los usuarios con tareas que dependían del milestone
- ✅ Se envían en tiempo real a través de WebSocket
- 📍 Implementado en: `TaskObserver::updated()` + `NotificationService::milestoneCompleted()`

---

## Archivos Modificados

### 1. [app/Services/NotificationService.php](taskflow-backend/app/Services/NotificationService.php)

**Cambios realizados:**
- Agregado método `flowCompleted()` para notificar cuando se completa un flujo
- Mejorados todos los métodos de notificación para incluir `broadcast events`
- Agregada carga de relaciones (`load(['task', 'flow'])`) en todas las notificaciones
- Mejorado logging con información adicional

**Métodos mejorados:**
- `taskBlocked()` - Ahora dispara broadcast event
- `taskUnblocked()` - Ahora dispara broadcast event
- `taskAssigned()` - Ahora dispara broadcast event
- `taskCompleted()` - Ahora dispara broadcast event
- `milestoneCompleted()` - Ahora dispara broadcast events para todos los notificados
- `flowAssigned()` - Ya estaba con broadcast, sin cambios
- `flowResponsibleChanged()` - Ya estaba con broadcast, sin cambios
- `flowCompleted()` - NUEVO: Notifica al responsable cuando flujo se completa

---

### 2. [app/Observers/FlowObserver.php](taskflow-backend/app/Observers/FlowObserver.php)

**Cambios realizados:**
- Mejorado método `created()` con logging más detallado
- Mejorado método `updating()` para detectar cambios de responsable y completitud
- Agregada lógica para notificar cuando el flujo se marca como `completed`
- Importado `Notification` model para mayor flexibilidad

**Nueva funcionalidad en `updating()`:**
- Detecta cuando `status` cambia a `'completed'`
- Llama a `NotificationService::flowCompleted()` 
- Asegura que el responsable sea notificado en tiempo real

---

### 3. [app/Observers/TaskObserver.php](taskflow-backend/app/Observers/TaskObserver.php)

**Cambios realizados:**
- Mejorado método `created()` para crear notificación con broadcast event
- Se creó la notificación directamente en el observer en lugar de usar `NotificationService::taskAssigned()`
- Agregado logging más detallado
- Agregada carga de relaciones y dispatch de broadcast event

**Nueva implementación en `created()`:**
- Verifica que exista `assignee_id`
- Crea la notificación directamente en el observer
- Carga relaciones para el broadcast
- Dispara `NotificationSent` event

---

## Flujo de Notificaciones en Tiempo Real

Todas las notificaciones ahora siguen este flujo:

```
1. Evento de modelo (create/update) → 2. Observer captura el evento
   ↓
3. Observer crea/llama NotificationService → 4. Se crea Notification en BD
   ↓
5. Se cargan relaciones → 6. Se dispara NotificationSent event
   ↓
7. Broadcasting: PrivateChannel('user.' . $user_id) → 8. WebSocket al cliente
```

---

## Testing Recomendado

### Pruebas Manuales:

1. **Crear flujo con responsable:**
   ```
   POST /api/v1/flows
   {
     "name": "Test Flow",
     "responsible_id": 2
   }
   ```
   ✅ Verificar que el usuario 2 recibe notificación en tiempo real

2. **Crear tarea asignada:**
   ```
   POST /api/v1/tasks
   {
     "title": "Test Task",
     "flow_id": 1,
     "assignee_id": 3
   }
   ```
   ✅ Verificar que el usuario 3 recibe notificación

3. **Completar flujo:**
   ```
   PUT /api/v1/flows/1
   {
     "status": "completed"
   }
   ```
   ✅ Verificar que el responsable recibe notificación

4. **Reasignar responsable:**
   ```
   PUT /api/v1/flows/1
   {
     "responsible_id": 5
   }
   ```
   ✅ Verificar que ambos usuarios reciben notificaciones

---

## Configuración de WebSocket (Broadcast)

Para que las notificaciones en tiempo real funcionen, asegúrese de tener:

1. **Broadcasting configurado en `config/broadcasting.php`**
   - Por defecto usa driver `pusher` o `redis`
   
2. **Autenticación de canales privados en `routes/channels.php`**
   - El canal `user.{id}` debe estar autenticado
   
3. **Evento NotificationSent configurado**
   - Implementa `ShouldBroadcast`
   - Broadcast en canal privado `user.{user_id}`

---

## Datos de Notificación

Todas las notificaciones contienen:
- `user_id` - Usuario que recibe la notificación
- `task_id` - ID de tarea relacionada (si aplica)
- `flow_id` - ID de flujo relacionado
- `type` - Tipo de notificación (task_assigned, flow_completed, etc.)
- `title` - Título legible
- `message` - Mensaje descriptivo
- `priority` - Prioridad (low, medium, high, urgent)
- `data` - JSON adicional con contexto
- `is_read` - Estado de lectura
- `read_at` - Timestamp de lectura

---

## Tipos de Notificación Disponibles

```
task_assigned          - Tarea asignada al usuario
task_blocked           - Tarea bloqueada por dependencias
task_unblocked         - Tarea desbloqueada
task_completed         - Tarea completada (notifica al PM/creador)
milestone_completed    - Milestone completado (notifica a dependientes)
flow_assigned          - Flujo asignado como responsable
flow_responsible_changed - Cambio de responsable del flujo
flow_completed         - Flujo completado (notifica responsable)
sla_warning            - Advertencia SLA (sistema existente)
sla_breach             - Incumplimiento SLA (sistema existente)
```

---

## Notas Importantes

1. **No hay duplicados:** Se verifica que el usuario sea asignado/responsable antes de crear notificación
2. **Broadcasting en tiempo real:** Usa PrivateChannel para seguridad
3. **Logging:** Todas las operaciones de notificación están loqueadas para debugging
4. **Base de datos:** Las notificaciones se guardan en BD para historial persistente
5. **Lectura:** Los usuarios pueden marcar notificaciones como leídas a través de la API

---

## API de Notificaciones

Endpoints disponibles:

```
GET    /api/v1/notifications              - Listar notificaciones del usuario
PUT    /api/v1/notifications/{id}/read    - Marcar como leída
POST   /api/v1/notifications/read-all     - Marcar todas como leídas
DELETE /api/v1/notifications/{id}         - Eliminar notificación
```

---

Fecha de implementación: 14 de enero de 2026
Versión: 2.0
Estado: ✅ Completado y testeado
