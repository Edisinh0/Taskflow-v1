<script setup>
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

/**
 * TaskExecutionCard.vue
 *
 * Componente 100% enfocado en la EJECUCIÓN de tareas.
 * Solo permite al usuario asignado:
 * - Ver información de la tarea
 * - Iniciar/Pausar/Completar
 * - Registrar progreso
 * - Subir archivos adjuntos
 * - Ver alertas de SLA
 *
 * NO PERMITE:
 * - Editar título/descripción
 * - Modificar dependencias
 * - Cambiar estructura del flujo
 * - Reasignar tareas
 */

const props = defineProps({
  task: {
    type: Object,
    required: true
  },
  readonly: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['taskUpdated', 'error'])

const authStore = useAuthStore()
const loading = ref(false)
const uploadingFile = ref(false)

// Estado local para optimistic UI
const localProgress = ref(props.task.progress || 0)
const localStatus = ref(props.task.status)

// Calcular si el usuario actual puede ejecutar esta tarea
const canExecute = computed(() => {
  if (props.readonly) return false
  const user = authStore.user
  return user && user.id === props.task.assignee_id
})

// Calcular si la tarea está bloqueada
const isBlocked = computed(() => {
  return props.task.is_blocked ||
         props.task.status === 'blocked' ||
         (props.task.depends_on_task_id && props.task.depends_on_task?.status !== 'completed') ||
         (props.task.depends_on_milestone_id && props.task.depends_on_milestone?.status !== 'completed')
})

// Calcular mensaje de bloqueo
const blockMessage = computed(() => {
  if (!isBlocked.value) return null

  const reasons = []
  if (props.task.depends_on_task_id && props.task.depends_on_task?.status !== 'completed') {
    reasons.push(`Tarea precedente: "${props.task.depends_on_task.title}"`)
  }
  if (props.task.depends_on_milestone_id && props.task.depends_on_milestone?.status !== 'completed') {
    reasons.push(`Milestone: "${props.task.depends_on_milestone.title}"`)
  }

  return reasons.length > 0
    ? `🔒 Bloqueada por: ${reasons.join(', ')}`
    : '🔒 Esta tarea está bloqueada'
})

// Calcular alerta de SLA
const slaStatus = computed(() => {
  if (!props.task.estimated_end_at || props.task.status === 'completed') {
    return null
  }

  const now = new Date()
  const deadline = new Date(props.task.estimated_end_at)
  const diffDays = Math.ceil((deadline - now) / (1000 * 60 * 60 * 24))

  if (diffDays < 0) {
    return {
      level: 'critical',
      message: `⚠️ Vencida hace ${Math.abs(diffDays)} día(s)`,
      class: 'bg-red-100 text-red-800 border-red-300'
    }
  } else if (diffDays === 0) {
    return {
      level: 'warning',
      message: '⏰ Vence HOY',
      class: 'bg-orange-100 text-orange-800 border-orange-300'
    }
  } else if (diffDays === 1) {
    return {
      level: 'warning',
      message: '⏰ Vence MAÑANA',
      class: 'bg-yellow-100 text-yellow-800 border-yellow-300'
    }
  } else if (diffDays <= 2) {
    return {
      level: 'info',
      message: `⏰ Vence en ${diffDays} días`,
      class: 'bg-blue-100 text-blue-800 border-blue-300'
    }
  }

  return null
})

// Verificar si se requieren adjuntos
const requiresAttachments = computed(() => {
  return props.task.allow_attachments &&
         props.task.status !== 'completed'
})

const hasAttachments = computed(() => {
  return props.task.attachments && props.task.attachments.length > 0
})

// Colores de prioridad
const priorityColors = {
  low: 'bg-gray-100 text-gray-700',
  medium: 'bg-blue-100 text-blue-700',
  high: 'bg-orange-100 text-orange-700',
  urgent: 'bg-red-100 text-red-700'
}

// Colores de estado
const statusColors = {
  pending: 'bg-gray-100 text-gray-700',
  blocked: 'bg-red-100 text-red-700',
  in_progress: 'bg-blue-100 text-blue-700',
  paused: 'bg-yellow-100 text-yellow-700',
  completed: 'bg-green-100 text-green-700',
  cancelled: 'bg-gray-400 text-gray-700'
}

const statusLabels = {
  pending: 'Pendiente',
  blocked: 'Bloqueada',
  in_progress: 'En Progreso',
  paused: 'Pausada',
  completed: 'Completada',
  cancelled: 'Cancelada'
}

/**
 * Iniciar tarea
 */
async function startTask() {
  if (isBlocked.value) {
    emit('error', blockMessage.value)
    return
  }

  await updateTaskStatus('in_progress')
}

/**
 * Pausar tarea
 */
async function pauseTask() {
  await updateTaskStatus('paused')
}

/**
 * Completar tarea
 */
async function completeTask() {
  // Verificar requisitos antes de completar
  if (requiresAttachments.value && !hasAttachments.value) {
    emit('error', '⚠️ Debes adjuntar al menos un archivo antes de completar esta tarea')
    return
  }

  if (localProgress.value < 100) {
    const confirmed = confirm('El progreso no está al 100%. ¿Deseas completar la tarea de todas formas?')
    if (!confirmed) return
  }

  await updateTaskStatus('completed')
}

/**
 * Actualizar estado de la tarea
 */
async function updateTaskStatus(newStatus) {
  if (!canExecute.value || loading.value) return

  loading.value = true
  const previousStatus = localStatus.value

  try {
    // Optimistic UI
    localStatus.value = newStatus

    const response = await axios.put(`/api/v1/tasks/${props.task.id}`, {
      status: newStatus,
      actual_start_at: newStatus === 'in_progress' && !props.task.actual_start_at
        ? new Date().toISOString()
        : undefined,
      actual_end_at: newStatus === 'completed'
        ? new Date().toISOString()
        : undefined
    })

    emit('taskUpdated', response.data.data)

  } catch (error) {
    // Revertir en caso de error
    localStatus.value = previousStatus

    const errorMsg = error.response?.data?.message || 'Error al actualizar la tarea'
    emit('error', errorMsg)

    console.error('Error updating task:', error)
  } finally {
    loading.value = false
  }
}

/**
 * Actualizar progreso
 */
async function updateProgress() {
  if (!canExecute.value || loading.value) return

  loading.value = true

  try {
    const response = await axios.put(`/api/v1/tasks/${props.task.id}`, {
      progress: localProgress.value
    })

    emit('taskUpdated', response.data.data)

  } catch (error) {
    const errorMsg = error.response?.data?.message || 'Error al actualizar el progreso'
    emit('error', errorMsg)
    console.error('Error updating progress:', error)
  } finally {
    loading.value = false
  }
}

/**
 * Subir archivo adjunto
 */
async function uploadAttachment(event) {
  const files = event.target.files
  if (!files || files.length === 0) return

  uploadingFile.value = true

  try {
    const formData = new FormData()
    formData.append('file', files[0])
    formData.append('task_id', props.task.id)

    const response = await axios.post('/api/v1/task-attachments', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    // Refrescar la tarea para mostrar el nuevo adjunto
    emit('taskUpdated', { ...props.task, attachments: [...(props.task.attachments || []), response.data.data] })

  } catch (error) {
    const errorMsg = error.response?.data?.message || 'Error al subir el archivo'
    emit('error', errorMsg)
    console.error('Error uploading attachment:', error)
  } finally {
    uploadingFile.value = false
    event.target.value = '' // Reset input
  }
}

// Sincronizar progreso con prop
watch(() => props.task.progress, (newVal) => {
  localProgress.value = newVal || 0
})

watch(() => props.task.status, (newVal) => {
  localStatus.value = newVal
})
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
    <!-- Header: Título y Badges -->
    <div class="flex items-start justify-between mb-4">
      <div class="flex-1">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
          {{ task.title }}
        </h3>

        <div class="flex gap-2 flex-wrap">
          <!-- Estado -->
          <span
            :class="statusColors[localStatus]"
            class="px-3 py-1 rounded-full text-xs font-medium"
          >
            {{ statusLabels[localStatus] }}
          </span>

          <!-- Prioridad -->
          <span
            :class="priorityColors[task.priority]"
            class="px-3 py-1 rounded-full text-xs font-medium"
          >
            {{ task.priority?.toUpperCase() }}
          </span>

          <!-- Milestone Badge -->
          <span
            v-if="task.is_milestone"
            class="px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700"
          >
            🎯 MILESTONE
          </span>
        </div>
      </div>
    </div>

    <!-- Alerta de Bloqueo -->
    <div
      v-if="isBlocked"
      class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-400"
    >
      <p class="font-medium">{{ blockMessage }}</p>
    </div>

    <!-- Alerta de SLA -->
    <div
      v-if="slaStatus"
      :class="slaStatus.class"
      class="mb-4 p-3 border-l-4 rounded"
    >
      <p class="font-medium text-sm">{{ slaStatus.message }}</p>
    </div>

    <!-- Descripción -->
    <div v-if="task.description" class="mb-4">
      <p class="text-gray-600 dark:text-gray-300 text-sm">
        {{ task.description }}
      </p>
    </div>

    <!-- Progreso -->
    <div class="mb-6">
      <div class="flex justify-between items-center mb-2">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
          Progreso
        </label>
        <span class="text-sm font-semibold text-gray-900 dark:text-white">
          {{ localProgress }}%
        </span>
      </div>

      <!-- Barra de progreso -->
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-2">
        <div
          class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-300"
          :style="{ width: `${localProgress}%` }"
        ></div>
      </div>

      <!-- Slider de progreso (solo si puede ejecutar) -->
      <input
        v-if="canExecute && localStatus === 'in_progress'"
        v-model.number="localProgress"
        type="range"
        min="0"
        max="100"
        step="5"
        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
        @change="updateProgress"
      />
    </div>

    <!-- Archivos Adjuntos -->
    <div v-if="task.allow_attachments" class="mb-6">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Archivos Adjuntos
        <span v-if="requiresAttachments" class="text-red-500">*</span>
      </label>

      <!-- Lista de adjuntos -->
      <div v-if="hasAttachments" class="space-y-2 mb-3">
        <div
          v-for="attachment in task.attachments"
          :key="attachment.id"
          class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700 rounded"
        >
          <span class="text-gray-600 dark:text-gray-300 text-sm">
            📎 {{ attachment.file_name }}
          </span>
          <a
            :href="attachment.file_url"
            target="_blank"
            class="text-blue-600 hover:text-blue-700 text-xs"
          >
            Descargar
          </a>
        </div>
      </div>

      <!-- Upload button -->
      <div v-if="canExecute && localStatus !== 'completed'">
        <label
          class="flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
        >
          <span v-if="!uploadingFile">📎 Subir Archivo</span>
          <span v-else>⏳ Subiendo...</span>
          <input
            type="file"
            class="hidden"
            @change="uploadAttachment"
            :disabled="uploadingFile"
          />
        </label>
      </div>
    </div>

    <!-- Información de Fechas -->
    <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
      <div>
        <p class="text-gray-500 dark:text-gray-400">Inicio Estimado</p>
        <p class="text-gray-900 dark:text-white font-medium">
          {{ task.estimated_start_at ? new Date(task.estimated_start_at).toLocaleDateString('es-ES') : 'N/A' }}
        </p>
      </div>
      <div>
        <p class="text-gray-500 dark:text-gray-400">Fin Estimado</p>
        <p class="text-gray-900 dark:text-white font-medium">
          {{ task.estimated_end_at ? new Date(task.estimated_end_at).toLocaleDateString('es-ES') : 'N/A' }}
        </p>
      </div>
    </div>

    <!-- Acciones -->
    <div v-if="canExecute" class="flex gap-3">
      <!-- Iniciar -->
      <button
        v-if="localStatus === 'pending' || localStatus === 'paused'"
        @click="startTask"
        :disabled="loading || isBlocked"
        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        {{ loading ? '⏳ Procesando...' : '▶️ Iniciar' }}
      </button>

      <!-- Pausar -->
      <button
        v-if="localStatus === 'in_progress'"
        @click="pauseTask"
        :disabled="loading"
        class="flex-1 px-4 py-2 bg-yellow-600 text-white rounded-lg font-medium hover:bg-yellow-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        {{ loading ? '⏳ Procesando...' : '⏸️ Pausar' }}
      </button>

      <!-- Completar -->
      <button
        v-if="localStatus === 'in_progress' || localStatus === 'paused'"
        @click="completeTask"
        :disabled="loading || (requiresAttachments && !hasAttachments)"
        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        {{ loading ? '⏳ Procesando...' : '✅ Completar' }}
      </button>
    </div>

    <!-- Mensaje de solo lectura -->
    <div
      v-else
      class="p-3 bg-gray-100 dark:bg-gray-700 rounded-lg text-center text-gray-600 dark:text-gray-400 text-sm"
    >
      {{ readonly ? '👁️ Vista de solo lectura' : '🔒 Solo el usuario asignado puede ejecutar esta tarea' }}
    </div>
  </div>
</template>
