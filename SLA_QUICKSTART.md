# 🚀 Inicio Rápido - Sistema SLA y Tiempo Real

## ✅ Sistema Listo para Usar

El sistema de SLA y notificaciones en tiempo real está completamente implementado y funcional.

## 📋 Checklist de Configuración

### Backend (Laravel)

- [x] Migración de campos SLA ejecutada
- [x] Modelos actualizados con lógica de SLA
- [x] Servicio de notificaciones implementado
- [x] Comando cron configurado
- [x] Eventos de broadcasting creados
- [x] Canales privados configurados
- [x] Redis funcionando
- [x] Rutas de API actualizadas

### Pendiente (Frontend)

- [ ] Instalar Laravel Echo
- [ ] Configurar WebSocket client
- [ ] Implementar componente de notificaciones
- [ ] Suscribirse a canales en tiempo real
- [ ] Mostrar alertas de SLA

## 🎯 Pasos para Iniciar

### 1. Verificar que todo está funcionando

```bash
# 1. Verificar Redis
docker-compose exec redis redis-cli ping
# Debe responder: PONG

# 2. Verificar migraciones
docker-compose exec app php artisan migrate:status
# Debe mostrar la migración 2025_12_17_210149_add_sla_fields_to_tasks_table como ejecutada

# 3. Probar el comando SLA
docker-compose exec app php artisan sla:check
# Debe ejecutarse sin errores
```

### 2. Iniciar el Queue Worker (IMPORTANTE)

El queue worker es necesario para que funcionen los eventos en tiempo real:

```bash
# En una terminal separada:
docker-compose exec app php artisan queue:work redis --verbose

# O en background:
docker-compose exec -d app php artisan queue:work redis
```

### 3. Programar el Cron (Producción)

Ya está configurado en `routes/console.php`, pero en producción necesitas agregar al crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

En desarrollo, puedes ejecutar:

```bash
# Ejecutar manualmente:
docker-compose exec app php artisan sla:check

# O ejecutar el scheduler en modo watch:
docker-compose exec app php artisan schedule:work
```

## 🧪 Probar el Sistema

### Opción 1: Crear Tarea de Prueba

```bash
docker-compose exec app php artisan tinker
```

Copiar y pegar en tinker:

```php
$flow = App\Models\Flow::first() ?? App\Models\Flow::create([
    'name' => 'Flujo de Prueba',
    'description' => 'Prueba de SLA',
    'created_by' => 1
]);

$task = App\Models\Task::create([
    'title' => 'Tarea con SLA Vencido',
    'description' => 'Tarea de prueba para el sistema de SLA',
    'flow_id' => $flow->id,
    'assignee_id' => 1,
    'priority' => 'high',
    'status' => 'in_progress',
    'estimated_start_at' => now()->subDays(10),
    'estimated_end_at' => now()->subDays(3),
    'sla_due_date' => now()->subDays(3),
    'progress' => 30,
]);

echo "✅ Tarea creada: " . $task->id . "\n";
```

### Opción 2: Ejecutar el Comando SLA

```bash
docker-compose exec app php artisan sla:check
```

### Opción 3: Verificar Notificaciones Creadas

```bash
docker-compose exec app php artisan tinker
```

```php
$notifications = App\Models\Notification::latest()->take(5)->get();
foreach ($notifications as $n) {
    echo "- {$n->title} ({$n->type})\n";
}
```

## 📡 Configurar WebSocket (Frontend)

### Instalación

```bash
cd taskflow-frontend
npm install --save laravel-echo socket.io-client
```

### Configuración Básica

```javascript
// src/echo.js
import Echo from 'laravel-echo';
import io from 'socket.io-client';

window.io = io;

export default new Echo({
    broadcaster: 'socket.io',
    host: `${window.location.hostname}:6001`,
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('authToken')}`,
        },
    },
});
```

### Usar en Componente

```javascript
import echo from './echo';

// Suscribirse a notificaciones del usuario
const userId = localStorage.getItem('userId');

echo.private(`user.${userId}`)
    .listen('.notification.sent', (event) => {
        console.log('📬 Nueva notificación:', event.notification);
        // Mostrar toast notification
        showToast(event.notification);
    });

