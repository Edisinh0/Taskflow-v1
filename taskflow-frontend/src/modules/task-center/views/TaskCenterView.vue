<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import TaskExecutionCard from '../components/TaskExecutionCard.vue'
import { useTaskExecution } from '../composables/useTaskExecution'
import { useAuthStore } from '@/stores/auth'
import Navbar from '@/components/AppNavbar.vue'
import { 
  Search, 
  Filter, 
  RotateCw, 
  ListTodo, 
  Clock, 
  CheckCircle2, 
  AlertOctagon, 
  Flame, 
  Layers 
} from 'lucide-vue-next'

/**
 * TaskCenterView.vue
 *
 * Vista principal del Task Center.
 * Muestra las tareas asignadas al usuario actual
 * organizadas por estado con filtros y estadísticas.
 */

const router = useRouter()
const authStore = useAuthStore()
const {
  tasks,
  loading,
  error,
  fetchMyTasks,
  inProgressTasks,
  pendingTasks,
  completedTasks,
  blockedTasks,
  overdueTasks,
  urgentTasks
} = useTaskExecution()

// Filtros
const selectedStatus = ref('all')
const selectedFlow = ref(null)
const searchQuery = ref('')

// Estado de notificaciones
const notification = ref({
  show: false,
  type: 'success',
  message: ''
})

// Tareas filtradas
const filteredTasks = computed(() => {
  let result = tasks.value

  // Filtrar por estado
  if (selectedStatus.value !== 'all') {
    if (selectedStatus.value === 'in_progress') {
      result = inProgressTasks.value
    } else if (selectedStatus.value === 'pending') {
      result = pendingTasks.value
    } else if (selectedStatus.value === 'completed') {
      result = completedTasks.value
    } else if (selectedStatus.value === 'blocked') {
      result = blockedTasks.value
    }
  }

  // Filtrar por búsqueda
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(task =>
      task.title.toLowerCase().includes(query) ||
      task.description?.toLowerCase().includes(query)
    )
  }

  // Filtrar por flujo
  if (selectedFlow.value) {
    result = result.filter(task => task.flow_id === selectedFlow.value)
  }

  return result
})

// Estadísticas
const stats = computed(() => ({
  total: tasks.value.length,
  inProgress: inProgressTasks.value.length,
  pending: pendingTasks.value.length,
  completed: completedTasks.value.length,
  overdue: overdueTasks.value.length,
  urgent: urgentTasks.value.length
}))

// Lista de flujos únicos para filtro
const availableFlows = computed(() => {
  const flows = new Map()
  tasks.value.forEach(task => {
    if (task.flow && !flows.has(task.flow.id)) {
      flows.set(task.flow.id, task.flow)
    }
  })
  return Array.from(flows.values())
})

// Cargar tareas al montar
onMounted(async () => {
  await loadTasks()
})

async function loadTasks() {
  try {
    await fetchMyTasks()
  } catch (err) {
    showNotification('error', 'Error al cargar las tareas')
  }
}

function handleTaskUpdate(updatedTask) {
  // Actualizar tarea en la lista
  const index = tasks.value.findIndex(t => t.id === updatedTask.id)
  if (index !== -1) {
    tasks.value[index] = updatedTask
  }

  showNotification('success', 'Tarea actualizada correctamente')
}

function handleError(errorMessage) {
  showNotification('error', errorMessage)
}

function showNotification(type, message) {
  notification.value = {
    show: true,
    type,
    message
  }

  setTimeout(() => {
    notification.value.show = false
  }, 5000)
}

function clearFilters() {
  selectedStatus.value = 'all'
  selectedFlow.value = null
  searchQuery.value = ''
}

