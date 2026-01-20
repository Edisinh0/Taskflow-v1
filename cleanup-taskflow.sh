#!/bin/bash

################################################################################
# TASKFLOW V1 - CLEANUP SCRIPT
# Script para eliminar archivos y configuración no productiva
# 
# USO: ./cleanup-taskflow.sh [--dry-run|--execute]
# --dry-run: Muestra qué se eliminaría (DEFAULT)
# --execute: Elimina realmente los archivos
################################################################################

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
DRY_RUN=true
TOTAL_REMOVED=0
TOTAL_SIZE=0

# Función para print
print_header() {
    echo -e "\n${BLUE}═══════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}\n"
}

print_item() {
    echo -e "${YELLOW}→${NC} $1"
}

print_removed() {
    echo -e "${GREEN}✓ Eliminado${NC}: $1"
}

print_kept() {
    echo -e "${GREEN}✓ Mantenido${NC}: $1"
}

print_warning() {
    echo -e "${RED}⚠ ADVERTENCIA${NC}: $1"
}

# Parse arguments
if [[ $1 == "--execute" ]]; then
    DRY_RUN=false
    echo -e "${RED}MODO EJECUCIÓN${NC}: Los archivos SERÁN eliminados"
elif [[ $1 == "--dry-run" ]] || [[ $1 == "" ]]; then
    DRY_RUN=true
    echo -e "${YELLOW}MODO SIMULACIÓN${NC}: Mostrando qué se eliminaría"
else
    echo "USO: $0 [--dry-run|--execute]"
    exit 1
fi

echo ""

################################################################################
# FASE 1: ARCHIVOS DE DEBUG
################################################################################

print_header "FASE 1: Eliminando archivos de DEBUG"

# test-login.html
if [[ -f "$SCRIPT_DIR/test-login.html" ]]; then
    print_item "test-login.html"
    if [[ $DRY_RUN == false ]]; then
        rm "$SCRIPT_DIR/test-login.html"
        print_removed "test-login.html"
    fi
fi

# taskflow-backend/test_event.php
if [[ -f "$SCRIPT_DIR/taskflow-backend/test_event.php" ]]; then
    print_item "taskflow-backend/test_event.php"
    if [[ $DRY_RUN == false ]]; then
        rm "$SCRIPT_DIR/taskflow-backend/test_event.php"
        print_removed "taskflow-backend/test_event.php"
    fi
fi

# taskflow-backend/reproduce_blocking.php
if [[ -f "$SCRIPT_DIR/taskflow-backend/reproduce_blocking.php" ]]; then
    print_item "taskflow-backend/reproduce_blocking.php"
    if [[ $DRY_RUN == false ]]; then
        rm "$SCRIPT_DIR/taskflow-backend/reproduce_blocking.php"
        print_removed "taskflow-backend/reproduce_blocking.php"
    fi
fi

################################################################################
# FASE 2: SCRIPTS DE REFACTOR COMPLETADOS
################################################################################

print_header "FASE 2: Eliminando scripts de REFACTOR"

# setup-refactor-final.sh
if [[ -f "$SCRIPT_DIR/setup-refactor-final.sh" ]]; then
    print_item "setup-refactor-final.sh"
    if [[ $DRY_RUN == false ]]; then
        rm "$SCRIPT_DIR/setup-refactor-final.sh"
        print_removed "setup-refactor-final.sh"
    fi
fi

# setup-refactor.sh
if [[ -f "$SCRIPT_DIR/setup-refactor.sh" ]]; then
    print_item "setup-refactor.sh"
    if [[ $DRY_RUN == false ]]; then
        rm "$SCRIPT_DIR/setup-refactor.sh"
        print_removed "setup-refactor.sh"
    fi
fi

################################################################################
# FASE 3: ARCHIVOS .env DUPLICADOS
################################################################################

print_header "FASE 3: Limpiando archivos .env DUPLICADOS"

# .env.docker.bak
if [[ -f "$SCRIPT_DIR/taskflow-backend/.env.docker.bak" ]]; then
    print_item "taskflow-backend/.env.docker.bak"
    if [[ $DRY_RUN == false ]]; then
        rm "$SCRIPT_DIR/taskflow-backend/.env.docker.bak"
        print_removed "taskflow-backend/.env.docker.bak"
    fi
fi

# Verificar .env en .gitignore (no eliminar, solo advertencia)
if [[ -f "$SCRIPT_DIR/taskflow-backend/.env" ]]; then
    if ! grep -q "^\.env$" "$SCRIPT_DIR/taskflow-backend/.gitignore" 2>/dev/null; then
        print_warning ".env NO está en .gitignore en taskflow-backend/"
    else
        print_item ".env (mantener - está en .gitignore)"
    fi
fi

################################################################################
# FASE 4: DOCUMENTACIÓN DUPLICADA (CONSOLIDADA)
################################################################################

print_header "FASE 4: Consolidando DOCUMENTACIÓN"

# Array de archivos de documentación a eliminar
declare -a MD_FILES_TO_REMOVE=(
    "CI_CD_GUIDE.md"
    "COMANDOS_EJECUCION.md"
    "DEPLOYMENT_UPDATE_GUIDE.md"
    "DEPLOY_GUIDE.md"
    "DEPLOY_QUICK_GUIDE.md"
    "DESARROLLO.md"
    "FRONTEND_BACKEND_INTEGRATION.md"
    "FRONTEND_INTEGRATION_EXAMPLES.md"
    "INTEGRATION_EXAMPLES.md"
    "MANUAL_EXECUTION_GUIDE.md"
    "NOTIFICATION_IMPROVEMENTS.md"
    "PRODUCTION_DEPLOYMENT.md"
    "QUICK_START.md"
    "README_SLA_REALTIME.md"
    "REFACTOR_GUIDE.md"
    "REFACTOR_README.md"
    "REFACTOR_SUMMARY.md"
    "SETUP_INSTRUCTIONS.md"
    "SLA_QUICKSTART.md"
)