// Suscribirse a actualizaciones de tarea
echo.private(`task.${taskId}`)
    .listen('.task.updated', (event) => {
        console.log('🔄 Tarea actualizada:', event.task);
        updateTaskInUI(event.task);
    })
    .listen('.sla.breached', (event) => {
        console.log('⚠️ SLA breach:', event.task);
        showSlaAlert(event.task);
    })
    .listen('.sla.escalated', (event) => {
        console.log('🚨 Escalado:', event.task);
        showEscalationAlert(event.task);
    });
```

## 📊 Endpoints Disponibles

### Notificaciones

```bash
# Obtener notificaciones del usuario
GET /api/v1/notifications

# Marcar como leída
PUT /api/v1/notifications/{id}/read

# Marcar todas como leídas
POST /api/v1/notifications/mark-all-read
```

### Tareas

```bash
# Actualizar tarea (dispara eventos en tiempo real)
PUT /api/v1/tasks/{id}
{
  "status": "completed",
  "progress": 100
}

# Obtener tareas con SLA vencido
GET /api/v1/tasks?sla_breached=true
```

## 🔧 Troubleshooting

### Los eventos no se reciben

1. **Verificar que el queue worker esté corriendo**:
```bash
docker-compose ps | grep app
# Debe estar ejecutando queue:work
```

2. **Verificar Redis**:
```bash
docker-compose exec redis redis-cli ping
```

3. **Ver logs**:
```bash
docker-compose exec app tail -f storage/logs/laravel.log
```

### Las notificaciones no se crean

1. **Verificar el servicio**:
```bash
docker-compose exec app php artisan tinker
```

```php
$service = new App\Services\SlaNotificationService();
$stats = $service->processOverdueTasks();
print_r($stats);
```

2. **Revisar permisos**:
```bash
docker-compose exec app chmod -R 775 storage
```

### Errores de autenticación en WebSocket

1. **Verificar token**:
```javascript
console.log(localStorage.getItem('authToken'));
```

2. **Verificar rutas de broadcasting**:
```bash
docker-compose exec app php artisan route:list | grep broadcasting
```

## 📚 Documentación Completa

- **Guía Completa**: `SLA_REALTIME_GUIDE.md`
- **Pruebas**: `TEST_SLA.md`
- **Resumen de Implementación**: `IMPLEMENTACION_SLA_RESUMEN.md`
- **Ejemplos Frontend**: `FRONTEND_INTEGRATION_EXAMPLES.md`

## 🎯 Casos de Uso

### Caso 1: Tarea con 1 día de retraso
```
✅ Sistema detecta SLA vencido
✅ Envía notificación al responsable
✅ Marca sla_notified_assignee = true
✅ Dispara evento NotificationSent
✅ Dispara evento SlaBreached
```

### Caso 2: Tarea con 2 días de retraso
```
✅ Sistema detecta necesidad de escalamiento
✅ Envía notificación al supervisor
✅ Envía notificación al responsable (escalamiento)
✅ Marca sla_escalated = true
✅ Dispara eventos en tiempo real
```

### Caso 3: Actualización de Tarea
```
✅ Usuario actualiza status via API
✅ Se guarda en base de datos
✅ Se dispara evento TaskUpdated
✅ Todos los suscriptores reciben update en tiempo real
✅ UI se actualiza sin recargar
```

## ⚡ Comandos Útiles

```bash
# Limpiar cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear

# Regenerar cache
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache

# Ver schedule
docker-compose exec app php artisan schedule:list

# Ejecutar SLA manualmente
docker-compose exec app php artisan sla:check

# Iniciar queue worker
docker-compose exec app php artisan queue:work redis --verbose

# Ver trabajos en la cola
docker-compose exec app php artisan queue:failed
```

## 🎉 ¡Listo!

El sistema está completamente funcional. Solo falta:

1. ✅ Iniciar el queue worker
2. ⏳ Configurar Laravel Echo en el frontend
3. ⏳ Implementar componentes de notificaciones
4. ⏳ Probar en producción

---

**¿Necesitas ayuda?** Revisa la documentación completa en:
- `SLA_REALTIME_GUIDE.md` - Guía detallada
- `TEST_SLA.md` - Scripts de prueba
- `FRONTEND_INTEGRATION_EXAMPLES.md` - Ejemplos de código frontend
