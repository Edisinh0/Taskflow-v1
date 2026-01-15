# Script de Prueba del Sistema SLA

## 🧪 Crear Datos de Prueba

### 1. Crear una tarea con SLA vencido

```bash
docker-compose exec app php artisan tinker
```

Luego ejecutar en tinker:

```php
// Obtener un flujo existente
$flow = App\Models\Flow::first();

if (!$flow) {
    echo "No hay flujos. Creando uno...\\n";
    $flow = App\Models\Flow::create([
        'name' => 'Flujo de Prueba SLA',
        'description' => 'Flujo para probar el sistema de SLA',
        'created_by' => 1
    ]);
}

// Crear una tarea con SLA vencido hace 3 días
$task = App\Models\Task::create([
    'title' => 'Tarea de Prueba - SLA Vencido',
    'description' => 'Esta tarea tiene un SLA vencido para probar notificaciones',
    'flow_id' => $flow->id,
    'assignee_id' => 1, // Asignar al admin
    'priority' => 'high',
    'status' => 'in_progress',
    'estimated_start_at' => now()->subDays(10),
    'estimated_end_at' => now()->subDays(3), // SLA vencido hace 3 días
    'sla_due_date' => now()->subDays(3),
    'progress' => 30,
]);

echo "✅ Tarea creada con ID: {$task->id}\\n";
echo "   SLA vencido hace: " . now()->diffInDays($task->sla_due_date) . " días\\n";
```

### 2. Ejecutar el comando de verificación SLA

```bash
docker-compose exec app php artisan sla:check
```

Deberías ver:
```
Iniciando verificación de SLA...
Proceso completado exitosamente:
  - Tareas verificadas: 1
  - Notificaciones enviadas: 1
  - Escalamientos realizados: 1
```

### 3. Verificar las notificaciones creadas

```bash
docker-compose exec app php artisan tinker
```

```php
// Ver todas las notificaciones
$notifications = App\Models\Notification::latest()->take(5)->get();
foreach ($notifications as $notif) {
    echo "- {$notif->title} ({$notif->type})\\n";
}

// Ver notificaciones de SLA específicas
$slaNotifications = App\Models\Notification::whereIn('type', [
    'sla_warning',
    'sla_escalation',
    'sla_escalation_notice'
])->latest()->get();

foreach ($slaNotifications as $notif) {
    echo "\\n=== {$notif->title} ===\\n";
    echo "Usuario: {$notif->user_id}\\n";
    echo "Tipo: {$notif->type}\\n";
    echo "Mensaje: {$notif->message}\\n";
    echo "Días de retraso: {$notif->data['days_overdue']}\\n";
}
```

### 4. Verificar el estado de la tarea

```php
$task = App\Models\Task::find(YOUR_TASK_ID);

echo "Estado del SLA:\\n";
echo "- Vencido: " . ($task->sla_breached ? 'Sí' : 'No') . "\\n";
echo "- Días de retraso: {$task->sla_days_overdue}\\n";
echo "- Notificado: " . ($task->sla_notified_assignee ? 'Sí' : 'No') . "\\n";
echo "- Escalado: " . ($task->sla_escalated ? 'Sí' : 'No') . "\\n";
```

## 🔴 Probar Eventos en Tiempo Real

### 1. Preparar el Frontend

Asegúrate de tener Laravel Echo configurado en tu frontend. Si no, usa este ejemplo básico:

```javascript
// En el navegador, abre la consola y ejecuta:
Echo.private('user.1')
    .listen('.notification.sent', (event) => {
        console.log('📬 Nueva notificación:', event.notification);
    });

Echo.private('task.YOUR_TASK_ID')
    .listen('.task.updated', (event) => {
        console.log('🔄 Tarea actualizada:', event.task);
    })
    .listen('.sla.breached', (event) => {
        console.log('⚠️ SLA breach:', event.task);
    })
    .listen('.sla.escalated', (event) => {
        console.log('🚨 SLA escalado:', event.task);
    });
```

### 2. Actualizar una tarea vía API

```bash
# Obtener un token de autenticación primero
TOKEN=$(curl -X POST http://localhost:8080/api/v1/auth/login \\
  -H "Content-Type: application/json" \\
  -d '{"email":"admin@taskflow.com","password":"password123"}' \\
  | jq -r '.token')

# Actualizar la tarea
curl -X PUT http://localhost:8080/api/v1/tasks/YOUR_TASK_ID \\
  -H "Authorization: Bearer $TOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{"status": "completed", "progress": 100}'
```

