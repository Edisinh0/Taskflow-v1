#!/bin/bash

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║  🚀 Configuración Final - Refactorización TaskFlow SRP       ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# Paso 1: Limpiar cache
echo "🧹 Paso 1: Limpiando cache..."
docker exec -it taskflow_app_dev php artisan config:clear
docker exec -it taskflow_app_dev php artisan cache:clear
docker exec -it taskflow_app_dev php artisan route:clear
echo "✅ Cache limpiado"
echo ""

# Paso 2: Regenerar autoload
echo "🔄 Paso 2: Regenerando autoload..."
docker exec -it taskflow_app_dev composer dump-autoload
echo "✅ Autoload regenerado"
echo ""

# Paso 3: Optimizar
echo "⚡ Paso 3: Optimizando..."
docker exec -it taskflow_app_dev php artisan optimize
echo "✅ Optimización completada"
echo ""

# Paso 4: Verificar rutas
echo "🛣️  Paso 4: Verificando rutas de módulos..."
echo ""
echo ">>> Flow Builder:"
docker exec -it taskflow_app_dev php artisan route:list | grep flow-builder
echo ""
echo ">>> Task Center:"
docker exec -it taskflow_app_dev php artisan route:list | grep task-center
echo ""
echo "✅ Rutas verificadas"
echo ""

# Paso 5: Reiniciar contenedores
echo "🔄 Paso 5: Reiniciando contenedores..."
docker restart taskflow_app_dev taskflow_nginx_dev
sleep 3
echo "✅ Contenedores reiniciados"
echo ""

# Paso 6: Test de API
echo "🧪 Paso 6: Probando API..."
echo ""
echo ">>> Probando endpoint de bienvenida..."
curl -s http://localhost:8080/api/v1
echo ""
echo ""

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                  ✅ CONFIGURACIÓN COMPLETADA                 ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "📋 IMPORTANTE: Ahora ejecuta este comando para actualizar roles:"
echo ""
echo "   docker exec -it taskflow_app_dev php artisan tinker"
echo ""
echo "Y dentro de tinker:"
echo "   User::where('email', 'admin@taskflow.com')->update(['role' => 'admin']);"
echo "   User::whereNull('role')->update(['role' => 'user']);"
echo "   User::select('id', 'name', 'email', 'role')->get();"
echo "   exit"
echo ""
echo "📝 Luego prueba el login con:"
echo ""
echo "   curl -X POST http://localhost:8080/api/v1/auth/login \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"email\": \"admin@taskflow.com\", \"password\": \"password\"}'"
echo ""
