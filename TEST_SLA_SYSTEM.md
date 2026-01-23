# Guía de Pruebas - Sistema de Alertas SLA

## ✅ Sistema Implementado Completamente

### Backend
- ✅ Modelo Task con métodos SLA (getSLAStatus, getDaysOverdue, getResponsible)
- ✅ SLANotificationService (checkAllTasks, checkTask, notifyAssignee, escalateToPM)
- ✅ Comando Artisan: `php artisan sla:check --verbose --task-id=X`
- ✅ Scheduler configurado (ejecución cada hora)
- ✅ TaskObserver con resolución automática
- ✅ Mail SLAEscalationMail con template profesional
- ✅ Configuración centralizada en config/sla.php

### Frontend
- ✅ Store notifications.js con alertas SLA
- ✅ Componente SLAAlertBadge.vue
- ✅ TaskTreeItem.vue con badges SLA
- ✅ NotificationsView.vue con filtro SLA
- ✅ Toast automático para alertas críticas

---

## 🧪 Cómo Probar el Sistema

### 1. Crear Tarea de Prueba con SLA Vencido

```bash
# Conectarse a la base de datos
docker exec -it $(docker ps -qf "name=db") mysql -utaskflow_user -ptaskflow_password taskflow_db
```

```sql
-- Crear tarea vencida hace 1 día (warning)
INSERT INTO tasks (
    title,
    description,
    flow_id,
    assignee_id,
    status,
    priority,
    sla_due_date,
    sla_breached,
    sla_notified_assignee,
    sla_escalated,
    created_at,
    updated_at
) VALUES (
    'Tarea de Prueba SLA - Warning (+1 día)',
    'Esta tarea tiene SLA vencido hace 25 horas para probar alertas',
    1,  -- Ajustar al flow_id que exista
    2,  -- Ajustar al user_id que exista
    'in_progress',
    'high',
    DATE_SUB(NOW(), INTERVAL 25 HOUR),  -- Vencida hace 25 horas
    0,  -- No marcada como breached aún
    0,  -- No notificada
    0,  -- No escalada
    NOW(),
    NOW()
);

-- Crear tarea vencida hace 3 días (escalation)
INSERT INTO tasks (
    title,
    description,
    flow_id,
    assignee_id,
    status,
    priority,
    sla_due_date,
    sla_breached,
    sla_notified_assignee,
    sla_escalated,
    created_at,
    updated_at
) VALUES (
    'Tarea de Prueba SLA - Escalation (+3 días)',
    'Esta tarea tiene SLA vencido hace 3 días para probar escalación',
    1,  -- Ajustar al flow_id que exista
    2,  -- Ajustar al user_id que exista
    'in_progress',
    'urgent',
    DATE_SUB(NOW(), INTERVAL 72 HOUR),  -- Vencida hace 72 horas (3 días)
    0,  -- No marcada como breached aún
    0,  -- No notificada
    0,  -- No escalada
    NOW(),
    NOW()
);

-- Verificar las tareas creadas
SELECT id, title, status, sla_due_date,
       TIMESTAMPDIFF(HOUR, sla_due_date, NOW()) as hours_overdue,
       sla_notified_assignee, sla_escalated
FROM tasks
WHERE title LIKE 'Tarea de Prueba SLA%';
```

### 2. Ejecutar Verificación Manual

```bash
# IMPORTANTE: Los comandos deben ejecutarse dentro del contenedor Docker

# Verificar que el comando existe
docker exec taskflow_backend_new php artisan list | grep sla

# Ejecutar verificación simple
docker exec taskflow_backend_new php artisan sla:check

# Ejecutar verificación con detalles
docker exec taskflow_backend_new php artisan sla:check --details

# Verificar una tarea específica
docker exec taskflow_backend_new php artisan sla:check --task-id=232
```

**Resultado Esperado:**
```
🔍 Iniciando verificación de alertas SLA...

✅ Verificación de SLA completada:

┌─────────────────────────────┬──────────┐
│ Métrica                     │ Cantidad │
├─────────────────────────────┼──────────┤
│ Tareas verificadas          │ 217      │
│ Alertas de advertencia      │ 1        │
│ Escalaciones críticas       │ 1        │
│ Total de alertas procesadas │ 2        │
└─────────────────────────────┴──────────┘
```

### 3. Verificar Notificaciones en Base de Datos

```sql
-- Ver notificaciones SLA creadas
SELECT id, user_id, type, title, priority, is_read, created_at
FROM notifications
WHERE type IN ('sla_warning', 'sla_escalation', 'sla_escalation_notice')
ORDER BY created_at DESC
LIMIT 10;

-- Ver estado actualizado de las tareas
SELECT id, title, sla_breached, sla_notified_assignee, sla_escalated, sla_notified_at, sla_escalated_at
FROM tasks
WHERE title LIKE 'Tarea de Prueba SLA%';
```

### 4. Verificar Email de Escalación

```bash
# Ver logs de Laravel
tail -f /Users/eddiecerpa/Downloads/Taskflow-v1/taskflow-backend/storage/logs/laravel.log | grep -i "email\|escalation"
```

**Nota:** Para que los emails funcionen, asegúrate de tener configurado:
```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
QUEUE_CONNECTION=database
```

### 5. Verificar Frontend

1. **Iniciar sesión en la aplicación** como el usuario asignado
2. **Abrir la vista de Flujos** donde esté la tarea
3. **Verificar que aparecen los badges SLA:**
   - Badge amarillo "⚠️ ALERTA (+1d)" para warning
   - Badge rojo pulsante "🚨 CRÍTICA (+3d)" para escalation
