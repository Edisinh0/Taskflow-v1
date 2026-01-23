# 🧪 TEST: Widget SLA - Prueba Frontend

## 🎯 OBJETIVO
Verificar que el widget SLA aparece/desaparece correctamente cuando editas una tarea desde el frontend.

---

## ✅ CORRECCIONES APLICADAS

### Problema Identificado:
1. ❌ TaskObserver NO se ejecutaba con `UPDATE` SQL directo
2. ❌ El evento `SLAStatusChanged` NO se disparaba al editar desde frontend
3. ❌ `sla_due_date` NO se sincronizaba con `estimated_end_at`

### Solución Implementada:
1. ✅ **MySQL Trigger:** `sync_sla_due_date_before_update` sincroniza automáticamente `sla_due_date` con `estimated_end_at` ANTES de UPDATE
2. ✅ **MySQL Trigger:** `sync_sla_due_date_before_insert` asigna `sla_due_date` en creación si no existe
3. ✅ **TaskController:** Verifica cambios de estado SLA después de `update()` y dispara evento manualmente

---

## 📋 PRUEBA PASO A PASO (5 minutos)

### PASO 1: Crear Tarea Normal

1. Ir a: `http://localhost/flows/1` (o cualquier flujo)
2. Click "Nueva Tarea"
3. Llenar:
   - **Título:** `TEST WIDGET: Tarea Normal`
   - **Descripción:** `Prueba de widget SLA`
   - **Responsable:** Tu usuario
   - **Prioridad:** Media
   - **Fecha Inicio:** Hoy
   - **Fecha Término:** **+5 días en el futuro** (ej: 2026-01-28)
4. Guardar
5. Ir a Dashboard: `http://localhost/dashboard`
6. **VERIFICAR:** Tarea NO aparece en "Tareas Urgentes" ✅

---

### PASO 2: Atrasar la Tarea (Debe Aparecer Widget)

**Abrir DevTools (F12) → Console** (importante para ver eventos)

1. En el listado de tareas del flujo, click en la tarea "TEST WIDGET: Tarea Normal"
2. Click "Editar" (ícono de lápiz)
3. Cambiar **Fecha Término** a: **Ayer** (ej: 2026-01-22)
4. Guardar

**EN LA CONSOLA DEL NAVEGADOR DEBE APARECER:**
```
🚨 Estado SLA cambió en Dashboard: {old_status: "none", new_status: "critical"}
📊 Detalles: {task_id: xxx, ...}
🔄 Recargando dashboard por cambio de estado SLA
```

**VERIFICAR VISUALMENTE (en menos de 2 segundos):**
- ✅ Dashboard se recarga automáticamente
- ✅ Tarea aparece en "Tareas Urgentes"
- ✅ Widget muestra: 🚨 **CRÍTICA** (+Xd)
- ✅ Contador "X pendientes" aumenta

**Si NO aparece:**
- Recargar página manualmente (Ctrl+R)
- Verificar que la tarea tenga más de 48 horas de retraso
- Ver sección "Debugging" abajo

---

### PASO 3: Adelantar la Tarea (Debe Desaparecer Widget)

**Mantener DevTools abierto en Console**

1. Click en la tarea "TEST WIDGET: Tarea Normal" en "Tareas Urgentes"
2. Click "Editar"
3. Cambiar **Fecha Término** a: **+10 días en el futuro** (ej: 2026-02-02)
4. Guardar

**EN LA CONSOLA DEL NAVEGADOR DEBE APARECER:**
```
🚨 Estado SLA cambió en Dashboard: {old_status: "critical", new_status: "none"}
📊 Detalles: {task_id: xxx, ...}
🔄 Recargando dashboard por cambio de estado SLA
```

**VERIFICAR VISUALMENTE (en menos de 2 segundos):**
- ✅ Tarea DESAPARECE de "Tareas Urgentes"
- ✅ Widget desaparece
- ✅ Contador "X pendientes" disminuye

