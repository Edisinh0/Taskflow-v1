# 🧪 TESTING: Widget SLA en Tareas Urgentes

## ✅ CORRECCIONES APLICADAS

### Problema Original:
1. ❌ Al atrasar una tarea → NO aparecía en "Tareas Urgentes"
2. ❌ Al adelantar una tarea → NO desaparecía de "Tareas Urgentes"
3. ❌ Widget no se actualizaba en tiempo real

### Solución Implementada:
1. ✅ **Backend:** `sla_due_date` se sincroniza automáticamente con `estimated_end_at`
2. ✅ **Backend:** Evento `SLAStatusChanged` se dispara SIEMPRE que cambian fechas
3. ✅ **Frontend:** Dashboard escucha `SLAStatusChanged` y recarga datos automáticamente

---

## 🎯 PRUEBA RÁPIDA (3 minutos)

### PASO 1: Preparar datos de prueba

```bash
# Ejecutar este comando para crear una tarea de prueba
docker exec taskflow_db_new mysql -u taskflow_user -ptaskflow_password taskflow_db -e "
DELETE FROM tasks WHERE title LIKE 'PRUEBA WIDGET%';

INSERT INTO tasks (
    flow_id,
    title,
    status,
    priority,
    assignee_id,
    estimated_end_at,
    created_at,
    updated_at
)
VALUES (
    1,
    'PRUEBA WIDGET: Tarea Normal',
    'in_progress',
    'medium',
    3,
    DATE_ADD(NOW(), INTERVAL 5 DAY),
    NOW(),
    NOW()
);
"

echo "✅ Tarea de prueba creada"
echo "   Título: PRUEBA WIDGET: Tarea Normal"
echo "   Fecha: +5 días (NO atrasada)"
echo "   Asignado a: Usuario ID 3"
```

---

### PASO 2: Verificar que NO aparece en Tareas Urgentes

1. Abrir navegador: `http://localhost/dashboard`
2. Iniciar sesión como usuario ID 3
3. **Verificar:** Sección "Tareas Urgentes" NO debe mostrar "PRUEBA WIDGET"
4. ✅ Correcto si NO aparece

---

### PASO 3: ATRASAR la tarea (debe aparecer en Tareas Urgentes)

**Abrir DevTools (F12) → Console** (importante para ver eventos)

```bash
# Atrasar la tarea a hace 2 días
docker exec taskflow_db_new mysql -u taskflow_user -ptaskflow_password taskflow_db -e "
UPDATE tasks
SET estimated_end_at = DATE_SUB(NOW(), INTERVAL 2 DAY),
    updated_at = NOW()
WHERE title = 'PRUEBA WIDGET: Tarea Normal';
"

echo "✅ Tarea atrasada 2 días (48 horas = CRÍTICA)"
```

**EN LA CONSOLA DEL NAVEGADOR DEBE APARECER:**
```
🚨 Estado SLA cambió en Dashboard: {old_status: "none", new_status: "critical"}
📊 Detalles: {task_id: 123, ...}
🔄 Recargando dashboard por cambio de estado SLA
```

**VERIFICAR VISUALMENTE (en menos de 2 segundos):**
- ✅ Tarea "PRUEBA WIDGET" APARECE en "Tareas Urgentes"
- ✅ Widget muestra: 🚨 **CRÍTICA** (+2d)
- ✅ Contador "X pendientes" aumenta

**Si NO aparece:**
- Recargar la página manualmente (Ctrl+R)
- Si sigue sin aparecer → Ver sección "Debugging" abajo

---

### PASO 4: ADELANTAR la tarea (debe desaparecer)

**Mantener DevTools abierto en Console**

```bash
# Adelantar la tarea a +10 días en el futuro
docker exec taskflow_db_new mysql -u taskflow_user -ptaskflow_password taskflow_db -e "
UPDATE tasks
SET estimated_end_at = DATE_ADD(NOW(), INTERVAL 10 DAY),
    updated_at = NOW()
WHERE title = 'PRUEBA WIDGET: Tarea Normal';
"

echo "✅ Tarea adelantada a +10 días (YA NO atrasada)"
```

**EN LA CONSOLA DEL NAVEGADOR DEBE APARECER:**
```
🚨 Estado SLA cambió en Dashboard: {old_status: "critical", new_status: "none"}
📊 Detalles: {task_id: 123, ...}
🔄 Recargando dashboard por cambio de estado SLA
```

**VERIFICAR VISUALMENTE (en menos de 2 segundos):**
- ✅ Tarea "PRUEBA WIDGET" DESAPARECE de "Tareas Urgentes"
- ✅ Contador "X pendientes" disminuye

---

### PASO 5: Limpiar datos de prueba

