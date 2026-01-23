import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useNotificationsStore = defineStore('notifications', () => {
  // State
  const notifications = ref([])
  const toasts = ref([])
  const slaAlerts = ref([])  // Array de alertas SLA activas
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

  const dateChangeNotifications = computed(() => {
    return notifications.value.filter((n) => n.type === 'task_date_changed')
  })

  const criticalSLAAlerts = computed(() => {
    return slaAlerts.value.filter((a) => a.alert_type === 'escalation')
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
    console.log('🔔 showToast llamado con:', notification)

    const toast = {
      id: notification.id || Date.now(),
      ...notification,
      timestamp: new Date()
    }

    toasts.value.push(toast)
    console.log('✅ Toast agregado al array. Total toasts:', toasts.value.length)

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

  function addSLAAlert(alert) {
    console.log('🚨 addSLAAlert llamado con:', alert)

    // Evitar duplicados
    const exists = slaAlerts.value.some((a) => a.task_id === alert.task_id)
    if (!exists) {
      slaAlerts.value.push(alert)

      // Si es escalación crítica, mostrar toast rojo pulsante
      if (alert.alert_type === 'escalation') {
        showToast({
          id: Date.now(),
          type: 'error',
          title: '🚨 ALERTA SLA CRÍTICA',
          message: alert.message || `La tarea tiene ${alert.days_overdue} días de atraso`,
          priority: 'urgent',
          task_id: alert.task_id,
          flow_id: alert.flow_id,
        })

        // Reproducir sonido de alerta
        playNotificationSound()
      } else if (alert.alert_type === 'warning') {
        showToast({
          id: Date.now(),
          type: 'warning',
          title: '⚠️ Alerta SLA',
          message: alert.message || `La tarea tiene ${alert.days_overdue} días de atraso`,
          priority: 'high',
          task_id: alert.task_id,
          flow_id: alert.flow_id,
        })
      }
    }
  }

  function removeSLAAlert(taskId) {
    const index = slaAlerts.value.findIndex((a) => a.task_id === taskId)
    if (index > -1) {
      slaAlerts.value.splice(index, 1)
      console.log(`✅ Alerta SLA removida para tarea ${taskId}`)
    }
  }

  function clearAll() {
    notifications.value = []
    toasts.value = []
    slaAlerts.value = []
  }

  return {
    // State
    notifications,
    toasts,
    slaAlerts,
    isLoading,
    // Getters
    unreadCount,
    unreadNotifications,
    slaNotifications,
    dateChangeNotifications,
    criticalSLAAlerts,
    // Actions
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    addNotification,
    showToast,
    removeToast,
    addSLAAlert,
    removeSLAAlert,
    clearAll
  }
})
