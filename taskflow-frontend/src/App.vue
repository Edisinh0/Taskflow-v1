<template>
  <RouterView />
  <NotificationToast ref="notificationToastRef" />
</template>

<script setup>
import { RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useThemeStore } from '@/stores/theme'
import { useNotificationsStore } from '@/stores/notifications'
import { onMounted, watch, ref } from 'vue'
import NotificationToast from '@/components/NotificationToast.vue'

const authStore = useAuthStore()
const themeStore = useThemeStore()
const notificationsStore = useNotificationsStore()
const notificationToastRef = ref(null)

// Cargar el usuario del localStorage al iniciar la app
onMounted(() => {
  authStore.loadFromStorage()
  themeStore.loadTheme() // Cargar tema
})

// Observar cambios en el array de toasts del store y mostrarlos
watch(
  () => notificationsStore.toasts.length,
  (newLength, oldLength) => {
    console.log('📊 Toast count changed:', { oldLength, newLength })

    // Solo mostrar si se agregó un nuevo toast
    if (newLength > oldLength && notificationToastRef.value) {
      const latestToast = notificationsStore.toasts[newLength - 1]
      console.log('🎨 Mostrando toast:', latestToast)
      notificationToastRef.value.addNotification(latestToast)
    }
  }
)
</script>

<style>
/* Asegurar que no haya estilos por defecto */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}
</style>