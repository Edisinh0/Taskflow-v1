<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors">
    <!-- Navbar profesional -->
    <Navbar />

    <!-- Contenido -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header y Acciones Principales -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <h2 class="text-3xl font-bold text-slate-800 dark:text-white tracking-tight">Proyectos</h2>
          <p class="text-slate-500 dark:text-slate-400 mt-1 text-lg">Gestiona tus proyectos y flujos de trabajo</p>
        </div>
        <button
          v-if="canManageFlows"
          @click="openNewFlowModal"
          class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 font-bold flex items-center justify-center shadow-lg shadow-blue-500/20 dark:shadow-blue-900/20 transition-all hover:-translate-y-0.5"
        >
          <Plus class="w-5 h-5 mr-2" />
          Nuevo Proyecto
        </button>
      </div>

      <!-- Barra de Herramientas (Filtros y Vistas) -->
      <div class="bg-white dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-200 dark:border-white/5 shadow-sm mb-8 flex flex-col md:flex-row gap-4 items-center justify-between backdrop-blur-sm">
        <!-- Buscador -->
        <div class="relative w-full md:w-96 group">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar proyectos..."
            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
          />
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto overflow-x-auto pb-2 md:pb-0">
          <!-- Filtro de Estado -->
          <div class="flex items-center space-x-2 bg-slate-50 dark:bg-slate-900/50 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
            <button
              v-for="status in statusOptions"
              :key="status.value"
              @click="filterStatus = status.value"
              class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all whitespace-nowrap"
              :class="filterStatus === status.value ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
            >
              {{ status.label }}
            </button>
          </div>

          <!-- Separador -->
          <div class="w-px h-8 bg-slate-200 dark:bg-slate-700 hidden md:block"></div>

          <!-- Toggle de Vista -->
          <div class="flex bg-slate-50 dark:bg-slate-900/50 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
            <button
              @click="viewMode = 'grid'"
              class="p-2 rounded-lg transition-all"
              :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
              title="Vista en cuadrícula"
            >
              <LayoutGrid class="w-5 h-5" />
            </button>
            <button
              @click="viewMode = 'list'"
              class="p-2 rounded-lg transition-all"
              :class="viewMode === 'list' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
              title="Vista en lista"
            >
              <List class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>

      <!-- VISTA GRID -->
      <div v-if="viewMode === 'grid' && filteredFlows.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-in fade-in duration-500">
        <div 
          v-for="flow in filteredFlows" 
          :key="flow.id" 
          class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg hover:shadow-xl dark:hover:shadow-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all p-6 border border-slate-200 dark:border-white/5 group relative overflow-hidden flex flex-col h-full"
        >
          <!-- Background accent -->
          <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 dark:bg-blue-500/10 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none group-hover:bg-blue-500/10 dark:group-hover:bg-blue-500/20 transition-all"></div>

          <div class="flex items-start justify-between mb-4 relative z-10">
            <h3 class="text-xl font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-1 pr-4">
              {{ flow.name }}
            </h3>
            <span :class="getStatusClass(flow.status)" class="px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-full whitespace-nowrap border border-current/20">
              {{ getStatusText(flow.status) }}
            </span>
          </div>
          
          <p class="text-slate-500 dark:text-slate-400 text-sm mb-4 line-clamp-2 h-10 flex-grow">
            {{ flow.description || 'Sin descripción' }}
          </p>

          <!-- Responsable (Avatar e Info) -->
          <div class="flex items-center space-x-3 mb-6 p-3 bg-slate-50/50 dark:bg-slate-900/30 rounded-xl border border-slate-100 dark:border-white/5 transition-colors group-hover:bg-white dark:group-hover:bg-slate-800">
            <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-[10px] font-bold text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 shadow-sm">
              {{ getInitials(flow.responsible?.name) }}
            </div>
            <div class="min-w-0">
              <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Responsable</p>
              <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate">
                {{ flow.responsible?.name || 'Sin asignar' }}
              </p>
            </div>
          </div>
          
          <!-- Estadísticas -->
          <div class="flex items-center justify-between mb-3 text-sm mt-auto">
            <div class="flex items-center text-slate-500 font-medium font-mono text-xs uppercase tracking-wide">
              <ListChecks class="w-4 h-4 mr-1.5" />
              <span>{{ flow.tasks?.length || 0 }} tareas</span>
            </div>
            <div class="text-slate-500 dark:text-slate-300 font-bold text-xs uppercase tracking-wide">
              <span class="text-blue-600 dark:text-blue-400 text-sm mr-1">{{ flow.progress || 0 }}%</span> completado
            </div>
          </div>

          <!-- Barra de progreso -->
          <div class="w-full bg-slate-100 dark:bg-slate-700/50 rounded-full h-1.5 mb-6 overflow-hidden">
            <div 
              class="bg-blue-500 h-1.5 rounded-full transition-all duration-1000 ease-out shadow-[0_0_10px_rgba(59,130,246,0.3)]"
              :style="`width: ${flow.progress || 0}%`"
              :class="{ 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]': (flow.progress || 0) === 100 }"
            ></div>
          </div>

          <!-- Botones -->
          <div class="flex space-x-3 pt-5 border-t border-slate-100 dark:border-white/5 relative z-10">
            <router-link
              :to="`/flows/${flow.id}`"
              class="flex-1 text-center bg-blue-50 dark:bg-blue-600/10 text-blue-600 dark:text-blue-400 py-2.5 rounded-xl hover:bg-blue-600 hover:text-white hover:shadow-lg hover:shadow-blue-500/20 transition-all duration-200 font-bold text-sm border border-blue-100 dark:border-blue-600/20 uppercase tracking-wide flex items-center justify-center gap-2 hover:scale-105 active:scale-95"
            >
              Ver Detalles
            </router-link>
            <button
              v-if="canManageFlows"
              @click.prevent="openEditFlowModal(flow)"
              class="px-3 py-2.5 bg-slate-100 dark:bg-slate-700/30 text-slate-500 dark:text-slate-400 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white transition-all duration-200 border border-slate-200 dark:border-white/5 hover:scale-110 active:scale-95"
              title="Editar proyecto"
            >
              <Edit2 class="w-5 h-5" />
            </button>
            <button
              v-if="canManageFlows"
              @click.prevent="deleteFlow(flow)"
              class="px-3 py-2.5 bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 rounded-xl hover:bg-rose-500 hover:text-white hover:shadow-lg hover:shadow-rose-500/20 transition-all duration-200 border border-rose-100 dark:border-rose-500/20 hover:scale-110 active:scale-95"
              title="Eliminar proyecto"
            >
              <Trash2 class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>

      <!-- VISTA LISTA -->
      <div v-else-if="viewMode === 'list' && filteredFlows.length > 0" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-white/5 overflow-hidden shadow-sm animate-in fade-in duration-500">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-white/5">
                <th class="px-6 py-4 font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-xs">Proyecto</th>
                <th class="px-6 py-4 font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-xs w-48">Responsable</th>
                <th class="px-6 py-4 font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-xs w-48">Estado</th>
                <th class="px-6 py-4 font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-xs w-64">Progreso</th>
                <th class="px-6 py-4 font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-xs w-32 text-center">Tareas</th>
                <th v-if="canManageFlows" class="px-6 py-4 font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-xs w-40 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
              <tr 
                v-for="flow in filteredFlows" 
                :key="flow.id" 
                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors group cursor-pointer"
                @click="router.push(`/flows/${flow.id}`)"
              >
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-800 dark:text-white text-base group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                    {{ flow.name }}
                  </div>
                  <div class="text-slate-500 dark:text-slate-400 text-xs mt-1 line-clamp-1">
                    {{ flow.description || 'Sin descripción' }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-[10px] font-bold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-white/10 shrink-0">
                      {{ getInitials(flow.responsible?.name) }}
                    </div>
                    <span class="text-slate-700 dark:text-slate-300 font-medium truncate max-w-[120px]">
                      {{ flow.responsible?.name || 'Sin asignar' }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span :class="getStatusClass(flow.status)" class="px-2.5 py-1 text-xs font-bold uppercase tracking-wider rounded-full border border-current/20">
                    {{ getStatusText(flow.status) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="flex-1 bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                      <div 
                        class="bg-blue-500 h-1.5 rounded-full"
                        :style="`width: ${flow.progress || 0}%`"
                        :class="{ 'bg-emerald-500': (flow.progress || 0) === 100 }"
                      ></div>
                    </div>
                    <span class="font-bold text-xs text-slate-600 dark:text-slate-300 w-12 text-right">
                      {{ flow.progress || 0 }}%
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 text-center">
                  <div class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono text-xs font-bold border border-slate-200 dark:border-white/10">
                    {{ flow.tasks?.length || 0 }}
                  </div>
                </td>
                <td v-if="canManageFlows" class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-2" @click.stop>
                     <button
                      @click="openEditFlowModal(flow)"
                      class="p-2 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all"
                      title="Editar"
                    >
                      <Edit2 class="w-4 h-4" />
                    </button>
                    <button
                      @click="deleteFlow(flow)"
                      class="p-2 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-all"
                      title="Eliminar"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Estado Vacío (si no hay flujos o no coincide la búsqueda) -->
      <div v-else class="col-span-full flex flex-col items-center justify-center py-20 bg-white dark:bg-slate-800/30 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-700/50 backdrop-blur-sm animate-in zoom-in-95 duration-300">
        <div class="bg-slate-50 dark:bg-slate-800 p-6 rounded-full mb-6 border border-slate-100 dark:border-white/5 shadow-xl">
            <LayoutGrid v-if="flows.length > 0" class="w-16 h-16 text-slate-400 dark:text-slate-600" />
            <FolderPen v-else class="w-16 h-16 text-slate-400 dark:text-slate-600" />
        </div>
        <p class="text-slate-800 dark:text-white text-2xl font-bold mb-3">
          {{ flows.length === 0 ? 'No hay proyectos creados' : 'No se encontraron proyectos' }}
        </p>
        <p class="text-slate-500 dark:text-slate-400 max-w-md text-center">
          {{ flows.length === 0 ? 'Crea tu primer proyecto para comenzar a organizar tus tareas.' : 'Intenta ajustar tus filtros o términos de búsqueda.' }}
        </p>
        <button
          v-if="flows.length === 0 && canManageFlows"
          @click="openNewFlowModal"
          class="mt-8 px-8 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-bold transition-all shadow-lg hover:shadow-blue-600/20 flex items-center"
        >
          <Plus class="w-5 h-5 mr-2" />
          Crear Primer Proyecto
        </button>
        <button
          v-else
          @click="clearFilters"
          class="mt-8 px-6 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 font-bold transition-all"
        >
          Limpiar Filtros
        </button>
      </div>
    </main>

    <!-- Modal de Flujo -->
    <FlowModal
      :is-open="showFlowModal"
      :flow="selectedFlow"
      :templates="templates"
      @close="closeFlowModal"
      @saved="handleFlowSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { flowsAPI, templatesAPI } from '@/services/api'
import FlowModal from '@/components/FlowModal.vue'
import Navbar from '@/components/AppNavbar.vue'
import { 
  Plus, 
  Search, 
  LayoutGrid, 
  List, 
  ListChecks, 
  Edit2, 
  Trash2, 
  FolderPen, 
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()
const { showError } = useToast()
const flows = ref([])
const templates = ref([])
const showFlowModal = ref(false)
const selectedFlow = ref(null)

// Permisos
const canManageFlows = computed(() => {
  const role = authStore.user?.role
  return ['admin', 'project_manager', 'pm'].includes(role)
})

// Filtros y Vistas
const searchQuery = ref('')
const filterStatus = ref('all')
const viewMode = ref('grid') // 'grid' | 'list'

const statusOptions = [
  { label: 'Todos', value: 'all' },
  { label: 'Activos', value: 'active' },
  { label: 'En Progreso', value: 'in_progress' }, // A veces se usa 'active' o 'in_progress', depende del backend. Asumiré que backend usa 'active' según el código anterior, pero agregaré 'completed'
  { label: 'Completados', value: 'completed' },
  { label: 'Pausados', value: 'paused' }
]

const openNewFlowModal = () => {
  selectedFlow.value = null
  showFlowModal.value = true
}

const openEditFlowModal = (flow) => {
  selectedFlow.value = flow
  showFlowModal.value = true
}

const closeFlowModal = () => {
  showFlowModal.value = false
  selectedFlow.value = null
}

const handleFlowSaved = async () => {
  await loadData()
}

const deleteFlow = async (flow) => {
  if (!confirm(`¿Estás seguro de eliminar el proyecto "${flow.name}"? Esto eliminará todas las tareas asociadas.`)) {
    return
  }

  try {
    const token = localStorage.getItem('token')
    await fetch(`${import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api/v1"}/flows/${flow.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    })

    await loadData()
  } catch (error) {
    console.error('Error eliminando proyecto:', error)
    showError('Error al eliminar el proyecto')
  }
}

const clearFilters = () => {
  searchQuery.value = ''
  filterStatus.value = 'all'
}

const filteredFlows = computed(() => {
  return flows.value.filter(flow => {
    // Filtrar por texto
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase()
      const matchesName = flow.name.toLowerCase().includes(query)
      const matchesDesc = flow.description?.toLowerCase().includes(query)
      if (!matchesName && !matchesDesc) return false
    }

    // Filtrar por estado
    if (filterStatus.value !== 'all') {
      if (filterStatus.value === 'active' && flow.status === 'active') return true
      if (filterStatus.value === 'completed' && flow.status === 'completed') return true
      if (filterStatus.value === 'paused' && flow.status === 'paused') return true
      // Si el status exacto no coincide
      return flow.status === filterStatus.value
    }

    return true
  })
})

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20',
    paused: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 border-amber-200 dark:border-amber-500/20',
    completed: 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400 border-blue-200 dark:border-blue-500/20',
    cancelled: 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border-rose-200 dark:border-rose-500/20'
  }
  return classes[status] || 'bg-slate-100 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-600/30'
}

const getStatusText = (status) => {
  const texts = {
    active: 'Activo',
    paused: 'Pausado',
    completed: 'Completado',
    cancelled: 'Cancelado'
  }
  return texts[status] || status
}

const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const loadData = async () => {
  try {
    const [flowsResponse, templatesResponse] = await Promise.all([
      flowsAPI.getAll(),
      templatesAPI.getAll()
    ])
    flows.value = flowsResponse.data.data
    templates.value = templatesResponse.data.data
  } catch (error) {
    console.error('Error cargando datos:', error)
  }
}

onMounted(() => {
  loadData()
})
</script>