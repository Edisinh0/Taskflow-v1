<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors">
    <Navbar />
    
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-slate-800 dark:text-white tracking-tight">Notificaciones</h2>
        <p class="text-slate-500 dark:text-slate-400 mt-1 text-lg">Mantente al día con todas las actualizaciones de tus proyectos</p>
      </div>

      <!-- Acciones -->
      <div class="mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="bg-white dark:bg-slate-800/50 p-1 rounded-xl border border-slate-200 dark:border-white/5 backdrop-blur-sm shadow-sm md:shadow-none">
          <button
            @click="filterType = 'all'"
            :class="filterType === 'all' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
            class="px-6 py-2 rounded-lg font-bold text-sm transition-all"
          >
            Todas
          </button>
          <button
            @click="filterType = 'unread'"
            :class="filterType === 'unread' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
            class="px-6 py-2 rounded-lg font-bold text-sm transition-all"
          >
            No leídas <span v-if="unreadCount > 0" class="ml-1 px-1.5 py-0.5 bg-blue-500 text-white text-[10px] rounded-full">{{ unreadCount }}</span>
          </button>
          <button
            @click="filterType = 'sla'"
            :class="filterType === 'sla' ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-red-700 dark:hover:text-red-400'"
            class="px-6 py-2 rounded-lg font-bold text-sm transition-all"
          >
            🚨 SLA <span v-if="slaCount > 0" class="ml-1 px-1.5 py-0.5 bg-red-500 text-white text-[10px] rounded-full">{{ slaCount }}</span>
          </button>
        </div>
        
        <button
          v-if="unreadCount > 0"
          @click="markAllAsRead"
          class="px-4 py-2 bg-blue-50 dark:bg-blue-600/10 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-600/20 rounded-xl hover:bg-blue-100 dark:hover:bg-blue-600/20 font-bold text-sm transition-all flex items-center"
        >
          <CheckCheck class="w-4 h-4 mr-2" />
          Marcar todas como leídas
        </button>
      </div>

      <!-- Lista de notificaciones -->
      <div class="space-y-4">
        <div
          v-for="notification in filteredNotifications"
          :key="notification.id"
          @click="handleNotificationClick(notification)"
          class="bg-white dark:bg-slate-800/80 rounded-2xl p-5 border transition-all cursor-pointer group relative overflow-hidden"
          :class="[
            !notification.is_read 
              ? 'border-blue-200 dark:border-blue-500/30 shadow-md shadow-blue-500/5 dark:shadow-blue-900/10' 
              : 'border-slate-200 dark:border-white/5 hover:border-slate-300 dark:hover:border-slate-600'
          ]"
        >
          <!-- Indicador de no leído -->
          <div v-if="!notification.is_read" class="absolute top-4 right-4 w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>

          <div class="flex items-start space-x-5">
            <!-- Icono -->
            <div
              class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center border shadow-sm"
              :class="getNotificationIconClass(notification.type)"
            >
              <component :is="getNotificationLucideIcon(notification.type)" class="w-6 h-6" stroke-width="2" />
            </div>

            <!-- Contenido -->
            <div class="flex-1 min-w-0 pt-0.5">
              <div class="flex items-start justify-between">
                <div class="flex-1 pr-6">
                  <p class="text-lg font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                    {{ notification.title }}
                  </p>
                  <p class="text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                    {{ notification.message }}
                  </p>
                  <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mt-3 flex items-center">
                    <Clock class="w-3 h-3 mr-1" />
                    {{ formatDate(notification.created_at) }}
                  </p>
                </div>

                <!-- Badge de prioridad -->
                <span
                  v-if="notification.priority === 'urgent' || notification.priority === 'high'"
                  class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border"
                  :class="notification.priority === 'urgent' 
                    ? 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-500/20' 
                    : 'bg-orange-50 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-200 dark:border-orange-500/20'"
                >
                  {{ notification.priority === 'urgent' ? 'Urgente' : 'Alta' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Vacío -->
        <div
          v-if="filteredNotifications.length === 0"
          class="text-center py-20 bg-slate-50 dark:bg-slate-800/30 rounded-3xl border-2 border-dashed border-slate-300 dark:border-slate-700"
        >
          <div class="bg-white dark:bg-slate-800 p-4 rounded-full inline-block mb-4 shadow-sm border border-slate-200 dark:border-white/5">
            <BellOff class="w-12 h-12 text-slate-400 dark:text-slate-500" />
          </div>
          <p class="text-slate-800 dark:text-white text-xl font-bold">No tienes notificaciones</p>
          <p class="text-slate-500 dark:text-slate-400 mt-2">Estás al día con todas tus tareas</p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Navbar from '@/components/AppNavbar.vue'
import {
  CheckCheck,
  Clock,
  BellOff,
  AlertTriangle,
  AlertCircle,
  CheckCircle2,
  ClipboardList,
  Lock,
  Unlock,
  Trophy,
  Bell,
  Briefcase,
  RefreshCw
} from 'lucide-vue-next'

const router = useRouter()
const notifications = ref([])
const unreadCount = ref(0)
const filterType = ref('all')

const slaNotifications = computed(() => {
  return notifications.value.filter(n =>
    ['sla_warning', 'sla_escalation', 'sla_escalation_notice', 'sla_resolved'].includes(n.type)
  )
})

const slaCount = computed(() => {
  return slaNotifications.value.filter(n => !n.is_read).length
})

const filteredNotifications = computed(() => {
  if (filterType.value === 'unread') {
    return notifications.value.filter(n => !n.is_read)
  }
  if (filterType.value === 'sla') {
    return slaNotifications.value
  }
  return notifications.value
})

const loadNotifications = async () => {
  try {
    const token = localStorage.getItem('token')
    const apiUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api/v1'
    const response = await axios.get(`${apiUrl}/notifications`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    
    notifications.value = response.data?.data || []
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
  return icons[type] || Bell
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
  if (diff < 604800) return `Hace ${Math.floor(diff / 86400)} días`
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })
}

onMounted(() => {
  loadNotifications()
})
</script>