</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors">
    <Navbar />
    
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow relative z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
              Task Center
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Bienvenido, {{ authStore.user?.name }}
            </p>
          </div>

          <button
            @click="loadTasks"
            :disabled="loading"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors shadow-sm font-medium"
          >
            <RotateCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
            <span>{{ loading ? 'Cargando...' : 'Refrescar' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Notification -->
    <transition name="slide-down">
      <div
        v-if="notification.show"
        :class="[
          'fixed top-20 right-4 z-50 p-4 rounded-lg shadow-lg max-w-md',
          notification.type === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'
        ]"
      >
        <p class="font-medium">{{ notification.message }}</p>
      </div>
    </transition>

    <!-- Stats Cards -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <!-- Total -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="flex items-center gap-3 mb-2">
            <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
              <ListTodo class="w-5 h-5 text-gray-600 dark:text-gray-400" />
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total</p>
          </div>
          <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
        </div>

        <!-- En Progreso -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="flex items-center gap-3 mb-2">
            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
              <Clock class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">En Progreso</p>
          </div>
          <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ stats.inProgress }}</p>
        </div>

        <!-- Pendientes -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="flex items-center gap-3 mb-2">
            <div class="p-2 bg-gray-100 dark:bg-gray-700/50 rounded-lg">
              <Layers class="w-5 h-5 text-gray-600 dark:text-gray-400" />
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Pendientes</p>
          </div>
          <p class="text-3xl font-bold text-gray-700 dark:text-gray-200">{{ stats.pending }}</p>
        </div>

        <!-- Completadas -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="flex items-center gap-3 mb-2">
            <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
              <CheckCircle2 class="w-5 h-5 text-green-600 dark:text-green-400" />
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Completadas</p>
          </div>
          <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ stats.completed }}</p>
        </div>

        <!-- Vencidas -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="flex items-center gap-3 mb-2">
            <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
              <AlertOctagon class="w-5 h-5 text-red-600 dark:text-red-400" />
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Vencidas</p>
          </div>
          <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ stats.overdue }}</p>
        </div>

        <!-- Urgentes -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="flex items-center gap-3 mb-2">
            <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
              <Flame class="w-5 h-5 text-orange-600 dark:text-orange-400" />
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Urgentes</p>
          </div>
          <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ stats.urgent }}</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <div class="flex flex-col md:flex-row gap-4">
          <!-- Búsqueda -->
          <div class="flex-1 relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar tareas..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all shadow-sm"
            />
            <Search class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" />
          </div>

          <!-- Filtro de Estado -->
          <select
            v-model="selectedStatus"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="all">Todos los estados</option>
            <option value="in_progress">En Progreso</option>
            <option value="pending">Pendientes</option>
            <option value="completed">Completadas</option>
            <option value="blocked">Bloqueadas</option>
          </select>

          <!-- Filtro de Flujo -->
          <select
            v-model="selectedFlow"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option :value="null">Todos los flujos</option>
            <option
              v-for="flow in availableFlows"
              :key="flow.id"
              :value="flow.id"
            >
              {{ flow.name }}
            </option>
          </select>

          <!-- Limpiar Filtros -->
          <button
            @click="clearFilters"
            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
          >
            Limpiar Filtros
          </button>
        </div>
      </div>

      <!-- Tasks Grid -->
      <div v-if="loading" class="text-center py-12">
        <p class="text-gray-500 dark:text-gray-400">⏳ Cargando tareas...</p>
      </div>

      <div v-else-if="error" class="text-center py-12">
        <p class="text-red-500">❌ {{ error }}</p>
        <button
          @click="loadTasks"
          class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          Reintentar
        </button>
      </div>

      <div v-else-if="filteredTasks.length === 0" class="text-center py-12">
        <p class="text-gray-500 dark:text-gray-400 text-lg">
          📭 No tienes tareas asignadas
        </p>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <TaskExecutionCard
          v-for="task in filteredTasks"
          :key="task.id"
          :task="task"
          @taskUpdated="handleTaskUpdate"
          @error="handleError"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}

.slide-down-enter-from {
  transform: translateY(-100%);
  opacity: 0;
}

.slide-down-leave-to {
  transform: translateY(-100%);
  opacity: 0;
}
</style>
