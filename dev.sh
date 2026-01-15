#!/bin/bash

# Script de desarrollo para Taskflow
# Este script facilita el inicio del entorno de desarrollo con Hot Module Replacement

echo "🚀 Iniciando entorno de desarrollo Taskflow..."
echo ""

# Verificar si Docker está corriendo
if ! docker info > /dev/null 2>&1; then
    echo "❌ Error: Docker no está corriendo. Por favor, inicia Docker Desktop."
    exit 1
fi

# Función para limpiar procesos al salir
cleanup() {
    echo ""
    echo "🛑 Deteniendo entorno de desarrollo..."
    kill $VITE_PID 2>/dev/null
    exit 0
}

trap cleanup SIGINT SIGTERM

# Iniciar backend en Docker si no está corriendo
echo "📦 Verificando contenedores Docker..."
cd taskflow-backend

if ! docker-compose ps | grep -q "Up"; then
    echo "🔧 Iniciando contenedores Docker (backend, base de datos, etc.)..."
    docker-compose up -d
    echo "⏳ Esperando a que los servicios estén listos..."
    sleep 5
else
    echo "✅ Contenedores Docker ya están corriendo"
fi

echo ""
echo "📊 Estado de los contenedores:"
docker-compose ps

# Iniciar frontend en modo desarrollo
echo ""
echo "🎨 Iniciando frontend con Vite (Hot Module Replacement)..."
cd ../taskflow-frontend

# Verificar si node_modules existe
if [ ! -d "node_modules" ]; then
    echo "📦 Instalando dependencias del frontend..."
    npm install
fi

echo ""
echo "✨ Entorno listo!"
echo ""
echo "📍 URLs disponibles:"
echo "   - Frontend (Dev): http://localhost:5173 (con HMR)"
echo "   - Backend API:    http://localhost/api/v1"
echo "   - Full App:       http://localhost (producción)"
echo ""
echo "💡 Presiona Ctrl+C para detener"
echo ""

# Iniciar Vite en primer plano
npm run dev
