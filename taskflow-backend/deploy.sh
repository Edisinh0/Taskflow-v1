#!/bin/bash

# Archivo: /Taskflow-Icontel/taskflow-backend/deploy.sh

set -e  # Detener en cualquier error

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}   TaskFlow - Despliegue a Producción${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Verificar que estamos en el directorio correcto
if [ ! -f "composer.json" ]; then
    echo -e "${RED}❌ Error: No se encontró composer.json${NC}"
    echo "Asegúrate de estar en /Taskflow-Icontel/taskflow-backend/"
    exit 1
fi

# Paso 1: Crear directorios necesarios
echo -e "${YELLOW}[1/12] Creando estructura de directorios...${NC}"
mkdir -p nginx/ssl
mkdir -p storage/logs
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p bootstrap/cache
echo -e "${GREEN}✓ Directorios creados${NC}\n"

# Paso 2: Verificar archivo .env
echo -e "${YELLOW}[2/12] Verificando configuración .env...${NC}"
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚠ No existe archivo .env${NC}"
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo -e "${GREEN}✓ Archivo .env creado desde .env.example${NC}"
        echo -e "${RED}⚠ IMPORTANTE: Edita el archivo .env con tus credenciales${NC}"
        echo "Ejecuta: nano .env"
        echo "Presiona Enter cuando hayas terminado..."
        read
    else
        echo -e "${RED}❌ No se encontró .env.example${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✓ Archivo .env encontrado${NC}"
fi
echo ""

# Paso 3: Configurar permisos
echo -e "${YELLOW}[3/12] Configurando permisos...${NC}"
sudo chown -R $USER:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
echo -e "${GREEN}✓ Permisos configurados${NC}\n"

# Paso 4: Verificar Docker
echo -e "${YELLOW}[4/12] Verificando Docker...${NC}"
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker no está instalado${NC}"
    exit 1
fi
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose no está instalado${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Docker instalado correctamente${NC}\n"

# Paso 5: Detener contenedores existentes
echo -e "${YELLOW}[5/12] Deteniendo contenedores existentes...${NC}"
docker-compose down 2>/dev/null || true
echo -e "${GREEN}✓ Contenedores detenidos${NC}\n"

# Paso 6: Limpiar caché de Docker (opcional)
echo -e "${YELLOW}[6/12] ¿Limpiar caché de Docker? (Recomendado en primer deploy)${NC}"
read -p "¿Continuar? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    docker system prune -f
    echo -e "${GREEN}✓ Caché limpiado${NC}"
else
    echo -e "${BLUE}○ Caché no limpiado${NC}"
fi
echo ""

# Paso 7: Construir imágenes
echo -e "${YELLOW}[7/12] Construyendo imágenes Docker (esto puede tomar varios minutos)...${NC}"
docker-compose build --no-cache
echo -e "${GREEN}✓ Imágenes construidas${NC}\n"

# Paso 8: Iniciar servicios
echo -e "${YELLOW}[8/12] Iniciando servicios...${NC}"
docker-compose up -d
echo -e "${GREEN}✓ Servicios iniciados${NC}\n"

# Paso 9: Esperar a que los servicios estén listos
echo -e "${YELLOW}[9/12] Esperando a que MariaDB esté listo...${NC}"
sleep 15
echo -e "${GREEN}✓ MariaDB debería estar listo${NC}\n"

# Paso 10: Generar APP_KEY si no existe
echo -e "${YELLOW}[10/12] Verificando APP_KEY...${NC}"
if grep -q "APP_KEY=$" .env || ! grep -q "APP_KEY=" .env; then
    echo "Generando APP_KEY..."
    docker-compose exec -T app php artisan key:generate --force
    echo -e "${GREEN}✓ APP_KEY generada${NC}"
else
    echo -e "${GREEN}✓ APP_KEY ya existe${NC}"
fi
echo ""

# Paso 11: Ejecutar migraciones
echo -e "${YELLOW}[11/12] Ejecutando migraciones de base de datos...${NC}"
docker-compose exec -T app php artisan migrate --force
echo -e "${GREEN}✓ Migraciones ejecutadas${NC}\n"

# Paso 12: Optimizar aplicación
echo -e "${YELLOW}[12/12] Optimizando aplicación para producción...${NC}"
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache
echo -e "${GREEN}✓ Aplicación optimizada${NC}\n"

# Verificar estado de contenedores
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}   Estado de los Contenedores${NC}"
echo -e "${GREEN}========================================${NC}\n"
docker-compose ps

# Verificar logs de Nginx
echo -e "\n${BLUE}Verificando logs de Nginx...${NC}"
docker-compose logs --tail=20 nginx

# Obtener IP del servidor
SERVER_IP=$(hostname -I | awk '{print $1}')

# Resumen final
echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}   ¡Despliegue Completado Exitosamente!${NC}"
echo -e "${GREEN}========================================${NC}\n"

echo -e "${BLUE}🌐 URL de acceso:${NC}"
echo -e "   ${GREEN}http://${SERVER_IP}${NC}"
echo -e "   ${GREEN}http://${SERVER_IP}/api${NC} (API)\n"

echo -e "${BLUE}📊 Comandos útiles:${NC}"
echo -e "   Ver logs:        ${YELLOW}docker-compose logs -f${NC}"
echo -e "   Reiniciar:       ${YELLOW}docker-compose restart${NC}"
echo -e "   Detener:         ${YELLOW}docker-compose down${NC}"
echo -e "   Entrar al app:   ${YELLOW}docker-compose exec app sh${NC}\n"

echo -e "${BLUE}🔥 Firewall:${NC}"
echo -e "   ${YELLOW}sudo ufw allow 80/tcp${NC}"
echo -e "   ${YELLOW}sudo ufw allow 443/tcp${NC}\n"

echo -e "${BLUE}🔍 Verificar que todo funciona:${NC}"
echo -e "   ${YELLOW}curl http://localhost${NC}"
echo -e "   ${YELLOW}curl http://${SERVER_IP}${NC}\n"

# Preguntar si quiere ver los logs en tiempo real
read -p "¿Ver logs en tiempo real? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    docker-compose logs -f
fi
