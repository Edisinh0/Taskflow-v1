# Guía Rápida de Despliegue - TaskFlow 🚀

## Resumen Ejecutivo

Esta es la guía simplificada para pasar tu proyecto TaskFlow a producción en tu VPS con Ubuntu.

---

## Antes de Empezar

✅ **Tienes:**
- VPS con Ubuntu
- Docker y Docker Compose instalados
- IP del servidor: `172.16.10.25` (o la que tengas)
- Versión anterior de TaskFlow corriendo (será reemplazada)

📝 **Necesitas:**
- Acceso SSH al servidor
- 30-45 minutos de tiempo
- Backup de la base de datos actual (lo haremos en el paso 1)

---

## Pasos Rápidos

### 1️⃣ Conectar al VPS y Hacer Backup

```bash
# Desde tu PC
ssh usuario@172.16.10.25

# En el VPS - Backup de la BD actual
docker ps  # Ver nombre del contenedor de MySQL
docker exec NOMBRE_CONTENEDOR_DB mysqldump -u root -p taskflow_db > ~/backup_$(date +%Y%m%d).sql

# Detener versión antigua
cd ~/directorio_proyecto_antiguo
docker compose down

# Opcional: Renombrar proyecto antiguo
cd ~
mv Taskflow-Icontel Taskflow-OLD
```

---

### 2️⃣ Subir Proyecto al VPS

**Opción A: Desde Git (Recomendado)**
```bash
# En el VPS
cd ~
git clone https://github.com/TU_USUARIO/TaskFlow.git Taskflow-Icontel
cd Taskflow-Icontel
```

**Opción B: Subir desde tu PC con SCP**
```bash
# Desde tu PC (en el directorio del proyecto)
tar -czf taskflow.tar.gz taskflow-backend taskflow-frontend nginx.conf docker-compose.prod.yml
scp taskflow.tar.gz usuario@172.16.10.25:~

# En el VPS
cd ~
tar -xzf taskflow.tar.gz
cd Taskflow-Icontel
```

---

### 3️⃣ Configurar Variables de Entorno

```bash
# Backend
cd taskflow-backend
cp .env.example .env
nano .env
```

**Configurar estas líneas CRÍTICAS:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://172.16.10.25  # TU IP

DB_HOST=db
DB_DATABASE=taskflow_db
DB_USERNAME=taskflow_user
DB_PASSWORD=tu_password_seguro_aqui  # CAMBIAR ESTO

BROADCAST_CONNECTION=pusher
PUSHER_HOST=soketi
PUSHER_APP_KEY=taskflow-key

REDIS_HOST=redis
QUEUE_CONNECTION=redis
```

```bash
# Frontend
cd ../taskflow-frontend
nano .env
```

**Contenido:**
```env
VITE_API_BASE_URL=http://172.16.10.25/api/v1  # TU IP
VITE_PUSHER_APP_KEY=taskflow-key
VITE_PUSHER_HOST=172.16.10.25  # TU IP
VITE_PUSHER_PORT=6001
```

---

### 4️⃣ Lanzar la Aplicación

```bash
# Volver a la raíz
cd ..

# Construir y lanzar
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d

# Esperar 30 segundos
sleep 30

# Ejecutar migraciones
docker compose -f docker-compose.prod.yml exec backend php artisan key:generate
docker compose -f docker-compose.prod.yml exec backend php artisan migrate --force
docker compose -f docker-compose.prod.yml exec backend php artisan storage:link

# Ajustar permisos
docker compose -f docker-compose.prod.yml exec backend chown -R www-data:www-data /var/www/html/storage
docker compose -f docker-compose.prod.yml exec backend chmod -R 775 /var/www/html/storage

# Cachear configuración
docker compose -f docker-compose.prod.yml exec backend php artisan config:cache
docker compose -f docker-compose.prod.yml exec backend php artisan route:cache
```

---

### 5️⃣ Verificar que Todo Funcione

```bash
# Ver estado de contenedores
docker compose -f docker-compose.prod.yml ps

