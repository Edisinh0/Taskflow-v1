<template>
  <Transition name="modal">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 dark:bg-slate-900/80 backdrop-blur-sm"
      @click.self="closeModal"
    >
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto mx-4 border border-slate-200 dark:border-white/10">
        <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-white/5 px-6 py-4 flex justify-between items-center z-10">
          <h2 class="text-2xl font-bold text-slate-800 dark:text-white">
            {{ isEditMode ? 'Editar Tarea' : 'Nueva Tarea' }}
          </h2>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
            <X class="w-6 h-6" />
          </button>
        </div>

        <!-- Formulario -->
        <div class="px-6 pb-6 pt-6">
          <form @submit.prevent="handleSubmit">
            <!-- Título -->
            <div class="mb-5">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
              Título <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="formData.title"
              type="text"
              required
              class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              placeholder="Nombre de la tarea"
            />
            </div>

            <!-- Descripción -->
            <div class="mb-5">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
              Descripción <span class="text-rose-500">*</span>
            </label>
            <textarea
              v-model="formData.description"
              required
              rows="3"
              class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              placeholder="Describe detalles, requerimientos..."
            ></textarea>
            </div>

            <!-- Notas (Opcional) -->
            <div class="mb-5">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
              Notas Adicionales <span class="text-xs text-slate-500">(Opcional)</span>
            </label>
            <textarea
              v-model="formData.notes"
              rows="2"
              class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-slate-400 dark:focus:ring-slate-500 focus:border-transparent transition-all"
              placeholder="Agrega notas, observaciones o comentarios..."
            ></textarea>
            </div>

            <!-- Grid de 2 columnas -->
            <div class="grid grid-cols-2 gap-5 mb-5">
              <!-- Responsable -->
              <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                  Responsable <span class="text-rose-500">*</span>
                </label>
                <select
                  v-model="formData.assignee_id"
                  required
                  class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                >
                  <option :value="null" disabled>Selecciona...</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }}
                  </option>
                </select>
              </div>

              <!-- Prioridad -->
              <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                  Prioridad <span class="text-rose-500">*</span>
                </label>
                <select
                  v-model="formData.priority"
                  required
                  class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                >
                  <option value="low">Baja</option>
                  <option value="medium">Media</option>
                  <option value="high">Alta</option>
                  <option value="urgent">Urgente</option>
                </select>
              </div>
            </div>

            <!-- Grid de 2 columnas -->
            <div class="grid grid-cols-2 gap-5 mb-5">
              <!-- Estado (Solo visible si NO es milestone y NO es subtarea) -->
              <div v-if="!formData.is_milestone && !formData.parent_task_id">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                  Estado <span class="text-rose-500">*</span>
                </label>
                <select
                  v-model="formData.status"
                  required
                  class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                >
                  <option value="pending">Pendiente</option>
                  <option value="in_progress">En Progreso</option>
                  <option value="completed">Completada</option>
                  <option value="paused">Pausada</option>
                  <option value="cancelled">Cancelada</option>
                </select>
              </div>

              <!-- Si es milestone, mostrar estado fijo -->
              <div v-else-if="formData.is_milestone">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                  Estado <span class="text-rose-500">*</span>
                </label>
                <div class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-400">
                  <span class="flex items-center">
                    <Zap class="w-4 h-4 mr-2 text-blue-500" />
                    En Progreso (automático)
                  </span>
                </div>
              </div>

              <!-- Si es subtarea, mostrar estado automático -->
              <div v-else-if="formData.parent_task_id">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                  Estado <span class="text-rose-500">*</span>
                </label>
                <div class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-400">
                  <span class="flex items-center">
                    <Clock class="w-4 h-4 mr-2 text-slate-500" />
                    Automático (secuencial)
                  </span>
                </div>
              </div>
            </div>

            <!-- Milestone Checkbox -->
            <div class="mb-4 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-white/5">
              <label class="flex items-center cursor-pointer group">
                <input
                  v-model="formData.is_milestone"
                  type="checkbox"
                  class="w-5 h-5 text-blue-600 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 rounded focus:ring-blue-500 focus:ring-offset-slate-100 dark:focus:ring-offset-slate-900"
                />
                <span class="ml-3 text-sm font-bold text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors flex items-center">
                  <Target class="w-4 h-4 mr-2 text-yellow-500" />
                  Esta tarea es un Milestone (Hito)
                </span>
              </label>
            </div>

            <!-- Attachments Checkbox (Permiso) -->
            <div class="mb-6 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-white/5">
              <label class="flex items-center cursor-pointer group">
                <input
                  v-model="formData.allow_attachments"
                  type="checkbox"
                  class="w-5 h-5 text-purple-600 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 rounded focus:ring-purple-500 focus:ring-offset-slate-100 dark:focus:ring-offset-slate-900"
                />
                <span class="ml-3 text-sm font-bold text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors flex items-center">
                  <Paperclip class="w-4 h-4 mr-2 text-purple-500" />
                  Permitir adjuntar archivos
                </span>
              </label>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 ml-8">
                Habilita el botón de adjuntos en la lista de tareas.
              </p>
            </div>

            <!-- Fechas estimadas -->
            <div class="grid grid-cols-2 gap-5 mb-6">
              <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                  Inicio Estimado
                  <span v-if="!isEditMode" class="text-rose-500">*</span>
                  <span v-else class="text-xs text-slate-500">(Opcional)</span>
                </label>
                <input
                  v-model="formData.estimated_start_at"
                  type="datetime-local"
                  :required="!isEditMode"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                />
              </div>
              <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                  Fin Estimado
                  <span v-if="!isEditMode" class="text-rose-500">*</span>
                  <span v-else class="text-xs text-slate-500">(Opcional)</span>
                </label>
                <input
                  v-model="formData.estimated_end_at"
                  type="datetime-local"
                  :required="!isEditMode"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                />
              </div>
            </div>

            <!-- Mensaje de error -->
            <div v-if="error" class="mb-6 p-4 bg-rose-900/20 border border-rose-500/30 text-rose-400 rounded-xl text-sm flex items-start">
              <AlertCircle class="w-5 h-5 mr-2 flex-shrink-0" />
              {{ error }}
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3 border-t border-slate-200 dark:border-white/5 pt-6">
              <button
                type="button"
                @click="closeModal"
                class="px-6 py-2.5 border border-slate-300 dark:border-slate-600/50 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white font-bold transition-all"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 font-bold shadow-lg shadow-blue-900/20 disabled:opacity-50 flex items-center"
              >
                <span v-if="loading" class="mr-2">
                  <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
                {{ isEditMode ? 'Actualizar' : 'Crear' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { tasksAPI } from '@/services/api'
import { Zap, Clock, X, Target, Paperclip, AlertCircle } from 'lucide-vue-next'

const props = defineProps({
  isOpen: Boolean,
  task: Object,
  flowId: Number,
  parentTaskId: Number,
  users: {
    type: Array,
    default: () => []
  },
  availableTasks: {
    type: Array,
    default: () => []
  },
  initialData: {
    type: Object,
    default: () => null
  }
})

const emit = defineEmits(['close', 'saved'])

const loading = ref(false)
const error = ref(null)

const isEditMode = computed(() => !!props.task)

// Filtrar tareas disponibles (excluir la tarea actual)
const filteredAvailableTasks = computed(() => {
  if (!props.availableTasks) return []
  
  // Si estamos editando, excluir la tarea actual
  if (isEditMode.value && props.task) {
    const filtered = props.availableTasks.filter(task => task.id !== props.task.id)
    return filtered
  }
  
  return props.availableTasks
})

// Filtrar solo milestones de las tareas disponibles
const availableMilestones = computed(() => {
  return filteredAvailableTasks.value.filter(task => task.is_milestone)
})

// Datos del formulario
const formData = ref({
  title: '',
  description: '',
  notes: '', // <-- Notas
  assignee_id: null,
  priority: 'medium',
  status: 'pending',
  progress: 0,
  is_milestone: false,
  allow_attachments: false,
  blocked_reason: '',
  depends_on_task_id: null,
  depends_on_milestone_id: null,
  estimated_start_at: '',
  estimated_end_at: '',
  flow_id: null,
  parent_task_id: null
})

const formatDBDate = (dateStr) => {
    if (!dateStr) return ''
    // Asegurar formato YYYY-MM-DDTHH:mm para input datetime-local
    return dateStr.replace(' ', 'T').slice(0, 16)
}

// Watch para cargar datos cuando se edita o cambia el flowId
watch([() => props.task, () => props.flowId, () => props.initialData], ([newTask, newFlowId, newInitialData]) => {
  if (newTask) {
    // Cargar datos de tarea existente (Modo Edición)
    // IMPORTANTE: Convertir status='blocked' a 'pending' si existe (legacy data)
    let taskStatus = newTask.status || 'pending'
    if (taskStatus === 'blocked') {
      taskStatus = 'pending'
    }

    formData.value = {
      title: newTask.title || '',
      description: newTask.description || '',
      notes: newTask.notes || '',
      assignee_id: newTask.assignee_id || null,
      priority: newTask.priority || 'medium',
      status: taskStatus,
      progress: newTask.progress || 0,
      is_milestone: newTask.is_milestone || false,
      allow_attachments: newTask.allow_attachments || false,
      blocked_reason: newTask.blocked_reason || '',
      depends_on_task_id: newTask.depends_on_task_id || null,
      depends_on_milestone_id: newTask.depends_on_milestone_id || null,
      estimated_start_at: formatDBDate(newTask.estimated_start_at),
      estimated_end_at: formatDBDate(newTask.estimated_end_at),
      flow_id: newTask.flow_id,
      parent_task_id: newTask.parent_task_id
    }
  } else {
    // Modo Creación
    const defaults = newInitialData || {}

    formData.value = {
      title: defaults.title || '',
      description: defaults.description || '',
      notes: defaults.notes || '',
      assignee_id: defaults.assignee_id || null,
      priority: defaults.priority || 'medium',
      // Solo establecer status si viene en defaults, sino dejar que el backend lo determine
      status: defaults.status !== undefined ? defaults.status : 'pending',
      progress: defaults.progress || 0,
      is_milestone: defaults.is_milestone !== undefined ? defaults.is_milestone : false,
      allow_attachments: defaults.allow_attachments !== undefined ? defaults.allow_attachments : false,
      blocked_reason: '',
      depends_on_task_id: defaults.depends_on_task_id || null,
      depends_on_milestone_id: defaults.depends_on_milestone_id || null,
      estimated_start_at: defaults.estimated_start_at || '',
      estimated_end_at: defaults.estimated_end_at || '',
      flow_id: newFlowId || null,
      parent_task_id: defaults.parent_task_id || props.parentTaskId || null
    }
  }
}, { immediate: true })

// Watch para cambiar estado automáticamente cuando se marca como milestone
watch(() => formData.value.is_milestone, (isMilestone) => {
  if (isMilestone) {
    formData.value.status = 'in_progress'
  }
})

const closeModal = () => {
  error.value = null
  emit('close')
}

const handleSubmit = async () => {
  try {
    loading.value = true
    error.value = null

    // Validar campos obligatorios que el HTML5 podría no atajar si son null
    // En modo edición, las fechas son opcionales
    const required = isEditMode.value
      ? ['title', 'description', 'assignee_id', 'priority']
      : ['title', 'description', 'assignee_id', 'priority', 'estimated_start_at', 'estimated_end_at']

    const missing = required.filter(k => !formData.value[k])

    if (missing.length > 0) {
        error.value = "Por favor complete todos los campos obligatorios (*)"
        loading.value = false
        return
    }

    if (isEditMode.value) {
      // Actualizar tarea existente
      // Limpiar campos vacíos para evitar errores de validación
      const dataToUpdate = { ...formData.value }

      // Convertir strings vacíos a null para campos opcionales
      if (dataToUpdate.estimated_start_at === '') dataToUpdate.estimated_start_at = null
      if (dataToUpdate.estimated_end_at === '') dataToUpdate.estimated_end_at = null
      if (dataToUpdate.blocked_reason === '') dataToUpdate.blocked_reason = null
      if (dataToUpdate.notes === '') dataToUpdate.notes = null
      if (dataToUpdate.description === '') dataToUpdate.description = null

      await tasksAPI.update(props.task.id, dataToUpdate)
    } else {
      // Crear nueva tarea
      // Preparar datos para envío - remover status si es el default para que backend lo determine
      const dataToSend = { ...formData.value }

      // Si la tarea tiene parent_task_id y status es 'pending' (default), no enviar status
      // para que el backend determine si debe ser 'in_progress' (primera subtarea)
      if (dataToSend.parent_task_id && dataToSend.status === 'pending') {
        delete dataToSend.status
      }

      await tasksAPI.create(dataToSend)
    }

    emit('saved')
    closeModal()
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al guardar la tarea'
    console.error('Error:', err)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.modal-enter-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-leave-active {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.6, 1);
}

.modal-enter-from {
  opacity: 0;
}

.modal-leave-to {
  opacity: 0;
}

.modal-enter-from > div {
  transform: scale(0.95) translateY(-20px);
  opacity: 0;
}

.modal-leave-to > div {
  transform: scale(0.95) translateY(20px);
  opacity: 0;
}

.modal-enter-active > div {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-leave-active > div {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.6, 1);
}
</style>