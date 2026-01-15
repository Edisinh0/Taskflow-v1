<template>
  <Transition name="modal">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 dark:bg-slate-900/80 backdrop-blur-sm"
      @click.self="closeModal"
    >
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg mx-4 border border-slate-200 dark:border-white/10">
        <div class="border-b border-slate-200 dark:border-white/5 px-6 py-4 flex justify-between items-center">
          <h2 class="text-xl font-bold text-slate-800 dark:text-white flex items-center">
            <FileText class="w-5 h-5 mr-2 text-yellow-500" />
            Notas de Tarea
          </h2>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
            <X class="w-6 h-6" />
          </button>
        </div>

        <div class="p-6">
          <form @submit.prevent="saveNotes">
            <div class="mb-4">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                Notas / Comentarios
              </label>
              <textarea
                v-model="notes"
                rows="6"
                class="w-full px-4 py-3 bg-yellow-50 dark:bg-yellow-900/10 border border-yellow-200 dark:border-yellow-500/20 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500/50 focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all resize-none"
                placeholder="Escribe tus notas aquí..."
              ></textarea>
            </div>

            <div v-if="error" class="mb-4 p-3 bg-rose-100 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 text-sm rounded-lg">
              {{ error }}
            </div>

            <div class="flex justify-end space-x-3 pt-2">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 font-medium transition-all"
              >
                Cerrar
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl font-bold shadow-lg shadow-yellow-500/20 transition-all flex items-center disabled:opacity-50"
              >
                <Save class="w-4 h-4 mr-2" />
                {{ loading ? 'Guardando...' : 'Guardar Notas' }}
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
import { FileText, X, Save } from 'lucide-vue-next'
import { tasksAPI } from '@/services/api'

const props = defineProps({
  isOpen: Boolean,
  task: Object
})

const emit = defineEmits(['close', 'saved'])

const notes = ref('')
const loading = ref(false)
const error = ref(null)

watch(() => props.task, (newTask) => {
  if (newTask) {
    notes.value = newTask.notes || ''
  }
}, { immediate: true })

const closeModal = () => {
  emit('close')
  error.value = null
}

const saveNotes = async () => {
  if (!props.task) return
  
  try {
    loading.value = true
    error.value = null
    
    // Solo enviamos el campo notes para actualizar
    await tasksAPI.update(props.task.id, {
      notes: notes.value
    })
    
    emit('saved')
    closeModal()
  } catch (err) {
    console.error('Error saving notes:', err)
    error.value = 'Error al guardar las notas. Por favor intenta de nuevo.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