```bash
docker exec taskflow_db_new mysql -u taskflow_user -ptaskflow_password taskflow_db -e "
DELETE FROM tasks WHERE title LIKE 'PRUEBA WIDGET%';
"

echo "✅ Datos de prueba eliminados"
```

---

## 🎮 PRUEBA MANUAL (usando UI)

### OPCIÓN A: Crear tarea atrasada desde TaskModal

1. Ir a un Flujo
2. Click "Nueva Tarea"
3. Llenar datos:
   - Título: "Tarea de prueba UI"
   - Asignado a: Tu usuario
   - **Fecha Término: Ayer** (fecha pasada)
4. Guardar
5. Ir al Dashboard
6. **Verificar:** Tarea aparece en "Tareas Urgentes" con widget 🚨

### OPCIÓN B: Editar tarea existente

1. En "Tareas Urgentes", click en una tarea atrasada
2. Cambiar "Fecha Término" a: **+10 días en el futuro**
3. Guardar
4. **Verificar:** Tarea desaparece de "Tareas Urgentes" en menos de 2 segundos

---

## 🐛 DEBUGGING

### Si NO aparecen eventos en Console

```bash
# 1. Verificar logs del backend
docker exec taskflow_backend_new tail -f storage/logs/laravel.log | grep "SLA"

# Debe mostrar:
# 🔄 Sincronizando sla_due_date con estimated_end_at
# 📅 Fechas o estado cambiaron, recalculando SLA
# 🚨 Disparando evento SLAStatusChanged
```

**Si NO aparece en logs:**
- El Observer NO se ejecutó
- Verificar que el comando UPDATE realmente modificó la tarea

---

### Si eventos aparecen en Console pero widget NO cambia

```javascript
// En Console del navegador, ejecutar:
console.log('allTasksData:', allTasksData.value)

// Buscar tu tarea en el array
// Verificar que sla_due_date esté actualizado
```

**Si sla_due_date está desactualizado:**
- `loadData()` no se ejecutó
- Verificar Network tab → Debe haber request a `/api/v1/tasks`

---

### Si widget cambia pero reaparece al recargar

```bash
# Verificar en BD que la fecha se guardó correctamente
docker exec taskflow_db_new mysql -u taskflow_user -ptaskflow_password taskflow_db -e "
SELECT
    id,
    title,
    estimated_end_at,
    sla_due_date,
    TIMESTAMPDIFF(HOUR, sla_due_date, NOW()) as horas_atraso
FROM tasks
WHERE title LIKE 'PRUEBA WIDGET%';
"
```

**Si horas_atraso >= 24:**
- La tarea SIGUE atrasada, debe aparecer
- Verificar que realmente modificaste la fecha al futuro

---

## 📊 ESTADOS ESPERADOS

| Horas de Atraso | Estado | Widget | Color |
|-----------------|--------|--------|-------|
| < 0 (futuro) | none | NO muestra | - |
| 0-23 horas | none | NO muestra | - |
| 24-47 horas | warning | ⚠️ ALERTA | Amarillo |
| 48+ horas | critical | 🚨 CRÍTICA | Rojo (pulsante) |

---

## ✅ RESULTADO ESPERADO

### Escenario 1: Tarea con fecha futura (+5 días)
- ❌ NO aparece en "Tareas Urgentes"
- ❌ NO tiene widget

### Escenario 2: Misma tarea atrasada (-2 días)
- ✅ APARECE en "Tareas Urgentes"
- ✅ Widget: 🚨 **CRÍTICA** (+2d)
- ✅ Fondo rojo con animación de pulso

### Escenario 3: Editar y adelantar a (+10 días)
- ✅ DESAPARECE de "Tareas Urgentes"
- ✅ Widget desaparece
- ✅ Todo en menos de 2 segundos (sin recargar página)

---

## 🚀 ARCHIVOS MODIFICADOS

| Archivo | Cambio |
|---------|--------|
| `taskflow-backend/app/Observers/TaskObserver.php` | Sincronización automática de `sla_due_date` con `estimated_end_at` |
| `taskflow-backend/app/Models/Task.php` | Método `recalculateSLAStatus()` |
| `taskflow-backend/app/Services/SLAService.php` | Limpieza de alertas obsoletas |
| `taskflow-backend/app/Events/SLAStatusChanged.php` | Evento WebSocket |
| `taskflow-frontend/src/views/DashboardView.vue` | Listener para `SLAStatusChanged` |
| `taskflow-frontend/src/composables/useRealtime.js` | Composable `useSLAStatusChanges()` |

---

**Fecha:** 2026-01-23
**Sistema:** Taskflow v1
**Fix Versión:** 2.1 (sincronización automática)
