<template>
  <Transition name="modal">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 dark:bg-slate-900/80 backdrop-blur-sm"
      @click.self="closeModal"
    >
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto mx-4 border border-slate-200 dark:border-white/10">
        <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-white/5 px-6 py-4 flex justify-between items-center z-10">
          <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-white flex items-center">
              <Paperclip class="w-5 h-5 mr-2 text-blue-500" />
              Archivos Adjuntos
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 truncate max-w-xs">
              {{ task?.title }}
            </p>
          </div>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
            <X class="w-6 h-6" />
          </button>
        </div>

        <div class="p-6">
          <TaskAttachments 
             v-if="task"
             :task="task" 
             :attachments="task.attachments || []"
             @uploaded="emit('updated')"
             @deleted="emit('updated')"
           />
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { Paperclip, X } from 'lucide-vue-next'
import TaskAttachments from './TaskAttachments.vue'

defineProps({
  isOpen: Boolean,
  task: Object
})

const emit = defineEmits(['close', 'updated'])

const closeModal = () => emit('close')
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
