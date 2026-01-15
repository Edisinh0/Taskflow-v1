import { ref, onUnmounted } from 'vue'
import { getEcho } from '@/services/echo'

/**
 * Composable para manejar conexiones en tiempo real
 * @param {string} channelName - Nombre del canal (ej: 'user.1', 'task.123')
 * @param {object} events - Objeto con eventos y sus handlers
 * @returns {object} Estado de la conexión y método para conectar
 */
export function useRealtime(channelName, events = {}) {
  const isConnected = ref(false)
  const channel = ref(null)
  const error = ref(null)

  const connect = () => {
    try {
      const echo = getEcho()

      if (!echo) {
        console.warn('Echo no está inicializado. Esperando autenticación...')
        return
      }

      // Subscribe to channel
      channel.value = echo.private(channelName)

      // Register event listeners
      Object.keys(events).forEach((eventName) => {
        const fullEventName = eventName.startsWith('.') ? eventName : `.${eventName}`
        channel.value.listen(fullEventName, events[eventName])
      })

      isConnected.value = true
      console.log(`🔗 Connected to channel: ${channelName}`)
    } catch (err) {
      error.value = err.message
      console.error(`❌ Error connecting to channel ${channelName}:`, err)
    }
  }

  const disconnect = () => {
    if (channel.value) {
      const echo = getEcho()
      if (echo) {
        echo.leave(channelName)
      }
      isConnected.value = false
      console.log(`🔌 Disconnected from channel: ${channelName}`)
    }
  }

  // Auto-disconnect on unmount
  onUnmounted(() => {
    disconnect()
  })

  // Auto-connect
  connect()

  return {
    isConnected,
    channel,
    error,
    connect,
    disconnect
  }
}

/**
 * Composable para escuchar notificaciones del usuario
 * @param {number} userId - ID del usuario
 * @param {function} onNotification - Callback cuando llega una notificación
 */
export function useUserNotifications(userId, onNotification) {
  return useRealtime(`user.${userId}`, {
    'notification.sent': onNotification
  })
}

/**
 * Composable para escuchar actualizaciones de tarea
 * @param {number} taskId - ID de la tarea
 * @param {object} handlers - Objeto con handlers para cada evento
 */
export function useTaskUpdates(taskId, handlers = {}) {
  const defaultHandlers = {
    'task.updated': handlers.onUpdate || (() => {}),
    'sla.breached': handlers.onSlaBreach || (() => {}),
    'sla.escalated': handlers.onSlaEscalation || (() => {})
  }

  return useRealtime(`task.${taskId}`, defaultHandlers)
}

/**
 * Composable para escuchar actualizaciones de flujo
 * @param {number} flowId - ID del flujo
 * @param {function} onUpdate - Callback cuando se actualiza el flujo
 */
export function useFlowUpdates(flowId, onUpdate) {
  return useRealtime(`flow.${flowId}`, {
    'task.updated': onUpdate
  })
}
