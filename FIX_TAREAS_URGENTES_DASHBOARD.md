# 🔧 FIX: Tareas Urgentes en Dashboard - Sistema SLA

## 📋 PROBLEMA IDENTIFICADO

**Síntoma:** El grid "Tareas Urgentes" en el dashboard estaba VACÍO aunque existían tareas con SLA vencido en la base de datos.

**Causa Raíz:**
1. ❌ El dashboard solo consultaba las tareas del usuario logueado (`assignee_id` filtrado)
2. ❌ Las estadísticas de `urgentTasks` solo contaban tareas con `priority === 'urgent'`
3. ❌ No se consideraban tareas con `sla_breached = true` o `sla_days_overdue > 0`

---

## ✅ SOLUCIÓN IMPLEMENTADA

### Archivo Modificado: `taskflow-frontend/src/views/DashboardView.vue`

#### 1. **Obtener TODAS las tareas para el grid de urgentes**

**ANTES:**
```javascript
const loadData = async () => {
  try {
    const [flowsRes, tasksRes] = await Promise.all([
      flowsAPI.getAll(),
      tasksAPI.getAll({ assignee_id: authStore.currentUser?.id }) // ❌ Solo del usuario
    ])

    const flows = flowsRes.data.data
    const tasks = tasksRes.data.data

    // Guardar todas las tareas para el computed
    allTasksData.value = tasks // ❌ Solo tareas del usuario actual
```

**DESPUÉS:**
```javascript
const loadData = async () => {
  try {
    const [flowsRes, tasksRes, allTasksRes] = await Promise.all([
      flowsAPI.getAll(),
      tasksAPI.getAll({ assignee_id: authStore.currentUser?.id }), // Stats del usuario
      tasksAPI.getAll() // ✅ TODAS las tareas para el grid urgentes
    ])

    const flows = flowsRes.data.data
    const tasks = tasksRes.data.data // Tareas del usuario actual (para stats)
    const allTasks = allTasksRes.data.data // ✅ Todas las tareas (para grid urgentes)

    // Guardar TODAS las tareas para el computed (no solo las del usuario)
    allTasksData.value = allTasks // ✅ Ahora incluye todas las tareas
```

**Razón del cambio:**
- Las **estadísticas** (stats) son personales del usuario logueado
- El **grid de "Tareas Urgentes"** debe mostrar TODAS las tareas críticas del sistema, no solo las del usuario
- Esto permite a PMs/Admins ver todas las tareas con SLA vencido

---

#### 2. **Actualizar estadística `urgentTasks` para incluir SLA**

**ANTES:**
```javascript
stats.value = {
  // ...
  urgentTasks: tasks.filter(t => t.priority === 'urgent' && t.status !== 'completed').length,
  // ❌ Solo cuenta tareas con priority = 'urgent'
}
```

**DESPUÉS:**
```javascript
stats.value = {
  // ...
  // Incluir tareas con priority=urgent O con SLA vencido
  urgentTasks: tasks.filter(t => {
    if (t.status === 'completed' || t.status === 'cancelled') return false
    return t.priority === 'urgent' || t.sla_breached === true || t.sla_days_overdue > 0
  }).length,
  // ✅ Ahora incluye: priority urgent, SLA vencido, o días de atraso
}
```

**Razón del cambio:**
- Una tarea es "urgente" si:
  - ✅ Tiene `priority = 'urgent'`, O
  - ✅ Tiene `sla_breached = true`, O
  - ✅ Tiene `sla_days_overdue > 0`
- El contador en el card "Tareas Pendientes" ahora refleja la realidad

---

#### 3. **Mejorar visualización con badges por tipo de urgencia**

**ANTES:**
```vue
<div class="flex items-start justify-between mb-2">
  <div class="flex-1">
    <div class="flex items-center gap-2 mb-1">
      <h4>{{ task.title }}</h4>
      <!-- Solo badge SLA -->
      <SLAAlertBadge
        v-if="getSLAStatus(task)"
        :alert-type="getSLAStatus(task)"
        :days-overdue="getDaysOverdue(task)"
      />
    </div>
  </div>
  <span class="bg-rose-50 ...">  <!-- ❌ Siempre color rose -->
    {{ getDaysRemaining(task.estimated_end_at) }}
  </span>
</div>
```

