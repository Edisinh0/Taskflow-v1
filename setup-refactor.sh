#!/bin/bash

# Script de configuración para refactorización TaskFlow
# Ejecutar desde: /Users/eddiecerpa/Taskflow-Icontel

set -e  # Detener si hay errores

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║     🚀 Setup Refactorización TaskFlow - Módulos SRP         ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -d "taskflow-backend" ] || [ ! -d "taskflow-frontend" ]; then
    echo "❌ Error: Este script debe ejecutarse desde el directorio principal de Taskflow"
    echo "   Ubicación esperada: /Users/eddiecerpa/Taskflow-Icontel"
    exit 1
fi

# Función para ejecutar comandos en Docker
run_docker() {
    if command -v docker-compose &> /dev/null; then
        docker-compose exec -T taskflow-app "$@"
    else
        echo "⚠️  docker-compose no encontrado, usando docker directamente..."
        CONTAINER=$(docker ps --filter name=taskflow-app --format "{{.Names}}" | head -1)
        if [ -z "$CONTAINER" ]; then
            echo "❌ No se encontró contenedor de taskflow-app"
            exit 1
        fi
        docker exec -i "$CONTAINER" "$@"
    fi
}

# Paso 1: Verificar Docker
echo "📦 Paso 1: Verificando Docker..."
if ! command -v docker &> /dev/null; then
    echo "❌ Docker no está instalado"
    exit 1
fi

docker ps > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo "❌ Docker no está corriendo. Por favor inicia Docker Desktop."
    exit 1
fi

echo "✅ Docker está corriendo"
echo ""

# Paso 2: Ejecutar migración
echo "📊 Paso 2: Ejecutando migración..."
run_docker php artisan migrate --force
echo "✅ Migración completada"
echo ""

# Paso 3: Limpiar cache
echo "🧹 Paso 3: Limpiando cache..."
run_docker php artisan config:clear
run_docker php artisan cache:clear
run_docker php artisan route:clear
echo "✅ Cache limpiado"
echo ""

# Paso 4: Generar autoload
echo "🔄 Paso 4: Regenerando autoload..."
run_docker composer dump-autoload
echo "✅ Autoload regenerado"
echo ""

# Paso 5: Optimizar
echo "⚡ Paso 5: Optimizando aplicación..."
run_docker php artisan optimize
echo "✅ Optimización completada"
echo ""

# Paso 6: Verificar rutas
echo "🛣️  Paso 6: Verificando rutas de módulos..."
echo ""
echo "Rutas de Flow Builder:"
run_docker php artisan route:list | grep flow-builder | head -5
echo ""
echo "Rutas de Task Center:"
run_docker php artisan route:list | grep task-center
echo ""
echo "✅ Rutas verificadas"
echo ""

# Paso 7: Actualizar roles (interactivo)
echo "👥 Paso 7: Actualizar roles de usuarios"
echo "Por favor ejecuta los siguientes comandos en tinker:"
echo ""
echo "docker-compose exec taskflow-app php artisan tinker"
echo ""
echo "Luego ejecuta:"
echo "User::where('email', 'admin@taskflow.com')->update(['role' => 'admin']);"
echo "User::whereNotIn('role', ['admin', 'project_manager'])->whereNull('role')->update(['role' => 'user']);"
echo "User::select('id', 'name', 'email', 'role')->get();"
echo "exit"
echo ""
read -p "Presiona ENTER cuando hayas actualizado los roles..."

# Paso 8: Reiniciar contenedores
echo ""
echo "🔄 Paso 8: Reiniciando contenedores..."
if command -v docker-compose &> /dev/null; then
    docker-compose restart
else
    docker restart $(docker ps --filter name=taskflow --format "{{.Names}}")
fi
echo "✅ Contenedores reiniciados"
echo ""

# Resumen final
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                  ✅ SETUP COMPLETADO                         ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "📋 Próximos pasos:"
echo ""
echo "1. Configurar Vue Router (ver INTEGRATION_EXAMPLES.md sección 2)"
echo "2. Actualizar Auth Store (ver INTEGRATION_EXAMPLES.md sección 3)"
echo "3. Probar endpoints (ver COMANDOS_EJECUCION.md)"
echo ""
echo "📚 Documentación disponible:"
echo "  - MANUAL_EXECUTION_GUIDE.md"
echo "  - REFACTOR_GUIDE.md"
echo "  - INTEGRATION_EXAMPLES.md"
echo "  - COMANDOS_EJECUCION.md"
echo ""