---

## 🐛 DEBUGGING

### Si NO aparecen eventos en Console

```bash
# Verificar logs del backend
docker exec taskflow_backend_new tail -50 storage/logs/laravel.log | grep -E "SLA|TaskController::update"

# Debe mostrar:
# 🎯 TaskController::update() - ANTES de actualizar
# 📅 Fecha o estado cambió, verificando SLA
# 🔄 Comparando estados SLA en Controller
# 🚨 Disparando evento SLAStatusChanged desde Controller
```

**Si NO aparece en logs:**
- El frontend NO está enviando el request de actualización
- Verificar Network tab → Debe haber PUT request a `/api/v1/tasks/{id}`

---

### Si eventos aparecen pero widget NO cambia

```javascript
// En Console del navegador, ejecutar:
console.log('allTasksData:', allTasksData.value)

// Buscar tu tarea en el array
// Verificar que sla_due_date esté actualizado
```

**Si sla_due_date está desactualizado:**
- El trigger MySQL NO se ejecutó
- Verificar que el trigger esté creado:

```bash
docker exec taskflow_db_new mysql -u taskflow_user -ptaskflow_password taskflow_db -e "SHOW TRIGGERS LIKE 'tasks';"

# Debe mostrar:
# - sync_sla_due_date_before_update
# - sync_sla_due_date_before_insert
```

---

### Verificar Sincronización en BD

```bash
docker exec taskflow_db_new mysql -u taskflow_user -ptaskflow_password taskflow_db -e "
SELECT
    id,
    title,
    estimated_end_at,
    sla_due_date,
    TIMESTAMPDIFF(HOUR, sla_due_date, NOW()) as horas_atraso,
    CASE
        WHEN estimated_end_at = sla_due_date THEN 'SYNC OK'
        ELSE 'DESINCRONIZADO'
    END as sync_status
FROM tasks
WHERE title LIKE 'TEST WIDGET%';
"
```

**Resultado esperado:**
- `sync_status`: SYNC OK
- `horas_atraso`: Debe coincidir con el retraso real

---

## 📊 ESTADOS ESPERADOS

| Horas de Atraso | Estado | Widget | Aparece en Tareas Urgentes |
|-----------------|--------|--------|----------------------------|
| < 0 (futuro) | none | NO muestra | NO |
| 0-23 horas | none | NO muestra | NO |
| 24-47 horas | warning | ⚠️ ALERTA | SÍ |
| 48+ horas | critical | 🚨 CRÍTICA | SÍ |

---

## ✅ RESULTADO ESPERADO FINAL

### Escenario 1: Tarea con fecha futura (+5 días)
- ❌ NO aparece en "Tareas Urgentes"
- ❌ NO tiene widget

### Escenario 2: Editar y atrasar a (ayer)
- ✅ APARECE en "Tareas Urgentes"
- ✅ Widget: 🚨 **CRÍTICA**
- ✅ Actualización en tiempo real (< 2 segundos)

### Escenario 3: Editar y adelantar a (+10 días)
- ✅ DESAPARECE de "Tareas Urgentes"
- ✅ Widget desaparece
- ✅ Actualización en tiempo real (< 2 segundos)

---

## 🚀 ARCHIVOS MODIFICADOS

| Archivo | Cambio |
|---------|--------|
| `taskflow-backend/database/migrations/2026_01_23_000001_add_trigger_sync_sla_due_date.php` | **NUEVO** - Triggers MySQL para sincronización automática |
| `taskflow-backend/app/Http/Controllers/Api/TaskController.php` | Detección de cambios SLA y disparo manual de evento |
| `taskflow-backend/app/Observers/TaskObserver.php` | Mejora en detección de cambios con getOriginal() |

---

**Fecha:** 2026-01-23
**Sistema:** Taskflow v1
**Fix Versión:** 3.0 (database triggers + controller event dispatch)
