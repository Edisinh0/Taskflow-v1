<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors">
    <!-- Navbar profesional -->
    <Navbar />

    <!-- Contenido -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-slate-800 dark:text-white tracking-tight">Plantillas de Flujos</h2>
        <p class="text-slate-500 dark:text-slate-400 mt-1 text-lg">Crea y gestiona plantillas reutilizables para optimizar tu trabajo</p>
      </div>

      <!-- Grid de Plantillas -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="template in templates" 
          :key="template.id"
          class="bg-white dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg hover:shadow-xl dark:hover:shadow-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all p-6 border border-slate-200 dark:border-white/5 group"
        >
          <!-- Header -->
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1 pr-4">
              <h3 class="text-xl font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors mb-1 line-wrap">
                {{ template.name }}
              </h3>
              <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-500 bg-slate-100 dark:bg-slate-900/50 px-2 py-0.5 rounded">
                Versión {{ template.version }}
              </span>
            </div>
            
            <div class="flex flex-col items-end gap-2">
              <span 
                :class="template.is_active 
                  ? 'bg-green-500/10 text-green-500 dark:text-green-400 border border-green-500/20' 
                  : 'bg-slate-100 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-600/30'"
                class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full whitespace-nowrap"
              >
                {{ template.is_active ? 'Activa' : 'Inactiva' }}
              </span>

              <button 
                @click.stop="deleteTemplate(template)"
                class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors opacity-0 group-hover:opacity-100"
                title="Eliminar plantilla"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Descripción -->
          <p class="text-slate-500 dark:text-slate-400 text-sm mb-6 line-clamp-3 h-15">
            {{ template.description || 'Sin descripción disponible.' }}
          </p>

          <!-- Información adicional -->
          <div v-if="template.config" class="space-y-3 mb-6 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-100 dark:border-white/5">
            <div v-if="template.config.estimated_duration_days" class="flex items-center text-sm">
              <svg class="w-4 h-4 mr-2.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-slate-500 dark:text-slate-400">
                Duración: <span class="font-bold text-slate-700 dark:text-slate-200">{{ template.config.estimated_duration_days }} días</span>
              </span>
            </div>
            <div v-if="template.config.required_roles" class="flex items-center text-sm">
              <svg class="w-4 h-4 mr-2.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <span class="text-slate-500 dark:text-slate-400">
                Roles: <span class="font-bold text-slate-700 dark:text-slate-200">{{ template.config.required_roles.join(', ') }}</span>
              </span>
            </div>
          </div>

          <!-- Botones de acción -->
          <div class="flex gap-2">
            <button
              @click="previewTemplate(template)"
              class="flex-1 py-3 bg-slate-100 dark:bg-slate-700/50 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-all font-bold flex items-center justify-center border border-slate-200 dark:border-white/5"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              Vista Previa
            </button>
            <button
              @click="useTemplate(template)"
              class="flex-1 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all font-bold shadow-lg shadow-blue-900/20 flex items-center justify-center hover:-translate-y-0.5"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Usar
            </button>
          </div>
        </div>

        <!-- Card vacía -->
        <div v-if="templates.length === 0" class="col-span-full flex flex-col items-center justify-center py-20 bg-white dark:bg-slate-800/30 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-700">
          <div class="bg-slate-100 dark:bg-slate-800 p-4 rounded-full mb-4">
             <svg class="w-12 h-12 text-slate-400 dark:text-slate-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
             </svg>
          </div>
          <p class="text-slate-800 dark:text-white text-xl font-bold mb-2">No hay plantillas disponibles</p>
          <p class="text-slate-500 dark:text-slate-400">Crea tu primera plantilla para estandarizar tus procesos</p>
        </div>
      </div>
    </main>

    <!-- Modal de Previsualización -->
    <Transition name="modal">
      <div v-if="isPreviewOpen" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closePreview">
        <div class="flex min-h-screen items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-900/50 dark:bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>

          <div class="relative bg-white dark:bg-slate-800 rounded-3xl shadow-2xl max-w-4xl w-full p-8 z-10 border border-slate-200 dark:border-white/10 max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex justify-between items-start mb-8">
              <div>
                <h3 class="text-2xl font-bold text-slate-800 dark:text-white flex items-center gap-3">
                  <div class="p-2 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </div>
                  Vista Previa: {{ selectedTemplatePreview?.name }}
                </h3>
                <p class="text-slate-500 dark:text-slate-400 mt-1">{{ selectedTemplatePreview?.description }}</p>
              </div>
              <button @click="closePreview" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700/50 rounded-xl transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Contenido Estructura (Milestones y Tareas) -->
            <div v-if="selectedTemplatePreview?.config?.tasks?.length > 0" class="space-y-6">
              <div 
                v-for="(milestone, index) in selectedTemplatePreview.config.tasks" 
                :key="index"
                class="bg-slate-50 dark:bg-slate-900/40 rounded-2xl p-6 border border-slate-200 dark:border-white/5"
              >
                <!-- Milestone Header -->
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-10 h-10 bg-blue-500/10 text-blue-500 rounded-xl flex items-center justify-center border border-blue-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                  </div>
                  <div>
                    <h4 class="text-lg font-bold text-slate-800 dark:text-white">{{ milestone.title }}</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ milestone.description || 'Sin descripción' }}</p>
                  </div>
                </div>

                <!-- Subtasks List -->
                <div v-if="milestone.subtasks?.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pl-13">
                  <div 
                    v-for="(subtask, sIndex) in milestone.subtasks" 
                    :key="sIndex"
                    class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-white/5 flex items-center gap-3 group/task transition-all hover:border-blue-500/30"
                  >
                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                    <div class="flex-1">
                      <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 group-hover/task:text-blue-500 transition-colors">{{ subtask.title }}</p>
                      <p v-if="subtask.priority" class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ subtask.priority }} priority</p>
                    </div>
                  </div>
                </div>
                <div v-else class="pl-13 text-sm text-slate-400 italic">
                  No hay tareas definidas para este hito.
                </div>
              </div>
            </div>
            <div v-else class="text-center py-12 bg-slate-50 dark:bg-slate-900/40 rounded-3xl border-2 border-dashed border-slate-200 dark:border-white/5">
              <p class="text-slate-500 dark:text-slate-400">Esta plantilla no tiene una estructura de tareas definida.</p>
            </div>

            <!-- Footer -->
            <div class="mt-8 flex justify-end">
              <button
                @click="useTemplate(selectedTemplatePreview)"
                class="px-8 py-3 bg-blue-600 text-white rounded-xl shadow-lg shadow-blue-500/20 font-bold hover:bg-blue-700 transition-all flex items-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Usar esta Plantilla
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Modal de Nueva Plantilla -->
    <TemplateModal
      :isOpen="isTemplateModalOpen"
      @close="isTemplateModalOpen = false"
      @saved="handleTemplateSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { templatesAPI, flowsAPI } from '@/services/api'
