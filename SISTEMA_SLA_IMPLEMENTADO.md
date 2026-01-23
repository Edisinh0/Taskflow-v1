# ✅ Sistema Robusto de Alertas Automáticas SLA - COMPLETADO

## 📋 Resumen Ejecutivo

Se ha implementado completamente un **Sistema de Alertas SLA** en Taskflow v1 que:

- ✅ Detecta automáticamente tareas con SLA vencido cada hora
- ✅ Envía alertas de advertencia a +1 día de atraso
- ✅ Escala al supervisor/PM a +2 días de atraso
- ✅ Envía emails profesionales en las escalaciones
- ✅ Muestra badges visuales en tiempo real en el frontend
- ✅ Resuelve alertas automáticamente cuando se completa la tarea
- ✅ Previene notificaciones duplicadas
- ✅ Totalmente configurable vía .env

---

## 🎯 Archivos Creados/Modificados

### Backend (Laravel)

#### Nuevos Archivos Creados

1. **app/Console/Commands/CheckSLAAlerts.php**
   - Comando Artisan para verificar tareas con SLA vencido
   - Signature: `php artisan sla:check --verbose --task-id=X`
   - Muestra estadísticas detalladas de alertas procesadas

2. **app/Mail/SLAEscalationMail.php**
   - Mailable para emails de escalación
   - Incluye toda la información de la tarea
   - CC automático al asignado

3. **resources/views/mail/sla-escalation.blade.php**
   - Template Markdown profesional para emails
   - Botón de acción "Ver Tarea en Taskflow"
   - Sección de acciones recomendadas

4. **config/sla.php**
   - Configuración centralizada del sistema SLA
   - Umbrales configurables (24h warning, 48h escalation)
   - Canales de notificación habilitables

#### Archivos Modificados

5. **app/Models/Task.php**
   - Agregados métodos:
     - `getSLAStatus()`: Retorna 'none' | 'warning' | 'escalation'
     - `getDaysOverdue()`: Calcula días de atraso
     - `getResponsible()`: Obtiene supervisor/PM
   - Scopes ya existentes reutilizados:
     - `scopeNeedsAssigneeNotification()`
     - `scopeNeedsEscalation()`

6. **app/Services/SlaNotificationService.php**
   - Agregados métodos:
     - `shouldNotify()`: Prevención de duplicados
     - `checkTask()`: Verificar tarea individual
     - `checkAllTasks()`: Verificar todas las tareas
   - Integración con Mail::send() para escalaciones

7. **app/Observers/TaskObserver.php**
   - Agregado método `resolveSLAAlerts()`: Resolución automática
   - Llamada automática en `updated()` cuando status = 'completed'
   - Marca notificaciones como leídas y crea notification de resolución

8. **routes/console.php**
   - Registrado comando en Scheduler (ejecución cada hora)
   - `Schedule::command('sla:check --verbose')->hourly()`

9. **.env**
   - Agregadas 12 variables de configuración SLA
   - FRONTEND_URL para links en emails

### Frontend (Vue 3)

#### Nuevos Archivos Creados

10. **src/components/SLAAlertBadge.vue**
    - Badge visual para alertas SLA
    - Badge amarillo para warning
    - Badge rojo pulsante para escalation
    - Muestra días de atraso

#### Archivos Modificados

11. **src/stores/notifications.js**
    - Agregado state: `slaAlerts`
    - Agregado getter: `criticalSLAAlerts`
    - Agregados actions:
      - `addSLAAlert()`: Agregar alerta con toast automático
      - `removeSLAAlert()`: Remover alerta resuelta

12. **src/components/TaskTreeItem.vue**
    - Importado SLAAlertBadge
    - Agregado computed `slaAlertStatus`: Calcula estado de alerta
    - Agregado computed `daysOverdue`: Calcula días de atraso
    - Badge SLA mostrado con máxima prioridad en la lista

