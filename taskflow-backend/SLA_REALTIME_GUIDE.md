# Guía de SLA y Tiempo Real - TaskFlow

## 📋 Resumen

Este documento describe el sistema completo de SLA (Service Level Agreement) y notificaciones en tiempo real implementado en TaskFlow.

## 🎯 Características Implementadas

### 1. Sistema de SLA
- ✅ Campos de SLA en la tabla de tareas
- ✅ Detección automática de tareas vencidas
- ✅ Notificación automática al responsable (+1 día de retraso)
- ✅ Escalamiento automático al supervisor (+2 días de retraso)
- ✅ Comando cron para verificación periódica

### 2. Notificaciones en Tiempo Real
- ✅ Eventos de WebSocket para actualizaciones de tareas
- ✅ Notificaciones instantáneas usando Laravel Broadcasting
- ✅ Canales privados por usuario, tarea y flujo
- ✅ Eventos de SLA breach y escalamiento

## 📊 Estructura de Base de Datos

### Nuevos Campos en la Tabla `tasks`

```sql
sla_due_date            TIMESTAMP    -- Fecha límite de SLA
sla_breached            BOOLEAN      -- Indica si se ha superado el SLA
sla_breach_at           TIMESTAMP    -- Fecha cuando se superó el SLA
sla_days_overdue        INTEGER      -- Días de retraso del SLA
sla_notified_assignee   BOOLEAN      -- Notificación enviada al responsable
sla_escalated           BOOLEAN      -- Escalado al supervisor
sla_notified_at         TIMESTAMP    -- Fecha de primera notificación
sla_escalated_at        TIMESTAMP    -- Fecha de escalamiento
```

## 🔧 Configuración

### 1. Variables de Entorno

El archivo `.env` ya está configurado con:

```env
BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 2. Ejecutar Migraciones

```bash
docker-compose exec app php artisan migrate
```

### 3. Configurar Cron Job

El comando SLA ya está programado en `routes/console.php`:

```php
Schedule::command('sla:check')->hourly();
```

Para ejecutar el scheduler en producción, agrega esto al crontab del servidor:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

En desarrollo, puedes ejecutar manualmente:

```bash
docker-compose exec app php artisan sla:check
```

O iniciar el worker de schedule:

```bash
docker-compose exec app php artisan schedule:work
```

### 4. Iniciar Queue Worker

Para que los eventos en tiempo real funcionen, debes iniciar el queue worker:

```bash
docker-compose exec app php artisan queue:work redis --sleep=3 --tries=3
```

## 📡 Broadcasting y WebSockets

### Canales Disponibles

1. **Canal de Usuario**: `user.{userId}`
   - Notificaciones personales
   - Alertas de SLA

2. **Canal de Tarea**: `task.{taskId}`
   - Actualizaciones de estado de tarea
   - Cambios en progreso

3. **Canal de Flujo**: `flow.{flowId}`
   - Actualizaciones de todas las tareas del flujo

### Eventos en Tiempo Real

#### 1. `TaskUpdated`
Disparado cuando se actualiza una tarea.

```javascript
Echo.private('task.' + taskId)
    .listen('.task.updated', (event) => {
        console.log('Tarea actualizada:', event.task);
        console.log('Cambios:', event.changes);
    });
```

#### 2. `NotificationSent`
Disparado cuando se envía una notificación.

```javascript
Echo.private('user.' + userId)
    .listen('.notification.sent', (event) => {
        console.log('Nueva notificación:', event.notification);
        // Mostrar notificación toast
        showToast(event.notification);
    });
```

#### 3. `SlaBreached`
Disparado cuando se detecta un breach de SLA o escalamiento.

```javascript
Echo.private('task.' + taskId)
    .listen('.sla.breached', (event) => {
        console.log('SLA breach:', event.task);
        // Actualizar UI para mostrar alerta
    });

Echo.private('task.' + taskId)
    .listen('.sla.escalated', (event) => {
        console.log('SLA escalado:', event.task);
        // Notificar al supervisor
    });
```

## 🚀 Uso del Sistema

### Lógica de SLA

1. **Establecer SLA**: La fecha de SLA se toma del campo `estimated_end_at` automáticamente
2. **Verificación**: Cada hora el comando `sla:check` verifica todas las tareas activas
3. **Notificación (+1 día)**:
   - Se crea una notificación para el responsable
   - Se marca `sla_notified_assignee = true`
   - Se dispara evento `SlaBreached`
4. **Escalamiento (+2 días)**:
   - Se crea notificación para el supervisor
   - Se notifica al responsable del escalamiento
   - Se marca `sla_escalated = true`
   - Se dispara evento `SlaBreached` con `escalated = true`

### Métodos del Modelo Task

```php
// Verificar y actualizar estado de SLA
$task->checkSlaStatus();

// Verificar si está retrasada
if ($task->isOverdue()) {
    // Hacer algo
}

