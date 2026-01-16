# 🔧 Troubleshooting - Taskflow Frontend

## Problemas Comunes y Soluciones

---

## 🚨 Error 404 en chunks JS (dashboard, etc.)

### Síntomas
```
[Error] Failed to load resource: the server responded with a status of 404 (Not Found) (dashboard, line 0)
```

### Causas Posibles
1. **Caché del navegador desactualizado** después de un rebuild
2. **Servidor de desarrollo no reconstruyó** los chunks correctamente
3. **Imports dinámicos** con rutas incorrectas

### Soluciones

#### 1. Limpiar caché y reconstruir

```bash
# Detener el servidor de desarrollo (Ctrl+C)

# Limpiar node_modules y reinstalar
rm -rf node_modules package-lock.json
npm install

# Limpiar dist y caché de Vite
rm -rf dist .vite

# Reconstruir
npm run build

# O para desarrollo
npm run dev
```

#### 2. Hard Refresh en el navegador

- **Chrome/Edge**: `Ctrl+Shift+R` (Windows/Linux) o `Cmd+Shift+R` (Mac)
- **Firefox**: `Ctrl+F5` (Windows/Linux) o `Cmd+Shift+R` (Mac)
- **Safari**: `Cmd+Option+R`

#### 3. Limpiar caché del navegador

**Chrome/Edge:**
1. Abrir DevTools (F12)
2. Click derecho en el botón de refresh → "Empty Cache and Hard Reload"

**Firefox:**
1. Preferences → Privacy & Security
2. Clear Data → Cached Web Content

#### 4. Verificar el archivo de rutas

Asegúrate de que [`src/router/index.js`](src/router/index.js) tenga la ruta correctamente definida:

```javascript
{
  path: '/dashboard',
  name: 'dashboard',
  component: () => import('../views/DashboardView.vue'),
  meta: { requiresAuth: true }
}
```

#### 5. Verificar que el archivo existe

```bash
ls -la src/views/DashboardView.vue
```

Si el archivo no existe, algo está mal con el proyecto.

---

## 🔌 WebSocket Connection Failed

### Síntomas
```
[Error] WebSocket connection to 'ws://localhost:6001/app/taskflow-key...' failed:
WebSocket is closed due to suspension.
```

### ¿Es esto un problema?

**NO** - Esta es solo una advertencia. La aplicación funciona perfectamente **sin WebSocket**.

### ¿Qué se pierde sin WebSocket?

- ❌ Notificaciones en tiempo real (necesitarás refrescar la página)
- ❌ Actualizaciones automáticas de tareas
- ✅ Todas las demás funciones funcionan normalmente

### Solución (si quieres notificaciones en tiempo real)

El WebSocket requiere un servidor **Soketi** o **Laravel WebSockets** corriendo.

#### Opción 1: Usar Soketi (Recomendado)

```bash
# Instalar Soketi globalmente
npm install -g @soketi/soketi

# Correr Soketi
soketi start --port=6001 --app-id=taskflow --app-key=taskflow-key --app-secret=taskflow-secret
```

#### Opción 2: Desactivar WebSocket completamente

Edita [`.env`](.env):

```env
# Comentar o eliminar estas líneas
# VITE_PUSHER_APP_KEY=taskflow-key
# VITE_PUSHER_HOST=localhost
# VITE_PUSHER_PORT=6001
```

Y modifica [`src/stores/auth.js`](src/stores/auth.js) para que no intente inicializar Echo.

#### Opción 3: Ignorar el error

Los errores de WebSocket ahora se manejan silenciosamente con `console.debug()` en lugar de `console.error()`, así que no aparecerán como errores rojos en la consola.

---

## 🎨 Los cambios de CSS no se reflejan

### Solución

```bash
# Limpiar caché de Tailwind/PostCSS
rm -rf .vite
npm run dev
```

---

## 📦 Error al importar componentes

### Síntomas
```
Failed to resolve import "..." from "..."
```

### Soluciones

1. **Verificar que el archivo existe**
2. **Verificar la ruta del import** (case-sensitive)
3. **Reinstalar dependencias**

```bash
rm -rf node_modules package-lock.json
npm install
```

---

## 🔒 CORS Error en API

### Síntomas
```
Access to fetch at 'http://localhost/api/v1/...' from origin 'http://localhost:5173'
has been blocked by CORS policy
```

