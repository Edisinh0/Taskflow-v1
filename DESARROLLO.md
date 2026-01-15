# Guía de Desarrollo Taskflow

Esta guía te ayudará a configurar y trabajar eficientemente en el proyecto Taskflow.

## 🚀 Inicio Rápido

### Opción 1: Script Automático (Recomendado)

Simplemente ejecuta:

```bash
./dev.sh
```

Este script:
- ✅ Verifica e inicia los contenedores Docker (backend, DB, Redis, etc.)
- ✅ Inicia el frontend con Vite y Hot Module Replacement
- ✅ Muestra los URLs disponibles

### Opción 2: Manual

```bash
# Terminal 1: Backend y servicios
cd taskflow-backend
docker-compose up

# Terminal 2: Frontend con HMR
cd taskflow-frontend
npm run dev
```

## 📍 URLs Disponibles

- **Frontend Desarrollo**: http://localhost:5173 (con Hot Module Replacement)
- **Backend API**: http://localhost/api/v1
- **App Completa (Docker)**: http://localhost
- **WebSockets (Soketi)**: http://localhost:6001

## 🔄 Flujo de Trabajo

### Desarrollo Frontend

**✅ Cambios automáticos (NO requieren reinicio):**
- Componentes Vue (.vue)
- JavaScript/TypeScript
- CSS/SCSS
- Rutas de Vue Router

Los cambios se reflejan **instantáneamente** gracias al Hot Module Replacement (HMR).

**⚠️ Requieren reiniciar Vite:**
- Variables de entorno (`.env`)
- `vite.config.js`
- `package.json`

Para reiniciar:
```bash
# Ctrl+C en la terminal de Vite, luego:
npm run dev
```

### Desarrollo Backend

**✅ Cambios automáticos (requieren restart suave):**
- Controladores PHP
- Modelos
- Rutas
- Servicios

Para aplicar cambios PHP:
```bash
docker-compose restart backend
```

**⚠️ Requieren reconstruir:**
- `composer.json` (nuevas dependencias)
- `Dockerfile`
- Migraciones de base de datos

Para migraciones:
```bash
docker-compose exec backend php artisan migrate
```

Para reconstruir backend:
```bash
docker-compose build backend
docker-compose up -d backend
```

## 🏗️ Despliegue a Producción

Cuando termines de desarrollar y quieras desplegar:

```bash
# 1. Reconstruir frontend con cambios
cd taskflow-backend
docker-compose build frontend

# 2. Reiniciar servicios
docker-compose up -d

# 3. Verificar que todo esté corriendo
docker-compose ps
```

## 📦 Gestión de Dependencias

### Frontend (npm)

```bash
# Agregar dependencia
cd taskflow-frontend
npm install nombre-paquete

# Reiniciar Vite para aplicar cambios
```

### Backend (Composer)

```bash
# Agregar dependencia
docker-compose exec backend composer require vendor/package

# Reiniciar backend
docker-compose restart backend
```

## 🐛 Resolución de Problemas

### Frontend no muestra cambios

**Si estás en http://localhost (Docker):**
```bash
cd taskflow-backend
docker-compose build frontend
docker-compose up -d frontend
```

**Si estás en http://localhost:5173 (Vite):**
- Los cambios deberían ser automáticos
- Verifica que Vite esté corriendo
- Haz hard refresh: `Cmd+Shift+R` (Mac) o `Ctrl+Shift+R` (Windows)

### Backend no responde

```bash
# Ver logs
docker-compose logs backend

# Reiniciar
docker-compose restart backend
```

### Base de datos no conecta

```bash
# Verificar que el contenedor esté corriendo
docker-compose ps

# Ver logs de la base de datos
docker-compose logs db

# Reiniciar servicios
docker-compose restart db backend
```

### Limpiar y reiniciar todo

```bash
# Detener todo
docker-compose down

# Limpiar volúmenes (⚠️ CUIDADO: borra la base de datos)
docker-compose down -v

# Iniciar de nuevo
docker-compose up -d
```

## 🎯 Mejores Prácticas

### Durante Desarrollo

1. **Usa Vite Dev Server** (http://localhost:5173) para el frontend
   - Cambios instantáneos
   - Mejor experiencia de desarrollo
   - Hot Module Replacement

2. **Mantén Docker corriendo** para el backend
   - Servicios estables
   - No necesitas reconstruir constantemente

3. **Commits frecuentes**
   ```bash
   git add .
   git commit -m "descripción del cambio"
   git push
   ```

### Antes de Desplegar

1. **Prueba en Docker completo**
   - Reconstruye el frontend: `docker-compose build frontend`
   - Accede a http://localhost
   - Verifica que todo funcione

2. **Ejecuta migraciones si hay cambios en DB**
   ```bash
   docker-compose exec backend php artisan migrate --force
   ```

3. **Verifica logs**
   ```bash
   docker-compose logs --tail=100
   ```

## 📚 Estructura del Proyecto

```
taskflow/
├── taskflow-backend/       # Laravel API
│   ├── app/               # Controladores, modelos, servicios
│   ├── database/          # Migraciones, seeders
│   ├── routes/            # Rutas API
│   └── docker-compose.yml # Configuración Docker
│
├── taskflow-frontend/     # Vue.js SPA
│   ├── src/
│   │   ├── components/   # Componentes Vue
│   │   ├── views/        # Vistas/Páginas
│   │   ├── stores/       # Pinia stores
│   │   └── services/     # API clients
│   ├── .env              # Variables desarrollo
│   └── .env.production   # Variables producción
│
└── dev.sh                # Script de desarrollo
```

## ❓ Preguntas Frecuentes

**P: ¿Cuándo uso http://localhost vs http://localhost:5173?**
- **Desarrollo**: Usa http://localhost:5173 (Vite con HMR)
- **Pruebas de producción**: Usa http://localhost (Docker compilado)

**P: ¿Necesito reconstruir Docker cada vez que cambio código?**
- **Frontend**: NO si usas Vite (puerto 5173)
- **Backend**: NO, solo restart: `docker-compose restart backend`
- **Solo reconstruir** cuando cambies `Dockerfile`, dependencias, o para deploy

**P: ¿Cómo sé si mis cambios se guardaron?**
- Vite mostrará "page reloaded" o "hmr update" en la consola
- Verás los cambios inmediatamente en el navegador

**P: ¿Qué hago si algo no funciona?**
1. Verifica que Docker esté corriendo
2. Revisa los logs: `docker-compose logs`
3. Reinicia los servicios: `docker-compose restart`
4. Si nada funciona: `docker-compose down && docker-compose up -d`

## 🎉 ¡Listo para Desarrollar!

Ahora puedes:
- ✅ Hacer cambios en Vue y verlos al instante
- ✅ Modificar el backend y reiniciar rápidamente
- ✅ Trabajar eficientemente sin reconstruir constantemente

**¡Feliz desarrollo!** 🚀
