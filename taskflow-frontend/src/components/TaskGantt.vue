<template>
  <div class="w-full h-full flex flex-col">
    <!-- Controles de Gantt -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex space-x-2">
        <button 
          v-for="mode in modes" 
          :key="mode.value"
          @click="currentMode = mode.value"
          class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors border"
          :class="currentMode === mode.value 
            ? 'bg-blue-600 text-white border-blue-600' 
            : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50'"
        >
          {{ mode.label }}
        </button>
      </div>
      <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center">
        <span class="w-3 h-3 rounded-full bg-blue-500 mr-2"></span> En curso
        <span class="w-3 h-3 rounded-full bg-emerald-500 ml-4 mr-2"></span> Completado
        <span class="w-3 h-3 rounded-full bg-rose-500 ml-4 mr-2"></span> Atrasado
      </div>
    </div>

    <!-- Contenedor del Chart -->
    <div class="flex-1 border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden bg-white dark:bg-slate-800 relative">
      <!-- Headers de fecha -->
      <div class="flex border-b border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-slate-800/50">
        <div class="w-64 flex-shrink-0 p-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-r border-slate-200 dark:border-white/10">Tarea</div>
        <div class="flex-1 overflow-hidden relative" ref="timelineHeader">
          <div class="flex h-10 divide-x divide-slate-200 dark:divide-white/5">
             <div 
                v-for="(date, i) in timelineDates" 
                :key="i"
                class="flex-shrink-0 flex items-center justify-center text-[10px] text-slate-500 border-r border-slate-100 dark:border-white/5"
                :style="{ width: `${columnWidth}px` }"
             >
                <div class="text-center">
                   <div class="font-bold">{{ getDayName(date) }}</div>
                   <div>{{ date.getDate() }}</div>
                </div>
             </div>
          </div>
        </div>
      </div>

      <!-- Cuerpo del Gantt -->
      <div class="overflow-y-auto max-h-[500px] overflow-x-hidden relative" ref="ganttBody">
        <div class="relative">
             <!-- Líneas de fondo -->
            <div class="absolute inset-0 left-64 flex pointer-events-none">
                <div 
                    v-for="(date, i) in timelineDates" 
                    :key="i"
                    class="flex-shrink-0 border-r border-slate-100 dark:border-white/5 h-full"
                    :class="{ 'bg-slate-50/50 dark:bg-slate-800/50': isWeekend(date) }"
                    :style="{ width: `${columnWidth}px` }"
                ></div>
            </div>

            <!-- Filas de Tareas -->
            <div 
                v-for="task in sortTasks(tasks)" 
                :key="task.id"
                class="flex border-b border-slate-100 dark:border-white/5 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors h-12 relative group"
            >
                <!-- Columna Nombre -->
                <div class="w-64 flex-shrink-0 px-4 flex items-center border-r border-slate-200 dark:border-white/10 bg-white dark:bg-slate-800 z-10 sticky left-0">
                    <div class="truncate text-sm font-medium text-slate-700 dark:text-slate-200" :title="task.title">
                        {{ task.title }}
                    </div>
                </div>

                <!-- Barra de Tiempo -->
                <div class="flex-1 relative">
                    <div 
                        v-if="isValidDate(task.estimated_start_at) && isValidDate(task.estimated_end_at)"
                        class="absolute h-6 top-3 rounded-md shadow-sm border border-black/10 transition-all cursor-pointer hover:brightness-110 flex items-center px-2 overflow-hidden text-xs text-white font-medium whitespace-nowrap"
                        :class="getTaskColor(task)"
                        :style="getTaskStyle(task)"
                        :title="`${task.title}: ${formatDate(task.estimated_start_at)} - ${formatDate(task.estimated_end_at)}`"
                    >
                        {{ task.progress }}%
                    </div>
                </div>
            </div>
             
             <!-- Empty State -->
             <div v-if="tasks.length === 0" class="p-8 text-center text-slate-400">
                No hay tareas con fechas para mostrar.
             </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  tasks: {
    type: Array,
    default: () => []
  }
})

const columnWidth = ref(40) // width per day in px
const currentMode = ref('month') // week, month

const modes = [
  { label: 'Semana', value: 'week' },
  { label: 'Mes', value: 'month' },
  { label: 'Trimestre', value: 'quarter' }
]

const today = new Date()

// Generar fechas para el timeline
const timelineDates = computed(() => {
  const dates = []
  const start = new Date(today)
  start.setDate(start.getDate() - 5) // Empezar 5 días atrás
  
  const daysToShow = currentMode.value === 'week' ? 14 : currentMode.value === 'month' ? 45 : 90
  
  for (let i = 0; i < daysToShow; i++) {
    const d = new Date(start)
    d.setDate(d.getDate() + i)
    dates.push(d)
  }
  return dates
})

const sortTasks = (tasks) => {
    return [...tasks].sort((a, b) => {
        if (!a.estimated_start_at) return 1;
        if (!b.estimated_start_at) return -1;
        return new Date(a.estimated_start_at) - new Date(b.estimated_start_at);
    });
}

const isValidDate = (date) => {
    return date && !isNaN(new Date(date).getTime())
}

const isWeekend = (date) => {
    const day = date.getDay()
    return day === 0 || day === 6
}

const getDayName = (date) => {
    return ['D','L','M','M','J','V','S'][date.getDay()]
}

const formatDate = (date) => {
    const d = new Date(date)
    return `${d.getDate()}/${d.getMonth()+1}`
}

const getTaskColor = (task) => {
  if (task.status === 'completed') return 'bg-emerald-500'
  
  const end = new Date(task.estimated_end_at)
  if (end < new Date()) return 'bg-rose-500' // Atrasado
  
  if (task.priority === 'urgent') return 'bg-orange-500'
  
  return 'bg-blue-500'
}

const getTaskStyle = (task) => {
    const startDate = new Date(task.estimated_start_at)
    const endDate = new Date(task.estimated_end_at)
    
    // Calcular posición X relativa al inicio del timeline
    const timelineStart = timelineDates.value[0]
    
    const diffTimeStart = startDate - timelineStart
    const diffDaysStart = Math.ceil(diffTimeStart / (1000 * 60 * 60 * 24))
    
    // Calcular duración en días
    const durationTime = endDate - startDate
    const durationDays = Math.ceil(durationTime / (1000 * 60 * 60 * 24)) + 1 // +1 para incluir el día final
    
    const left = diffDaysStart * columnWidth.value
    const width = Math.max(durationDays * columnWidth.value, columnWidth.value) // Mínimo 1 columna
    
    return {
        left: `${left}px`,
        width: `${width-4}px` // -4 para margen derecho
    }
}
</script>

<style scoped>
/* Scrollbar fino para el timeline */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.dark ::-webkit-scrollbar-thumb {
    background: #475569;
}
</style>
