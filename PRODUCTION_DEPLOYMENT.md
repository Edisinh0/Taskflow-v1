# Guía de Despliegue a Producción - TaskFlow 🚀

## Prerequisitos

- VPS con Ubuntu (ya configurado)
- Docker y Docker Compose instalados
- Acceso SSH al servidor
- Una versión anterior de TaskFlow ya corriendo (será reemplazada)

---

## Estrategia de Despliegue

Como ya tienes una versión anterior corriendo, vamos a:
1. Hacer backup de la base de datos actual
2. Detener los contenedores antiguos
3. Desplegar la nueva versión
4. Migrar la base de datos
5. Verificar que todo funcione

---

## Paso 1: Preparar el Servidor (Desde tu PC local)

### 1.1 Conectarse al VPS vía SSH

```bash
ssh usuario@IP_DE_TU_VPS
# Ejemplo: ssh ubuntu@172.16.10.25
```

### 1.2 Verificar Docker instalado

```bash
docker --version
docker compose version
```

Si no están instalados:
```bash
# Instalar Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Instalar Docker Compose
sudo apt install docker-compose-plugin -y

# Dar permisos
sudo usermod -aG docker $USER
newgrp docker
```

---

## Paso 2: Backup de la Versión Actual (EN EL VPS)

### 2.1 Identificar contenedores actuales

```bash
# Ver contenedores corriendo
docker ps

# Ver todos los contenedores
docker ps -a
```

### 2.2 Hacer backup de la base de datos

```bash
# Encuentra el nombre del contenedor de MySQL/MariaDB
# Usualmente es algo como: taskflow_db, taskflow-db-1, etc.

# Hacer backup
docker exec NOMBRE_CONTENEDOR_DB mysqldump -u root -p taskflow_db > ~/taskflow_backup_$(date +%Y%m%d_%H%M%S).sql

# Nota: Te pedirá la contraseña de la base de datos
```

### 2.3 Detener y remover contenedores antiguos

```bash
# Ir al directorio del proyecto antiguo
cd ~/Taskflow-Icontel  # o el directorio donde esté

# Detener contenedores
docker compose down

# O si está en otro directorio
cd /ruta/del/proyecto/antiguo
docker compose down
```

---

## Paso 3: Desplegar Nueva Versión (EN EL VPS)

### 3.1 Clonar el proyecto nuevo en un directorio diferente

```bash
# Ir al home
cd ~

# Si ya existe el directorio, renombrarlo
mv Taskflow-Icontel Taskflow-Icontel-OLD

# Clonar la nueva versión
git clone https://github.com/TU_USUARIO/TU_REPO.git Taskflow-Icontel-NEW
cd Taskflow-Icontel-NEW
```

### 3.2 Configurar variables de entorno - Backend

```bash
cd taskflow-backend
cp .env.example .env
nano .env
```

Configurar las siguientes variables CRÍTICAS:

```env
# === APLICACIÓN ===
APP_NAME="TaskFlow"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://172.16.10.25  # Cambiar a tu IP/dominio

# === BASE DE DATOS ===
DB_CONNECTION=mysql
DB_HOST=db                    # Nombre del servicio Docker
DB_PORT=3306
DB_DATABASE=taskflow_db
DB_USERNAME=taskflow_user
DB_PASSWORD=TU_PASSWORD_SEGURO  # Cambiar esto

# === BROADCASTING (Soketi para WebSockets) ===
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=taskflow-app
PUSHER_APP_KEY=taskflow-key
PUSHER_APP_SECRET=taskflow-secret
PUSHER_HOST=soketi
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1

# === CORS ===
SANCTUM_STATEFUL_DOMAINS=172.16.10.25,localhost
SESSION_DOMAIN=.172.16.10.25  # Cambiar a tu IP/dominio

# === REDIS (para colas) ===
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

# === MAIL (opcional, para notificaciones por email) ===
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_password_app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

Guardar: `Ctrl + O`, `Enter`, `Ctrl + X`

### 3.3 Configurar variables de entorno - Frontend

```bash
cd ../taskflow-frontend
nano .env
```

Contenido:

```env
# === API Backend ===
VITE_API_BASE_URL=http://172.16.10.25/api/v1  # Cambiar a tu IP/dominio

