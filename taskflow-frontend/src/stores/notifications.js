import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useNotificationsStore = defineStore('notifications', () => {
  // State
  const notifications = ref([])
  const toasts = ref([])
  const isLoading = ref(false)

  // Getters
  const unreadCount = computed(() => {
    return notifications.value.filter((n) => !n.is_read).length
  })

  const unreadNotifications = computed(() => {
    return notifications.value.filter((n) => !n.is_read)
  })

  const slaNotifications = computed(() => {
    return notifications.value.filter((n) =>
      ['sla_warning', 'sla_escalation', 'sla_escalation_notice'].includes(n.type)
    )
  })

  // Actions
  async function fetchNotifications() {
    try {
      isLoading.value = true
      const response = await api.get('/notifications')
      notifications.value = response.data.data || []
    } catch (error) {
      console.error('Error fetching notifications:', error)
    } finally {
      isLoading.value = false
    }
  }

  async function markAsRead(notificationId) {
    try {
      await api.put(`/notifications/${notificationId}/read`)
      const notification = notifications.value.find((n) => n.id === notificationId)
      if (notification) {
        notification.is_read = true
      }
    } catch (error) {
      console.error('Error marking as read:', error)
    }
  }

  async function markAllAsRead() {
    try {
      await api.post('/notifications/read-all')
      notifications.value.forEach((n) => {
        n.is_read = true
      })
    } catch (error) {
      console.error('Error marking all as read:', error)
    }
  }

  function addNotification(notification) {
    // Evitar duplicados
    const exists = notifications.value.some((n) => n.id === notification.id)
    if (!exists) {
      notifications.value.unshift(notification)
    }
  }

  function showToast(notification) {
    const toast = {
      id: notification.id || Date.now(),
      ...notification,
      timestamp: new Date()
    }

    toasts.value.push(toast)

    // Auto-remove after 5 seconds
    setTimeout(() => {
      removeToast(toast.id)
    }, 5000)

    // Play sound for urgent notifications
    if (notification.priority === 'urgent') {
      playNotificationSound()
    }
  }

  function removeToast(toastId) {
    const index = toasts.value.findIndex((t) => t.id === toastId)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  function playNotificationSound() {
    try {
      const audio = new Audio('/notification.mp3')
      audio.volume = 0.5
      audio.play().catch((e) => console.log('Cannot play sound:', e))
    } catch (error) {
      console.log('Sound error:', error)
    }
  }

  function clearAll() {
    notifications.value = []
    toasts.value = []
  }

  return {
    // State
    notifications,
    toasts,
    isLoading,
    // Getters
    unreadCount,
    unreadNotifications,
    slaNotifications,
    // Actions
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    addNotification,
    showToast,
    removeToast,
    clearAll
  }
})
