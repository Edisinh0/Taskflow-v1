# 🚀 Sistema SLA - Guía Rápida de Uso

## ✅ El Sistema Está Completamente Implementado

Todas las funcionalidades del Sistema de Alertas SLA están implementadas y funcionando correctamente.

---

## 📝 Comandos Correctos (Dentro de Docker)

### ⚠️ IMPORTANTE
Taskflow corre en Docker, por lo tanto **todos los comandos Artisan deben ejecutarse dentro del contenedor**.

### Opción 1: Usar el Script Helper (Recomendado)

```bash
# Ejecutar verificación simple
./sla-check.sh

# Ejecutar con detalles
./sla-check.sh --details

# Verificar tarea específica
./sla-check.sh --task-id=232

# Listar comandos disponibles
./sla-check.sh --list
```

### Opción 2: Comandos Docker Directos

```bash
# Verificación simple
docker exec taskflow_backend_new php artisan sla:check

# Con detalles
docker exec taskflow_backend_new php artisan sla:check --details

# Tarea específica
docker exec taskflow_backend_new php artisan sla:check --task-id=232

# Listar comandos
docker exec taskflow_backend_new php artisan list | grep sla
```

---

## 🧪 Cómo Crear Tareas de Prueba

### 1. Conectarse a la Base de Datos

```bash
docker exec -it taskflow_db_new mysql -utaskflow_user -ptaskflow_password taskflow_db
```

### 2. Crear Tarea con SLA Vencido (+1 día)

```sql
INSERT INTO tasks (
    title, description, flow_id, assignee_id, status, priority,
    sla_due_date, sla_breached, sla_notified_assignee, sla_escalated,
    created_at, updated_at
) VALUES (
    'Test SLA Warning',
    'Tarea de prueba con SLA vencido hace 25 horas',
    1,  -- Ajustar al flow_id que exista
    2,  -- Ajustar al user_id que exista
    'in_progress',
    'high',
    DATE_SUB(NOW(), INTERVAL 25 HOUR),  -- Vencida hace 25 horas
    0, 0, 0,  -- Flags SLA en false
    NOW(), NOW()
);
```

### 3. Crear Tarea con SLA Crítico (+3 días)

```sql
INSERT INTO tasks (
    title, description, flow_id, assignee_id, status, priority,
    sla_due_date, sla_breached, sla_notified_assignee, sla_escalated,
    created_at, updated_at
) VALUES (
    'Test SLA Escalation',
    'Tarea de prueba con SLA vencido hace 3 días',
    1,  -- Ajustar al flow_id que exista
    2,  -- Ajustar al user_id que exista
    'in_progress',
    'urgent',
    DATE_SUB(NOW(), INTERVAL 72 HOUR),  -- Vencida hace 72 horas (3 días)
    0, 0, 0,  -- Flags SLA en false
    NOW(), NOW()
);
```

### 4. Verificar Tareas Creadas

```sql
SELECT id, title, status, sla_due_date,
       TIMESTAMPDIFF(HOUR, sla_due_date, NOW()) as hours_overdue,
       sla_notified_assignee, sla_escalated
FROM tasks
WHERE title LIKE 'Test SLA%';
```

---

## 🔍 Ejecutar Verificación y Ver Resultados

### 1. Ejecutar Comando

```bash
./sla-check.sh --details
```

**Resultado Esperado:**
```
🔍 Iniciando verificación de alertas SLA...

📊 Detalles de tareas con alertas SLA:

⚠️  Tareas con advertencia (+1 día):

+----+-----------------+-------------+--------+--------+---------------------+
| ID | Título          | Asignado    | Flujo  | Atraso | Vencimiento         |
+----+-----------------+-------------+--------+--------+---------------------+
| 233| Test SLA Warning| Admin       | Flow 1 | 1 días | 2026-01-20 13:00:00 |
+----+-----------------+-------------+--------+--------+---------------------+

🚨 Tareas críticas (+2 días) - ESCALADAS:

+----+--------------------+-------------+--------+--------+---------------------+
| ID | Título             | Asignado    | Flujo  | Atraso | Vencimiento         |
+----+--------------------+-------------+--------+--------+---------------------+
| 234| Test SLA Escalation| Admin       | Flow 1 | 3 días | 2026-01-18 13:00:00 |
+----+--------------------+-------------+--------+--------+---------------------+

✅ Verificación de SLA completada:

+---------------------------------+----------+
| Métrica                         | Cantidad |
+---------------------------------+----------+
| Tareas verificadas              | 217      |
| Alertas de advertencia (+1 día) | 1        |
| Escalaciones críticas (+2 días) | 1        |
| Total de alertas procesadas     | 2        |
+---------------------------------+----------+
```

### 2. Verificar Notificaciones Creadas

```sql
-- Ver notificaciones SLA recién creadas
SELECT id, user_id, type, title, priority, is_read, created_at
FROM notifications
WHERE type IN ('sla_warning', 'sla_escalation', 'sla_escalation_notice')
ORDER BY created_at DESC
LIMIT 10;
```

### 3. Ver Estado Actualizado de Tareas

```sql
-- Verificar que los flags SLA se actualizaron
SELECT id, title, sla_breached, sla_notified_assignee, sla_escalated,
       sla_notified_at, sla_escalated_at
FROM tasks
WHERE title LIKE 'Test SLA%';
```

