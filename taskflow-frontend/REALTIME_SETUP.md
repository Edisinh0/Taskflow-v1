# 🚀 Configuración de Tiempo Real - Frontend

## ✅ Implementación Completa

El sistema de notificaciones en tiempo real ha sido completamente configurado en el frontend.

## 📦 Archivos Creados

### 1. **Servicios**
- `src/services/echo.js` - Configuración de Laravel Echo

### 2. **Stores (Pinia)**
- `src/stores/notifications.js` - Store de notificaciones

### 3. **Composables**
- `src/composables/useRealtime.js` - Hooks para tiempo real

### 4. **Componentes**
- `src/components/NotificationCenter.vue` - Centro de notificaciones completo

## 🎯 Funcionamiento

### Inicialización Automática

El sistema se inicializa automáticamente cuando el usuario hace login:

1. **Login** → Inicializa Echo con el token
2. **Cargar desde storage** → Restaura Echo si hay sesión
3. **Logout** → Desconecta Echo

### Flujo de Notificaciones

```
Backend envía evento
    ↓
Laravel Echo recibe via WebSocket
    ↓
NotificationCenter escucha el canal user.{userId}
    ↓
Store agrega notificación
    ↓
Se muestra:
  - Toast (popup temporal)
  - Badge en el ícono
  - Panel de notificaciones
```

## 🔧 Uso en Componentes

### Escuchar Notificaciones del Usuario

```vue
<script setup>
import { useUserNotifications } from '@/composables/useRealtime'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

useUserNotifications(authStore.user.id, (event) => {
  console.log('Nueva notificación:', event.notification)
  // Hacer algo con la notificación
})
</script>
```

### Escuchar Actualizaciones de Tarea

```vue
<script setup>
import { ref } from 'vue'
import { useTaskUpdates } from '@/composables/useRealtime'

const props = defineProps(['taskId'])
const task = ref({})

useTaskUpdates(props.taskId, {
  onUpdate: (event) => {
    console.log('Tarea actualizada:', event.task)
    task.value = event.task
  },
  onSlaBreach: (event) => {
    console.log('⚠️ SLA breach:', event.task)
    // Mostrar alerta
  },
  onSlaEscalation: (event) => {
    console.log('🚨 Escalado:', event.task)
    // Mostrar alerta urgente
  }
})
</script>
```

### Escuchar Actualizaciones de Flujo

```vue
<script setup>
import { useFlowUpdates } from '@/composables/useRealtime'

const props = defineProps(['flowId'])

useFlowUpdates(props.flowId, (event) => {
  console.log('Flujo actualizado:', event)
  // Refrescar lista de tareas
  loadTasks()
})
</script>
```

## 🎨 Personalización

### Modificar Estilos del Toast

Edita `NotificationCenter.vue`:

```vue
<style scoped>
.toast {
  /* Personaliza aquí */
}
</style>
```

### Agregar Sonido Personalizado

1. Agregar archivo de sonido en `public/notification.mp3`
2. El sistema lo reproduce automáticamente para notificaciones urgentes

### Cambiar Duración del Toast

En `src/stores/notifications.js`:

```javascript
// Cambiar de 5000ms (5 segundos) a otro valor
setTimeout(() => {
  removeToast(toast.id)
}, 5000) // ← Cambiar aquí
```

## 📊 Store de Notificaciones

### State
- `notifications` - Array de notificaciones
- `toasts` - Array de toasts activos
- `isLoading` - Estado de carga

### Getters
- `unreadCount` - Contador de no leídas
- `unreadNotifications` - Notificaciones no leídas
- `slaNotifications` - Solo notificaciones de SLA

### Actions
- `fetchNotifications()` - Cargar desde API
- `markAsRead(id)` - Marcar como leída
- `markAllAsRead()` - Marcar todas
- `addNotification(notification)` - Agregar nueva
- `showToast(notification)` - Mostrar toast
- `removeToast(id)` - Quitar toast

## 🔔 Eventos Disponibles

### Canal: `user.{userId}`
- `notification.sent` - Nueva notificación

### Canal: `task.{taskId}`
- `task.updated` - Tarea actualizada
- `sla.breached` - SLA vencido
- `sla.escalated` - SLA escalado

### Canal: `flow.{flowId}`
- `task.updated` - Tarea del flujo actualizada

## 🐛 Troubleshooting

### Las notificaciones no llegan

1. **Verificar Echo está inicializado**:
```javascript
import { getEcho } from '@/services/echo'
console.log(getEcho()) // Debe devolver instancia de Echo
```

2. **Verificar token**:
```javascript
console.log(localStorage.getItem('token'))
```

3. **Verificar conexión en DevTools**:
- Abrir Network → WS (WebSockets)
- Debe ver conexión a `localhost:6001`

### Error de autenticación

- Verificar que el backend tenga las rutas de broadcasting configuradas
- Verificar que el token sea válido
- Ver errores en consola del navegador

### No se muestran los toasts

- Verificar que Tailwind CSS esté configurado
- Verificar z-index del contenedor de toasts
- Ver errores en consola

## ⚙️ Configuración Avanzada

### Cambiar Host de WebSocket

En `src/services/echo.js`:

```javascript
const wsHost = 'tu-servidor.com' // En producción
```

### Agregar Más Canales

En `src/composables/useRealtime.js`:

```javascript
export function useCustomChannel(channelName, events) {
  return useRealtime(`custom.${channelName}`, events)
}
```

### Deshabilitar Sonidos

En `src/stores/notifications.js`:

```javascript
function showToast(notification) {
  // Comentar esta línea:
  // if (notification.priority === 'urgent') {
  //   playNotificationSound()
  // }
}
```

## 📱 Variables de Entorno

Crear archivo `.env.local`:

```env
VITE_API_URL=http://localhost:8080
VITE_WS_HOST=localhost
VITE_WS_PORT=6001
```

Usar en el código:

```javascript
const wsHost = import.meta.env.VITE_WS_HOST || 'localhost'
const wsPort = import.meta.env.VITE_WS_PORT || 6001
```

## 🎯 Próximos Pasos

1. **Probar en desarrollo**:
```bash
npm run dev
```

2. **Hacer login** y verificar que aparece el ícono de notificaciones

3. **Crear una tarea vencida** en el backend para ver las notificaciones

4. **Actualizar una tarea** y ver el evento en tiempo real

## 🚀 Despliegue en Producción

1. **Configurar WebSocket Server** (Laravel Echo Server o Soketi)

2. **Actualizar variables de entorno**:
```env
VITE_WS_HOST=tu-dominio.com
VITE_WS_PORT=6001
```

3. **Build**:
```bash
npm run build
```

4. **Servir archivos estáticos** con Nginx/Apache

---

**Sistema completamente funcional** ✅

El centro de notificaciones está integrado en el navbar y funcionará automáticamente cuando el usuario inicie sesión.
