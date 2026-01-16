import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

let echoInstance = null

export function initializeEcho(authToken) {
  if (echoInstance) {
    return echoInstance
  }

  try {
    const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost/api/v1'
    // Broadcasting auth está bajo /api/broadcasting/auth, no /broadcasting/auth
    const broadcastingAuthEndpoint = apiBaseUrl.replace('/v1', '/broadcasting/auth')

    echoInstance = new Echo({
      broadcaster: 'pusher',
      key: import.meta.env.VITE_PUSHER_APP_KEY || 'taskflow-key',
      cluster: import.meta.env.VITE_PUSHER_CLUSTER || 'mt1',
      wsHost: import.meta.env.VITE_PUSHER_HOST || 'localhost',
      wsPort: import.meta.env.VITE_PUSHER_PORT || 6001,
      wssPort: import.meta.env.VITE_PUSHER_PORT || 6001,
      forceTLS: import.meta.env.VITE_PUSHER_SCHEME === 'https',
      encrypted: import.meta.env.VITE_PUSHER_SCHEME === 'https',
      disableStats: true,
      enabledTransports: ['ws', 'wss'],
      authEndpoint: broadcastingAuthEndpoint,
      auth: {
        headers: {
          Authorization: `Bearer ${authToken}`,
          Accept: 'application/json'
        }
      }
    })

    // Manejar errores de conexión silenciosamente
    if (echoInstance && echoInstance.connector && echoInstance.connector.pusher) {
      echoInstance.connector.pusher.connection.bind('error', (err) => {
        // Silenciosamente manejar errores de WebSocket
        // La aplicación funciona sin WebSocket, solo no habrá notificaciones en tiempo real
        console.debug('WebSocket unavailable - real-time notifications disabled', err.error?.data?.message || '')
      })

      echoInstance.connector.pusher.connection.bind('unavailable', () => {
        console.debug('WebSocket server unavailable - continuing without real-time updates')
      })

      echoInstance.connector.pusher.connection.bind('failed', () => {
        console.debug('WebSocket connection failed - app will work without real-time notifications')
      })
    }

    console.log('✅ Echo initialized', {
      wsHost: import.meta.env.VITE_PUSHER_HOST || 'localhost',
      wsPort: import.meta.env.VITE_PUSHER_PORT || 6001,
      authEndpoint: broadcastingAuthEndpoint
    })
    return echoInstance
  } catch (error) {
    console.debug('Echo initialization failed - continuing without WebSocket', error.message)
    return null
  }
}

export function getEcho() {
  return echoInstance
}

export function disconnectEcho() {
  if (echoInstance) {
    echoInstance.disconnect()
    echoInstance = null
    console.log('🔌 Echo disconnected')
  }
}

export default {
  install(app) {
    app.config.globalProperties.$echo = echoInstance
    app.provide('echo', echoInstance)
  }
}