### Solución

Verificar que el backend tenga CORS configurado correctamente en [`taskflow-backend/config/cors.php`](../taskflow-backend/config/cors.php):

```php
'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],
'allowed_origins' => ['http://localhost:5173', 'http://localhost'],
```

---

## 🐌 Vite muy lento en desarrollo

### Solución

Edita [`vite.config.js`](vite.config.js):

```javascript
export default defineConfig({
  // ... config actual
  optimizeDeps: {
    include: [
      'vue',
      'vue-router',
      'pinia',
      'axios',
      'sweetalert2'
    ]
  }
})
```

---

## 🔄 Hot Module Replacement (HMR) no funciona

### Síntomas
- Los cambios en `.vue` no se reflejan automáticamente
- Necesitas refrescar manualmente

### Soluciones

1. **Verificar que el puerto 5173 no está bloqueado**
2. **Usar el script de desarrollo**

```bash
npm run dev
```

3. **Si estás usando Docker**, asegúrate de exponer el puerto:

```yaml
ports:
  - "5173:5173"
```

---

## 📊 Chart.js no renderiza gráficos

### Solución

Verificar que Chart.js esté correctamente registrado en el componente:

```javascript
import { Chart, registerables } from 'chart.js'
Chart.register(...registerables)
```

---

## 🔐 "Token expired" o "Unauthenticated"

### Solución

```javascript
// Limpiar localStorage y volver a login
localStorage.clear()
window.location.href = '/login'
```

O hacer logout y login de nuevo.

---

## 💾 localStorage no persiste

### Causa
- Navegación en modo incógnito
- Configuración de privacidad del navegador

### Solución
- Usar navegación normal (no incógnito)
- Verificar configuración de cookies/storage del navegador

---

## 🎭 SweetAlert2 no muestra iconos

### Solución

Verificar que SweetAlert2 esté instalado:

```bash
npm install sweetalert2
```

Y que el composable `useToast` esté importado correctamente:

```javascript
import { useToast } from '@/composables/useToast'
```

---

## 🚀 Build de producción falla

### Síntomas
```
npm run build
ERROR: ...
```

### Soluciones

1. **Limpiar y reinstalar**

```bash
rm -rf node_modules package-lock.json dist .vite
npm install
npm run build
```

2. **Verificar errores de TypeScript/ESLint**

```bash
npm run lint
```

3. **Aumentar memoria de Node.js**

```bash
NODE_OPTIONS="--max-old-space-size=4096" npm run build
```

---

## 📱 Responsive no funciona correctamente

### Solución

Verificar que Tailwind CSS esté configurado en [`tailwind.config.js`](tailwind.config.js):

```javascript
module.exports = {
  content: [
    './index.html',
    './src/**/*.{vue,js,ts,jsx,tsx}'
  ],
  // ...
}
```

---

## 🔍 Cómo depurar problemas

### 1. Abrir DevTools (F12)

- **Console**: Ver errores de JavaScript
- **Network**: Ver requests fallidos (404, 500, etc.)
- **Application**: Ver localStorage, cookies

### 2. Usar Vue DevTools

Instalar extensión: [Vue DevTools](https://devtools.vuejs.org/)

### 3. Habilitar logs de debug

En `.env`:

```env
VITE_DEBUG=true
```

### 4. Verificar versiones

```bash
node --version  # Debe ser 20.x o superior
npm --version
```

---

## 📞 Obtener ayuda

Si ninguna solución funciona:

1. **Verificar logs del navegador** (Console en DevTools)
2. **Verificar logs del backend** (`taskflow-backend/storage/logs/laravel.log`)
3. **Crear un issue** con:
   - Descripción del problema
   - Pasos para reproducir
   - Capturas de pantalla
   - Logs relevantes

---

## ✅ Checklist de salud del proyecto

```bash
# ¿Node.js versión correcta?
node --version  # Debe ser >= 20.19.0

# ¿Dependencias instaladas?
ls node_modules | wc -l  # Debe ser > 100

# ¿Variables de entorno configuradas?
cat .env

# ¿Backend corriendo?
curl http://localhost/api/v1/health

# ¿Build funciona?
npm run build
```

---

**Última actualización:** 2026-01-15
