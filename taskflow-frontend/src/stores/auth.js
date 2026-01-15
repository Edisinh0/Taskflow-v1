import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authAPI } from '@/services/api'
import { initializeEcho, disconnectEcho } from '@/services/echo'

export const useAuthStore = defineStore('auth', () => {
  // Estado
  const user = ref(null)
  const token = ref(null)
  const isLoading = ref(false)
  const error = ref(null)

  // Getters (computed)
  const isAuthenticated = computed(() => !!token.value)
  const currentUser = computed(() => user.value)

  // Cargar datos del localStorage al iniciar
  const loadFromStorage = () => {
    const storedToken = localStorage.getItem('token')
    const storedUser = localStorage.getItem('user')

    if (storedToken && storedUser) {
      token.value = storedToken
      user.value = JSON.parse(storedUser)
      // Inicializar Echo si hay token
      initializeEcho(storedToken)
    }
  }

  // Login
  const login = async (credentials) => {
    try {
      isLoading.value = true
      error.value = null

      const response = await authAPI.login(credentials)
      const { user: userData, token: authToken } = response.data

      // Guardar en el estado
      user.value = userData
      token.value = authToken

      // Guardar en localStorage
      localStorage.setItem('token', authToken)
      localStorage.setItem('user', JSON.stringify(userData))

      // Inicializar Echo con el nuevo token
      initializeEcho(authToken)

      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al iniciar sesión'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  // Logout
  const logout = async () => {
    try {
      await authAPI.logout()
    } catch (err) {
      console.error('Error al hacer logout:', err)
    } finally {
      // Desconectar Echo
      disconnectEcho()

      // Limpiar todo
      user.value = null
      token.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }

  // Obtener datos del usuario actual
  const fetchCurrentUser = async () => {
    try {
      const response = await authAPI.me()
      user.value = response.data.user
      localStorage.setItem('user', JSON.stringify(response.data.user))
    } catch (err) {
      console.error('Error al obtener usuario:', err)
      logout()
    }
  }

  return {
    // Estado
    user,
    token,
    isLoading,
    error,
    // Getters
    isAuthenticated,
    currentUser,
    // Acciones
    login,
    logout,
    fetchCurrentUser,
    loadFromStorage,
  }
})