Deberías ver el evento `task.updated` en la consola del navegador en tiempo real.

### 3. Probar notificación manual

```bash
docker-compose exec app php artisan tinker
```

```php
$task = App\Models\Task::find(YOUR_TASK_ID);
$service = new App\\Services\\SlaNotificationService();

// Probar notificación al assignee
$service->notifyAssignee($task);

// Probar escalamiento
$service->escalateToSupervisor($task);
```

Deberías ver los eventos en tiempo real en el navegador.

## 📊 Verificar el Sistema Completo

### Script de verificación completo

```php
// En tinker:

echo "\\n=== VERIFICACIÓN DEL SISTEMA SLA ===\\n\\n";

// 1. Verificar tareas con SLA
echo "1. Tareas con SLA configurado:\\n";
$tasksWithSla = App\\Models\\Task::whereNotNull('sla_due_date')->count();
echo "   Total: {$tasksWithSla}\\n\\n";

// 2. Tareas vencidas
echo "2. Tareas con SLA vencido:\\n";
$overdueTasks = App\\Models\\Task::slaBreach()->count();
echo "   Total: {$overdueTasks}\\n\\n";

// 3. Tareas que necesitan notificación
echo "3. Tareas que necesitan notificación (+1 día):\\n";
$needsNotification = App\\Models\\Task::needsAssigneeNotification()->count();
echo "   Total: {$needsNotification}\\n\\n";

// 4. Tareas que necesitan escalamiento
echo "4. Tareas que necesitan escalamiento (+2 días):\\n";
$needsEscalation = App\\Models\\Task::needsEscalation()->count();
echo "   Total: {$needsEscalation}\\n\\n";

// 5. Notificaciones de SLA
echo "5. Notificaciones de SLA creadas:\\n";
$slaNotifs = App\\Models\\Notification::whereIn('type', [
    'sla_warning',
    'sla_escalation',
    'sla_escalation_notice'
])->count();
echo "   Total: {$slaNotifs}\\n\\n";

// 6. Estado de Redis
echo "6. Estado de Redis:\\n";
try {
    Illuminate\\Support\\Facades\\Redis::ping();
    echo "   ✅ Conectado\\n\\n";
} catch (\\Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\\n\\n";
}

// 7. Broadcasting configurado
echo "7. Broadcasting:\\n";
echo "   Driver: " . config('broadcasting.default') . "\\n";
echo "   Redis Host: " . config('database.redis.default.host') . "\\n\\n";

echo "=== FIN DE LA VERIFICACIÓN ===\\n\\n";
```

## 🔧 Troubleshooting

### No se crean notificaciones

1. Verificar que el servicio está funcionando:
```php
$service = new App\\Services\\SlaNotificationService();
$stats = $service->processOverdueTasks();
dd($stats);
```

2. Verificar logs:
```bash
docker-compose exec app tail -f storage/logs/laravel.log
```

### Los eventos no se reciben

1. Verificar que Redis esté funcionando:
```bash
docker-compose exec redis redis-cli ping
```

2. Verificar configuración de broadcasting:
```bash
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
```

3. Iniciar el queue worker:
```bash
docker-compose exec app php artisan queue:work redis --verbose
```

### Errores de permisos

```bash
docker-compose exec app chmod -R 775 storage
docker-compose exec app chown -R www-data:www-data storage
```

## ✅ Checklist de Funcionalidad

- [ ] Crear tarea con SLA vencido
- [ ] Ejecutar comando `sla:check`
- [ ] Verificar que se crean notificaciones
- [ ] Verificar que se actualiza el estado de la tarea
- [ ] Probar escalamiento (+2 días)
- [ ] Redis funcionando
- [ ] Broadcasting configurado
- [ ] Eventos en tiempo real funcionando
- [ ] Queue worker activo
- [ ] Canales privados con autenticación

## 📝 Notas

- El sistema verifica SLA cada hora automáticamente
- Las notificaciones se envían en tiempo real vía WebSocket
- El escalamiento es automático después de 2 días
- Todas las acciones se registran en los logs
