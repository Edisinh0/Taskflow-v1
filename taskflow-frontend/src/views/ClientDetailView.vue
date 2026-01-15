<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors pb-12">
    <Navbar />

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-screen">
      <div class="flex flex-col items-center">
        <Loader2 class="w-12 h-12 text-blue-600 animate-spin mb-4" />
        <p class="text-slate-400 text-lg">Cargando ficha del cliente...</p>
      </div>
    </div>

    <main v-else-if="client" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header del Cliente -->
      <div class="rounded-3xl p-8 mb-8 border border-slate-200 dark:border-white/5 bg-white/80 dark:bg-slate-800/40 backdrop-blur-xl shadow-lg relative overflow-hidden">
        <!-- Background Glow -->
        <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row justify-between items-start relative z-10">
          <div class="flex items-start space-x-4">
            <!-- Avatar -->
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-3xl shadow-lg shadow-blue-500/30">
              {{ getInitials(client.name) }}
            </div>

            <div>
              <div class="flex items-center gap-3 mb-2">
                <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                  {{ client.name }}
                </h2>
                <span
                  :class="client.status === 'active'
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
                    : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'"
                  class="px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wider"
                >
                  {{ client.status === 'active' ? 'Activo' : 'Inactivo' }}
                </span>
              </div>

              <div class="flex flex-wrap gap-4 text-sm text-slate-500 dark:text-slate-400">
                <div v-if="client.email" class="flex items-center gap-1.5">
                  <Mail class="w-4 h-4" />
                  {{ client.email }}
                </div>
                <div v-if="client.phone" class="flex items-center gap-1.5">
                  <Phone class="w-4 h-4" />
                  {{ client.phone }}
                </div>
                <div v-if="client.industry" class="flex items-center gap-1.5">
                  <Building2 class="w-4 h-4" />
                  {{ client.industry }}
                </div>
              </div>

              <div v-if="client.address" class="mt-2 flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400">
                <MapPin class="w-4 h-4" />
                {{ client.address }}
              </div>

              <div v-if="client.sweetcrm_id" class="mt-3">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-mono bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 rounded-lg border border-purple-200 dark:border-purple-500/20">
                  <Database class="w-3.5 h-3.5" />
                  SweetCRM: {{ client.sweetcrm_id }}
                </span>
              </div>
            </div>
          </div>

          <!-- Botones de Acción -->
          <div class="flex items-center gap-2 mt-4 md:mt-0">
            <button
              @click="openEditModal"
              class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors flex items-center gap-2"
            >
              <Edit2 class="w-4 h-4" />
              Editar
            </button>
            <router-link
              to="/clients"
              class="px-4 py-2 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors flex items-center gap-2"
            >
              <ArrowLeft class="w-4 h-4" />
              Volver
            </router-link>
          </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
          <div class="bg-slate-50 dark:bg-slate-900/50 p-5 rounded-2xl border border-slate-200 dark:border-white/5">
            <p class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-2">Proyectos Totales</p>
            <p class="text-3xl font-bold text-slate-800 dark:text-white">{{ stats.total_flows }}</p>
          </div>
          <div class="bg-slate-50 dark:bg-slate-900/50 p-5 rounded-2xl border border-slate-200 dark:border-white/5">
            <p class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-2">Proyectos Activos</p>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ stats.active_flows }}</p>
          </div>
          <div class="bg-slate-50 dark:bg-slate-900/50 p-5 rounded-2xl border border-slate-200 dark:border-white/5">
            <p class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-2">Completados</p>
            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.completed_flows }}</p>
          </div>
          <div class="bg-slate-50 dark:bg-slate-900/50 p-5 rounded-2xl border border-slate-200 dark:border-white/5">
            <p class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-2">% Completado</p>
            <div class="flex items-end gap-2">
              <p class="text-3xl font-bold text-slate-800 dark:text-white">{{ stats.completion_rate }}%</p>
              <p class="text-sm text-slate-500 mb-1">{{ stats.completed_tasks }}/{{ stats.total_tasks }} tareas</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs de Contenido -->
      <div class="mb-6">
        <div class="flex gap-1 bg-slate-100 dark:bg-slate-800/50 p-1 rounded-xl w-fit border border-slate-200 dark:border-slate-700">
          <button
            @click="activeTab = 'flows'"
            :class="activeTab === 'flows' ? 'bg-white dark:bg-slate-700 shadow-sm' : 'hover:bg-slate-200 dark:hover:bg-slate-700'"
            class="px-5 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2"
          >
            <FolderKanban class="w-4 h-4" />
            Proyectos
          </button>
          <button
            @click="activeTab = 'documents'"
            :class="activeTab === 'documents' ? 'bg-white dark:bg-slate-700 shadow-sm' : 'hover:bg-slate-200 dark:hover:bg-slate-700'"
            class="px-5 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2"
          >
            <FileText class="w-4 h-4" />
            Documentos
          </button>
          <button
            @click="activeTab = 'notes'"
            :class="activeTab === 'notes' ? 'bg-white dark:bg-slate-700 shadow-sm' : 'hover:bg-slate-200 dark:hover:bg-slate-700'"
            class="px-5 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2"
          >
            <StickyNote class="w-4 h-4" />
            Notas
          </button>
        </div>
      </div>

      <!-- Contenido de Tabs -->
      <!-- Tab: Proyectos -->
      <div v-if="activeTab === 'flows'" class="space-y-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
            <FolderKanban class="w-5 h-5 text-blue-500" />
            Historial de Proyectos
          </h3>
          <router-link
            :to="{ name: 'flows', query: { client_id: client.id } }"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-colors flex items-center gap-2"
          >
            <Plus class="w-4 h-4" />
            Nuevo Proyecto
          </router-link>
        </div>

        <!-- Lista de Flujos -->
        <div v-if="client.flows && client.flows.length > 0" class="space-y-3">
          <router-link
            v-for="flow in client.flows"
            :key="flow.id"
            :to="`/flows/${flow.id}`"
            class="block bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-white/5 hover:border-blue-500/30 hover:shadow-md transition-all group"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <h4 class="font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                    {{ flow.name }}
                  </h4>
                  <span
                    :class="getFlowStatusClass(flow.status)"
                    class="px-2.5 py-0.5 text-xs font-bold rounded-full uppercase tracking-wider"
                  >
                    {{ getFlowStatusText(flow.status) }}
                  </span>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-1">
                  {{ flow.description || 'Sin descripción' }}
                </p>
                <div class="flex items-center gap-4 mt-3 text-xs text-slate-500">
                  <span class="flex items-center gap-1">
                    <Calendar class="w-3.5 h-3.5" />
                    {{ formatDate(flow.created_at) }}
                  </span>
                  <span v-if="flow.creator" class="flex items-center gap-1">
                    <User class="w-3.5 h-3.5" />
                    {{ flow.creator.name }}
                  </span>
                  <span class="flex items-center gap-1">
                    <CheckCircle2 class="w-3.5 h-3.5" />
                    {{ flow.tasks?.filter(t => t.status === 'completed').length || 0 }}/{{ flow.tasks?.length || 0 }} tareas
                  </span>
                </div>
              </div>
              <div class="flex items-center">
                <!-- Barra de progreso mini -->
                <div class="w-24 mr-4">
                  <div class="flex justify-between text-xs mb-1">
                    <span class="text-slate-500">Progreso</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ flow.progress || 0 }}%</span>
                  </div>
                  <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5">
                    <div
                      class="h-1.5 rounded-full transition-all"
                      :class="flow.progress === 100 ? 'bg-emerald-500' : 'bg-blue-500'"
                      :style="`width: ${flow.progress || 0}%`"
                    ></div>
                  </div>
                </div>
                <ChevronRight class="w-5 h-5 text-slate-400 group-hover:text-blue-500 transition-colors" />
              </div>
            </div>
          </router-link>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12 bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-white/5 border-dashed">
          <FolderKanban class="w-12 h-12 mx-auto text-slate-400 mb-4" />
          <h3 class="text-lg font-medium text-slate-700 dark:text-white">No hay proyectos aún</h3>
          <p class="text-slate-500 mt-1">Este cliente no tiene proyectos asignados.</p>
        </div>
      </div>

      <!-- Tab: Documentos -->
      <div v-else-if="activeTab === 'documents'" class="space-y-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
            <FileText class="w-5 h-5 text-blue-500" />
            Documentos del Cliente
          </h3>
          <button
            @click="openUploadModal"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-colors flex items-center gap-2"
          >
            <Upload class="w-4 h-4" />
            Subir Documento
          </button>
        </div>

        <!-- Lista de Documentos (placeholder) -->
        <div class="text-center py-12 bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-white/5 border-dashed">
          <FileText class="w-12 h-12 mx-auto text-slate-400 mb-4" />
          <h3 class="text-lg font-medium text-slate-700 dark:text-white">Documentos del cliente</h3>
          <p class="text-slate-500 mt-1">Contratos, planos base, documentación técnica.</p>
          <p class="text-xs text-slate-400 mt-2">(Funcionalidad próximamente)</p>
        </div>
      </div>

      <!-- Tab: Notas -->
      <div v-else-if="activeTab === 'notes'" class="space-y-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
            <StickyNote class="w-5 h-5 text-yellow-500" />
            Notas del Cliente
          </h3>
          <button
            class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium rounded-xl transition-colors flex items-center gap-2"
          >
            <Plus class="w-4 h-4" />
            Nueva Nota
          </button>
        </div>

        <!-- Notas (placeholder) -->
        <div class="text-center py-12 bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-white/5 border-dashed">
          <StickyNote class="w-12 h-12 mx-auto text-slate-400 mb-4" />
          <h3 class="text-lg font-medium text-slate-700 dark:text-white">Notas internas</h3>
          <p class="text-slate-500 mt-1">Apuntes y recordatorios sobre este cliente.</p>
          <p class="text-xs text-slate-400 mt-2">(Funcionalidad próximamente)</p>
        </div>
      </div>
    </main>

    <!-- Error State -->
    <div v-else class="flex flex-col items-center justify-center h-screen">
      <AlertCircle class="w-16 h-16 text-rose-500 mb-4" />
      <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Cliente no encontrado</h2>
      <p class="text-slate-500 mt-2">El cliente que buscas no existe o fue eliminado.</p>
      <router-link
        to="/clients"
        class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors"
      >
        Volver a Clientes
      </router-link>
    </div>

    <!-- Modal de Edición -->
    <ClientModal
      :is-open="showEditModal"
      :client="client"
      :loading="processing"
      @close="showEditModal = false"
      @save="handleSaveClient"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Navbar from '@/components/AppNavbar.vue'
