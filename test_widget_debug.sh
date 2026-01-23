#!/bin/bash

DB_CONTAINER="taskflow_db_new"
DB_USER="taskflow_user"
DB_PASS="taskflow_password"
DB_NAME="taskflow_db"

run_sql() {
    docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASS $DB_NAME -e "$1"
}

echo "🔍 DEBUGGING: Widget SLA no aparece al editar fecha"
echo "======================================================"
echo ""

# 1. Crear tarea con fecha futura
echo "📝 Paso 1: Creando tarea con fecha futura (+5 días)..."
run_sql "
DELETE FROM tasks WHERE title LIKE 'DEBUG WIDGET%';

INSERT INTO tasks (
    flow_id,
    title,
    status,
    priority,
    assignee_id,
    estimated_end_at,
    sla_due_date,
    created_at,
    updated_at
) VALUES (
    1,
    'DEBUG WIDGET: Tarea Normal',
    'in_progress',
    'medium',
    3,
    DATE_ADD(NOW(), INTERVAL 5 DAY),
    DATE_ADD(NOW(), INTERVAL 5 DAY),
    NOW(),
    NOW()
);
"

TASK_ID=$(run_sql "SELECT id FROM tasks WHERE title = 'DEBUG WIDGET: Tarea Normal' ORDER BY id DESC LIMIT 1;" | tail -n 1)
echo "✅ Tarea creada con ID: $TASK_ID"
echo ""

# 2. Verificar estado inicial
echo "📊 Estado INICIAL:"
run_sql "
SELECT 
    id,
    title,
    estimated_end_at,
    sla_due_date,
    TIMESTAMPDIFF(HOUR, NOW(), sla_due_date) as horas_restantes,
    status
FROM tasks 
WHERE id = $TASK_ID;
"
echo ""
echo "✅ La tarea NO debe estar en Tareas Urgentes (horas_restantes > 0)"
echo ""
echo "⏳ Esperando 3 segundos..."
sleep 3

# 3. SIMULAR EDICIÓN: Atrasar la fecha 2 días
echo "🔧 Paso 2: EDITANDO tarea - Atrasando 2 días (48 horas)..."
run_sql "
UPDATE tasks
SET 
    estimated_end_at = DATE_SUB(NOW(), INTERVAL 2 DAY),
    updated_at = NOW()
WHERE id = $TASK_ID;
"
echo ""

# 4. Verificar qué pasó con sla_due_date
echo "📊 Estado DESPUÉS de UPDATE:"
run_sql "
SELECT 
    id,
    title,
    estimated_end_at,
    sla_due_date,
    TIMESTAMPDIFF(HOUR, sla_due_date, NOW()) as horas_atraso,
    status
FROM tasks 
WHERE id = $TASK_ID;
"
echo ""

# 5. DIAGNÓSTICO
echo "🔍 DIAGNÓSTICO:"
echo "==============="
echo ""
echo "Si sla_due_date es DIFERENTE a estimated_end_at:"
echo "  ❌ ERROR: TaskObserver::saving() NO sincronizó correctamente"
echo ""
echo "Si sla_due_date es IGUAL a estimated_end_at:"
echo "  ✅ SYNC OK, pero verificar si se disparó evento SLAStatusChanged"
echo ""

# 6. Verificar si las fechas están sincronizadas
SYNC_CHECK=$(run_sql "
SELECT 
    CASE 
        WHEN estimated_end_at = sla_due_date THEN 'SINCRONIZADO'
        ELSE 'DESINCRONIZADO'
    END as sync_status
FROM tasks 
WHERE id = $TASK_ID;
" | tail -n 1)

echo "📌 Estado de sincronización: $SYNC_CHECK"
echo ""

if [ "$SYNC_CHECK" = "DESINCRONIZADO" ]; then
    echo "❌ PROBLEMA DETECTADO:"
    echo "   El Observer NO está sincronizando sla_due_date con estimated_end_at"
    echo ""
    echo "🔧 SOLUCIÓN:"
    echo "   Verificar que TaskObserver.php tenga el código de sincronización:"
    echo "   - Método: saving()"
    echo "   - Líneas: 29-51"
    echo ""
    echo "🧪 CORRECCIÓN MANUAL (para testing):"
    echo "   docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASS $DB_NAME -e \""
    echo "   UPDATE tasks"
    echo "   SET sla_due_date = estimated_end_at"
    echo "   WHERE id = $TASK_ID;"
    echo "   \""
else
    echo "✅ Sincronización correcta"
    echo ""
    echo "🔍 Ahora verificar backend logs para evento SLAStatusChanged:"
    echo "   docker exec taskflow_backend_new tail -50 storage/logs/laravel.log | grep 'SLA'"
fi

echo ""
echo "======================================================"
echo "🧹 Para limpiar: docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASS $DB_NAME -e \"DELETE FROM tasks WHERE id = $TASK_ID;\""