import Navbar from '@/components/AppNavbar.vue'
import TemplateModal from '@/components/TemplateModal.vue'

const router = useRouter()
const templates = ref([])
const flows = ref([])
const isTemplateModalOpen = ref(false)

// Preview State
const isPreviewOpen = ref(false)
const selectedTemplatePreview = ref(null)

const previewTemplate = (template) => {
  selectedTemplatePreview.value = template
  isPreviewOpen.value = true
}

const closePreview = () => {
  isPreviewOpen.value = false
  selectedTemplatePreview.value = null
}



const handleTemplateSaved = async () => {
  await loadData()
}

const getFlowCount = (templateId) => {
  return flows.value.filter(f => f.template_id === templateId).length
}

const useTemplate = (template) => {
  // Redirigir a crear flujo con esta plantilla pre-seleccionada
  router.push({
    path: '/flows',
    query: { template: template.id }
  })
}

const deleteTemplate = async (template) => {
  if (!confirm(`¿Estás seguro de eliminar la plantilla "${template.name}"?`)) return
  
  try {
    await templatesAPI.delete(template.id)
    await loadData()
  } catch (error) {
    console.error('Error eliminando plantilla:', error)
    alert('Error al eliminar la plantilla')
  }
}

const loadData = async () => {
  try {
    const [templatesResponse, flowsResponse] = await Promise.all([
      templatesAPI.getAll(),
      flowsAPI.getAll()
    ])
    templates.value = templatesResponse.data.data
    flows.value = flowsResponse.data.data
  } catch (error) {
    console.error('Error cargando plantillas:', error)
  }
}

onMounted(() => {
  loadData()
})
</script>