# Ver logs en tiempo real
docker compose -f docker-compose.prod.yml logs -f

# Salir de los logs: Ctrl+C
```

**Abrir en el navegador:**
```
http://172.16.10.25
```

Deberías ver TaskFlow funcionando! 🎉

---

## Restaurar Datos del Backup (Opcional)

Si quieres mantener los datos de la versión anterior:

```bash
# Copiar backup al contenedor
docker cp ~/backup_FECHA.sql taskflow_db_prod:/tmp/backup.sql

# Restaurar
docker compose -f docker-compose.prod.yml exec db mysql -u taskflow_user -p taskflow_db < /tmp/backup.sql
```

---

## Comandos Útiles

### Ver logs de un servicio
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

### Detener todo
```bash
docker compose -f docker-compose.prod.yml down
```

### Actualizar código
```bash
git pull origin main
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec backend php artisan migrate --force
docker compose -f docker-compose.prod.yml exec backend php artisan config:cache
```

---

## Solución de Problemas Rápidos

### ❌ Error 500
```bash
docker compose -f docker-compose.prod.yml logs backend
docker compose -f docker-compose.prod.yml exec backend php artisan config:clear
docker compose -f docker-compose.prod.yml exec backend chown -R www-data:www-data /var/www/html/storage
```

### ❌ No conecta a la BD
```bash
# Verificar contraseñas en .env
docker compose -f docker-compose.prod.yml exec backend cat .env | grep DB_

# Verificar MySQL
docker compose -f docker-compose.prod.yml exec db mysqladmin -u root -p ping
```

### ❌ WebSocket no funciona
```bash
# Ver logs de Soketi
docker compose -f docker-compose.prod.yml logs soketi

# Reiniciar
docker compose -f docker-compose.prod.yml restart soketi

# Verificar puerto 6001 abierto
sudo netstat -tulpn | grep 6001
```

---

## Archivos Necesarios

Asegúrate de tener estos archivos en la raíz del proyecto:

1. ✅ `docker-compose.prod.yml` - Orquestación de contenedores
2. ✅ `nginx.conf` - Configuración del proxy inverso
3. ✅ `taskflow-backend/.env` - Variables de entorno backend
4. ✅ `taskflow-frontend/.env` - Variables de entorno frontend

**Si no los tienes, revisa el archivo `PRODUCTION_DEPLOYMENT.md` para los contenidos completos.**

---

## Checklist de Despliegue

- [ ] Backup de base de datos creado
- [ ] Versión antigua detenida
- [ ] Proyecto nuevo subido al VPS
- [ ] `.env` del backend configurado
- [ ] `.env` del frontend configurado
- [ ] `docker-compose.prod.yml` creado
- [ ] `nginx.conf` creado
- [ ] Contenedores construidos
- [ ] Contenedores lanzados
- [ ] Migraciones ejecutadas
- [ ] Permisos configurados
- [ ] Aplicación accesible en navegador
- [ ] Login funcional
- [ ] Notificaciones en tiempo real funcionan

---

## Seguridad Básica

```bash
# Firewall
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS (futuro)
sudo ufw enable

# Cambiar contraseñas por defecto
# - DB_PASSWORD en .env
# - MYSQL_ROOT_PASSWORD en docker-compose.prod.yml
```

---

## Soporte

Si tienes problemas:

1. Revisa los logs: `docker compose -f docker-compose.prod.yml logs -f`
2. Consulta `PRODUCTION_DEPLOYMENT.md` para la guía completa
3. Verifica que todos los servicios estén "Up": `docker compose -f docker-compose.prod.yml ps`

---

**¡Listo! Tu TaskFlow está en producción** 🚀

Para actualizaciones futuras, simplemente:
```bash
git pull
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec backend php artisan migrate --force
```
