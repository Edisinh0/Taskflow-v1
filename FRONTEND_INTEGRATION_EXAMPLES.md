# 🎨 Ejemplos de Integración Frontend - SLA y Tiempo Real

## 📦 Instalación de Dependencias

```bash
npm install --save laravel-echo socket.io-client
```

## ⚙️ Configuración de Laravel Echo

### 1. Configurar Echo (Vue.js / React)

```javascript
// src/echo.js o src/plugins/echo.js
import Echo from 'laravel-echo';
import io from 'socket.io-client';

window.io = io;

const echo = new Echo({
    broadcaster: 'socket.io',
    host: `${window.location.hostname}:6001`,
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('authToken')}`,
            Accept: 'application/json',
        },
    },
});

export default echo;
```

### 2. Inicializar en la Aplicación

#### Vue 3
```javascript
// main.js
import { createApp } from 'vue';
import App from './App.vue';
import echo from './echo';

const app = createApp(App);
app.config.globalProperties.$echo = echo;
app.mount('#app');
```

#### React
```javascript
// App.js
import { useEffect } from 'react';
import echo from './echo';

function App() {
    useEffect(() => {
        window.Echo = echo;
        return () => {
            echo.disconnect();
        };
    }, []);

    return <div>...</div>;
}
```

## 🔔 Componente de Notificaciones

### Vue 3 Example

```vue
<!-- NotificationCenter.vue -->
<template>
  <div class="notification-center">
    <button @click="togglePanel" class="notification-bell">
      <i class="bell-icon"></i>
      <span v-if="unreadCount > 0" class="badge">{{ unreadCount }}</span>
    </button>

    <div v-if="showPanel" class="notification-panel">
      <div class="panel-header">
        <h3>Notificaciones</h3>
        <button @click="markAllAsRead">Marcar todas como leídas</button>
      </div>

      <div class="notifications-list">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          :class="['notification-item', notification.priority, { unread: !notification.is_read }]"
          @click="handleNotificationClick(notification)"
        >
          <div class="notification-icon">
            <i :class="getNotificationIcon(notification.type)"></i>
          </div>
          <div class="notification-content">
            <h4>{{ notification.title }}</h4>
            <p>{{ notification.message }}</p>
            <small>{{ formatDate(notification.created_at) }}</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Notifications -->
    <transition-group name="toast" tag="div" class="toast-container">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="['toast', toast.priority]"
      >
        <i :class="getNotificationIcon(toast.type)"></i>
        <div class="toast-content">
          <h4>{{ toast.title }}</h4>
          <p>{{ toast.message }}</p>
        </div>
        <button @click="closeToast(toast.id)">×</button>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, inject } from 'vue';
import axios from 'axios';

const echo = inject('$echo');
const notifications = ref([]);
const toasts = ref([]);
const showPanel = ref(false);

const unreadCount = computed(() => {
  return notifications.value.filter(n => !n.is_read).length;
});

const togglePanel = () => {
  showPanel.value = !showPanel.value;
};

const loadNotifications = async () => {
  try {
    const response = await axios.get('/api/v1/notifications');
    notifications.value = response.data.data;
  } catch (error) {
    console.error('Error loading notifications:', error);
  }
};

const markAllAsRead = async () => {
  try {
    await axios.post('/api/v1/notifications/mark-all-read');
    notifications.value.forEach(n => n.is_read = true);
  } catch (error) {
    console.error('Error marking as read:', error);
  }
};

const handleNotificationClick = async (notification) => {
  if (!notification.is_read) {
    try {
      await axios.put(`/api/v1/notifications/${notification.id}/read`);
      notification.is_read = true;
    } catch (error) {
      console.error('Error marking as read:', error);
    }
  }

  // Navegar a la tarea si existe
  if (notification.task_id) {
    // router.push({ name: 'task', params: { id: notification.task_id } });
  }
};

const showToast = (notification) => {
  const toast = {
    id: Date.now(),
    ...notification,
  };
  toasts.value.push(toast);

  // Auto-remove after 5 seconds
  setTimeout(() => {
    closeToast(toast.id);
  }, 5000);

  // Play sound for urgent notifications
  if (notification.priority === 'urgent') {
    playNotificationSound();
  }
};