# === WebSocket (Pusher/Soketi) ===
VITE_PUSHER_APP_KEY=taskflow-key
VITE_PUSHER_HOST=172.16.10.25                  # Cambiar a tu IP/dominio
VITE_PUSHER_PORT=6001
VITE_PUSHER_SCHEME=http
VITE_PUSHER_CLUSTER=mt1
```

Guardar: `Ctrl + O`, `Enter`, `Ctrl + X`

### 3.4 Volver al directorio raíz

```bash
cd ..
```

---

## Paso 4: Crear Docker Compose de Producción

Crear archivo `docker-compose.prod.yml` en la raíz del proyecto:

```bash
nano docker-compose.prod.yml
```

Contenido:

```yaml
version: '3.8'

services:
  # === Base de Datos MySQL ===
  db:
    image: mysql:8.0
    container_name: taskflow_db_prod
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: root_password_seguro
      MYSQL_DATABASE: taskflow_db
      MYSQL_USER: taskflow_user
      MYSQL_PASSWORD: TU_PASSWORD_SEGURO  # Mismo que en .env
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - taskflow_network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

  # === Redis (para colas y caché) ===
  redis:
    image: redis:7-alpine
    container_name: taskflow_redis_prod
    restart: unless-stopped
    networks:
      - taskflow_network
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 3s
      retries: 3

  # === Soketi (WebSocket Server) ===
  soketi:
    image: quay.io/soketi/soketi:1.6-16-alpine
    container_name: taskflow_soketi_prod
    restart: unless-stopped
    ports:
      - "6001:6001"
    environment:
      SOKETI_DEBUG: '0'
      SOKETI_DEFAULT_APP_ID: 'taskflow-app'
      SOKETI_DEFAULT_APP_KEY: 'taskflow-key'
      SOKETI_DEFAULT_APP_SECRET: 'taskflow-secret'
    networks:
      - taskflow_network

  # === Backend Laravel ===
  backend:
    build:
      context: ./taskflow-backend
      dockerfile: Dockerfile
    container_name: taskflow_backend_prod
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./taskflow-backend:/var/www/html
      - backend_storage:/var/www/html/storage
    environment:
      - APP_ENV=production
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_healthy
    networks:
      - taskflow_network
    command: >
      sh -c "
        composer install --no-dev --optimize-autoloader &&
        php artisan config:cache &&
        php artisan route:cache &&
        php artisan view:cache &&
        chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache &&
        chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache &&
        php-fpm
      "

  # === Queue Worker (procesa trabajos en segundo plano) ===
  queue:
    build:
      context: ./taskflow-backend
      dockerfile: Dockerfile
    container_name: taskflow_queue_prod
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./taskflow-backend:/var/www/html
    depends_on:
      - backend
      - redis
    networks:
      - taskflow_network
    command: php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

  # === Frontend Vue.js ===
  frontend:
    build:
      context: ./taskflow-frontend
      dockerfile: Dockerfile
    container_name: taskflow_frontend_prod
    restart: unless-stopped
    networks:
      - taskflow_network

  # === Nginx (reverse proxy) ===
  nginx:
    image: nginx:alpine
    container_name: taskflow_nginx_prod
    restart: unless-stopped
    ports:
      - "80:80"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf:ro
      - ./taskflow-backend/public:/var/www/html/public:ro
    depends_on:
      - backend
      - frontend
    networks:
      - taskflow_network

volumes:
  mysql_data:
    driver: local
  backend_storage:
    driver: local

networks:
  taskflow_network:
    driver: bridge
