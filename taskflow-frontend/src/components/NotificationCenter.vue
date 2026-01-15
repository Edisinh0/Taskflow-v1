<template>
  <div class="notification-center">
    <!-- Notification Bell Button -->
    <button
      @click.stop="togglePanel"
      type="button"
      class="relative p-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700/50 rounded-full transition-colors"
      :class="{ 'animate-pulse': unreadCount > 0 }"
    >
      <Bell :size="24" />
      <span
        v-if="unreadCount > 0"
        class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Notification Panel -->
    <transition name="slide-fade">
      <div
        v-if="showPanel"
        v-click-outside="closePanel"
        class="absolute right-0 top-12 w-96 max-h-[600px] bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-white/10 z-50 overflow-hidden"
      >
        <!-- Panel Header -->
        <div
          class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-slate-900/50"
        >
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Notificaciones</h3>
          <button
            v-if="unreadCount > 0"
            @click="markAllAsRead"
            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium transition-colors"
          >
            Marcar todas como leídas
          </button>
        </div>

        <!-- Notifications List -->
        <div class="overflow-y-auto max-h-[500px]">
          <div v-if="isLoading" class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 dark:border-blue-400"></div>
          </div>

          <div v-else-if="notifications.length === 0" class="text-center py-8 text-slate-500 dark:text-slate-400">
            <Bell :size="48" class="mx-auto mb-2 opacity-50" />
            <p>No tienes notificaciones</p>
          </div>

          <div v-else>
            <div
              v-for="notification in notifications"
              :key="notification.id"
              @click="handleNotificationClick(notification)"
              :class="[
                'px-4 py-3 border-b border-slate-100 dark:border-white/5 cursor-pointer transition-colors hover:bg-slate-50 dark:hover:bg-slate-700/30 group',
                {
                  'bg-blue-50 dark:bg-blue-500/10': !notification.is_read,
                  'border-l-4 border-rose-500': notification.priority === 'urgent'
                }
              ]"
            >
              <div class="flex items-start gap-3">
                <div class="flex-shrink-0 mt-1 text-slate-600 dark:text-slate-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                  <component :is="getNotificationIcon(notification.type)" :size="20" />
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">
                    {{ notification.title }}
                  </h4>
                  <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">
                    {{ notification.message }}
                  </p>
                  <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                    {{ formatDate(notification.created_at) }}
                  </p>
                </div>
                <div v-if="!notification.is_read" class="flex-shrink-0">
                  <div class="w-2 h-2 bg-blue-600 dark:bg-blue-400 rounded-full animate-pulse"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Toast Notifications -->
    <transition-group name="toast" tag="div" class="fixed inset-x-4 top-4 md:inset-x-auto md:right-4 z-[9999] space-y-3 pointer-events-none">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[
          'flex items-start gap-3 min-w-[320px] max-w-md p-4 bg-white dark:bg-slate-800 rounded-xl shadow-lg border-l-4 pointer-events-auto',
          {
            'border-rose-500': toast.priority === 'urgent',
            'border-amber-500': toast.priority === 'high',
            'border-blue-500': toast.priority === 'medium',
            'border-emerald-500': toast.priority === 'low'
          }
        ]"
      >
        <component :is="getNotificationIcon(toast.type)" :size="24" class="flex-shrink-0 mt-0.5 text-slate-600 dark:text-slate-400" />
        <div class="flex-1 min-w-0">
          <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">{{ toast.title }}</h4>
          <p class="text-sm text-slate-600 dark:text-slate-300">{{ toast.message }}</p>
        </div>
        <button @click="removeToast(toast.id)" class="flex-shrink-0 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
          <X :size="20" />
        </button>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import { useNotificationsStore } from '@/stores/notifications'
import { useUserNotifications } from '@/composables/useRealtime'
import { useAuthStore } from '@/stores/auth'
import {
  Bell,
  AlertTriangle,
  ArrowUp,
  Info,
  CheckCircle,
  X,
  Briefcase,
  RefreshCw
} from 'lucide-vue-next'

const notificationsStore = useNotificationsStore()
const authStore = useAuthStore()

const showPanel = ref(false)

// Computed
const notifications = computed(() => notificationsStore.notifications)
const toasts = computed(() => notificationsStore.toasts)
const unreadCount = computed(() => notificationsStore.unreadCount)
const isLoading = computed(() => notificationsStore.isLoading)

// Setup realtime notifications
const connection = ref(null)

watch(
  () => authStore.user?.id,
  (newUserId) => {
    if (connection.value) {
      connection.value.disconnect()
      connection.value = null
    }

    if (newUserId) {
      console.log('📡 Iniciando escucha de notificaciones para usuario:', newUserId)
      connection.value = useUserNotifications(newUserId, (event) => {
        console.log('📬 Nueva notificación en tiempo real recibida:', event.notification)
        notificationsStore.addNotification(event.notification)
        notificationsStore.showToast(event.notification)
      })
    }
  },
  { immediate: true }
)

onUnmounted(() => {
  if (connection.value) {
    connection.value.disconnect()
  }
})

// Methods
function togglePanel() {
  console.log('🔔 Toggle panel clicked, current state:', showPanel.value)
  showPanel.value = !showPanel.value
  console.log('🔔 New state:', showPanel.value)
}

function closePanel() {
  showPanel.value = false
}

function markAllAsRead() {
  notificationsStore.markAllAsRead()
}

async function handleNotificationClick(notification) {
  if (!notification.is_read) {
    await notificationsStore.markAsRead(notification.id)
  }

  // Navigate to task if exists
  if (notification.task_id) {
    // router.push({ name: 'task', params: { id: notification.task_id } })
    closePanel()
  }
}

function removeToast(toastId) {
  notificationsStore.removeToast(toastId)
}

function getNotificationIcon(type) {
  const icons = {
    sla_warning: AlertTriangle,
    sla_escalation: ArrowUp,
    sla_escalation_notice: Info,
    task_assigned: Bell,
    task_reassigned: RefreshCw,
    task_completed: CheckCircle,
    flow_assigned: Briefcase,
    flow_responsible_changed: RefreshCw
  }
  return icons[type] || Bell
}

function formatDate(dateString) {
  const date = new Date(dateString)
  const now = new Date()
  const diff = now - date
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 1) return 'Ahora'
  if (minutes < 60) return `Hace ${minutes}m`
  if (hours < 24) return `Hace ${hours}h`
  if (days < 7) return `Hace ${days}d`
  return date.toLocaleDateString('es-ES')
}

// Click outside directive
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value()
      }
    }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent)
  }
}

// Initialize - Load notifications on mount
onMounted(async () => {
  try {
    await notificationsStore.fetchNotifications()
  } catch (error) {
    console.error('Error loading notifications:', error)
  }
})
</script>

<style scoped>
.notification-center {
  position: relative;
}

/* Slide fade transition */
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(-10px);
  opacity: 0;
}

/* Toast transitions */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.toast-move {
  transition: transform 0.3s;
}

/* Line clamp */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
