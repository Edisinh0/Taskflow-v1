#!/bin/bash

# Archivo: /Taskflow-Icontel/taskflow-backend/check-nginx.sh
# Script para verificar configuración de Nginx

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}Verificando configuración de Nginx...${NC}\n"

# Verificar sintaxis de configuración
echo -e "${YELLOW}1. Probando sintaxis de Nginx...${NC}"
docker-compose exec nginx nginx -t
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Sintaxis correcta${NC}\n"
else
    echo -e "${RED}✗ Error en sintaxis${NC}\n"
    exit 1
fi

# Verificar archivos montados
echo -e "${YELLOW}2. Verificando archivos montados en el contenedor...${NC}"
echo "Archivos en /etc/nginx/conf.d/:"
docker-compose exec nginx ls -lah /etc/nginx/conf.d/
echo ""

# Verificar logs
echo -e "${YELLOW}3. Últimas 10 líneas del log de errores:${NC}"
docker-compose exec nginx tail -n 10 /var/log/nginx/error.log 2>/dev/null || echo "No hay errores registrados"
echo ""

# Verificar proceso Nginx
echo -e "${YELLOW}4. Estado del proceso Nginx:${NC}"
docker-compose exec nginx ps aux | grep nginx
echo ""

# Test de conectividad
echo -e "${YELLOW}5. Test de conectividad local:${NC}"
curl -I http://localhost 2>/dev/null | head -n 1 || echo -e "${RED}No se puede conectar${NC}"
echo ""

echo -e "${GREEN}Verificación completada${NC}"
