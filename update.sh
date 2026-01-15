#!/bin/bash

# Script de actualización para Taskflow en producción
# Uso: ./update.sh

set -e  # Detener en cualquier error

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}   TaskFlow - Actualización Producción${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Verificar que estamos en el directorio correcto
if [ ! -f "docker-compose.prod.yml" ]; then
    echo -e "${RED}❌ Error: No se encontró docker-compose.prod.yml${NC}"
    echo "Asegúrate de estar en /ruta/a/Taskflow-Icontel/"
    exit 1
fi

# Paso 1: Git Pull
echo -e "${YELLOW}[1/6] Obteniendo últimos cambios desde GitHub...${NC}"
git pull origin main
echo -e "${GREEN}✓ Código actualizado${NC}\n"

# Paso 2: Detener contenedores
echo -e "${YELLOW}[2/6] Deteniendo contenedores...${NC}"
docker-compose -f docker-compose.prod.yml down
echo -e "${GREEN}✓ Contenedores detenidos${NC}\n"

# Paso 3: Limpiar imágenes antiguas (opcional)
echo -e "${YELLOW}[3/6] Limpiando imágenes antiguas...${NC}"
docker image prune -f
echo -e "${GREEN}✓ Imágenes limpiadas${NC}\n"

# Paso 4: Reconstruir imágenes
echo -e "${YELLOW}[4/6] Reconstruyendo imágenes (esto puede tardar varios minutos)...${NC}"
export COMPOSE_HTTP_TIMEOUT=300
export DOCKER_CLIENT_TIMEOUT=300
docker-compose -f docker-compose.prod.yml build --no-cache
echo -e "${GREEN}✓ Imágenes reconstruidas${NC}\n"

# Paso 5: Levantar servicios
echo -e "${YELLOW}[5/6] Levantando servicios...${NC}"
docker-compose -f docker-compose.prod.yml up -d
echo -e "${GREEN}✓ Servicios iniciados${NC}\n"

# Paso 6: Esperar y ejecutar migraciones
echo -e "${YELLOW}[6/7] Esperando a que los servicios estén listos...${NC}"
sleep 10

echo -e "${BLUE}Ejecutando migraciones de base de datos...${NC}"
docker-compose -f docker-compose.prod.yml exec -T backend php artisan migrate --force
echo -e "${GREEN}✓ Migraciones ejecutadas${NC}\n"

# Paso 7: Limpiar cachés
echo -e "${YELLOW}[7/7] Limpiando cachés de Laravel...${NC}"
docker-compose -f docker-compose.prod.yml exec -T backend php artisan optimize:clear
docker-compose -f docker-compose.prod.yml exec -T backend php artisan config:cache
docker-compose -f docker-compose.prod.yml exec -T backend php artisan route:cache
docker-compose -f docker-compose.prod.yml exec -T backend php artisan view:cache
echo -e "${GREEN}✓ Cachés limpiados y optimizados${NC}\n"

# Verificar estado
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}   Estado de los Contenedores${NC}"
echo -e "${GREEN}========================================${NC}\n"
docker-compose -f docker-compose.prod.yml ps

# Obtener IP del servidor
SERVER_IP=$(hostname -I | awk '{print $1}' 2>/dev/null || echo "localhost")

# Resumen final
echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}   ¡Actualización Completada!${NC}"
echo -e "${GREEN}========================================${NC}\n"

echo -e "${BLUE}🌐 URL de acceso:${NC}"
echo -e "   ${GREEN}http://${SERVER_IP}${NC}\n"

echo -e "${BLUE}📊 Comandos útiles:${NC}"
echo -e "   Ver logs:        ${YELLOW}docker-compose -f docker-compose.prod.yml logs -f${NC}"
echo -e "   Ver estado:      ${YELLOW}docker-compose -f docker-compose.prod.yml ps${NC}"
echo -e "   Reiniciar:       ${YELLOW}docker-compose -f docker-compose.prod.yml restart${NC}\n"

echo -e "${BLUE}🔍 Verificar que todo funciona:${NC}"
echo -e "   ${YELLOW}curl http://localhost/api/v1/health${NC}"
echo -e "   ${YELLOW}curl http://${SERVER_IP}${NC}\n"