13. **src/views/NotificationsView.vue**
    - Agregado botón de filtro "🚨 SLA"
    - Agregado computed `slaNotifications`
    - Agregado computed `slaCount`
    - Lógica de filtrado integrada

---

## 🔧 Configuración

### Variables de Entorno (.env)

```env
# Sistema SLA
SLA_ENABLED=true
SLA_WARNING_HOURS=24
SLA_ESCALATION_HOURS=48
SLA_NOTIFY_IN_APP=true
SLA_NOTIFY_EMAIL=true
SLA_NOTIFY_SLACK=false
SLA_EMAIL_CC_ASSIGNEE=true
SLA_CHECK_FREQUENCY=hourly
SLA_CHECK_VERBOSE=true
SLA_DUPLICATE_PREVENTION_MINUTES=60
SLA_AUTO_RESOLVE=true
SLA_REALTIME_ENABLED=true

# Frontend URL (para links en emails)
FRONTEND_URL=http://localhost:5173

# Queue (requerido para emails)
QUEUE_CONNECTION=database
```

---

## 📊 Flujo de Funcionamiento

### Regla 1: Alerta a +1 día de atraso

```
Condiciones:
✓ Tarea existe y no está eliminada
✓ Status ≠ 'completed' Y ≠ 'cancelled'
✓ sla_due_date definido Y vencido
✓ now() - sla_due_date > 24 horas
✓ sla_notified_assignee = false

Acciones:
→ Crear Notification tipo 'sla_warning' para assignee
→ Actualizar task: sla_notified_assignee = true
→ Broadcast NotificationSent a users.{assignee_id}
→ Frontend muestra badge amarillo "⚠️ ALERTA"
```

### Regla 2: Escalación a +2 días de atraso

```
Condiciones:
✓ Cumple todo de Regla 1
✓ now() - sla_due_date > 48 horas
✓ sla_escalated = false

Acciones:
→ Enviar EMAIL al supervisor con CC al asignado
→ Crear Notification tipo 'sla_escalation' para supervisor
→ Crear Notification tipo 'sla_escalation_notice' para assignee
→ Actualizar task: sla_escalated = true
→ Broadcast eventos a ambos usuarios
→ Frontend muestra badge rojo pulsante "🚨 CRÍTICA"
→ Toast automático + sonido de alerta
```

### Regla 3: Prevención de Duplicados

```
✓ NO crear alerta si ya existe una del mismo tipo
✓ Verificar en últimos 60 minutos (configurable)
✓ Evita spam de notificaciones
```

### Regla 4: Resolución Automática

```
Trigger: Task.status cambia a 'completed'

Acciones:
→ Buscar todas las Notifications SLA pendientes de esa tarea
→ Marcar como is_read = true (limpiar visualmente)
→ Crear Notification tipo 'sla_resolved'
→ Broadcast NotificationSent
→ Frontend oculta badge SLA
→ Muestra toast verde "✅ SLA Resuelto"
```

---

## 🚀 Comandos Disponibles

### Verificación Manual

```bash
# IMPORTANTE: Ejecutar dentro del contenedor Docker

# Ver comandos SLA disponibles
docker exec taskflow_backend_new php artisan list | grep sla

# Ejecutar verificación de todas las tareas
docker exec taskflow_backend_new php artisan sla:check

# Ejecutar con detalles (recomendado)
docker exec taskflow_backend_new php artisan sla:check --details

# Verificar una tarea específica
docker exec taskflow_backend_new php artisan sla:check --task-id=232
```

### Scheduler

```bash
# Ver comandos programados
php artisan schedule:list

# Ejecutar scheduler manualmente (útil en desarrollo)
php artisan schedule:run

# En producción: Configurar cron job
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Queue (para emails)

```bash
# Procesar cola de emails
php artisan queue:work

