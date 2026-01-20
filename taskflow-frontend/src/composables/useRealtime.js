import { ref, onUnmounted } from 'vue'
import { getEcho } from '@/services/echo'

/**
 * Composable para manejar conexiones en tiempo real
 * @param {string} channelName - Nombre del canal (ej: 'user.1', 'task.123')
 * @param {object} events - Objeto con eventos y sus handlers
 * @returns {object} Estado de la conexión y método para conectar
 */
// Cache global de canales para evitar múltiples suscripciones
const channelCache = new Map()

export function useRealtime(channelName, events = {}) {
  const isConnected = ref(false)
  const channel = ref(null)
  const error = ref(null)
  const listenerIds = ref([])

  const connect = () => {
    try {
      const echo = getEcho()

      if (!echo) {
        console.warn('Echo no está inicializado. Esperando autenticación...')
        return
      }

      // Verificar si ya existe una suscripción activa para este canal
      if (channelCache.has(channelName)) {
        console.log(`♻️ Reutilizando canal existente: ${channelName}`)
        channel.value = channelCache.get(channelName)
      } else {
        console.log(`🆕 Creando nuevo canal: ${channelName}`)
        // Subscribe to channel
        channel.value = echo.private(channelName)

        // Guardar en cache
        channelCache.set(channelName, channel.value)

        // Debug: Acceder al Pusher connection directamente para ver TODOS los eventos
        if (echo.connector && echo.connector.pusher) {
          const pusherChannel = echo.connector.pusher.channel(`private-${channelName}`)
          if (pusherChannel) {
            pusherChannel.bind_global((eventName, data) => {
              console.log('🌍 [PUSHER GLOBAL] Evento capturado:', eventName, data)
            })
          }
        }
      }

      // Register event listeners (SIEMPRE, incluso si el canal ya existe)
      Object.keys(events).forEach((eventName) => {
        // Laravel Echo requiere punto al inicio para eventos personalizados (no Pusher defaults)
        const fullEventName = eventName.startsWith('.') ? eventName : `.${eventName}`
        console.log(`👂 Registrando listener para evento: ${fullEventName} en canal: ${channelName}`)

        // Crear listener wrapper para poder removerlo después
        const listenerId = `${channelName}-${eventName}-${Date.now()}`
        const listener = (data) => {
          console.log(`📨 Evento recibido: ${fullEventName}`, data)
          console.log(`📦 Datos completos:`, JSON.stringify(data, null, 2))
          events[eventName](data)
        }

        channel.value.listen(fullEventName, listener)
        listenerIds.value.push({ eventName: fullEventName, listener, id: listenerId })
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
      // Remover solo los listeners de este composable, no todo el canal
      listenerIds.value.forEach(({ eventName, listener }) => {
        try {
          channel.value.stopListening(eventName, listener)
          console.log(`🔇 Listener removido: ${eventName}`)
        } catch (err) {
          console.warn(`⚠️ Error removiendo listener ${eventName}:`, err)
        }
      })

      listenerIds.value = []
      isConnected.value = false
      console.log(`🔌 Listeners disconnected from channel: ${channelName} (canal sigue activo)`)
    }
  }

  // Auto-disconnect on unmount (solo los listeners, no el canal completo)
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
