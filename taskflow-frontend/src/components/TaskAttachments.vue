<template>
  <div class="mt-4">
    <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-3 flex items-center">
      <Paperclip class="w-4 h-4 mr-2" />
      Archivos Adjuntos
    </h3>

    <!-- Lista de archivos -->
    <TransitionGroup 
      name="list" 
      tag="div" 
      class="space-y-2 mb-4"
      v-if="localAttachments.length > 0"
    >
      <div 
        v-for="file in localAttachments" 
        :key="file.id"
        class="flex items-center justify-between p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-xl group hover:border-blue-500/30 transition-all"
      >
        <div class="flex items-center min-w-0 pr-3">
          <!-- Icono según tipo -->
          <div class="mr-3 p-2 bg-slate-100 dark:bg-slate-700 rounded-lg text-slate-500 flex-shrink-0">
            <FileText v-if="isDoc(file)" class="w-5 h-5" />
            <Image v-else-if="isImage(file)" class="w-5 h-5" />
            <Paperclip v-else class="w-5 h-5" />
          </div>
          <div class="truncate min-w-0">
            <!-- Enlace de descarga (necesitará ruta completa) -->
            <a 
              :href="getFileUrl(file)" 
              target="_blank" 
              class="text-sm font-medium text-slate-700 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 truncate block transition-colors"
            >
              {{ file.name }}
            </a>
            <span class="text-xs text-slate-400 flex items-center mt-0.5">
              {{ formatSize(file.file_size) }} 
              <span class="mx-1">•</span> 
              {{ formatDate(file.created_at) }}
              <span class="mx-1">•</span>
              {{ file.uploader?.name || 'Usuario' }}
            </span>
          </div>
        </div>
        
        <button 
          @click="deleteFile(file)"
          class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg opacity-0 group-hover:opacity-100 transition-all flex-shrink-0"
          title="Eliminar archivo"
        >
          <Trash2 class="w-4 h-4" />
        </button>
      </div>
    </TransitionGroup>

    <!-- Upload Area -->
    <div 
      class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-xl p-6 text-center hover:border-blue-500 dark:hover:border-blue-500 transition-colors cursor-pointer bg-slate-50 dark:bg-slate-900/50 relative overflow-hidden group"
      @click="triggerFileInput"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="handleDrop"
      :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-900/20': isDragging }"
    >
      <input 
        ref="fileInput"
        type="file" 
        class="hidden" 
        multiple
        @change="handleFileSelect"
      />
      
      <div v-if="isUploading" class="space-y-3 relative z-10">
        <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Subiendo {{ uploadProgress }}%...</p>
      </div>
      
      <div v-else class="relative z-10">
        <UploadCloud 
          class="w-10 h-10 text-slate-400 mx-auto mb-3 group-hover:text-blue-500 transition-colors" 
          :class="{ 'text-blue-500': isDragging }"
        />
        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
          <span v-if="isDragging">¡Suelta los archivos aquí!</span>
          <span v-else>Arrastra archivos aquí o <span class="text-blue-500">haz clic para explorar</span></span>
        </p>
        <p class="text-xs text-slate-500 mt-2">PDF, Imágenes, Word (Max 10MB)</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { FileText, Image, Paperclip, Trash2, UploadCloud } from 'lucide-vue-next'
import { tasksAPI } from '@/services/api'

const props = defineProps({
  task: {
    type: Object,
    required: true
  },
  attachments: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['uploaded', 'deleted'])

const fileInput = ref(null)
const isUploading = ref(false)
const isDragging = ref(false)
const uploadProgress = ref(0)
const localAttachments = computed(() => props.attachments)

const triggerFileInput = () => {
  if (!isUploading.value) fileInput.value.click()
}

const handleFileSelect = (event) => {
  const files = event.target.files
  if (files.length > 0) processFiles(files)
}

const handleDrop = (event) => {
  isDragging.value = false
  const files = event.dataTransfer.files
  if (files.length > 0) processFiles(files)
}

const processFiles = async (files) => {
  isUploading.value = true
  
  for (let i = 0; i < files.length; i++) {
    const file = files[i]
    const formData = new FormData()
    formData.append('file', file)
    
    try {
      await tasksAPI.uploadAttachment(props.task.id, formData, (progressEvent) => {
        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total)
        uploadProgress.value = percentCompleted
      })
      emit('uploaded')
    } catch (error) {
      console.error('Error subiendo archivo:', error)
      alert(`Error subiendo ${file.name}`)
    }
  }
  
  isUploading.value = false
  uploadProgress.value = 0
  if (fileInput.value) fileInput.value.value = ''
}

const deleteFile = async (file) => {
  if (!confirm(`¿Eliminar archivo "${file.name}"?`)) return
  
  try {
    await tasksAPI.deleteAttachment(file.id)
    emit('deleted', file.id)
  } catch (error) {
    console.error('Error eliminando archivo:', error)
  }
}

// Helpers
const isImage = (file) => file.file_type?.startsWith('image/')
const isDoc = (file) => file.file_type?.includes('pdf') || file.file_type?.includes('word') || file.file_type?.includes('document')

const formatSize = (bytes) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

const formatDate = (date) => new Date(date).toLocaleString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })

const getFileUrl = (file) => {
  // Construir URL completa
  // Asumiendo que el backend sirve storage/ públicamente via symlink
  // file.file_path es 'attachments/xyz.pdf'
  // URL debe ser baseURL + '/storage/' + file.file_path (o similar)
  // Pero lo ideal es que la API devuelva la full_url si es posible.
  // Por ahora construiremos una relativa
  return `${import.meta.env.VITE_API_BASE_URL?.replace('/api/v1', '') || ''}/storage/${file.file_path}`
}
</script>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}
</style>