# Ver trabajos en cola
php artisan queue:failed
```

---

## 🎨 Características del Frontend

### Badges Visuales

1. **Badge de Warning (+1 día)**
   - Color: Amarillo (bg-yellow-500)
   - Texto: "⚠️ ALERTA (+Xd)"
   - Sin animación

2. **Badge de Escalation (+2 días)**
   - Color: Rojo (bg-red-500)
   - Texto: "🚨 CRÍTICA (+Xd)"
   - Animación de pulso
   - Sombra intensa (shadow-red-500/50)

### Toasts Automáticos

- Warning: Toast amarillo, duración 5s
- Escalation: Toast rojo, duración 10s + sonido de alerta
- Resolución: Toast verde, duración 5s

### Filtro en Notificaciones

- Botón "🚨 SLA" con contador de alertas pendientes
- Filtra notificaciones de tipo:
  - sla_warning
  - sla_escalation
  - sla_escalation_notice
  - sla_resolved

---

## 📧 Email de Escalación

### Características

- **Asunto:** `[SLA ESCALADA] Tarea '{título}' - X días atrasada`
- **From:** Configurable en .env (MAIL_FROM_ADDRESS)
- **To:** Supervisor/PM del flujo
- **CC:** Asignado de la tarea (configurable)
- **Template:** Markdown profesional con:
  - Título de la tarea
  - Flujo asociado
  - Asignado actual
  - Prioridad y estado
  - Días de atraso destacados
  - Descripción completa
  - Notas adicionales
  - Botón "Ver Tarea en Taskflow" (link directo)
  - Sección de acciones recomendadas
  - Footer profesional

---

## 🧪 Pruebas

Ver archivo completo: **TEST_SLA_SYSTEM.md**

### Quick Test

```sql
-- Crear tarea de prueba vencida hace 1 día
INSERT INTO tasks (title, flow_id, assignee_id, status, sla_due_date, sla_breached, sla_notified_assignee, sla_escalated, created_at, updated_at)
VALUES ('Test SLA Warning', 1, 2, 'in_progress', DATE_SUB(NOW(), INTERVAL 25 HOUR), 0, 0, 0, NOW(), NOW());

-- Ejecutar comando
php artisan sla:check --verbose

-- Verificar notificación creada
SELECT * FROM notifications WHERE type = 'sla_warning' ORDER BY created_at DESC LIMIT 1;
```

---

## 📐 Arquitectura del Sistema

```
┌──────────────────────────────────────────────────────────────┐
│                    Laravel Scheduler                         │
│                    (routes/console.php)                      │
│                   Ejecuta cada hora                          │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│               CheckSLAAlerts Command                         │
│         (app/Console/Commands/CheckSLAAlerts.php)           │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│            SLANotificationService                            │
│         (app/Services/SlaNotificationService.php)           │
│                                                              │
│  • checkAllTasks() → Verificar todas                       │
│  • checkTask() → Verificar una                             │
│  • notifyAssignee() → Alerta +1 día                        │
│  • escalateToSupervisor() → Escalar +2 días               │
└────────────────────────┬─────────────────────────────────────┘
                         │
           ┌─────────────┴──────────────┐
           │                            │
           ▼                            ▼
┌──────────────────────┐    ┌──────────────────────┐
│   Notification       │    │   Email              │
│   (Base de datos)    │    │   (SLAEscalationMail)│
└──────────┬───────────┘    └──────────┬───────────┘
           │                            │
           ▼                            ▼
