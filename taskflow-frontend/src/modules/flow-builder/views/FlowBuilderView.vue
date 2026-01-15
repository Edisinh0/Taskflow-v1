<script setup>
import { ref, onMounted } from 'vue'
import { flowsAPI } from '@/services/api' // We will update this later to use flowBuilderAPI

const flows = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const response = await flowsAPI.getAll()
    flows.value = response.data.data
  } catch (error) {
    console.error('Error fetching flows:', error)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="p-6">
    <header class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Flow Builder</h1>
      <p class="text-gray-600 dark:text-gray-400 mt-2">Diseña y estructura tus flujos de trabajo.</p>
    </header>

    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
      <p class="mt-4 text-gray-500">Cargando flujos...</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="flow in flows" :key="flow.id" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">{{ flow.name }}</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">{{ flow.description || 'Sin descripción' }}</p>
        
        <div class="flex justify-between items-center mt-4">
          <span :class="{
            'px-2 py-1 rounded text-xs font-medium': true,
            'bg-green-100 text-green-800': flow.status === 'active',
            'bg-yellow-100 text-yellow-800': flow.status === 'paused',
            'bg-blue-100 text-blue-800': flow.status === 'completed',
            'bg-gray-100 text-gray-800': flow.status === 'cancelled',
          }">
            {{ flow.status }}
          </span>
          
          <router-link :to="`/flows/${flow.id}`" class="text-primary-600 hover:text-primary-700 font-medium text-sm">
            Editar Flujo →
          </router-link>
        </div>
      </div>
      
      <!-- Create New Flow Card -->
      <button class="bg-gray-50 dark:bg-gray-800/50 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 p-6 flex flex-col items-center justify-center text-gray-500 hover:text-primary-600 hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/10 transition-all group h-full min-h-[200px]">
        <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 group-hover:bg-primary-100 dark:group-hover:bg-primary-900 flex items-center justify-center mb-3 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        </div>
        <span class="font-medium">Crear Nuevo Flujo</span>
      </button>
    </div>
  </div>
</template>