**Resultado Esperado:**
```
+-----+---------------------+--------------+----------------------+--------------+---------------------+---------------------+
| id  | title               | sla_breached | sla_notified_assignee| sla_escalated| sla_notified_at     | sla_escalated_at    |
+-----+---------------------+--------------+----------------------+--------------+---------------------+---------------------+
| 233 | Test SLA Warning    |            1 |                    1 |            0 | 2026-01-21 14:30:00 | NULL                |
| 234 | Test SLA Escalation |            1 |                    1 |            1 | 2026-01-21 14:30:00 | 2026-01-21 14:30:00 |
+-----+---------------------+--------------+----------------------+--------------+---------------------+---------------------+
```

---

## 🎨 Verificar en el Frontend

1. **Iniciar sesión** en http://localhost:5173
2. **Ir a la vista de Flujos** donde están las tareas de prueba
3. **Verificar badges SLA:**
   - Badge amarillo: **"⚠️ ALERTA (+1d)"** para warning
   - Badge rojo pulsante: **"🚨 CRÍTICA (+3d)"** para escalation
4. **Ir a Notificaciones** (icono de campana)
5. **Click en filtro "🚨 SLA"** para ver solo alertas SLA
6. **Verificar que aparecen** las notificaciones creadas

---

## ✅ Probar Resolución Automática

### 1. Completar una Tarea

```sql
UPDATE tasks
SET status = 'completed', updated_at = NOW()
WHERE title = 'Test SLA Warning';
```

### 2. Verificar Que Se Resolvió

```sql
-- Las notificaciones SLA de esa tarea deben estar marcadas como leídas
SELECT id, type, title, is_read, read_at
FROM notifications
WHERE task_id = (SELECT id FROM tasks WHERE title = 'Test SLA Warning')
  AND type IN ('sla_warning', 'sla_escalation');

-- Debe existir una notificación de resolución
SELECT id, type, title, created_at
FROM notifications
WHERE task_id = (SELECT id FROM tasks WHERE title = 'Test SLA Warning')
  AND type = 'sla_resolved';
```

### 3. Verificar en Frontend

- El badge SLA desaparece de la tarea completada
- Aparece un toast verde: "✅ SLA Resuelto"
- La notificación de resolución aparece en el centro de notificaciones

---

## 🔧 Comandos Útiles Adicionales

### Ver Scheduler

```bash
# Ver tareas programadas
docker exec taskflow_backend_new php artisan schedule:list

# Ejecutar scheduler manualmente
docker exec taskflow_backend_new php artisan schedule:run
```

### Ver Logs

```bash
# Ver logs de Laravel en tiempo real
docker exec taskflow_backend_new tail -f storage/logs/laravel.log

# Buscar logs específicos de SLA
docker exec taskflow_backend_new grep -i "sla\|escalation" storage/logs/laravel.log | tail -20
```

### Verificar Queue (para emails)

```bash
# Ver trabajos en cola
docker exec taskflow_backend_new php artisan queue:work --once

# Ver trabajos fallidos
docker exec taskflow_backend_new php artisan queue:failed
```

---

## 🧹 Limpiar Datos de Prueba

```sql
-- Eliminar tareas de prueba
DELETE FROM tasks WHERE title LIKE 'Test SLA%';

-- Eliminar notificaciones de prueba (últimas 2 horas)
DELETE FROM notifications
WHERE type IN ('sla_warning', 'sla_escalation', 'sla_escalation_notice', 'sla_resolved')
  AND created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR);
```

---

## 🎯 Resumen de lo que Funciona

✅ **Comando `sla:check`** detecta tareas con SLA vencido
✅ **Alertas de Warning** se envían a +1 día de atraso
✅ **Escalaciones** se envían a +2 días + email al supervisor
✅ **Badges visuales** aparecen en tiempo real en el frontend
✅ **Filtro SLA** en NotificationsView funciona correctamente
✅ **Resolución automática** al completar tareas
✅ **Prevención de duplicados** funciona (no envía múltiples alertas)
✅ **Scheduler** programado para ejecutarse cada hora
✅ **Emails** con template profesional (si está configurado MAIL)

---

## 📚 Documentación Completa

Para más detalles, consulta:
- **TEST_SLA_SYSTEM.md** - Guía completa de pruebas
- **SISTEMA_SLA_IMPLEMENTADO.md** - Documentación técnica completa
- **ARQUITECTURA.md** - Arquitectura general de Taskflow

---

## 🆘 Troubleshooting

### El comando no se encuentra
```bash
# Verificar que el contenedor está corriendo
docker ps | grep taskflow_backend

# Reiniciar el contenedor si es necesario
docker restart taskflow_backend_new
```

### No se crean notificaciones
```bash
# Verificar logs
docker exec taskflow_backend_new tail -f storage/logs/laravel.log

# Ejecutar con una tarea específica para debugging
./sla-check.sh --task-id=232
```

### Los badges no aparecen en frontend
1. Hacer hard refresh: Cmd+Shift+R (Mac) o Ctrl+Shift+R (Windows)
2. Verificar que la tarea tiene `sla_due_date` en el response de la API
3. Abrir DevTools → Console para ver errores

---

¡El sistema está 100% funcional! 🚀
