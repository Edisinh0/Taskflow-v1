# 🚀 Guía de Actualización de Taskflow en Producción

## Problema Resuelto

Se corrigieron dos problemas principales:

### 1. **Error CORS** ❌
```
Origin http://172.16.10.25 is not allowed by Access-Control-Allow-Origin
```

**Causa:** El backend no permitía peticiones desde la IP del VPS.

**Solución:** 
- Actualizado `config/cors.php` para permitir todos los orígenes en producción
- Agregadas cabeceras CORS en el gateway Nginx

### 2. **URL incorrecta del API** ❌
```
XMLHttpRequest cannot load http://localhost:8080/api/v1/notifications
```

**Causa:** El frontend estaba usando `localhost:8080` en producción.

**Solución:**
- Creado `.env.production` con `VITE_API_BASE_URL=/api/v1`
- Actualizado `Dockerfile` del frontend para usar este archivo
- Ahora usa URLs relativas que el gateway proxy-pasa correctamente

---

## 📋 Archivos Modificados

1. ✅ `docker-compose.prod.yml` - Corregido valor booleano de `APP_DEBUG`
2. ✅ `taskflow-backend/config/cors.php` - Permitir orígenes en producción
3. ✅ `nginx-gateway/conf.d/default.conf` - Agregadas cabeceras CORS
4. ✅ `taskflow-frontend/.env.production` - Configuración de API para producción
5. ✅ `taskflow-frontend/Dockerfile` - Copiar archivo .env.production
6. ✅ `update.sh` - Script automatizado de actualización

---

## 🔧 Cómo Actualizar la Aplicación

### Opción 1: Usar el Script Automatizado (Recomendado)

```bash
# En el VPS, dentro del directorio del proyecto
cd /ruta/a/Taskflow-Icontel
./update.sh
```

Este script hace todo automáticamente:
- ✅ Git pull de los últimos cambios
- ✅ Detiene contenedores
- ✅ Reconstruye imágenes con los nuevos cambios
- ✅ Levanta los servicios
- ✅ Limpia cachés de Laravel
- ✅ Muestra el estado final

### Opción 2: Paso a Paso Manual

```bash
# 1. Ir al directorio
cd /ruta/a/Taskflow-Icontel

# 2. Obtener cambios
git pull origin main

# 3. Aumentar timeout (si es necesario)
export COMPOSE_HTTP_TIMEOUT=300
export DOCKER_CLIENT_TIMEOUT=300

# 4. Detener contenedores
docker-compose -f docker-compose.prod.yml down

# 5. Reconstruir imágenes
docker-compose -f docker-compose.prod.yml build --no-cache

# 6. Levantar servicios
docker-compose -f docker-compose.prod.yml up -d

# 7. Limpiar cachés
docker-compose -f docker-compose.prod.yml exec backend php artisan optimize:clear
docker-compose -f docker-compose.prod.yml exec backend php artisan config:cache
docker-compose -f docker-compose.prod.yml exec backend php artisan route:cache
docker-compose -f docker-compose.prod.yml exec backend php artisan view:cache
```

---

## 🔍 Verificar que Todo Funciona

### 1. Ver estado de contenedores
```bash
docker-compose -f docker-compose.prod.yml ps
```

Deberías ver 6 contenedores con estado **"Up"**:
- ✅ taskflow_frontend
- ✅ taskflow_backend
- ✅ taskflow_queue
- ✅ taskflow_gateway
- ✅ taskflow_db
- ✅ taskflow_redis

### 2. Ver logs
```bash
# Todos los servicios
docker-compose -f docker-compose.prod.yml logs -f

# Solo backend
docker-compose -f docker-compose.prod.yml logs -f backend

# Solo frontend
docker-compose -f docker-compose.prod.yml logs -f frontend
```

### 3. Probar la aplicación
```bash
# Desde el servidor
curl http://localhost

# Desde tu navegador
http://172.16.10.25
```

---

## 🐛 Solución de Problemas

### Error: "Read timed out"
```bash
# Aumentar timeout antes de construir
export COMPOSE_HTTP_TIMEOUT=300
export DOCKER_CLIENT_TIMEOUT=300
```

### Error: "No space left on device"
```bash
# Limpiar Docker
docker system prune -a --volumes
```

### Los cambios no se reflejan
```bash
# Asegúrate de reconstruir las imágenes
docker-compose -f docker-compose.prod.yml build --no-cache

# Limpiar cachés de Laravel
docker-compose -f docker-compose.prod.yml exec backend php artisan optimize:clear
```

### Error CORS persiste
```bash
# Verificar que el backend esté en modo producción
docker-compose -f docker-compose.prod.yml exec backend php artisan env

# Limpiar caché de configuración
docker-compose -f docker-compose.prod.yml exec backend php artisan config:clear
docker-compose -f docker-compose.prod.yml exec backend php artisan config:cache
```

---

## 📊 Comandos Útiles

```bash
# Ver uso de recursos
docker stats

# Ver espacio en disco
docker system df

# Reiniciar un servicio específico
docker-compose -f docker-compose.prod.yml restart backend

# Entrar a un contenedor
docker-compose -f docker-compose.prod.yml exec backend sh

# Ver últimos 100 logs
docker-compose -f docker-compose.prod.yml logs --tail=100 backend
```

---

## 🎯 Próximos Pasos

Para hacer el despliegue aún más automático, considera:

1. **Configurar GitHub Actions** para despliegue automático
2. **Agregar SSL/HTTPS** con Let's Encrypt
3. **Configurar backups automáticos** de la base de datos
4. **Monitoreo** con herramientas como Prometheus/Grafana

---

## 📞 Soporte

Si encuentras algún problema, revisa:
1. Los logs: `docker-compose -f docker-compose.prod.yml logs -f`
2. El estado: `docker-compose -f docker-compose.prod.yml ps`
3. La consola del navegador (F12)
