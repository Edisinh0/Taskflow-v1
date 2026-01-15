<template>
  <div class="relative">
    <!-- Botón de campana -->
    <button
      @click="toggleDropdown"
      class="relative p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition-all"
      title="Notificaciones"
    >
      <!-- Icono de campana -->
      <BellIcon class="w-6 h-6" stroke-width="2" />
      
      <!-- Badge de cantidad -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 bg-gradient-to-r from-red-500 to-rose-500 text-white text-[10px] font-bold rounded-full h-5 w-5 flex items-center justify-center shadow-lg shadow-rose-500/30 dark:shadow-rose-900/50 animate-pulse"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown de notificaciones -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-1"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-96 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-white/10 z-50 backdrop-blur-xl"
        @click.stop
      >
        <!-- Header -->
        <div class="px-5 py-4 border-b border-slate-100 dark:border-white/5 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50 rounded-t-xl">
          <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider">
            Notificaciones
          </h3>
          <button
            v-if="unreadCount > 0"
            @click="markAllAsRead"
            class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-bold transition-colors flex items-center gap-1"
          >
            Marcar todas leídas
          </button>
        </div>

        <!-- Lista de notificaciones -->
        <div class="max-h-96 overflow-y-auto scrollbar-thin scrollbar-thumb-slate-200 dark:scrollbar-thumb-slate-700 scrollbar-track-transparent">
          <div
            v-for="notification in notifications"
            :key="notification.id"
            class="px-5 py-4 border-b border-slate-100 dark:border-white/5 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors cursor-pointer group"
            :class="{ 'bg-blue-50 dark:bg-blue-900/10': !notification.is_read }"
            @click="handleNotificationClick(notification)"
          >
            <div class="flex items-start space-x-4">
              <!-- Icono según tipo -->
              <div
                class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center shadow-sm dark:shadow-inner border"
                :class="getNotificationIconClass(notification.type)"
              >
                <component :is="getNotificationLucideIcon(notification.type)" class="w-5 h-5" stroke-width="2" />
              </div>

              <!-- Contenido -->
              <div class="flex-1 min-w-0 pt-0.5">
                <p class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                  {{ notification.title }}
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed line-clamp-2">
                  {{ notification.message }}
                </p>
                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 mt-2 uppercase tracking-wide flex items-center gap-1">
                  <Clock class="w-3 h-3" />
                  {{ formatDate(notification.created_at) }}
                </p>
              </div>

              <!-- Badge de prioridad -->
              <span
                v-if="notification.priority === 'urgent'"
                class="flex-shrink-0 px-2 py-0.5 bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20 text-[10px] font-bold rounded-full uppercase tracking-wider"
              >
                Urgente
              </span>
            </div>
          </div>

          <!-- Vacío -->
          <div
            v-if="notifications.length === 0"
            class="px-6 py-10 text-center"
          >
            <div class="bg-slate-100 dark:bg-slate-800/80 p-3 rounded-full inline-block mb-3 border border-slate-200 dark:border-white/5">
                <BellOff class="w-8 h-8 text-slate-400 dark:text-slate-600" />
            </div>
            <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">No tienes notificaciones</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-slate-800/50 rounded-b-xl hover:bg-slate-100 dark:hover:bg-slate-700/30 transition-colors text-center">
          <router-link
            to="/notifications"
            class="text-xs font-bold text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-white uppercase tracking-wider block w-full h-full"
            @click="isOpen = false"
          >
            Ver todas las notificaciones
          </router-link>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, inject } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUserNotifications } from '@/composables/useRealtime'
import axios from 'axios'
import {
  Bell as BellIcon,
  BellOff,
  Clock,
  AlertTriangle,
  AlertCircle,
  CheckCircle2,
  ClipboardList,
  Lock,
  Unlock,
  Trophy,
  Briefcase,
  RefreshCw
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()
const isOpen = ref(false)
const notifications = ref([])
const unreadCount = ref(0)

// Inject el componente de toast desde el layout principal
const toastComponent = inject('notificationToast', null)

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    loadNotifications()
  }
}