import ClientModal from '@/components/ClientModal.vue'
import ClientService from '@/services/ClientService'
import Swal from 'sweetalert2'
import {
  Loader2, Mail, Phone, Building2, MapPin, Database, Edit2, ArrowLeft,
  FolderKanban, FileText, StickyNote, Plus, Upload, Calendar, User,
  CheckCircle2, ChevronRight, AlertCircle
} from 'lucide-vue-next'

const route = useRoute()

const client = ref(null)
const stats = ref({
  total_flows: 0,
  active_flows: 0,
  completed_flows: 0,
  total_tasks: 0,
  completed_tasks: 0,
  pending_tasks: 0,
  completion_rate: 0
})
const loading = ref(true)
const processing = ref(false)
const activeTab = ref('flows')
const showEditModal = ref(false)

const fetchClient = async () => {
  loading.value = true
  try {
    const response = await ClientService.get(route.params.id)
    client.value = response.data.client
    stats.value = response.data.stats
  } catch (error) {
    console.error('Error fetching client:', error)
    client.value = null
  } finally {
    loading.value = false
  }
}

const openEditModal = () => {
  showEditModal.value = true
}

const handleSaveClient = async (formData) => {
  processing.value = true
  try {
    const response = await ClientService.update(client.value.id, formData)
    client.value = { ...client.value, ...response.data }
    showEditModal.value = false
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Cliente actualizado',
      showConfirmButton: false,
      timer: 3000
    })
  } catch (error) {
    console.error('Error updating client:', error)
    Swal.fire('Error', 'No se pudo actualizar el cliente', 'error')
  } finally {
    processing.value = false
  }
}

const openUploadModal = () => {
  Swal.fire('Próximamente', 'Esta funcionalidad estará disponible pronto.', 'info')
}

const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

const getFlowStatusClass = (status) => {
  const classes = {
    active: 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
    completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
    paused: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400',
    cancelled: 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400'
  }
  return classes[status] || 'bg-slate-100 text-slate-600'
}

const getFlowStatusText = (status) => {
  const texts = {
    active: 'Activo',
    completed: 'Completado',
    paused: 'Pausado',
    cancelled: 'Cancelado'
  }
  return texts[status] || status
}

onMounted(() => {
  fetchClient()
})
</script>