**DESPUÉS:**
```vue
<div class="flex items-start justify-between mb-2">
  <div class="flex-1">
    <div class="flex items-center gap-2 mb-1">
      <h4>{{ task.title }}</h4>
      <!-- Badge SLA si la tarea tiene SLA vencido -->
      <SLAAlertBadge
        v-if="getSLAStatus(task)"
        :alert-type="getSLAStatus(task)"
        :days-overdue="getDaysOverdue(task)"
        class="shrink-0"
      />
      <!-- ✅ Badge de prioridad urgente (solo si no tiene SLA vencido) -->
      <span
        v-else-if="task.priority === 'urgent'"
        class="px-2 py-0.5 bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 text-[10px] font-bold uppercase tracking-wider rounded-full border border-rose-200 dark:border-rose-500/20"
      >
        Urgente
      </span>
    </div>
  </div>
  <!-- ✅ Color dinámico según severidad -->
  <span
    :class="[
      'px-2.5 py-1 text-xs font-bold rounded-lg border shadow-sm shrink-0 ml-3',
      getSLAStatus(task) === 'escalation' ? 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 border-red-200 dark:border-red-500/20' :
      getSLAStatus(task) === 'warning' ? 'bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-500/20' :
      'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-100 dark:border-rose-500/20'
    ]"
  >
    {{ getDaysRemaining(task.estimated_end_at) }}
  </span>
</div>
```

**Razón del cambio:**
- ✅ Muestra badge "URGENTE" para tareas con `priority = urgent` (sin SLA vencido)
- ✅ Muestra badge SLA (warning/escalation) para tareas con SLA vencido
- ✅ El badge de días restantes cambia de color según severidad:
  - 🔴 **Rojo** → SLA escalation (48+ horas vencido)
  - 🟠 **Naranja** → SLA warning (24+ horas vencido)
  - 🌹 **Rose** → Prioridad urgent (sin SLA)

---

## 🧪 PRUEBAS REALIZADAS

### Test 1: Tarea con SLA vencido (3 días)

**Tarea creada:**
```sql
INSERT INTO tasks (
  flow_id, title, status, priority, assignee_id,
  estimated_end_at, sla_due_date, sla_breached, sla_days_overdue
) VALUES (
  1, 'TEST DASHBOARD: Tarea con SLA Vencido',
  'in_progress', 'medium', 3,
  DATE_SUB(NOW(), INTERVAL 3 DAY),
  DATE_SUB(NOW(), INTERVAL 3 DAY),
  1, 3
);
```

**Datos verificados en backend:**
```json
{
  "id": 239,
  "title": "TEST DASHBOARD: Tarea con SLA Vencido",
  "priority": "medium",
  "status": "in_progress",
  "sla_due_date": "2026-01-18T17:48:51.000000Z",
  "sla_breached": true,
  "sla_days_overdue": 3,
  "assignee_id": 3
}
```

**Resultado:**
✅ La tarea ahora aparece en el grid "Tareas Urgentes"
✅ Muestra badge SLA con estado "escalation" (48+ horas)
✅ El contador de "urgentes" se incrementó correctamente

---

## 📊 COMPORTAMIENTO ACTUALIZADO

### Grid "Tareas Urgentes" ahora muestra:

| Tipo de Tarea | Condición | Badge | Color Badge Días |
|---------------|-----------|-------|------------------|
| SLA Escalation | `sla_days_overdue >= 2` | 🚨 CRÍTICO +X días | 🔴 Rojo |
| SLA Warning | `sla_days_overdue >= 1` | ⚠️ SLA +X días | 🟠 Naranja |
| Priority Urgent | `priority = 'urgent'` | 🔴 URGENTE | 🌹 Rose |

### Ordenamiento del Grid:

1. **SLA Escalation (48+ horas)** - Más críticas
2. **SLA Warning (24+ horas)** - Críticas
3. **Priority Urgent** - Urgentes
4. **Priority High, Medium, Low** - Por prioridad

Máximo 10 tareas mostradas (más urgentes).

---

## 🔍 VERIFICACIÓN EN PRODUCCIÓN

### Paso 1: Crear tarea de prueba
```bash
docker exec taskflow_db_new mysql -u taskflow_user -ptaskflow_password taskflow_db -e "
INSERT INTO tasks (flow_id, title, status, priority, assignee_id, sla_due_date, sla_breached, sla_days_overdue, created_at, updated_at)
VALUES (1, 'TEST: SLA Vencido', 'in_progress', 'medium', 3, DATE_SUB(NOW(), INTERVAL 2 DAY), 1, 2, NOW(), NOW());
"
```

### Paso 2: Verificar en frontend
1. Iniciar sesión en el dashboard
2. Verificar que la tarea aparece en "Tareas Urgentes"
3. Verificar que muestra el badge SLA correspondiente
4. Verificar que el contador "X urgentes" se actualiza

