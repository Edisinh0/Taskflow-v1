#!/bin/bash

# 🧪 Script de Prueba del Sistema SLA - Taskflow v1
# Este script crea una tarea de prueba, ejecuta la verificación SLA,
# y muestra los resultados paso a paso.

set -e  # Exit on error

echo "════════════════════════════════════════════════════════════"
echo "🧪 PRUEBA DEL SISTEMA SLA - TASKFLOW V1"
echo "════════════════════════════════════════════════════════════"
echo ""

# Colores para output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Variables
DB_CONTAINER="taskflow_db_new"
BACKEND_CONTAINER="taskflow_backend_new"
DB_USER="taskflow_user"
DB_PASS="taskflow_password"
DB_NAME="taskflow_db"

# Función para ejecutar queries
run_query() {
    docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASS $DB_NAME -se "$1"
}

# Función para mostrar resultados de query en tabla
show_query() {
    docker exec $DB_CONTAINER mysql -u $DB_USER -p$DB_PASS $DB_NAME -e "$1"
}

echo -e "${BLUE}📋 PASO 1: Crear tarea de prueba con SLA vencido (2 días atrás)${NC}"
echo "────────────────────────────────────────────────────────────"

TASK_ID=$(run_query "
INSERT INTO tasks (
  flow_id,
  title,
  description,
  status,
  priority,
  assignee_id,
  estimated_end_at,
  sla_due_date,
  created_at,
  updated_at
) VALUES (
  1,
  'AUTO-TEST: Tarea Atrasada SLA',
  'Tarea de prueba automática para verificar sistema SLA',
  'in_progress',
  'urgent',
  3,
  DATE_SUB(NOW(), INTERVAL 2 DAY),
  DATE_SUB(NOW(), INTERVAL 2 DAY),
  NOW(),
  NOW()
);
SELECT LAST_INSERT_ID();
")

echo -e "${GREEN}✅ Tarea creada exitosamente con ID: $TASK_ID${NC}"
echo ""

# Mostrar detalles de la tarea
echo -e "${BLUE}�� Detalles de la tarea creada:${NC}"
show_query "
SELECT
  id,
  title,
  status,
  priority,
  assignee_id,
  sla_due_date,
  sla_breached,
  sla_days_overdue,
  sla_notified_assignee,
  sla_escalated
FROM tasks
WHERE id = $TASK_ID;
"
echo ""

echo -e "${BLUE}🔍 PASO 2: Ejecutar verificación SLA${NC}"
echo "────────────────────────────────────────────────────────────"
docker exec $BACKEND_CONTAINER php artisan sla:check --task-id=$TASK_ID
echo ""

echo -e "${BLUE}📊 PASO 3: Verificar estado actualizado de la tarea${NC}"
echo "────────────────────────────────────────────────────────────"
show_query "
SELECT
  id,
  title,
  sla_breached,
  sla_days_overdue,
  sla_notified_assignee,
  sla_escalated,
  sla_notified_at,
  sla_escalated_at
FROM tasks
WHERE id = $TASK_ID;
"
echo ""

echo -e "${BLUE}📬 PASO 4: Verificar notificaciones creadas${NC}"
echo "────────────────────────────────────────────────────────────"
NOTIFICATION_COUNT=$(run_query "SELECT COUNT(*) FROM notifications WHERE task_id = $TASK_ID;")

if [ "$NOTIFICATION_COUNT" -eq "0" ]; then
    echo -e "${RED}❌ ERROR: No se crearon notificaciones${NC}"
    echo -e "${YELLOW}⚠️  Posibles causas:${NC}"
    echo "   - La tarea no cumple los criterios de SLA (revisar fecha)"
    echo "   - El assignee_id no existe en la tabla users"
    echo "   - Hay un error en SlaNotificationService"
else
    echo -e "${GREEN}✅ Se crearon $NOTIFICATION_COUNT notificaciones:${NC}"
    show_query "
    SELECT
      id,
      user_id,
      type,
      title,
      message,
      priority,
      is_read,
      created_at
    FROM notifications
    WHERE task_id = $TASK_ID
    ORDER BY created_at DESC;
    "
fi
echo ""

echo -e "${BLUE}📋 PASO 5: Verificar tipos de notificaciones esperadas${NC}"
echo "────────────────────────────────────────────────────────────"
echo "Para una tarea atrasada 2+ días, se esperan:"
echo "  1. ✅ sla_warning (para el assignee)"
echo "  2. ✅ sla_escalation (para el supervisor)"
echo "  3. ✅ sla_escalation_notice (notificar assignee sobre escalación)"
echo ""

WARNING_COUNT=$(run_query "SELECT COUNT(*) FROM notifications WHERE task_id = $TASK_ID AND type = 'sla_warning';")
ESCALATION_COUNT=$(run_query "SELECT COUNT(*) FROM notifications WHERE task_id = $TASK_ID AND type = 'sla_escalation';")
NOTICE_COUNT=$(run_query "SELECT COUNT(*) FROM notifications WHERE task_id = $TASK_ID AND type = 'sla_escalation_notice';")

echo "Notificaciones creadas:"
echo "  - sla_warning: $WARNING_COUNT (esperado: 1)"
echo "  - sla_escalation: $ESCALATION_COUNT (esperado: 1)"
echo "  - sla_escalation_notice: $NOTICE_COUNT (esperado: 1)"
echo ""

# Verificar resultado
if [ "$WARNING_COUNT" -eq "1" ] && [ "$ESCALATION_COUNT" -eq "1" ] && [ "$NOTICE_COUNT" -eq "1" ]; then
    echo -e "${GREEN}✅ ¡SISTEMA SLA FUNCIONANDO CORRECTAMENTE!${NC}"
    TEST_PASSED=true
else
    echo -e "${YELLOW}⚠️  Sistema parcialmente funcional - Revisar configuración${NC}"
    TEST_PASSED=false
fi
echo ""

echo -e "${BLUE}🧹 PASO 6: Limpiar datos de prueba${NC}"
echo "────────────────────────────────────────────────────────────"
read -p "¿Deseas eliminar la tarea de prueba y sus notificaciones? (s/N): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[SsYy]$ ]]; then
    run_query "DELETE FROM notifications WHERE task_id = $TASK_ID;"
    run_query "DELETE FROM tasks WHERE id = $TASK_ID;"
    echo -e "${GREEN}✅ Datos de prueba eliminados${NC}"
else
    echo -e "${YELLOW}⚠️  Datos de prueba conservados (Task ID: $TASK_ID)${NC}"
    echo "   Para eliminar manualmente:"
    echo "   DELETE FROM notifications WHERE task_id = $TASK_ID;"
    echo "   DELETE FROM tasks WHERE id = $TASK_ID;"
fi
echo ""

echo "════════════════════════════════════════════════════════════"
if [ "$TEST_PASSED" = true ]; then
    echo -e "${GREEN}✅ PRUEBA COMPLETADA EXITOSAMENTE${NC}"
else
    echo -e "${YELLOW}⚠️  PRUEBA COMPLETADA CON ADVERTENCIAS${NC}"
fi
echo "════════════════════════════════════════════════════════════"
echo ""

echo "📖 Para más información, consulta: DIAGNOSTICO_Y_SOLUCION_SLA.md"
