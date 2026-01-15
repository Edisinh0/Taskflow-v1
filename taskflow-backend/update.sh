#!/bin/bash

# Archivo: /Taskflow-Icontel/taskflow-backend/update.sh
# Script para actualizar la aplicación sin reconstruir todo

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}   TaskFlow - Actualización Rápida${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Activar modo mantenimiento
echo -e "${YELLOW}[1/8] Activando modo mantenimiento...${NC}"
docker-compose exec -T app php artisan down || true

# Pull de cambios (si usas Git)
if [ -d ".git" ]; then
    echo -e "${YELLOW}[2/8] Obteniendo últimos cambios de Git...${NC}"
    git pull
else
    echo -e "${YELLOW}[2/8] No es repositorio Git, saltando...${NC}"
fi

# Actualizar dependencias
echo -e "${YELLOW}[3/8] Actualizando dependencias...${NC}"
docker-compose exec -T app composer install --no-dev --optimize-autoloader

# Ejecutar migraciones
echo -e "${YELLOW}[4/8] Ejecutando migraciones...${NC}"
docker-compose exec -T app php artisan migrate --force

# Limpiar cachés
echo -e "${YELLOW}[5/8] Limpiando cachés...${NC}"
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan route:clear
docker-compose exec -T app php artisan view:clear

# Optimizar
echo -e "${YELLOW}[6/8] Optimizando aplicación...${NC}"
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

# Reiniciar servicios
echo -e "${YELLOW}[7/8] Reiniciando servicios...${NC}"
docker-compose restart app queue

# Desactivar modo mantenimiento
echo -e "${YELLOW}[8/8] Desactivando modo mantenimiento...${NC}"
docker-compose exec -T app php artisan up

echo -e "\n${GREEN}✓ Actualización completada exitosamente${NC}\n"