### Paso 3: Limpiar
```bash
docker exec taskflow_db_new mysql -u taskflow_user -ptaskflow_password taskflow_db -e "
DELETE FROM tasks WHERE title LIKE 'TEST%';
"
```

---

## 📁 ARCHIVOS MODIFICADOS

| Archivo | Cambios | Líneas |
|---------|---------|--------|
| `taskflow-frontend/src/views/DashboardView.vue` | • loadData: fetch all tasks<br>• stats.urgentTasks: incluir SLA<br>• Template: badges dinámicos | 678-705,<br>243-276 |

**Total de archivos modificados:** 1

---

## 🎯 CÓDIGO RELEVANTE

### Computed `computedUrgentTasks` (YA EXISTÍA - NO MODIFICADO)

```javascript
// Computed para obtener tareas urgentes (incluyendo SLA atrasadas)
const computedUrgentTasks = computed(() => {
  const tasks = allTasksData.value.filter(t => {
    // Excluir tareas completadas
    if (t.status === 'completed' || t.status === 'cancelled') return false

    // Incluir tareas con SLA vencido
    const slaStatus = getSLAStatus(t)
    if (slaStatus) return true

    // Incluir tareas con prioridad urgente
    if (t.priority === 'urgent') return true

    return false
  })

  // Ordenar por criticidad:
  // 1. SLA escalation (48+ horas)
  // 2. SLA warning (24+ horas)
  // 3. Prioridad urgent
  return tasks.sort((a, b) => {
    const slaA = getSLAStatus(a)
    const slaB = getSLAStatus(b)

    // Prioridad a escalations
    if (slaA === 'escalation' && slaB !== 'escalation') return -1
    if (slaB === 'escalation' && slaA !== 'escalation') return 1

    // Luego warnings
    if (slaA === 'warning' && slaB !== 'warning') return -1
    if (slaB === 'warning' && slaA !== 'warning') return 1

    // Finalmente por prioridad
    const priorityOrder = { urgent: 0, high: 1, medium: 2, low: 3 }
    return (priorityOrder[a.priority] || 3) - (priorityOrder[b.priority] || 3)
  }).slice(0, 10) // Limitar a 10 tareas más urgentes
})
```

**NOTA:** Este computed YA estaba correctamente implementado. El problema era que `allTasksData` no contenía todas las tareas del sistema.

---

## ✅ RESULTADO FINAL

**ANTES DEL FIX:**
- ❌ Grid "Tareas Urgentes" vacío aunque había tareas con SLA vencido
- ❌ Solo mostraba tareas del usuario logueado
- ❌ Solo contaba `priority = 'urgent'`

**DESPUÉS DEL FIX:**
- ✅ Grid muestra TODAS las tareas urgentes del sistema
- ✅ Incluye tareas con SLA vencido (sla_breached, sla_days_overdue)
- ✅ Incluye tareas con priority urgent
- ✅ Badges diferenciados por tipo de urgencia
- ✅ Colores dinámicos según severidad
- ✅ Ordenamiento por criticidad (escalation > warning > urgent)

---

## 🎓 NOTAS TÉCNICAS

### ¿Por qué se necesitan TODAS las tareas?

El dashboard es una vista **global** del sistema, especialmente útil para:
- **Project Managers**: Ver todas las tareas críticas del equipo
- **Admins**: Monitorear el estado general del sistema
- **Supervisores**: Detectar cuellos de botella y tareas escaladas

Si solo mostráramos las tareas del usuario logueado, un PM no vería las tareas urgentes de su equipo.

### ¿Afecta el rendimiento?

No significativamente porque:
- Se usan `Promise.all()` para consultas paralelas
- Las tareas ya están en memoria (2 consultas en lugar de 1)
- El computed `computedUrgentTasks` limita a 10 tareas máximo
- Paginación futura podría optimizar aún más

### Backend ya envía los campos SLA correctamente

Verificado con tinker:
```json
{
  "sla_due_date": "2026-01-18T17:48:51.000000Z",
  "sla_breached": true,
  "sla_breach_at": null,
  "sla_days_overdue": 3,
  "sla_notified_assignee": false,
  "sla_escalated": false
}
```

No se requiere crear un TaskResource porque el modelo Task ya serializa correctamente todos los campos SLA.

---

**Fecha de implementación:** 2026-01-21
**Sistema:** Taskflow v1 (Laravel 11 + Vue 3)
**Estado:** ✅ COMPLETADO Y PROBADO
