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
            Avances - {{ task?.title }}
          </h2>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
            <X class="w-6 h-6" />
          </button>
        </div>

        <!-- Contenido -->
        <div class="px-6 pb-6 pt-6">
          <!-- Formulario para agregar nuevo avance -->
          <form @submit.prevent="handleAddProgress" class="mb-8 p-5 bg-slate-50 dark:bg-slate-900/30 rounded-xl border border-slate-200 dark:border-white/5">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4 flex items-center">
              <TrendingUp class="w-5 h-5 mr-2 text-cyan-500" />
              Nuevo Avance
            </h3>

            <!-- Descripción -->
            <div class="mb-4">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                Descripción del Avance <span class="text-rose-500">*</span>
              </label>
              <textarea
                v-model="formData.description"
                rows="4"
                required
                placeholder="Describe los detalles del avance realizado..."
                class="w-full px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
              />
            </div>

            <!-- Adjuntos -->
            <div class="mb-4">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                Adjuntar Documentos
              </label>
              <div class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-xl p-4 text-center hover:bg-slate-100 dark:hover:bg-slate-900/50 transition-colors cursor-pointer">
                <input
                  type="file"
                  multiple
                  @change="handleFileSelect"
                  class="hidden"
                  ref="fileInput"
                  accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip"
                />
                <button
                  type="button"
                  @click="$refs.fileInput?.click()"
                  class="text-slate-600 dark:text-slate-400 text-sm font-medium"
                >
                  <Upload class="w-5 h-5 mx-auto mb-1" />
                  Haz clic o arrastra archivos aquí
                </button>
              </div>
              
              <!-- Archivos seleccionados -->
              <div v-if="formData.files.length > 0" class="mt-2 space-y-1">
                <p class="text-xs font-bold text-slate-500 uppercase">Archivos seleccionados:</p>
                <div v-for="(file, index) in formData.files" :key="index" class="flex items-center justify-between text-xs bg-cyan-50 dark:bg-cyan-900/10 p-2 rounded border border-cyan-200 dark:border-cyan-500/20">
                  <span class="text-cyan-700 dark:text-cyan-400">{{ file.name }}</span>
                  <button type="button" @click="removeFile(index)" class="text-cyan-600 hover:text-cyan-800 dark:text-cyan-500 dark:hover:text-cyan-300">
                    ✕
                  </button>
                </div>
              </div>
            </div>

            <!-- Errores -->
            <div v-if="error" class="mb-4 p-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-500/30 text-rose-600 dark:text-rose-400 rounded-xl text-sm flex items-start">
              <AlertCircle class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" />
              {{ error }}
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-200 dark:border-white/5">
              <button
                type="button"
                @click="resetForm"
                class="px-4 py-2 border border-slate-300 dark:border-slate-600/50 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 font-bold transition-all"
              >
                Limpiar
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-4 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 text-white rounded-xl hover:from-cyan-700 hover:to-blue-700 font-bold shadow-lg shadow-cyan-900/20 disabled:opacity-50 flex items-center"
              >
                <span v-if="loading" class="mr-2">
                  <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
                {{ loading ? 'Guardando...' : 'Agregar Avance' }}
              </button>
            </div>
          </form>

          <!-- Lista de Avances -->
          <div v-if="progressList.length > 0" class="space-y-3">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">Historial de Avances</h3>

            <div v-for="progress in progressList" :key="progress.id" class="p-4 bg-slate-50 dark:bg-slate-900/30 rounded-xl border border-slate-200 dark:border-white/5 hover:shadow-md transition-shadow">
              <div class="flex justify-between items-start mb-3">
                <div class="flex-1">
                  <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">
                    {{ progress.created_by?.name || 'Usuario' }} • {{ formatDateTime(progress.created_at) }}
                  </p>
                </div>
              </div>

              <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                {{ progress.description }}
              </p>

              <!-- Adjuntos -->
              <div v-if="progress.attachments && progress.attachments.length > 0" class="mt-3 pt-3 border-t border-slate-200 dark:border-white/5">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Documentos adjuntos:</p>
                <div class="flex flex-wrap gap-2">
                  <a
                    v-for="attachment in progress.attachments"
                    :key="attachment.id"
                    :href="`/storage/${attachment.file_path}`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-xs px-3 py-2 bg-cyan-100 dark:bg-cyan-900/20 text-cyan-700 dark:text-cyan-400 rounded border border-cyan-200 dark:border-cyan-500/30 hover:bg-cyan-200 dark:hover:bg-cyan-900/40 transition-colors flex items-center"
                  >
                    📎 {{ attachment.name }}
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Sin avances -->
          <div v-else class="text-center py-8 text-slate-500 dark:text-slate-400">
            <TrendingUp class="w-8 h-8 mx-auto mb-2 opacity-50" />
            <p class="text-sm">No hay avances registrados aún</p>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch } from 'vue'
import { X, TrendingUp, Upload, AlertCircle } from 'lucide-vue-next'
import { default as api } from '@/services/api'

const props = defineProps({
  isOpen: Boolean,
  task: Object
})

const emit = defineEmits(['close', 'saved'])

const loading = ref(false)
const error = ref(null)
const progressList = ref([])

const formData = ref({
  description: '',
  files: []
})

watch(() => props.isOpen, (newVal) => {
  if (newVal && props.task) {
    loadProgressList()
  }
})

const formatDateTime = (dateTime) => {
  return new Date(dateTime).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const handleFileSelect = (event) => {
  const files = event.target.files
  if (files) {
    formData.value.files = [...formData.value.files, ...Array.from(files)]
  }
}

const removeFile = (index) => {
  formData.value.files.splice(index, 1)
}

const resetForm = () => {
  formData.value = {
    description: '',
    files: []
  }
  error.value = null
}

const loadProgressList = async () => {
  try {
    if (!props.task?.id) return
    const response = await api.get(`/tasks/${props.task.id}/progress`)
    progressList.value = response.data
  } catch (err) {
    console.error('Error loading progress:', err)
    progressList.value = []
  }
}

const handleAddProgress = async () => {
  try {
    loading.value = true
    error.value = null

    if (!formData.value.description.trim()) {
      error.value = 'La descripción del avance es requerida'
      return
    }

    // Crear FormData para enviar archivos
    const progressFormData = new FormData()
    progressFormData.append('task_id', props.task.id)
    progressFormData.append('description', formData.value.description)

    // Agregar archivos
    if (formData.value.files && formData.value.files.length > 0) {
      formData.value.files.forEach((file) => {
        progressFormData.append('files[]', file)
      })
    }

    // Enviar FormData - Axios establece Content-Type automáticamente con boundary
    const response = await api.post('/progress', progressFormData)

    progressList.value.unshift(response.data)
    resetForm()
    emit('saved')
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al guardar el avance'
    console.error('Error:', err)
  } finally {
    loading.value = false
  }
}

const closeModal = () => {
  error.value = null
  emit('close')
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
