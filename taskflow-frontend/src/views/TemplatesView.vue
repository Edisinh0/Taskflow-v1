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

            <!-- Resumen de la plantilla -->
            <div class="grid grid-cols-3 gap-4 mb-8">
              <div class="bg-gradient-to-br from-blue-50 to-blue-100/50 dark:from-blue-900/20 dark:to-blue-800/10 rounded-2xl p-5 border border-blue-200/50 dark:border-blue-500/20">
                <div class="flex items-center gap-3 mb-2">
                  <div class="p-2 bg-blue-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                  </div>
                  <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ selectedTemplatePreview?.config?.tasks?.length || 0 }}</span>
                </div>
                <p class="text-xs font-bold uppercase tracking-wider text-blue-600/70 dark:text-blue-400/70">Hitos Totales</p>
              </div>

              <div class="bg-gradient-to-br from-purple-50 to-purple-100/50 dark:from-purple-900/20 dark:to-purple-800/10 rounded-2xl p-5 border border-purple-200/50 dark:border-purple-500/20">
                <div class="flex items-center gap-3 mb-2">
                  <div class="p-2 bg-purple-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                  </div>
                  <span class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ getTotalSubtasks() }}</span>
                </div>
                <p class="text-xs font-bold uppercase tracking-wider text-purple-600/70 dark:text-purple-400/70">Tareas Totales</p>
              </div>

              <div class="bg-gradient-to-br from-emerald-50 to-emerald-100/50 dark:from-emerald-900/20 dark:to-emerald-800/10 rounded-2xl p-5 border border-emerald-200/50 dark:border-emerald-500/20">
                <div class="flex items-center gap-3 mb-2">
                  <div class="p-2 bg-emerald-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ selectedTemplatePreview?.config?.estimated_duration_days || 'N/A' }}</span>
                </div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-600/70 dark:text-emerald-400/70">{{ selectedTemplatePreview?.config?.estimated_duration_days ? 'Días Estimados' : 'Sin Duración' }}</p>
              </div>
            </div>

            <!-- Hitos del Proyecto (Estilo FlowDetailView) -->
            <div v-if="selectedTemplatePreview?.config?.tasks?.length > 0">
              <h3 class="text-2xl font-bold text-slate-800 dark:text-white flex items-center mb-6">
                <span class="bg-gradient-to-br from-blue-500/10 to-purple-500/10 dark:from-blue-500/20 dark:to-purple-500/20 text-blue-600 dark:text-blue-400 p-2.5 rounded-xl mr-3 border border-slate-200 dark:border-white/5">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                </span>
                Estructura del Flujo
              </h3>

              <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div
                  v-for="(milestone, index) in selectedTemplatePreview.config.tasks"
                  :key="index"
                  class="bg-white dark:bg-slate-800/80 rounded-3xl p-6 border border-slate-300 dark:border-white/5 shadow-md dark:shadow-xl hover:shadow-lg dark:hover:shadow-2xl hover:border-slate-400 dark:hover:border-slate-600/50 transition-all duration-300 group flex flex-col"
                >
                  <!-- Card Header -->
                  <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                      <span class="p-2.5 rounded-xl inline-flex items-center justify-center bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                      </span>
                      <div>
                        <h4 class="text-lg font-bold text-slate-800 dark:text-white leading-tight">{{ milestone.title }}</h4>
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-widest mt-1 block">
                          Hito {{ index + 1 }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <p class="text-sm text-slate-600 dark:text-slate-400 mb-6 leading-relaxed line-clamp-2 min-h-[40px] font-medium">
                    {{ milestone.description || 'Sin descripción' }}
                  </p>

                  <!-- Metadata Box -->
                  <div class="space-y-3 mb-6 bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-200 dark:border-white/5">
                    <div v-if="milestone.priority" class="flex justify-between items-center text-sm">
                      <span class="text-slate-500 font-medium">Prioridad</span>
                      <span :class="getPriorityClass(milestone.priority)" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                        {{ milestone.priority }}
                      </span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                      <span class="text-slate-500 font-medium">Tareas</span>
                      <span class="text-slate-700 dark:text-slate-200 font-semibold">
                        {{ milestone.subtasks?.length || 0 }} tareas
                      </span>
                    </div>
                  </div>

                  <!-- Lista Tareas -->
                  <div class="flex-1 overflow-visible">
                    <div v-if="milestone.subtasks && milestone.subtasks.length > 0">
                      <div class="flex items-center justify-between mb-3 px-1">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tareas Secuenciales</p>
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/80 px-2 py-0.5 rounded-md border border-slate-200 dark:border-white/5">
                          {{ milestone.subtasks.length }}
                        </span>
                      </div>
                      <div class="space-y-1">
                        <div
                          v-for="(subtask, sIndex) in milestone.subtasks"
                          :key="sIndex"
                          class="flex items-center p-2.5 rounded-xl bg-slate-50 hover:bg-white dark:bg-slate-800/30 dark:hover:bg-slate-700/40 transition-all border border-slate-200 hover:border-blue-300 dark:border-white/5 dark:hover:border-white/10 shadow-sm hover:shadow group/task mb-2"
                        >
                          <div class="mr-3 flex-shrink-0">
                            <div class="w-6 h-6 rounded-full border-2 border-slate-400 dark:border-slate-700/80 bg-white dark:bg-slate-800/50"></div>
                          </div>

                          <span class="flex-1 text-sm font-semibold transition-colors line-clamp-1 mr-2 text-slate-700 dark:text-slate-200 group-hover/task:text-blue-700 dark:group-hover/task:text-white">
                            {{ subtask.title }}
                          </span>

                          <!-- Badge de prioridad -->
                          <span v-if="subtask.priority" :class="getPriorityClass(subtask.priority, true)" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider whitespace-nowrap">
                            {{ subtask.priority }}
                          </span>
                        </div>
                      </div>
                    </div>
                    <div v-else class="text-center py-6 text-slate-400 dark:text-slate-500 text-sm italic bg-slate-50 dark:bg-slate-900/40 rounded-xl border-2 border-dashed border-slate-200 dark:border-white/5">
                      No hay tareas secuenciales aún
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-16 bg-slate-50 dark:bg-slate-900/40 rounded-3xl border-2 border-dashed border-slate-200 dark:border-white/5">
              <div class="inline-block p-4 bg-slate-100 dark:bg-slate-800 rounded-full mb-4">
                <svg class="w-12 h-12 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
              <p class="text-lg font-semibold text-slate-600 dark:text-slate-400 mb-1">Sin estructura de tareas</p>
              <p class="text-sm text-slate-500 dark:text-slate-500">Esta plantilla no tiene tareas definidas todavía</p>
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
import { useToast } from '@/composables/useToast'
import Navbar from '@/components/AppNavbar.vue'
import TemplateModal from '@/components/TemplateModal.vue'

const router = useRouter()
const { showError } = useToast()
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

const getTotalSubtasks = () => {
  if (!selectedTemplatePreview.value?.config?.tasks) return 0
  return selectedTemplatePreview.value.config.tasks.reduce((total, milestone) => {
    return total + (milestone.subtasks?.length || 0)
  }, 0)
}

const getPriorityClass = (priority, small = false) => {
  const baseClasses = small ? '' : 'border'
  const priorities = {
    urgent: `bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 ${baseClasses} border-rose-200 dark:border-rose-500/30`,
    high: `bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 ${baseClasses} border-orange-200 dark:border-orange-500/30`,
    medium: `bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 ${baseClasses} border-amber-200 dark:border-amber-500/30`,
    low: `bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 ${baseClasses} border-slate-200 dark:border-slate-600/30`
  }
  return priorities[priority] || priorities.medium
}

const handleTemplateSaved = async () => {
  await loadData()
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
    showError('Error al eliminar la plantilla')
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