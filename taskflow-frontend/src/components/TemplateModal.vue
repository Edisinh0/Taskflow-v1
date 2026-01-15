<template>
  <Transition name="modal">
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
      <div class="flex min-h-screen items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/50 dark:bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>

        <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full p-8 z-10 border border-slate-200 dark:border-white/10">
          <!-- Header -->
          <div class="flex justify-between items-start mb-8">
            <h3 class="text-2xl font-bold text-slate-800 dark:text-white">
              Nueva Plantilla
            </h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Formulario -->
          <form @submit.prevent="handleSubmit">
            <!-- Nombre -->
            <div class="mb-5">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                Nombre de la Plantilla <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="formData.name"
                type="text"
                required
                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                placeholder="Ej: Plantilla de Instalación Estándar"
              />
            </div>

            <!-- Descripción -->
            <div class="mb-5">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                Descripción
              </label>
              <textarea
                v-model="formData.description"
                rows="3"
                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                placeholder="Describe el propósito de esta plantilla..."
              ></textarea>
            </div>

            <!-- Versión -->
            <div class="mb-5">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                Versión
              </label>
              <input
                v-model="formData.version"
                type="text"
                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                placeholder="1.0.0"
              />
            </div>

            <!-- Basic JSON Config Editor -->
            <div class="mb-6">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                Configuración JSON (Tareas)
              </label>
              <div class="relative">
                <textarea
                  v-model="jsonConfigString"
                  rows="8"
                  class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                  placeholder='{"tasks": [{"title": "Tarea 1", "is_milestone": false}]}'
                ></textarea>
                <div v-if="jsonError" class="absolute bottom-2 right-2 text-xs text-rose-500 bg-rose-100 dark:bg-rose-900 px-2 py-1 rounded">
                  JSON Inválido
                </div>
              </div>
              <p class="text-xs text-slate-500 mt-2">
                Define la estructura de tareas aquí. Se recomienda copiar una estructura existente.
              </p>
            </div>

            <!-- Error -->
            <div v-if="error" class="mb-6 p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-500/30 text-rose-600 dark:text-rose-400 rounded-xl text-sm flex items-start">
              <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
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
                :disabled="loading || !!jsonError"
                class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 font-bold shadow-lg shadow-blue-900/20 disabled:opacity-50 flex items-center"
              >
                <span v-if="loading" class="mr-2">
                  <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
                Crear Plantilla
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch } from 'vue'
import { templatesAPI } from '@/services/api'

defineProps({
  isOpen: Boolean
})

const emit = defineEmits(['close', 'saved'])

const loading = ref(false)
const error = ref(null)
const jsonConfigString = ref('{\n  "tasks": [\n    {\n      "title": "Hito Inicial",\n      "is_milestone": true,\n      "subtasks": [\n        {"title": "Tarea 1"}\n      ]\n    }\n  ]\n}')
const jsonError = ref(false)

const formData = ref({
  name: '',
  description: '',
  version: '1.0.0',
  config: {}
})

watch(jsonConfigString, (newVal) => {
  try {
    formData.value.config = JSON.parse(newVal)
    jsonError.value = false
  } catch {
    jsonError.value = true
  }
})

const closeModal = () => {
  error.value = null
  emit('close')
}

const handleSubmit = async () => {
  if (jsonError.value) return

  try {
    loading.value = true
    error.value = null
    await templatesAPI.create(formData.value)
    emit('saved')
    closeModal()
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al crear la plantilla'
    console.error('Error:', err)
  } finally {
    loading.value = false
  }
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
