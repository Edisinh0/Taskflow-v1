#!/bin/bash

# Script helper para ejecutar comandos SLA en el contenedor Docker
# Uso: ./sla-check.sh [opciones]

CONTAINER_NAME="taskflow_backend_new"

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}🔍 Sistema de Alertas SLA - Taskflow v1${NC}"
echo ""

# Verificar que el contenedor existe
if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
    echo -e "${RED}❌ Error: Contenedor ${CONTAINER_NAME} no encontrado${NC}"
    echo "Asegúrate de que Docker está corriendo y los contenedores están activos"
    exit 1
fi

# Si no hay argumentos, mostrar ayuda
if [ $# -eq 0 ]; then
    echo "Uso:"
    echo "  ./sla-check.sh                    # Ejecutar verificación simple"
    echo "  ./sla-check.sh --details          # Ejecutar con detalles"
    echo "  ./sla-check.sh --task-id=232      # Verificar tarea específica"
    echo "  ./sla-check.sh --list             # Listar comandos SLA disponibles"
    echo ""
    echo "Ejecutando verificación simple..."
    echo ""
fi

# Procesar argumentos
case "$1" in
    --list)
        echo "Comandos SLA disponibles:"
        docker exec $CONTAINER_NAME php artisan list | grep sla
        ;;
    --task-id=*)
        TASK_ID="${1#*=}"
        docker exec $CONTAINER_NAME php artisan sla:check --task-id=$TASK_ID
        ;;
    --details)
        docker exec $CONTAINER_NAME php artisan sla:check --details
        ;;
    *)
        docker exec $CONTAINER_NAME php artisan sla:check
        ;;
esac

echo ""
echo -e "${YELLOW}💡 Tip: Usa './sla-check.sh --details' para ver información detallada${NC}"
