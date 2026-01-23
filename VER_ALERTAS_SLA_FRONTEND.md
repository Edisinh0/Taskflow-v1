# 🎨 Cómo Ver las Alertas SLA en el Frontend

## ✅ El Sistema Ya Está Integrado

Todo el código frontend ya está implementado. Las alertas SLA se mostrarán **automáticamente** cuando:

1. Una tarea tenga `sla_due_date` definido
2. El `sla_due_date` esté vencido (en el pasado)
3. El status de la tarea sea `in_progress` o `pending` (no completada)

---

## 📍 Dónde Ver las Alertas SLA

### 1️⃣ Vista de Flujos (Lista de Tareas)

**Ubicación:** `/flows/{id}` (Vista de detalle del flujo)

**Qué verás:**

#### Badge de Warning (+1 día de atraso):
```
┌─────────────────────────────────────────────────┐
│ 📋 Tarea de Prueba SLA - Warning (+1 día)      │
│                                                 │
│ [⚠️ ALERTA (+1d)]  ← Badge amarillo            │
│ [EN PROGRESO]                                   │
│ [ALTA PRIORIDAD]                                │
└─────────────────────────────────────────────────┘
```

#### Badge de Escalation (+2 días de atraso):
```
┌─────────────────────────────────────────────────┐
│ 📋 Tarea de Prueba SLA - Escalation (+3 días)  │
│                                                 │
│ [🚨 CRÍTICA (+3d)]  ← Badge rojo pulsante      │
│ [EN PROGRESO]                                   │
│ [URGENTE]                                       │
└─────────────────────────────────────────────────┘
```

**Características visuales:**
- ⚠️ Badge amarillo para warning (24-48 horas)
- 🚨 Badge rojo pulsante para críticas (48+ horas)
- El badge se muestra **arriba de todos** los demás badges
- Incluye el número de días de atraso: `(+3d)`

---

### 2️⃣ Centro de Notificaciones

**Ubicación:** Click en el icono de campana 🔔 en el navbar

**Qué verás:**

1. **Badge con contador** en el icono de campana:
   ```
   🔔 (5)  ← Número de notificaciones sin leer
   ```

2. **Botón de filtro SLA:**
   ```
   [Todas] [No leídas] [🚨 SLA (2)]  ← Contador de alertas SLA
   ```

3. **Lista de notificaciones SLA:**
   ```
   ┌─────────────────────────────────────────────┐
   │ 🚨  ⚠️ Alerta SLA: 'Nombre de la Tarea'    │
   │     La tarea está 1 días atrasada.         │
   │     SLA: 2026-01-20 13:51:22               │
   │     🕐 Hace 5 minutos                       │
   └─────────────────────────────────────────────┘

   ┌─────────────────────────────────────────────┐
   │ 🚨  [ESCALACIÓN] Nombre de la Tarea        │
   │     La tarea está 3 días atrasada y        │
   │     requiere atención inmediata.           │
   │     🕐 Hace 10 minutos                      │
   └─────────────────────────────────────────────┘
   ```

---

### 3️⃣ Toasts (Notificaciones Emergentes)

**Ubicación:** Esquina superior derecha de la pantalla

**Cuándo aparecen:**
- Cuando el backend envía una notificación SLA vía WebSocket
- Automáticamente al detectar una nueva alerta

**Tipos de toasts:**

#### Toast de Warning (Amarillo):
```
┌─────────────────────────────────────┐
│ ⚠️ Alerta SLA                       │
│ La tarea tiene 1 días de atraso     │
│                                     │
│ [Cerrar]                            │
└─────────────────────────────────────┘
```
- Duración: 5 segundos
- Color: Amarillo
- Sin sonido

#### Toast de Escalation (Rojo):
```
┌─────────────────────────────────────┐
│ 🚨 ALERTA SLA CRÍTICA               │
│ La tarea tiene 3 días de atraso     │
│                                     │
│ [Cerrar]                            │
└─────────────────────────────────────┘
```
- Duración: 10 segundos
- Color: Rojo con animación de pulso
- **CON SONIDO de alerta** 🔊
- Clickeable para ir a la tarea

---

## 🧪 Prueba Paso a Paso