const closeToast = (id) => {
  const index = toasts.value.findIndex(t => t.id === id);
  if (index > -1) {
    toasts.value.splice(index, 1);
  }
};

const getNotificationIcon = (type) => {
  const icons = {
    'sla_warning': 'fas fa-exclamation-triangle',
    'sla_escalation': 'fas fa-arrow-up',
    'sla_escalation_notice': 'fas fa-info-circle',
    'task_assigned': 'fas fa-tasks',
    'task_completed': 'fas fa-check-circle',
  };
  return icons[type] || 'fas fa-bell';
};

const formatDate = (date) => {
  return new Date(date).toLocaleString('es-ES');
};

const playNotificationSound = () => {
  const audio = new Audio('/sounds/notification.mp3');
  audio.play().catch(e => console.log('Cannot play sound:', e));
};

onMounted(() => {
  loadNotifications();

  // Subscribe to user's notification channel
  const userId = localStorage.getItem('userId');

  echo.private(`user.${userId}`)
    .listen('.notification.sent', (event) => {
      console.log('Nueva notificación:', event.notification);

      // Add to notifications list
      notifications.value.unshift(event.notification);

      // Show toast
      showToast(event.notification);
    });
});

onUnmounted(() => {
  const userId = localStorage.getItem('userId');
  echo.leave(`user.${userId}`);
});
</script>

<style scoped>
.notification-center {
  position: relative;
}

.notification-bell {
  position: relative;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 24px;
}

.badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #ff4444;
  color: white;
  border-radius: 50%;
  padding: 2px 6px;
  font-size: 12px;
}

.notification-panel {
  position: absolute;
  top: 50px;
  right: 0;
  width: 400px;
  max-height: 600px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  overflow: hidden;
  z-index: 1000;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #eee;
}

.notifications-list {
  max-height: 500px;
  overflow-y: auto;
}

.notification-item {
  display: flex;
  padding: 12px;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  transition: background 0.2s;
}

.notification-item:hover {
  background: #f8f8f8;
}

.notification-item.unread {
  background: #f0f7ff;
}

.notification-item.urgent {
  border-left: 4px solid #ff4444;
}

.notification-icon {
  margin-right: 12px;
  font-size: 24px;
}

.notification-content h4 {
  margin: 0 0 4px 0;
  font-size: 14px;
}

.notification-content p {
  margin: 0 0 4px 0;
  font-size: 13px;
  color: #666;
}

.notification-content small {
  font-size: 11px;
  color: #999;
}

.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
}

.toast {
  display: flex;
  align-items: center;
  min-width: 300px;
  padding: 16px;
  margin-bottom: 12px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  border-left: 4px solid #2196F3;
}

.toast.urgent {
  border-left-color: #ff4444;
  animation: shake 0.5s;
}

.toast i {
  font-size: 24px;
  margin-right: 12px;
}

.toast-content {
  flex: 1;
}

.toast-content h4 {
  margin: 0 0 4px 0;
  font-size: 14px;
}

.toast-content p {
  margin: 0;
  font-size: 13px;
  color: #666;
}

.toast button {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #999;
}

.toast-enter-active, .toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.toast-leave-to {
  opacity: 0;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}
</style>
```

## 📊 Componente de Tarea con SLA

```vue
<!-- TaskCard.vue -->
<template>
  <div :class="['task-card', { 'sla-breach': task.sla_breached }]">
    <div class="task-header">
      <h3>{{ task.title }}</h3>
      <span :class="['priority-badge', task.priority]">
        {{ task.priority }}
      </span>
    </div>

    <p>{{ task.description }}</p>

    <div class="task-meta">
      <div class="assignee">
        <i class="fas fa-user"></i>
        {{ task.assignee?.name || 'No asignado' }}
      </div>

      <div v-if="task.sla_due_date" class="sla-info">
        <i class="fas fa-clock"></i>
        <span v-if="!task.sla_breached">
          Vence: {{ formatDate(task.sla_due_date) }}
        </span>
        <span v-else class="overdue">
          Retrasado: {{ task.sla_days_overdue }} días
          <i v-if="task.sla_escalated" class="fas fa-arrow-up" title="Escalado"></i>
        </span>
      </div>
    </div>

    <div class="task-progress">
      <div class="progress-bar">
        <div
          class="progress-fill"
          :style="{ width: task.progress + '%' }"
        ></div>
      </div>
      <span>{{ task.progress }}%</span>
    </div>

    <div class="task-actions">
      <button @click="updateStatus('in_progress')" v-if="task.status === 'pending'">
        Iniciar
      </button>
      <button @click="updateStatus('completed')" v-if="task.status === 'in_progress'">
        Completar
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, inject } from 'vue';
import axios from 'axios';