// Obtener supervisor para escalamiento
$supervisor = $task->getSupervisor();

// Scopes útiles
Task::slaBreach()->get();                    // Tareas con SLA vencido
Task::needsAssigneeNotification()->get();    // Necesitan notificación
Task::needsEscalation()->get();              // Necesitan escalamiento
```

### Servicio de Notificaciones

```php
use App\Services\SlaNotificationService;

$slaService = new SlaNotificationService();

// Notificar al responsable
$slaService->notifyAssignee($task);

// Escalar al supervisor
$slaService->escalateToSupervisor($task);

// Procesar todas las tareas vencidas
$stats = $slaService->processOverdueTasks();
```

## 🔌 Integración Frontend

### 1. Instalar Laravel Echo

```bash
npm install --save laravel-echo pusher-js
```

### 2. Configurar Echo (usando Socket.IO)

```javascript
import Echo from 'laravel-echo';
import io from 'socket.io-client';

window.io = io;

window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: window.location.hostname + ':6001',
    auth: {
        headers: {
            Authorization: 'Bearer ' + authToken,
        },
    },
});
```

### 3. Suscribirse a Canales

```javascript
// Escuchar notificaciones del usuario
Echo.private(`user.${userId}`)
    .listen('.notification.sent', (event) => {
        // Mostrar notificación
        notificationStore.add(event.notification);
    });

// Escuchar actualizaciones de tarea
Echo.private(`task.${taskId}`)
    .listen('.task.updated', (event) => {
        // Actualizar tarea en el store
        taskStore.updateTask(event.task);
    })
    .listen('.sla.breached', (event) => {
        // Mostrar alerta de SLA
        showSlaAlert(event.task);
    })
    .listen('.sla.escalated', (event) => {
        // Mostrar alerta de escalamiento
        showEscalationAlert(event.task);
    });

// Escuchar actualizaciones del flujo
Echo.private(`flow.${flowId}`)
    .listen('.task.updated', (event) => {
        // Actualizar lista de tareas
        refreshTaskList();
    });
```

### 4. Componente de Notificaciones (Vue/React)

```vue
<template>
  <div v-if="notification" class="notification" :class="notification.priority">
    <h4>{{ notification.title }}</h4>
    <p>{{ notification.message }}</p>
    <small>{{ formatDate(notification.created_at) }}</small>
  </div>
</template>

<script>
export default {
  data() {
    return {
      notification: null
    }
  },
  mounted() {
    Echo.private(`user.${this.userId}`)
      .listen('.notification.sent', (event) => {
        this.notification = event.notification;
        this.showToast();
      });
  },
  methods: {
    showToast() {
      // Mostrar toast notification
    }
  }
}
</script>
```

## 🧪 Testing

### Probar el Sistema de SLA

1. **Crear una tarea con SLA vencido**:

```bash
docker-compose exec app php artisan tinker
```

```php
$task = Task::first();
$task->update([
    'sla_due_date' => now()->subDays(3),
    'status' => 'in_progress'
]);
```

2. **Ejecutar verificación de SLA**:

```bash
docker-compose exec app php artisan sla:check
```

3. **Verificar notificaciones creadas**:

```php
Notification::where('task_id', $task->id)->get();
```

### Probar Eventos en Tiempo Real

1. **Actualizar una tarea vía API**:

```bash
curl -X PUT http://localhost:8080/api/v1/tasks/1 \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "completed", "progress": 100}'
```

2. **Verificar en el navegador** que el evento se recibe en tiempo real.

## 📝 Notas Importantes

- **Redis**: Debe estar ejecutándose para que funcione el broadcasting
- **Queue Worker**: Debe estar activo para procesar eventos
- **Autenticación**: Los canales privados requieren autenticación vía Sanctum
- **Permisos**: Solo los usuarios autorizados pueden suscribirse a canales privados

## 🐛 Troubleshooting

### Los eventos no se reciben

1. Verificar que Redis esté ejecutándose:
```bash
docker-compose exec redis redis-cli ping
```

2. Verificar que el queue worker esté activo:
```bash
docker-compose exec app php artisan queue:work redis
```

3. Revisar logs:
```bash
docker-compose exec app tail -f storage/logs/laravel.log
```

### Las notificaciones no se envían

1. Verificar que el cron esté configurado
2. Ejecutar manualmente: `php artisan sla:check`
3. Revisar permisos del modelo User

## 🔐 Seguridad

- Los canales de broadcasting son privados y requieren autenticación
- Solo el assignee y el creador del flujo pueden acceder a los canales de tareas
- Las notificaciones solo se envían a usuarios autorizados

## 📚 Referencias

- [Laravel Broadcasting](https://laravel.com/docs/broadcasting)
- [Laravel Echo](https://github.com/laravel/echo)
- [Task Scheduling](https://laravel.com/docs/scheduling)
- [Queues](https://laravel.com/docs/queues)
