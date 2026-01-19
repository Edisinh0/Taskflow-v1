<template>
  <Teleport to="body">
    <TransitionGroup
      tag="div"
      name="notification"
      class="fixed top-4 right-4 z-[9999] space-y-3 max-w-md pointer-events-none"
    >
      <div
        v-for="notification in visibleNotifications"
        :key="notification.id"
        class="pointer-events-auto bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-white/10 overflow-hidden backdrop-blur-xl animate-slide-in-right"
        @click="handleClick(notification)"
      >
        <div class="flex items-start p-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
          <!-- Icono -->
          <div
            class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center shadow-sm"
            :class="getNotificationIconClass(notification.type)"
          >
            <component :is="getNotificationIcon(notification.type)" class="w-5 h-5" stroke-width="2" />
          </div>

          <!-- Contenido -->
          <div class="flex-1 ml-3 min-w-0">
            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 leading-tight">
              {{ notification.title }}
            </p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed line-clamp-2">
              {{ notification.message }}
            </p>
          </div>

          <!-- Botón cerrar -->
          <button
            @click.stop="removeNotification(notification.id)"
            class="flex-shrink-0 ml-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Barra de progreso -->
        <div class="h-1 bg-slate-100 dark:bg-slate-700">
          <div
            class="h-full transition-all duration-100 ease-linear"
            :class="getPriorityBarClass(notification.priority)"
            :style="{ width: `${notification.progress || 100}%` }"
          ></div>
        </div>
      </div>
    </TransitionGroup>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  X,
  AlertTriangle,
  AlertCircle,
  CheckCircle2,
  ClipboardList,
  Lock,
  Unlock,
  Trophy,
  Briefcase,
  RefreshCw,
  Bell
} from 'lucide-vue-next'

const router = useRouter()
const notifications = ref([])

const visibleNotifications = computed(() => notifications.value)

const addNotification = (notification) => {
  console.log('🎯 NotificationToast.addNotification llamado con:', notification)

  const id = notification.id || Date.now() + Math.random()
  const newNotification = {
    ...notification,
    id,
    progress: 100
  }

  notifications.value.push(newNotification)
  console.log('✅ Notificación agregada. Total visible:', notifications.value.length)

  // Auto-dismiss después de 5 segundos
  const duration = 5000
  const interval = 50
  const decrement = (interval / duration) * 100

  const timer = setInterval(() => {
    const notif = notifications.value.find(n => n.id === id)
    if (!notif) {
      clearInterval(timer)
      return
    }

    notif.progress -= decrement

    if (notif.progress <= 0) {
      clearInterval(timer)
      removeNotification(id)
    }
  }, interval)
}

const removeNotification = (id) => {
  const index = notifications.value.findIndex(n => n.id === id)
  if (index > -1) {
    notifications.value.splice(index, 1)
  }
}

const handleClick = (notification) => {
  // Navegar a la tarea o flujo
  if (notification.task_id && notification.flow_id) {
    router.push(`/flows/${notification.flow_id}`)
  } else if (notification.flow_id) {
    router.push(`/flows/${notification.flow_id}`)
  }
  removeNotification(notification.id)
}

const getNotificationIcon = (type) => {
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
    sla_warning: 'bg-yellow-50 dark:bg-yellow-500/10 text-yellow-600 dark:text-yellow-500 border border-yellow-200 dark:border-yellow-500/20',
    task_overdue: 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-500 border border-rose-200 dark:border-rose-500/20',
    task_completed: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-500 border border-emerald-200 dark:border-emerald-500/20',
    task_assigned: 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-500 border border-blue-200 dark:border-blue-500/20',
    task_blocked: 'bg-slate-100 dark:bg-slate-500/10 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-500/20',
    task_unblocked: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-500 border border-emerald-200 dark:border-emerald-500/20',
    milestone_completed: 'bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-500 border border-purple-200 dark:border-purple-500/20',
    flow_assigned: 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-500 border border-blue-200 dark:border-blue-500/20',
    flow_responsible_changed: 'bg-slate-100 dark:bg-slate-500/10 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-500/20'
  }
  return classes[type] || 'bg-slate-50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-600/30'
}

const getPriorityBarClass = (priority) => {
  const classes = {
    low: 'bg-slate-400 dark:bg-slate-500',
    medium: 'bg-blue-500 dark:bg-blue-600',
    high: 'bg-orange-500 dark:bg-orange-600',
    urgent: 'bg-rose-500 dark:bg-rose-600'
  }
  return classes[priority] || 'bg-blue-500'
}

// Exponer método para que otros componentes puedan agregar notificaciones
defineExpose({
  addNotification
})
</script>

<style scoped>
.notification-enter-active,
.notification-leave-active {
  transition: all 0.3s ease;
}

.notification-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.notification-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.notification-move {
  transition: transform 0.3s ease;
}

@keyframes slide-in-right {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.animate-slide-in-right {
  animation: slide-in-right 0.3s ease-out;
}
</style>