const props = defineProps({
  task: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['task-updated']);
const echo = inject('$echo');

const updateStatus = async (status) => {
  try {
    const response = await axios.put(`/api/v1/tasks/${props.task.id}`, { status });
    emit('task-updated', response.data.data);
  } catch (error) {
    console.error('Error updating task:', error);
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-ES');
};

onMounted(() => {
  // Subscribe to task updates
  echo.private(`task.${props.task.id}`)
    .listen('.task.updated', (event) => {
      console.log('Tarea actualizada:', event.task);
      emit('task-updated', event.task);
    })
    .listen('.sla.breached', (event) => {
      console.log('SLA breach:', event.task);
      // Show visual alert
      if (event.escalated) {
        alert(`⚠️ Tarea escalada: ${event.task.title}`);
      }
    });
});

onUnmounted(() => {
  echo.leave(`task.${props.task.id}`);
});
</script>

<style scoped>
.task-card {
  background: white;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  transition: all 0.3s;
}

.task-card.sla-breach {
  border-left: 4px solid #ff4444;
  animation: pulse 2s infinite;
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.priority-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: bold;
}

.priority-badge.urgent {
  background: #ff4444;
  color: white;
}

.priority-badge.high {
  background: #ff9800;
  color: white;
}

.priority-badge.medium {
  background: #2196F3;
  color: white;
}

.priority-badge.low {
  background: #4CAF50;
  color: white;
}

.task-meta {
  display: flex;
  justify-content: space-between;
  margin: 12px 0;
  font-size: 14px;
  color: #666;
}

.sla-info .overdue {
  color: #ff4444;
  font-weight: bold;
}

.task-progress {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 12px 0;
}

.progress-bar {
  flex: 1;
  height: 8px;
  background: #f0f0f0;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #4CAF50;
  transition: width 0.3s;
}

.task-actions {
  display: flex;
  gap: 8px;
}

.task-actions button {
  flex: 1;
  padding: 8px;
  border: none;
  border-radius: 4px;
  background: #2196F3;
  color: white;
  cursor: pointer;
  transition: background 0.2s;
}

.task-actions button:hover {
  background: #1976D2;
}

@keyframes pulse {
  0%, 100% {
    box-shadow: 0 2px 4px rgba(255, 68, 68, 0.3);
  }
  50% {
    box-shadow: 0 2px 12px rgba(255, 68, 68, 0.6);
  }
}
</style>
```

## 🔄 Composable para Tiempo Real (Vue 3)

```javascript
// composables/useRealtime.js
import { ref, onMounted, onUnmounted, inject } from 'vue';

export function useRealtime(channelName, events = {}) {
  const echo = inject('$echo');
  const isConnected = ref(false);
  const channel = ref(null);

  onMounted(() => {
    if (!echo) {
      console.error('Echo is not available');
      return;
    }

    // Subscribe to channel
    channel.value = echo.private(channelName);

    // Register event listeners
    Object.keys(events).forEach(eventName => {
      channel.value.listen(`.${eventName}`, events[eventName]);
    });

    isConnected.value = true;
    console.log(`Connected to channel: ${channelName}`);
  });

  onUnmounted(() => {
    if (channel.value) {
      echo.leave(channelName);
      isConnected.value = false;
      console.log(`Disconnected from channel: ${channelName}`);
    }
  });

  return {
    isConnected,
    channel
  };
}

// Uso en componente:
// const { isConnected } = useRealtime(`task.${taskId}`, {
//   'task.updated': (event) => {
//     console.log('Task updated:', event.task);
//   },
//   'sla.breached': (event) => {
//     showAlert(event.task);
//   }
// });
```

## 📱 React Hook para Tiempo Real

```javascript
// hooks/useRealtime.js
import { useEffect, useRef } from 'react';

export function useRealtime(channelName, events = {}) {
  const channelRef = useRef(null);

  useEffect(() => {
    if (!window.Echo) {
      console.error('Echo is not available');
      return;
    }

    // Subscribe to channel
    channelRef.current = window.Echo.private(channelName);

    // Register event listeners
    Object.keys(events).forEach(eventName => {
      channelRef.current.listen(`.${eventName}`, events[eventName]);
    });

    console.log(`Connected to channel: ${channelName}`);

    // Cleanup
    return () => {
      if (channelRef.current) {
        window.Echo.leave(channelName);
        console.log(`Disconnected from channel: ${channelName}`);
      }
    };
  }, [channelName]);

  return channelRef.current;
}

// Uso en componente:
// useRealtime(`task.${taskId}`, {
//   'task.updated': (event) => {
//     setTask(event.task);
//   },
//   'sla.breached': (event) => {
//     showAlert(event.task);
//   }
// });
```

## 🎯 Store para Notificaciones (Pinia/Vuex)

```javascript
// stores/notifications.js
import { defineStore } from 'pinia';
import axios from 'axios';

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    unreadCount: 0,
  }),

  getters: {
    unread: (state) => state.notifications.filter(n => !n.is_read),

    byType: (state) => (type) => state.notifications.filter(n => n.type === type),
  },

  actions: {
    async fetchNotifications() {
      try {
        const response = await axios.get('/api/v1/notifications');
        this.notifications = response.data.data;
        this.updateUnreadCount();
      } catch (error) {
        console.error('Error fetching notifications:', error);
      }
    },

    async markAsRead(id) {
      try {
        await axios.put(`/api/v1/notifications/${id}/read`);
        const notification = this.notifications.find(n => n.id === id);
        if (notification) {
          notification.is_read = true;
          this.updateUnreadCount();
        }
      } catch (error) {
        console.error('Error marking as read:', error);
      }
    },

    async markAllAsRead() {
      try {
        await axios.post('/api/v1/notifications/mark-all-read');
        this.notifications.forEach(n => n.is_read = true);
        this.updateUnreadCount();
      } catch (error) {
        console.error('Error marking all as read:', error);
      }
    },

    addNotification(notification) {
      this.notifications.unshift(notification);
      this.updateUnreadCount();
    },

    updateUnreadCount() {
      this.unreadCount = this.notifications.filter(n => !n.is_read).length;
    },
  },
});
```

## 🚀 Inicialización Completa

```javascript
// main.js (Vue 3)
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import echo from './echo';
import { useNotificationsStore } from './stores/notifications';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.config.globalProperties.$echo = echo;
app.provide('$echo', echo);

app.mount('#app');

// Setup global notification listener
const notificationsStore = useNotificationsStore();
const userId = localStorage.getItem('userId');

if (userId) {
  echo.private(`user.${userId}`)
    .listen('.notification.sent', (event) => {
      notificationsStore.addNotification(event.notification);
    });
}
```

## 🎨 Estilos CSS Globales

```css
/* global.css */
:root {
  --color-urgent: #ff4444;
  --color-high: #ff9800;
  --color-medium: #2196F3;
  --color-low: #4CAF50;
  --color-success: #4CAF50;
  --color-warning: #ff9800;
  --color-error: #ff4444;
}

.sla-breach {
  animation: sla-pulse 2s infinite;
}

@keyframes sla-pulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(255, 68, 68, 0.7);
  }
  50% {
    box-shadow: 0 0 0 10px rgba(255, 68, 68, 0);
  }
}

.notification-sound {
  display: none;
}
```

---

**Nota**: Estos son ejemplos base. Adapta según tu framework y necesidades específicas.