┌──────────────────────────────────────────────────────────────┐
│                  Broadcasting (Reverb)                       │
│              → users.{assignee_id}                          │
│              → users.{supervisor_id}                        │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                    Frontend (Vue 3)                          │
│                                                              │
│  • notifications.js → addSLAAlert()                         │
│  • SLAAlertBadge.vue → Mostrar badge                       │
│  • TaskTreeItem.vue → Calcular estado SLA                  │
│  • NotificationToast.vue → Toast automático                │
└──────────────────────────────────────────────────────────────┘
```

---

## 🔐 Seguridad y Prevención

### Prevención de Spam

- ✅ Verificación de duplicados (60 minutos)
- ✅ Solo envía una alerta por tarea por tipo
- ✅ No reenvía alertas ya procesadas

### Validaciones

- ✅ Verifica que assignee_id existe antes de notificar
- ✅ Verifica que supervisor existe antes de escalar
- ✅ Valida que sla_due_date está definido
- ✅ Excluye tareas completadas y canceladas

### Manejo de Errores

- ✅ Try-catch en envío de emails
- ✅ Logs detallados en Laravel log
- ✅ Comando retorna códigos de error apropiados
- ✅ Frontend maneja errores de API gracefully

---

## 📈 Métricas y Monitoreo

### Logs de Laravel

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log | grep -i "sla\|escalation"

# Buscar estadísticas
grep "Proceso de SLA completado" storage/logs/laravel.log
```

### Base de Datos

```sql
-- Tareas con SLA vencido actualmente
SELECT COUNT(*) as total_breached
FROM tasks
WHERE sla_breached = true
  AND status NOT IN ('completed', 'cancelled');

-- Tareas notificadas pero no escaladas
SELECT COUNT(*) as pending_escalation
FROM tasks
WHERE sla_notified_assignee = true
  AND sla_escalated = false
  AND status NOT IN ('completed', 'cancelled');

-- Promedio de días de atraso
SELECT AVG(sla_days_overdue) as avg_days
FROM tasks
WHERE sla_breached = true
  AND status NOT IN ('completed', 'cancelled');
```

---

## 🎓 Mejoras Futuras Sugeridas

### Backend

1. **Dashboard de Métricas SLA**
   - Endpoint `/api/v1/reports/sla-metrics`
   - Gráficas de tendencias
   - Tareas más atrasadas

2. **Notificaciones Slack**
   - Integración con webhooks de Slack
   - Mensajes formateados con botones de acción

3. **Configuración por Flujo**
   - Umbrales SLA personalizados por tipo de flujo
   - Diferentes supervisores por proyecto

4. **Historial de Alertas**
   - Tabla `sla_alerts_log` para auditoría
   - Tracking de tiempo de resolución

### Frontend

1. **Widget de Dashboard SLA**
   - Resumen de alertas activas
   - Gráfico de tareas atrasadas
   - Lista de críticas

2. **Filtros Avanzados**
   - Filtrar por días de atraso
   - Filtrar por prioridad
   - Filtrar por flujo

3. **Sonido Personalizable**
   - Diferentes sonidos por tipo de alerta
   - Opción de silenciar

---

## ✅ Checklist de Implementación

- [x] Modelo Task con métodos SLA
- [x] SLANotificationService completo
- [x] Comando Artisan CheckSLAAlerts
- [x] Scheduler configurado
- [x] Configuración config/sla.php
- [x] Variables .env agregadas
- [x] TaskObserver con resolución automática
- [x] Mail SLAEscalationMail
- [x] Template Blade de email
- [x] Store notifications.js actualizado
- [x] Componente SLAAlertBadge.vue
- [x] TaskTreeItem.vue con badges
- [x] NotificationsView.vue con filtro
- [x] Documentación de pruebas
- [x] Prevención de duplicados
- [x] Broadcasting en tiempo real
- [x] Toasts automáticos
- [x] Emails con cola

---

## 🎉 ¡Sistema Completamente Funcional!

El Sistema de Alertas SLA está **100% implementado y listo para producción**.

Para comenzar a usarlo:

1. Configura las variables en `.env`
2. Ejecuta `php artisan sla:check --verbose` para verificar
3. Espera que el scheduler ejecute automáticamente cada hora
4. ¡Observa las alertas en tiempo real en el frontend!

**Documentación adicional:**
- Ver `TEST_SLA_SYSTEM.md` para guía completa de pruebas
- Ver `ARQUITECTURA.md` para documentación general del sistema

---

**Implementado por:** Claude Sonnet 4.5
**Fecha:** 21 de enero de 2026
**Versión:** Taskflow v1 - Sistema SLA v1.0