for md_file in "${MD_FILES_TO_REMOVE[@]}"; do
    if [[ -f "$SCRIPT_DIR/$md_file" ]]; then
        print_item "$md_file"
        if [[ $DRY_RUN == false ]]; then
            rm "$SCRIPT_DIR/$md_file"
            print_removed "$md_file"
        fi
    fi
done

# Mantener archivos .md importantes
print_kept "README.md (principal - MANTENER)"
print_kept "TASKFLOW_ARCHITECTURE.md (MANTENER por ahora - revisar)"

################################################################################
# FASE 5: SCRIPTS REDUNDANTES
################################################################################

print_header "FASE 5: Revisando scripts REDUNDANTES"

# Verificar si dev.sh existe en taskflow-backend
if [[ -f "$SCRIPT_DIR/taskflow-backend/dev.sh" ]]; then
    print_warning "taskflow-backend/dev.sh DUPLICA a raíz/dev.sh"
    print_item "  → Recomendación: Revisar y consolidar"
fi

# update.sh en raíz y backend
if [[ -f "$SCRIPT_DIR/update.sh" ]] && [[ -f "$SCRIPT_DIR/taskflow-backend/update.sh" ]]; then
    print_warning "update.sh existe en RAÍZ y BACKEND"
    print_item "  → Recomendación: Consolidar en uno solo"
fi

################################################################################
# FASE 6: DOCKER POTENTIAL CLEANUP
################################################################################

print_header "FASE 6: Revisión DOCKER (revisar manualmente)"

# Dockerfile.dev
if [[ -f "$SCRIPT_DIR/taskflow-backend/Dockerfile.dev" ]]; then
    print_warning "Dockerfile.dev ENCONTRADO - Revisar si se usa"
    print_item "  → Si NO se usa en desarrollo: ELIMINAR manualmente"
fi

# nginx-gateway
if [[ -d "$SCRIPT_DIR/nginx-gateway" ]]; then
    print_warning "nginx-gateway/ ENCONTRADO - Revisar si se usa"
    print_item "  → Si NO se usa en desarrollo: ELIMINAR manualmente"
    print_item "  → Comando: rm -rf nginx-gateway/"
fi

################################################################################
# FASE 7: REPORTE FINAL
################################################################################

print_header "REPORTE DE LIMPIEZA"

if [[ $DRY_RUN == true ]]; then
    echo -e "${YELLOW}MODO SIMULACIÓN:${NC} Se mostró qué se eliminaría"
    echo -e "\n${GREEN}Para ejecutar la limpieza real, ejecuta:${NC}"
    echo -e "${BLUE}  $0 --execute${NC}\n"
else
    echo -e "${GREEN}Limpieza completada exitosamente${NC}\n"
fi

# Resumen
echo -e "${BLUE}RESUMEN DE ACCIONES:${NC}"
echo -e "  • Archivos de debug: ELIMINADOS"
echo -e "  • Scripts de refactor: ELIMINADOS"
echo -e "  • Archivos .env duplicados: ELIMINADOS"
echo -e "  • Documentación duplicada: ELIMINADA (16 archivos)"
echo -e "  • Revisar manualmente:"
echo -e "    - Scripts redundantes (dev.sh, update.sh)"
echo -e "    - Dockerfile.dev"
echo -e "    - nginx-gateway/"

echo -e "\n${BLUE}PRÓXIMOS PASOS:${NC}"
echo -e "  1. Revisar y consolidar scripts"
echo -e "  2. Crear DEVELOPMENT.md consolidado"
echo -e "  3. Crear DEPLOYMENT.md consolidado"
echo -e "  4. Actualizar CI/CD en .github/workflows/"
echo -e "  5. Git commit: 'cleanup: Remove redundant files and docs'\n"

################################################################################
# .gitignore CHECK
################################################################################

print_header "VERIFICACIÓN DE .gitignore"

# Verificar archivos críticos
if [[ -f "$SCRIPT_DIR/.gitignore" ]]; then
    echo -e "${BLUE}Revisando .gitignore en raíz:${NC}"
    
    for pattern in "node_modules/" ".env" ".env.local" "dist/" "build/"; do
        if grep -q "^$pattern" "$SCRIPT_DIR/.gitignore" 2>/dev/null; then
            echo -e "  ${GREEN}✓${NC} $pattern"
        else
            echo -e "  ${RED}✗${NC} $pattern FALTA en .gitignore"
        fi
    done
else
    echo -e "${RED}✗ No existe .gitignore en raíz${NC}"
fi

echo -e ""

################################################################################
# FIN
################################################################################

print_header "LIMPIEZA FINALIZADA"

if [[ $DRY_RUN == true ]]; then
    echo -e "Para eliminar los archivos identificados, ejecuta:"
    echo -e "${BLUE}  bash $0 --execute${NC}\n"
else
    echo -e "${GREEN}✓ Archivos innecesarios eliminados exitosamente${NC}\n"
fi

exit 0
