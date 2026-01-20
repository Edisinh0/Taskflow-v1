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
    const broadcastingAuthEndpoint = apiBaseUrl.replace('/v1', '/broadcasting/auth')

    echoInstance = new Echo({
      broadcaster: 'pusher',
      key: import.meta.env.VITE_REVERB_APP_KEY || 'akufrsblgemtbdz3a5on',
      wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
      wsPort: import.meta.env.VITE_REVERB_PORT || 80,
      wssPort: import.meta.env.VITE_REVERB_PORT || 80,
      forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
      enabledTransports: ['ws', 'wss'],
      disableStats: true,
      cluster: 'mt1',
      activityTimeout: 30000, // 30 segundos - mantener conexión viva
      pongTimeout: 10000, // 10 segundos - timeout para pong
      authEndpoint: broadcastingAuthEndpoint,
      auth: {
        headers: {
          Authorization: `Bearer ${authToken}`,
          Accept: 'application/json'
        }
      }
    })

    console.log('✅ Echo initialized for Reverb', {
      wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
      wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
      authEndpoint: broadcastingAuthEndpoint
    })

    if (echoInstance && echoInstance.connector && echoInstance.connector.pusher) {
      const connection = echoInstance.connector.pusher.connection

      connection.bind('connecting', () => {
        console.log('🔄 Connecting to Reverb...')
      })

      connection.bind('connected', () => {
        console.log('✅ Connected to Reverb successfully!')
        console.log('📡 Connection ID:', connection.socket_id)
      })

      connection.bind('error', (err) => {
        console.error('❌ WebSocket connection error:', err)
        console.error('Error type:', err?.type)
        console.error('Error data:', err?.data)
        console.error('Error code:', err?.data?.code)
        console.error('Error message:', err?.data?.message)
      })

      connection.bind('unavailable', () => {
        console.error('❌ WebSocket server unavailable')
      })

      connection.bind('failed', () => {
        console.error('❌ WebSocket connection failed')
      })

      connection.bind('disconnected', () => {
        console.warn('⚠️ Disconnected from Reverb')
        // 🔧 NUEVO: Intentar reconectar automáticamente
        setTimeout(() => {
          console.log('🔄 Attempting to reconnect...')
          if (echoInstance && echoInstance.connector && echoInstance.connector.pusher) {
            echoInstance.connector.pusher.connect()
          }
        }, 3000) // Intentar reconectar después de 3 segundos
      })

      // 🔧 NUEVO: Listener para estado de conexión
      connection.bind('state_change', (states) => {
        console.log('🔄 Connection state changed:', {
          previous: states.previous,
          current: states.current
        })
      })
    }

    return echoInstance
  } catch (error) {
    console.error('❌ Echo initialization failed:', error)
    console.error('Error details:', {
      message: error.message,
      stack: error.stack,
      error: error
    })
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
