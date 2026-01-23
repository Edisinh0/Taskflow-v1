#!/bin/bash

# Script de testing para verificar actualización en tiempo real de alertas SLA
# Taskflow v1 - 2026-01-23

set -e

DB_CONTAINER="taskflow_db_new"
BACKEND_CONTAINER="taskflow_backend_new"
DB_USER="taskflow_user"
DB_PASS="taskflow_password"
DB_NAME="taskflow_db"

echo "🧪 ====================================="
echo "   TEST: SLA Realtime Update"
echo "====================================="
echo ""

# Función para ejecutar SQL
run_sql() {
    docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASS $DB_NAME -e "$1"
}

# Función para mostrar resultado
show_result() {
    echo "✅ $1"
    echo ""
}

# Paso 1: Limpiar datos de prueba anteriores
echo "🧹 Paso 1: Limpiando datos de prueba anteriores..."
run_sql "DELETE FROM tasks WHERE title LIKE 'TEST SLA REALTIME%';"
run_sql "DELETE FROM notifications WHERE task_id IN (SELECT id FROM tasks WHERE title LIKE 'TEST SLA REALTIME%');"
show_result "Datos antiguos eliminados"

# Paso 2: Crear tarea atrasada (2 días atrás)
echo "📝 Paso 2: Creando tarea atrasada (48 horas = CRITICAL)..."
run_sql "
INSERT INTO tasks (flow_id, title, status, priority, assignee_id, estimated_end_at, sla_due_date, created_at, updated_at)
VALUES (
    1,
    'TEST SLA REALTIME: Tarea Crítica',
    'in_progress',
    'high',
    3,
    DATE_SUB(NOW(), INTERVAL 2 DAY),
    DATE_SUB(NOW(), INTERVAL 2 DAY),
    NOW(),
    NOW()
);
"

TASK_ID=$(run_sql "SELECT id FROM tasks WHERE title = 'TEST SLA REALTIME: Tarea Crítica' ORDER BY id DESC LIMIT 1;" | tail -n 1)
echo "   Task ID creado: $TASK_ID"
show_result "Tarea atrasada creada"

# Paso 3: Ejecutar comando SLA para generar alerta
echo "🚨 Paso 3: Ejecutando comando SLA para generar alerta..."
docker exec $BACKEND_CONTAINER php artisan sla:check --details
show_result "Comando SLA ejecutado"

# Paso 4: Verificar notificación creada
echo "📬 Paso 4: Verificando notificación SLA creada..."
NOTIF_COUNT=$(run_sql "
SELECT COUNT(*) as count FROM notifications
WHERE task_id = $TASK_ID AND type IN ('sla_warning', 'sla_warning_48h');
" | tail -n 1)

echo "   Notificaciones SLA encontradas: $NOTIF_COUNT"

if [ "$NOTIF_COUNT" -gt 0 ]; then
    show_result "Notificación SLA creada correctamente"
else
    echo "❌ ERROR: No se creó notificación SLA"
    exit 1
fi

# Paso 5: Mostrar estado actual
echo "📊 Paso 5: Estado actual de la tarea..."
run_sql "
SELECT
    id,
    title,
    status,
    sla_due_date,
    estimated_end_at,
    sla_breached,
    sla_days_overdue
FROM tasks
WHERE id = $TASK_ID;
"
show_result "Estado mostrado"

echo ""
echo "🎯 ====================================="
echo "   INSTRUCCIONES MANUALES"
echo "====================================="
echo ""
echo "1. Abre el dashboard en el navegador:"
echo "   http://localhost/dashboard"
echo ""
echo "2. Inicia sesión como usuario ID 3 (Juan Pérez)"
echo ""
echo "3. Debes ver en 'Tareas Urgentes':"
echo "   🚨 TEST SLA REALTIME: Tarea Crítica"
echo "   Badge: CRÍTICA (+2d)"
echo ""
echo "4. Abre DevTools (F12) → Console"
echo ""
echo "5. Ejecuta este SQL para ADELANTAR la fecha:"
echo ""
echo "   docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASS $DB_NAME -e \""
echo "   UPDATE tasks"
echo "   SET estimated_end_at = DATE_ADD(NOW(), INTERVAL 5 DAY),"
echo "       sla_due_date = DATE_ADD(NOW(), INTERVAL 5 DAY),"
echo "       updated_at = NOW()"
echo "   WHERE id = $TASK_ID;"
echo "   \""
echo ""
echo "6. En la consola del navegador DEBE aparecer:"
echo "   🚨 Estado SLA cambió en Dashboard: {old_status: 'critical', new_status: 'none'}"
echo "   🔄 Recargando dashboard por cambio de estado SLA"
echo ""
echo "7. VERIFICAR VISUALMENTE:"
echo "   ✅ Widget desaparece de 'Tareas Urgentes'"
echo "   ✅ Contador de tareas urgentes disminuye"
echo ""
echo "8. VERIFICAR EN BD:"
echo ""
echo "   docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASS $DB_NAME -e \""
echo "   SELECT COUNT(*) as alertas_activas FROM notifications"
echo "   WHERE task_id = $TASK_ID AND type IN ('sla_warning', 'sla_warning_48h');"
echo "   \""
echo ""
echo "   Debe retornar: 0"
echo ""
echo "   docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASS $DB_NAME -e \""
echo "   SELECT COUNT(*) as alertas_resueltas FROM notifications"
echo "   WHERE task_id = $TASK_ID AND type = 'sla_resolved';"
echo "   \""
echo ""
echo "   Debe retornar: 1"
echo ""
echo "9. Para REVERTIR y probar de nuevo:"
echo ""
echo "   docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASS $DB_NAME -e \""
echo "   UPDATE tasks"
echo "   SET estimated_end_at = DATE_SUB(NOW(), INTERVAL 2 DAY),"
echo "       sla_due_date = DATE_SUB(NOW(), INTERVAL 2 DAY),"
echo "       updated_at = NOW()"
echo "   WHERE id = $TASK_ID;"
echo "   \""
echo ""
echo "   Y ejecutar: docker exec $BACKEND_CONTAINER php artisan sla:check --details"
echo ""
echo "====================================="
echo ""
echo "✅ Setup de testing completado"
echo "   Task ID: $TASK_ID"
echo ""
