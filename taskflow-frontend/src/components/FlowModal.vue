<template>
  <Transition name="modal">
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
      <div class="flex min-h-screen items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/50 dark:bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>

        <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full p-8 z-10 border border-slate-200 dark:border-white/10">
          <!-- Header -->
          <div class="flex justify-between items-start mb-8">
            <h3 class="text-2xl font-bold text-slate-800 dark:text-white">
              {{ isEditMode ? 'Editar Flujo' : 'Nuevo Flujo' }}
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
                Nombre del Proyecto <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="formData.name"
                type="text"
                required
                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                placeholder="Ej: Instalación Cliente XYZ"
              />
            </div>

<!-- 
            <div class="mb-5">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                Cliente Asociado
              </label>
              <select
                v-model="formData.client_id"
                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              >
                <option :value="null">Seleccionar Cliente...</option>
                <option v-for="client in clients" :key="client.id" :value="client.id">
                  {{ client.name }}
                </option>
              </select>
            </div>
            -->

            <!-- Responsable del Flujo -->
            <div class="mb-5">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                Responsable del Flujo <span class="text-rose-500">*</span>
              </label>
              <select
                v-model.number="formData.responsible_id"
                required
                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              >
                <option :value="null">Seleccionar Responsable...</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
              <p class="text-xs text-slate-500 mt-1">Responsable actual: {{ formData.responsible_id || 'Sin asignar' }}</p>
            </div>

            <!-- Descripción -->
            <div class="mb-5">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                Descripción
              </label>
              <textarea
                v-model="formData.description"
                rows="4"
                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                placeholder="Describe el flujo de trabajo..."
              ></textarea>
            </div>

            <!-- Plantilla Base -->
            <div class="mb-5">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                Plantilla Base
              </label>
              <select
                v-model="formData.template_id"
                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              >
                <option :value="null">Sin plantilla</option>
                <option v-for="template in templates" :key="template.id" :value="template.id">
                  {{ template.name }} (v{{ template.version }})
                </option>
              </select>
              <p class="text-xs text-slate-500 mt-2">
                Opcional: Selecciona una plantilla para pre-cargar tareas
              </p>
            </div>

            <!-- Información de plantilla seleccionada -->
            <div v-if="selectedTemplate" class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-500/20 rounded-xl">
              <h4 class="font-bold text-blue-400 mb-2 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Plantilla: {{ selectedTemplate.name }}
              </h4>
              <p class="text-sm text-blue-300/80 mb-2">{{ selectedTemplate.description }}</p>
              <div class="mt-2 text-xs font-semibold text-blue-500 uppercase tracking-wide">
                <span class="text-blue-400">Versión:</span> {{ selectedTemplate.version }} |
                <span class="text-blue-400">Duración estimada:</span> {{ selectedTemplate.config?.estimated_duration_days || 'N/A' }} días
              </div>
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
                :disabled="loading"
                class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 font-bold shadow-lg shadow-blue-900/20 disabled:opacity-50 flex items-center"
              >
                <span v-if="loading" class="mr-2">
                  <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
                {{ isEditMode ? 'Actualizar Flujo' : 'Crear Flujo' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { flowsAPI, usersAPI } from '@/services/api'
import ClientService from '@/services/ClientService'

const props = defineProps({
  isOpen: Boolean,
  flow: Object,
  templates: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'saved'])

const loading = ref(false)
const error = ref(null)

const isEditMode = computed(() => !!props.flow)

const formData = ref({
  name: '',
  description: '',
  template_id: null,
  client_id: null,
  responsible_id: null,
  status: 'active'
})

const clients = ref([])
const users = ref([])

onMounted(async () => {
  try {
    const [clientsResponse, usersResponse] = await Promise.all([
      ClientService.getAll(),
      usersAPI.getAll()
    ])
    clients.value = clientsResponse.data

    // La estructura del UserController es: { success: true, data: [...] }
    // Pero Axios devuelve: { data: { success: true, data: [...] } }
    // Por lo tanto, necesitamos acceder a usersResponse.data.data
    if (usersResponse.data?.data) {
      users.value = usersResponse.data.data
    } else if (Array.isArray(usersResponse.data)) {
      users.value = usersResponse.data
    } else {
      users.value = usersResponse
    }

  } catch (err) {
    console.error('Error fetching data:', err)
  }
})

const selectedTemplate = computed(() => {
  if (!formData.value.template_id) return null
  return props.templates.find(t => t.id === formData.value.template_id)
})

watch(() => props.flow, (newFlow) => {
  if (newFlow) {
    formData.value = {
      name: newFlow.name || '',
      description: newFlow.description || '',
      template_id: newFlow.template_id,
      client_id: newFlow.client_id,
      responsible_id: newFlow.responsible_id || newFlow.user_id,
      status: newFlow.status || 'active'
    }
  } else {
    formData.value = {
      name: '',
      description: '',
      template_id: null,
      client_id: null,
      responsible_id: null,
      status: 'active'
    }
  }
}, { immediate: true })

const closeModal = () => {
  error.value = null
  emit('close')
}

const handleSubmit = async () => {
  try {
    loading.value = true
    error.value = null

    if (isEditMode.value) {
      await flowsAPI.update(props.flow.id, formData.value)
    } else {
      await flowsAPI.create(formData.value)
    }

    emit('saved')
    closeModal()
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al guardar el flujo'
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