const loadNotifications = async () => {
  try {
    const token = localStorage.getItem('token')
    const apiUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api/v1'
    const response = await axios.get(`${apiUrl}/notifications`, {
      headers: { Authorization: `Bearer ${token}` },
      params: { unread: false }
    })
    
    notifications.value = (response.data?.data || []).slice(0, 10)
    unreadCount.value = response.data?.meta?.unread_count || 0
  } catch (error) {
    console.error('Error cargando notificaciones:', error)
    notifications.value = []
    unreadCount.value = 0
  }
}

const markAllAsRead = async () => {
  try {
    const token = localStorage.getItem('token')
    const apiUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api/v1'
    await axios.post(`${apiUrl}/notifications/read-all`, {}, {
      headers: { Authorization: `Bearer ${token}` }
    })
    await loadNotifications()
  } catch (error) {
    console.error('Error marcando notificaciones:', error)
  }
}

const handleNotificationClick = async (notification) => {
  try {
    // Marcar como leída
    const token = localStorage.getItem('token')
    const apiUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api/v1'
    await axios.put(`${apiUrl}/notifications/${notification.id}/read`, {}, {
      headers: { Authorization: `Bearer ${token}` }
    })

    // Navegar según el tipo
    if (notification.task_id) {
      router.push(`/flows/${notification.flow_id}`)
    } else if (notification.flow_id) {
      router.push(`/flows/${notification.flow_id}`)
    }

    isOpen.value = false
    await loadNotifications()
  } catch (error) {
    console.error('Error manejando notificación:', error)
  }
}

const getNotificationLucideIcon = (type) => {
  const icons = {
    sla_warning: AlertTriangle,
    task_overdue: AlertCircle,
    task_completed: CheckCircle2,
    task_assigned: ClipboardList,
    task_blocked: Lock,
    task_unblocked: Unlock,
    milestone_completed: Trophy,
    flow_assigned: Briefcase,
    flow_responsible_changed: RefreshCw
  }
  return icons[type] || BellIcon
}

const getNotificationIconClass = (type) => {
  const classes = {
    sla_warning: 'bg-yellow-50 dark:bg-yellow-500/10 text-yellow-600 dark:text-yellow-500 border-yellow-200 dark:border-yellow-500/20',
    task_overdue: 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-500 border-rose-200 dark:border-rose-500/20',
    task_completed: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-500 border-emerald-200 dark:border-emerald-500/20',
    task_assigned: 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-500 border-blue-200 dark:border-blue-500/20',
    task_blocked: 'bg-slate-100 dark:bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-500/20',
    task_unblocked: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-500 border-emerald-200 dark:border-emerald-500/20',
    milestone_completed: 'bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-500 border-purple-200 dark:border-purple-500/20',
    flow_assigned: 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-500 border-blue-200 dark:border-blue-500/20',
    flow_responsible_changed: 'bg-slate-100 dark:bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-500/20'
  }
  return classes[type] || 'bg-slate-50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-600/30'
}

const formatDate = (date) => {
  const d = new Date(date)
  const now = new Date()
  const diff = Math.floor((now - d) / 1000)

  if (diff < 60) return 'Ahora'
  if (diff < 3600) return `Hace ${Math.floor(diff / 60)} min`
  if (diff < 86400) return `Hace ${Math.floor(diff / 3600)} h`
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' })
}

// Handler para notificaciones en tiempo real
const handleRealtimeNotification = (data) => {
  console.log('📬 Nueva notificación en tiempo real:', data)

  // Actualizar contador
  unreadCount.value++

  // Mostrar toast si está disponible
  if (toastComponent) {
    toastComponent.addNotification(data.notification)
  }

  // Recargar lista de notificaciones si el dropdown está abierto
  if (isOpen.value) {
    loadNotifications()
  }
}

// Configurar WebSocket para notificaciones en tiempo real
let realtimeConnection = null

onMounted(() => {
  loadNotifications()

  // Conectar a WebSocket si el usuario está autenticado
  if (authStore.user?.id) {
    realtimeConnection = useUserNotifications(authStore.user.id, handleRealtimeNotification)
  }
})

onUnmounted(() => {
  if (realtimeConnection) {
    realtimeConnection.disconnect()
  }
})

// Cerrar al hacer clic fuera
const handleClickOutside = (e) => {
  if (isOpen.value && !e.target.closest('.relative')) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>