### Paso 1: Verificar que las Tareas Tienen SLA

```bash
docker exec -i taskflow_db_new mysql -utaskflow_user -ptaskflow_password taskflow_db -e "
SELECT id, title, status, sla_due_date,
       TIMESTAMPDIFF(HOUR, sla_due_date, NOW()) as hours_overdue
FROM tasks
WHERE sla_due_date IS NOT NULL
  AND status IN ('pending', 'in_progress')
LIMIT 5;"
```

**Deberías ver algo como:**
```
+-----+----------------------------------+-------------+---------------------+---------------+
| id  | title                            | status      | sla_due_date        | hours_overdue |
+-----+----------------------------------+-------------+---------------------+---------------+
| 231 | Tarea de Prueba SLA - Warning    | in_progress | 2026-01-20 13:51:22 |            25 |
| 232 | Tarea de Prueba SLA - Escalation | in_progress | 2026-01-18 14:52:28 |            72 |
+-----+----------------------------------+-------------+---------------------+---------------+
```

### Paso 2: Ejecutar el Comando SLA

```bash
./sla-check.sh --details
```

Esto creará las notificaciones en la base de datos.

### Paso 3: Abrir el Frontend

```
http://localhost:5173
```

### Paso 4: Iniciar Sesión

- Usuario: El usuario asignado a las tareas (assignee_id)
- Si no sabes cuál es, consulta:

```sql
SELECT u.id, u.name, u.email, t.title
FROM users u
JOIN tasks t ON t.assignee_id = u.id
WHERE t.sla_due_date IS NOT NULL
LIMIT 5;
```

### Paso 5: Navegar al Flujo

1. Click en **"Flujos"** en el menú
2. Busca el flujo que contiene las tareas de prueba (Flow ID 1 por ejemplo)
3. Click en el flujo para ver su detalle

### Paso 6: Verificar Badges SLA

En la lista de tareas, deberías ver:

- **Tarea con 25 horas de atraso** → Badge amarillo `⚠️ ALERTA (+1d)`
- **Tarea con 72 horas de atraso** → Badge rojo pulsante `🚨 CRÍTICA (+3d)`

### Paso 7: Verificar Notificaciones

1. Click en el icono de campana 🔔 en el navbar
2. Deberías ver un badge con número: `🔔 (2)` o más
3. Click en el botón **"🚨 SLA"**
4. Verás las notificaciones SLA filtradas

---

## 🔍 Si NO Ves los Badges

### Checklist de Debugging:

#### 1. Verificar que la API retorna `sla_due_date`

Abre DevTools (F12) → Network → Busca la request a `/api/v1/flows/{id}`

En el response, busca el array de `tasks` y verifica que cada tarea tenga:
```json
{
  "id": 231,
  "title": "Tarea de Prueba SLA - Warning",
  "status": "in_progress",
  "sla_due_date": "2026-01-20 13:51:22",  ← Debe existir
  "sla_breached": true,
  "sla_days_overdue": 1,
  ...
}
```

#### 2. Verificar Console de JavaScript

Abre DevTools (F12) → Console

Busca errores relacionados con:
- `SLAAlertBadge`
- `slaAlertStatus`
- `computed`

Si ves errores, cópialos y compártelos.

#### 3. Hard Refresh del Navegador

A veces el navegador cachea el código viejo:

- **Mac:** Cmd + Shift + R
- **Windows/Linux:** Ctrl + Shift + R

#### 4. Verificar que el Componente Está Importado

```bash
# Verificar que SLAAlertBadge.vue existe
ls -la taskflow-frontend/src/components/SLAAlertBadge.vue

# Verificar que TaskTreeItem.vue lo importa
grep -n "SLAAlertBadge" taskflow-frontend/src/components/TaskTreeItem.vue
```

#### 5. Ver Logs del Frontend

En la consola del navegador, escribe:
```javascript
// Ver el valor del computed slaAlertStatus para una tarea
console.log(document.querySelectorAll('[class*="task"]'))
```

---

## 🎬 Demo Visual del Flujo Completo

### Escenario: Tarea con 25 horas de atraso

