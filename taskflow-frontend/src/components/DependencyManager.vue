<template>
  <Transition name="modal">
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
      <div class="flex min-h-screen items-center justify-center p-4">
        <!-- Fondo oscuro -->
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>

        <!-- Contenido del Modal -->
        <div class="relative bg-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full p-8 z-10 border border-white/10">
          <!-- Header -->
          <div class="flex justify-between items-start mb-8">
            <div>
              <h3 class="text-2xl font-bold text-white">
                🔗 Gestionar Dependencias
              </h3>
              <p class="text-sm text-slate-400 mt-1 font-medium">
                {{ task?.title }}
              </p>
            </div>
            <button
              @click="closeModal"
              class="text-slate-400 hover:text-white transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Estado Actual -->
          <div v-if="task?.is_blocked" class="mb-6 p-4 bg-rose-900/20 border border-rose-500/20 rounded-xl">
            <div class="flex items-start">
              <span class="text-2xl mr-3 filter drop-shadow">🔒</span>
              <div>
                <p class="font-bold text-rose-400">Esta tarea está bloqueada</p>
                <p class="text-sm text-rose-300/80 mt-1">
                  No se puede iniciar o completar hasta que se cumplan sus dependencias.
                </p>
              </div>
            </div>
          </div>

          <!-- Formulario -->
          <form @submit.prevent="handleSubmit">
            <!-- Tarea Precedente -->
            <div class="mb-6">
              <label class="block text-sm font-bold text-slate-300 mb-2">
                📋 Tarea Precedente
              </label>
              <p class="text-xs text-slate-500 mb-2">
                Esta tarea no puede iniciarse hasta que se complete la tarea seleccionada
              </p>
              <select
                v-model="formData.depends_on_task_id"
                class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              >
                <option :value="null">Sin dependencia de tarea</option>
                <option
                  v-for="availableTask in availableTasks"
                  :key="availableTask.id"
                  :value="availableTask.id"
                  :disabled="availableTask.id === task?.id"
                >
                  {{ availableTask.title }}
                  <span v-if="availableTask.status === 'completed'">✅</span>
                  <span v-else-if="availableTask.is_blocked">🔒</span>
                </option>
              </select>
              
              <!-- Info de la tarea seleccionada -->
              <div v-if="selectedPrecedentTask" class="mt-3 p-3 bg-slate-900/50 rounded-lg border border-white/5">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-slate-200">{{ selectedPrecedentTask.title }}</span>
                  <span
                    :class="getStatusBadgeClass(selectedPrecedentTask.status)"
                    class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border border-current/20"
                  >
                    {{ getStatusText(selectedPrecedentTask.status) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Milestone Requerido -->
            <div class="mb-6">
              <label class="block text-sm font-bold text-slate-300 mb-2">
                ⭐ Milestone Requerido
              </label>
              <p class="text-xs text-slate-500 mb-2">
                Esta tarea no puede iniciarse hasta que se complete el milestone seleccionado
              </p>
              <select
                v-model="formData.depends_on_milestone_id"
                class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              >
                <option :value="null">Sin dependencia de milestone</option>
                <option
                  v-for="milestone in availableMilestones"
                  :key="milestone.id"
                  :value="milestone.id"
                  :disabled="milestone.id === task?.id"
                >
                  {{ milestone.title }}
                  <span v-if="milestone.status === 'completed'">✅</span>
                  <span v-else-if="milestone.is_blocked">🔒</span>
                </option>
              </select>

              <!-- Info del milestone seleccionado -->
              <div v-if="selectedMilestone" class="mt-3 p-3 bg-yellow-900/10 rounded-lg border border-yellow-500/20">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-yellow-100">⭐ {{ selectedMilestone.title }}</span>
                  <span
                    :class="getStatusBadgeClass(selectedMilestone.status)"
                    class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border border-current/20"
                  >
                    {{ getStatusText(selectedMilestone.status) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Advertencia de validación -->
            <div v-if="validationError" class="mb-6 p-4 bg-rose-900/20 border border-rose-500/30 text-rose-400 rounded-xl text-sm font-medium">
              ⚠️ {{ validationError }}
            </div>

            <!-- Mensaje de error -->
            <div v-if="error" class="mb-6 p-4 bg-rose-900/20 border border-rose-500/30 text-rose-400 rounded-xl text-sm">
              {{ error }}
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3 border-t border-white/5 pt-6">
              <button
                type="button"
                @click="closeModal"
                class="px-6 py-2.5 border border-slate-600/50 rounded-xl text-slate-300 hover:bg-slate-700 hover:text-white font-bold transition-all"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="loading || !!validationError"
                class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 font-bold shadow-lg shadow-blue-900/20 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
              >
                {{ loading ? 'Guardando...' : 'Guardar Dependencias' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { tasksAPI } from '@/services/api'

console.log('✅ DependencyManager.vue cargado')

const props = defineProps({
  isOpen: Boolean,
  task: Object,
  availableTasks: {
    type: Array,
    default: () => []
  }
})

onMounted(() => {
  console.log('🎬 DependencyManager montado')
})

const emit = defineEmits(['close', 'updated'])

const loading = ref(false)
const error = ref(null)

const formData = ref({
  depends_on_task_id: null,
  depends_on_milestone_id: null
})

// Tareas disponibles (excluyendo la tarea actual y milestones)
const availableTasks = computed(() => {
  return props.availableTasks.filter(t => 
    t.id !== props.task?.id && !t.is_milestone
  )
})

// Milestones disponibles
const availableMilestones = computed(() => {
  return props.availableTasks.filter(t => 
    t.id !== props.task?.id && t.is_milestone
  )
})

// Tarea precedente seleccionada
const selectedPrecedentTask = computed(() => {
  if (!formData.value.depends_on_task_id) return null
  return props.availableTasks.find(t => t.id === formData.value.depends_on_task_id)
})

// Milestone seleccionado
const selectedMilestone = computed(() => {
  if (!formData.value.depends_on_milestone_id) return null
  return props.availableTasks.find(t => t.id === formData.value.depends_on_milestone_id)
})

// Validación de dependencias circulares
const validationError = computed(() => {
  // No puede depender de la misma tarea como precedente y milestone
  if (formData.value.depends_on_task_id && 
      formData.value.depends_on_milestone_id &&
      formData.value.depends_on_task_id === formData.value.depends_on_milestone_id) {
    return 'No puedes seleccionar la misma tarea como precedente y milestone'
  }
  return null
})

// Watch para cargar datos cuando se abre el modal
watch(() => props.isOpen, (isOpen) => {
  console.log('🔍 DependencyManager - Modal abierto:', isOpen)
  if (isOpen && props.task) {
    console.log('📋 Cargando tarea:', props.task)
    formData.value = {
      depends_on_task_id: props.task.depends_on_task_id || null,
      depends_on_milestone_id: props.task.depends_on_milestone_id || null
    }
    console.log('📝 FormData inicial:', formData.value)
    error.value = null
  }
})

const closeModal = () => {
  console.log('❌ Cerrando modal')
  emit('close')
}

const handleSubmit = async () => {
  console.log('🚀 handleSubmit ejecutándose')
  console.log('📊 FormData:', formData.value)
  console.log('⚠️ ValidationError:', validationError.value)
  
  if (validationError.value) {
    console.log('❌ Validación falló, abortando')
    return
  }

  loading.value = true
  error.value = null

  try {
    // Siempre enviar ambos campos para permitir limpiar dependencias
    const payload = {
      depends_on_task_id: formData.value.depends_on_task_id,
      depends_on_milestone_id: formData.value.depends_on_milestone_id
    }

    console.log('Enviando dependencias:', payload)
    
    const response = await tasksAPI.update(props.task.id, payload)
    console.log('Respuesta:', response.data)

    emit('updated')
    closeModal()
  } catch (err) {
    console.error('Error al actualizar dependencias:', err)
    console.error('Response:', err.response?.data)
    error.value = err.response?.data?.message || 'Error al actualizar las dependencias'
  } finally {
    loading.value = false
  }
}

const getStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-slate-700/50 text-slate-400 border-slate-600/30',
    blocked: 'bg-rose-500/10 text-rose-400 border-rose-500/20',
    in_progress: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    paused: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
    completed: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
    cancelled: 'bg-red-500/10 text-red-400 border-red-500/20'
  }
  return classes[status] || 'bg-slate-700/50 text-slate-400'
}

const getStatusText = (status) => {
  const texts = {
    pending: 'Pendiente',
    blocked: 'Bloqueada',
    in_progress: 'En Progreso',
    paused: 'Pausada',
    completed: 'Completada',
    cancelled: 'Cancelada'
  }
  return texts[status] || status
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>