```

Guardar: `Ctrl + O`, `Enter`, `Ctrl + X`

---

## Paso 5: Crear Configuración de Nginx

```bash
nano nginx.conf
```

Contenido:

```nginx
events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    upstream backend {
        server backend:9000;
    }

    upstream frontend {
        server frontend:80;
    }

    server {
        listen 80;
        server_name _;

        client_max_body_size 100M;

        # Frontend (Vue.js)
        location / {
            proxy_pass http://frontend;
            proxy_http_version 1.1;
            proxy_set_header Upgrade $http_upgrade;
            proxy_set_header Connection 'upgrade';
            proxy_set_header Host $host;
            proxy_cache_bypass $http_upgrade;
        }

        # API Backend (Laravel)
        location /api {
            try_files $uri $uri/ /index.php?$query_string;
            fastcgi_pass backend;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME /var/www/html/public/index.php;
            include fastcgi_params;
        }

        # Laravel Broadcasting Auth
        location /broadcasting {
            try_files $uri $uri/ /index.php?$query_string;
            fastcgi_pass backend;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME /var/www/html/public/index.php;
            include fastcgi_params;
        }

        # Archivos estáticos de Laravel
        location ~ ^/(storage|images)/ {
            root /var/www/html/public;
            try_files $uri =404;
        }
    }
}
```

Guardar: `Ctrl + O`, `Enter`, `Ctrl + X`

---

## Paso 6: Construir y Lanzar los Contenedores

```bash
# Construir imágenes
docker compose -f docker-compose.prod.yml build

# Lanzar contenedores en segundo plano
docker compose -f docker-compose.prod.yml up -d
```

---

## Paso 7: Ejecutar Migraciones y Seeders

```bash
# Esperar a que los contenedores estén listos (30 segundos)
sleep 30

# Generar key de Laravel
docker compose -f docker-compose.prod.yml exec backend php artisan key:generate

# Ejecutar migraciones
docker compose -f docker-compose.prod.yml exec backend php artisan migrate --force

# (Opcional) Ejecutar seeders si es primera instalación
docker compose -f docker-compose.prod.yml exec backend php artisan db:seed --force

# Limpiar cachés
docker compose -f docker-compose.prod.yml exec backend php artisan config:cache
docker compose -f docker-compose.prod.yml exec backend php artisan route:cache
docker compose -f docker-compose.prod.yml exec backend php artisan view:cache

# Crear enlace simbólico para storage
docker compose -f docker-compose.prod.yml exec backend php artisan storage:link

# Ajustar permisos
docker compose -f docker-compose.prod.yml exec backend chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker compose -f docker-compose.prod.yml exec backend chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

---

## Paso 8: Restaurar Datos del Backup (Opcional)

Si quieres mantener los datos de la versión anterior:

```bash
# Copiar el backup SQL al contenedor
docker cp ~/taskflow_backup_FECHA.sql taskflow_db_prod:/tmp/backup.sql

# Restaurar
docker exec -it taskflow_db_prod mysql -u taskflow_user -p taskflow_db < /tmp/backup.sql
```

---

## Paso 9: Verificar el Despliegue

### 9.1 Ver logs en tiempo real

```bash
docker compose -f docker-compose.prod.yml logs -f
```

### 9.2 Verificar contenedores corriendo

```bash
docker compose -f docker-compose.prod.yml ps
```

Deberías ver todos los servicios con estado "Up":
- taskflow_db_prod
- taskflow_redis_prod
- taskflow_soketi_prod
- taskflow_backend_prod
- taskflow_queue_prod
- taskflow_frontend_prod
- taskflow_nginx_prod

### 9.3 Probar la aplicación

Abre tu navegador y ve a:
```
http://172.16.10.25  # O tu IP/dominio
```

Deberías ver la aplicación TaskFlow funcionando.

---

## Paso 10: Configurar Actualizaciones Futuras

Crear script de actualización:

```bash
nano ~/update-taskflow.sh
```

Contenido:

```bash
#!/bin/bash

echo "🔄 Actualizando TaskFlow..."

cd ~/Taskflow-Icontel-NEW

# Pull cambios
git pull origin main

# Reconstruir y reiniciar
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d

# Esperar a que arranquen
sleep 30

# Ejecutar migraciones
docker compose -f docker-compose.prod.yml exec -T backend php artisan migrate --force

# Limpiar cachés
docker compose -f docker-compose.prod.yml exec -T backend php artisan config:cache
docker compose -f docker-compose.prod.yml exec -T backend php artisan route:cache
docker compose -f docker-compose.prod.yml exec -T backend php artisan view:cache

echo "✅ Actualización completada"
```

Dar permisos de ejecución:

```bash
chmod +x ~/update-taskflow.sh
```

Para actualizar en el futuro:

```bash
~/update-taskflow.sh
```

---

## Comandos Útiles

### Ver logs de un servicio específico

```bash
docker compose -f docker-compose.prod.yml logs -f backend
docker compose -f docker-compose.prod.yml logs -f frontend
docker compose -f docker-compose.prod.yml logs -f queue
```

### Reiniciar un servicio

```bash
docker compose -f docker-compose.prod.yml restart backend
docker compose -f docker-compose.prod.yml restart queue
```

### Entrar a un contenedor

```bash
docker compose -f docker-compose.prod.yml exec backend bash
docker compose -f docker-compose.prod.yml exec db mysql -u taskflow_user -p
```

### Detener todo

```bash
docker compose -f docker-compose.prod.yml down
```

### Eliminar todo (incluyendo volúmenes)

```bash
docker compose -f docker-compose.prod.yml down -v
```

---

## Troubleshooting

### Error 500 en el backend

```bash
# Ver logs
docker compose -f docker-compose.prod.yml logs backend

# Verificar permisos
docker compose -f docker-compose.prod.yml exec backend chown -R www-data:www-data /var/www/html/storage
docker compose -f docker-compose.prod.yml exec backend chmod -R 775 /var/www/html/storage

# Limpiar cachés
docker compose -f docker-compose.prod.yml exec backend php artisan config:clear
docker compose -f docker-compose.prod.yml exec backend php artisan cache:clear
```

### Notificaciones en tiempo real no funcionan

```bash
# Verificar Soketi
docker compose -f docker-compose.prod.yml logs soketi

# Verificar que el puerto 6001 esté expuesto
netstat -tulpn | grep 6001

# Reiniciar Soketi
docker compose -f docker-compose.prod.yml restart soketi
```

### Queue worker no procesa trabajos

```bash
# Ver logs del worker
docker compose -f docker-compose.prod.yml logs queue

# Reiniciar worker
docker compose -f docker-compose.prod.yml restart queue
```

### Base de datos no conecta

```bash
# Verificar estado de MySQL
docker compose -f docker-compose.prod.yml exec db mysqladmin -u root -p ping

# Verificar credenciales en .env
docker compose -f docker-compose.prod.yml exec backend cat .env | grep DB_
```

---

## Seguridad Adicional

### 1. Firewall

```bash
# Permitir solo puertos necesarios
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS (si usas SSL)
sudo ufw enable
```

### 2. Configurar HTTPS con Let's Encrypt (Opcional)

Si tienes un dominio:

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx

# Obtener certificado
sudo certbot --nginx -d tu-dominio.com

# Renovación automática
sudo certbot renew --dry-run
```

---

## Resumen

1. ✅ Backup de datos
2. ✅ Detener versión antigua
3. ✅ Clonar nueva versión
4. ✅ Configurar `.env` files
5. ✅ Crear `docker-compose.prod.yml`
6. ✅ Crear `nginx.conf`
7. ✅ Construir y lanzar contenedores
8. ✅ Ejecutar migraciones
9. ✅ Verificar funcionamiento
10. ✅ Script de actualización

¡Tu aplicación TaskFlow está ahora en producción! 🎉