4. **Ir a Notificaciones**
5. **Hacer clic en el filtro "🚨 SLA"**
6. **Verificar que aparecen las notificaciones SLA**

### 6. Probar Resolución Automática

```sql
-- Completar una de las tareas de prueba
UPDATE tasks
SET status = 'completed', updated_at = NOW()
WHERE title = 'Tarea de Prueba SLA - Warning (+1 día)';
```

**Verificar:**
1. Las notificaciones SLA de esa tarea se marcan como leídas automáticamente
2. Se crea una notificación de tipo `sla_resolved`
3. El badge SLA desaparece del frontend

### 7. Probar Scheduler (Opcional)

```bash
# Ejecutar el scheduler manualmente
php artisan schedule:run

# O esperar a que se ejecute automáticamente cada hora
```

### 8. Limpiar Datos de Prueba

```sql
-- Eliminar tareas de prueba
DELETE FROM tasks WHERE title LIKE 'Tarea de Prueba SLA%';

-- Eliminar notificaciones de prueba
DELETE FROM notifications WHERE type IN ('sla_warning', 'sla_escalation', 'sla_escalation_notice', 'sla_resolved')
AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

---

## 📊 Flujo Completo del Sistema

### Escenario 1: Tarea con 1 día de atraso

```
1. Scheduler ejecuta: php artisan sla:check
   ↓
2. SLANotificationService::checkAllTasks()
   ↓
3. Task::needsAssigneeNotification() encuentra tareas +1 día
   ↓
4. SLANotificationService::notifyAssignee()
   ↓
5. Se crea Notification tipo 'sla_warning'
   ↓
6. Se marca task.sla_notified_assignee = true
   ↓
7. Broadcast NotificationSent a users.{assignee_id}
   ↓
8. Frontend recibe evento → muestra toast amarillo
   ↓
9. Badge "⚠️ ALERTA" aparece en TaskTreeItem
```

### Escenario 2: Tarea con 2+ días de atraso

```
1. Scheduler ejecuta: php artisan sla:check
   ↓
2. SLANotificationService::checkAllTasks()
   ↓
3. Task::needsEscalation() encuentra tareas +2 días
   ↓
4. SLANotificationService::escalateToSupervisor()
   ↓
5. Se crea Notification tipo 'sla_escalation' para supervisor
   ↓
6. Se crea Notification tipo 'sla_escalation_notice' para asignado
   ↓
7. Se envía email a supervisor con CC al asignado
   ↓
8. Se marca task.sla_escalated = true
   ↓
9. Broadcast eventos a ambos usuarios
   ↓
10. Frontend recibe eventos → toast rojo pulsante + sonido
   ↓
11. Badge "🚨 CRÍTICA" aparece en TaskTreeItem
```

### Escenario 3: Tarea completada

```
1. Usuario marca tarea como completed
   ↓
2. TaskObserver@updated detecta cambio
   ↓
3. TaskObserver::resolveSLAAlerts()
   ↓
4. Busca notificaciones SLA pendientes
   ↓
5. Marca todas como is_read = true
   ↓
6. Crea Notification tipo 'sla_resolved'
   ↓
7. Broadcast NotificationSent
   ↓
8. Frontend actualiza → badge SLA desaparece
   ↓
9. Muestra toast verde "✅ SLA Resuelto"
```

---

## ⚙️ Configuración Recomendada

### Producción

```env
SLA_ENABLED=true
SLA_WARNING_HOURS=24
SLA_ESCALATION_HOURS=48
SLA_NOTIFY_IN_APP=true
SLA_NOTIFY_EMAIL=true
SLA_CHECK_FREQUENCY=hourly
QUEUE_CONNECTION=database
```

### Desarrollo/Testing

```env
SLA_ENABLED=true
SLA_WARNING_HOURS=1  # 1 hora en lugar de 24
SLA_ESCALATION_HOURS=2  # 2 horas en lugar de 48
SLA_NOTIFY_IN_APP=true
SLA_NOTIFY_EMAIL=false  # Desactivar emails en desarrollo
SLA_CHECK_FREQUENCY=everyFifteenMinutes  # Más frecuente para testing
```

---

## 🐛 Troubleshooting

### Las notificaciones no se crean

1. Verificar que las tareas tienen `sla_due_date` definido
2. Verificar que `sla_notified_assignee` es false
3. Ejecutar comando con `--verbose` para ver detalles
4. Revisar logs: `tail -f storage/logs/laravel.log`

### Los emails no se envían

1. Verificar configuración MAIL_* en .env
2. Verificar que la cola está corriendo: `php artisan queue:work`
3. Verificar en MailHog (si está configurado): http://localhost:8025

### Los badges no aparecen en frontend

1. Verificar que la tarea tiene `sla_due_date` en el response
2. Abrir DevTools → Console para ver errores
3. Verificar que SLAAlertBadge está importado correctamente
4. Hacer hard refresh: Cmd+Shift+R (Mac) o Ctrl+Shift+R (Windows)

### El scheduler no ejecuta automáticamente

1. Verificar que está corriendo: `php artisan schedule:list`
2. En desarrollo, ejecutar manualmente: `php artisan schedule:run`
3. En producción, configurar cron job:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📝 Notas Finales

- **Prevención de Duplicados:** El sistema no crea notificaciones si ya existe una del mismo tipo en los últimos 60 minutos
- **Umbrales Configurables:** Puedes ajustar los umbrales en `config/sla.php` o `.env`
- **Broadcasting:** Las notificaciones se transmiten en tiempo real via Reverb
- **Resolución Automática:** Al completar una tarea, todas sus alertas SLA se resuelven automáticamente
- **Emails con Cola:** Los emails se envían en background para no bloquear la ejecución

¡El sistema está listo para producción! 🚀