```
1. Backend detecta SLA vencido (+25 horas)
   ↓
2. Comando SLA crea Notification tipo 'sla_warning'
   ↓
3. Broadcast vía Reverb a users.{assignee_id}
   ↓
4. Frontend recibe evento WebSocket
   ↓
5. Store notifications.js → addSLAAlert()
   ↓
6. Toast amarillo aparece: "⚠️ Alerta SLA - La tarea tiene 1 días de atraso"
   ↓
7. Usuario navega a /flows/{id}
   ↓
8. TaskTreeItem.vue calcula slaAlertStatus = 'warning'
   ↓
9. SLAAlertBadge.vue renderiza: [⚠️ ALERTA (+1d)]
   ↓
10. Badge amarillo visible junto a la tarea
```

### Escenario: Tarea con 72 horas de atraso

```
1. Backend detecta SLA crítico (+72 horas)
   ↓
2. Comando SLA crea Notification tipo 'sla_escalation'
   ↓
3. Envía email al supervisor
   ↓
4. Broadcast vía Reverb a users.{supervisor_id} y users.{assignee_id}
   ↓
5. Frontend recibe evento WebSocket
   ↓
6. Store notifications.js → addSLAAlert()
   ↓
7. Toast ROJO pulsante + SONIDO: "🚨 ALERTA SLA CRÍTICA"
   ↓
8. Usuario navega a /flows/{id}
   ↓
9. TaskTreeItem.vue calcula slaAlertStatus = 'escalation'
   ↓
10. SLAAlertBadge.vue renderiza: [🚨 CRÍTICA (+3d)] con animación de pulso
   ↓
11. Badge rojo visible y pulsando junto a la tarea
```

---

## 📸 Capturas de Pantalla Esperadas

### Vista de Tareas:
```
┌───────────────────────────────────────────────────────────┐
│ Flujo: Proyecto Demo                                      │
├───────────────────────────────────────────────────────────┤
│                                                           │
│  📋 Tarea Normal                                          │
│      [PENDIENTE] [MEDIA]                                  │
│                                                           │
│  📋 Tarea de Prueba SLA - Warning                         │
│      [⚠️ ALERTA (+1d)] [EN PROGRESO] [ALTA]              │
│                                                           │
│  📋 Tarea de Prueba SLA - Escalation                      │
│      [🚨 CRÍTICA (+3d)] [EN PROGRESO] [URGENTE]          │
│                                                           │
└───────────────────────────────────────────────────────────┘
```

### Centro de Notificaciones:
```
┌───────────────────────────────────────────────────────────┐
│ Notificaciones                        [Marcar todas leídas]│
├───────────────────────────────────────────────────────────┤
│                                                           │
│ [Todas] [No leídas] [🚨 SLA (2)]                         │
│                                                           │
│ ┌───────────────────────────────────────────────────┐   │
│ │ 🚨  ⚠️ Alerta SLA: 'Tarea Warning'                │   │
│ │     La tarea está 1 días atrasada.                │   │
│ │     🕐 Hace 5 minutos                              │   │
│ └───────────────────────────────────────────────────┘   │
│                                                           │
│ ┌───────────────────────────────────────────────────┐   │
│ │ 🚨  [ESCALACIÓN] Tarea Escalation                 │   │
│ │     La tarea está 3 días atrasada y requiere      │   │
│ │     atención inmediata.                            │   │
│ │     🕐 Hace 10 minutos                             │   │
│ └───────────────────────────────────────────────────┘   │
│                                                           │
└───────────────────────────────────────────────────────────┘
```

---

## ✅ Resumen

**Para ver las alertas SLA en el frontend:**

1. ✅ Crear tareas con `sla_due_date` vencido (ya hecho)
2. ✅ Ejecutar `./sla-check.sh` (crea notificaciones)
3. ✅ Abrir frontend en `http://localhost:5173`
4. ✅ Iniciar sesión con el usuario asignado
5. ✅ Navegar al flujo → **Ver badges SLA**
6. ✅ Click en campana → **Ver notificaciones SLA**

**El frontend YA está completamente implementado** - solo necesitas que las tareas tengan datos SLA y que el comando haya corrido.

Si después de seguir estos pasos NO ves los badges, avísame y te ayudo a debuggear! 🐛
