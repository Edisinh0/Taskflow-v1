<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors pb-12">
    <Navbar />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
        <div>
          <h2 class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight flex items-center">
            <Users class="w-8 h-8 mr-3 text-blue-600 dark:text-blue-400" />
            Clientes
          </h2>
          <p class="text-slate-500 dark:text-slate-400 mt-1 text-lg">
            Gestiona tu cartera de clientes y empresas.
          </p>
        </div>

        <button
          @click="openCreateModal"
          class="inline-flex items-center px-5 py-2.5 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all hover:scale-105"
        >
          <Plus class="w-5 h-5 mr-2" />
          Nuevo Cliente
        </button>
      </div>

      <!-- Filtros y Búsqueda -->
      <div class="mb-6 flex flex-col sm:flex-row gap-4">
        <div class="relative flex-1">
          <Search class="absolute left-3 top-2.5 w-5 h-5 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar por nombre, email o ID..."
            class="w-full pl-10 pr-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all dark:text-white"
          />
        </div>
        
        <select
          v-model="statusFilter"
          class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-slate-700 dark:text-white"
        >
          <option value="all">Todos los estados</option>
          <option value="active">Activos</option>
          <option value="inactive">Inactivos</option>
        </select>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <Loader2 class="w-10 h-10 text-blue-600 animate-spin" />
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredClients.length === 0" class="text-center py-16 bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-white/5 border-dashed">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-700/50 mb-4">
          <Users class="w-8 h-8 text-slate-400" />
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white">No hay clientes encontrados</h3>
        <p class="mt-1 text-slate-500 dark:text-slate-400">Comienza agregando un nuevo cliente a la plataforma.</p>
        <button
          @click="openCreateModal"
          class="mt-4 inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-500/10 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-500/20 transition-colors"
        >
          <Plus class="w-4 h-4 mr-2" />
          Crear Cliente
        </button>
      </div>

      <!-- Grid de Clientes -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="client in filteredClients"
          :key="client.id"
          class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-white/5 hover:shadow-md hover:border-blue-500/30 transition-all group relative overflow-hidden"
        >
          <!-- Indicador de estado -->
          <div 
            class="absolute top-0 right-0 w-16 h-16 -mr-8 -mt-8 rotate-45 transform transition-colors"
            :class="client.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400'"
          ></div>

          <div class="flex items-start justify-between mb-4">
            <div class="flex items-center space-x-3">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-blue-500/20">
                {{ getInitials(client.name) }}
              </div>
              <div>
                <h3 class="font-bold text-slate-800 dark:text-white text-lg leading-tight group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                  {{ client.name }}
                </h3>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300 mt-1 inline-block">
                  {{ client.industry || 'Sin industria' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Detalles -->
          <div class="space-y-3 text-sm text-slate-600 dark:text-slate-300 mb-6">
            <div class="flex items-center space-x-2">
              <Mail class="w-4 h-4 text-slate-400" />
              <span class="truncate">{{ client.email || 'Sin email' }}</span>
            </div>
            <div class="flex items-center space-x-2">
              <Phone class="w-4 h-4 text-slate-400" />
              <span>{{ client.phone || 'Sin teléfono' }}</span>
            </div>
            <div class="flex items-center space-x-2">
              <MapPin class="w-4 h-4 text-slate-400" />
              <span class="truncate">{{ client.address || 'Sin dirección' }}</span>
            </div>
            <div v-if="client.sweetcrm_id" class="flex items-center space-x-2">
              <Database class="w-4 h-4 text-purple-500" />
              <span class="text-xs font-mono bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 px-1.5 py-0.5 rounded border border-purple-100 dark:border-purple-500/20">
                CRM: {{ client.sweetcrm_id }}
              </span>
            </div>
          </div>

          <!-- Acciones -->
          <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-white/5">
            <button
              @click="goToClientDetail(client.id)"
              class="flex-1 mr-2 px-3 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-500/20 rounded-lg transition-all duration-200 flex items-center justify-center hover:scale-105 active:scale-95"
            >
              <Eye class="w-4 h-4 mr-2" />
              Ver
            </button>
            <button
              @click="openEditModal(client)"
              class="px-3 py-2 text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-all duration-200 hover:scale-110 active:scale-95"
              title="Editar"
            >
              <Edit2 class="w-4 h-4" />
            </button>
            <button
              @click="confirmDelete(client)"
              class="px-3 py-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg transition-all duration-200 hover:scale-110 active:scale-95"
              title="Eliminar"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal Formulario -->
    <ClientModal
      :is-open="showModal"
      :client="selectedClient"
      :loading="processing"
      @close="closeModal"
      @save="handleSave"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Navbar from '@/components/AppNavbar.vue'
import ClientModal from '@/components/ClientModal.vue'
import ClientService from '@/services/ClientService'
import { Users, Plus, Search, Mail, Phone, MapPin, Database, Edit2, Trash2, Loader2, Eye } from 'lucide-vue-next'
import Swal from 'sweetalert2'

const router = useRouter()

const clients = ref([])
const loading = ref(true)
const processing = ref(false)
const searchQuery = ref('')
const statusFilter = ref('all')

const showModal = ref(false)
const selectedClient = ref(null)

// Computed para filtrar
const filteredClients = computed(() => {
  return clients.value.filter(client => {
    // Filtro texto
    const searchLower = searchQuery.value.toLowerCase()
    const matchSearch = 
      client.name.toLowerCase().includes(searchLower) ||
      (client.email && client.email.toLowerCase().includes(searchLower)) ||
      (client.sweetcrm_id && client.sweetcrm_id.toLowerCase().includes(searchLower))

    // Filtro estado
    const matchStatus = statusFilter.value === 'all' || client.status === statusFilter.value

    return matchSearch && matchStatus
  })
})

const fetchClients = async () => {
  loading.value = true
  try {
    const response = await ClientService.getAll()
    clients.value = response.data
  } catch (error) {
    console.error('Error fetching clients:', error)
    Swal.fire('Error', 'No se pudieron cargar los clientes', 'error')
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  selectedClient.value = null
  showModal.value = true
}

const openEditModal = (client) => {
  selectedClient.value = { ...client }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedClient.value = null
}

const handleSave = async (formData) => {
  processing.value = true
  try {
    if (selectedClient.value) {
      // Editar
      const response = await ClientService.update(selectedClient.value.id, formData)
      const index = clients.value.findIndex(c => c.id === response.data.id)
      if (index !== -1) {
        clients.value[index] = response.data
      }
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Cliente actualizado correctamente',
        showConfirmButton: false,
        timer: 3000
      })
    } else {
      // Crear
      const response = await ClientService.create(formData)
      clients.value.push(response.data)
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Cliente creado correctamente',
        showConfirmButton: false,
        timer: 3000
      })
    }
    closeModal()
  } catch (error) {
    console.error('Error saving client:', error)
    Swal.fire('Error', 'No se pudo guardar el cliente', 'error')
  } finally {
    processing.value = false
  }
}

const confirmDelete = (client) => {
  Swal.fire({
    title: '¿Estás seguro?',
    text: `Eliminarás al cliente "${client.name}". Esta acción no se puede deshacer.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e11d48', // rose-600
    cancelButtonColor: '#64748b', // slate-500
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await ClientService.delete(client.id)
        clients.value = clients.value.filter(c => c.id !== client.id)
        Swal.fire('Eliminado', 'El cliente ha sido eliminado.', 'success')
      } catch (error) {
        console.error('Error deleting client:', error)
        Swal.fire('Error', 'No se pudo eliminar el cliente', 'error')
      }
    }
  })
}

const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const goToClientDetail = (clientId) => {
  router.push({ name: 'client-detail', params: { id: clientId } })
}

onMounted(() => {
  fetchClients()
})
</script>
