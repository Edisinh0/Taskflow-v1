<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors pb-12">
    <Navbar />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Título -->
      <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight">Dashboard</h2>
        <p class="text-slate-500 dark:text-slate-400 mt-1 text-lg">Bienvenido de nuevo, <span class="text-blue-500 dark:text-blue-400 font-semibold">{{ authStore.currentUser?.name }}</span></p>
      </div>

      <!-- Estadísticas Principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Flujos Activos -->
        <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 hover:border-blue-500/20 transition-all group">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-sm font-medium uppercase tracking-wider">Flujos Activos</p>
              <p class="text-3xl font-extrabold text-slate-800 dark:text-white mt-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ stats.activeFlows }}</p>
              <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 font-medium">+{{ stats.flowsThisWeek }} esta semana</p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-500/10 p-3 rounded-xl border border-blue-100 dark:border-blue-500/20 group-hover:bg-blue-100 dark:group-hover:bg-blue-500/20 transition-colors">
              <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Tareas Pendientes -->
        <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 hover:border-amber-500/20 transition-all group">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-sm font-medium uppercase tracking-wider">Pendientes</p>
              <p class="text-3xl font-extrabold text-slate-800 dark:text-white mt-1 group-hover:text-amber-500 dark:group-hover:text-amber-400 transition-colors">{{ stats.pendingTasks }}</p>
              <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 font-medium">{{ stats.urgentTasks }} urgentes</p>
            </div>
            <div class="bg-amber-50 dark:bg-amber-500/10 p-3 rounded-xl border border-amber-100 dark:border-amber-500/20 group-hover:bg-amber-100 dark:group-hover:bg-amber-500/20 transition-colors">
              <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Tareas Completadas -->
        <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 hover:border-emerald-500/20 transition-all group">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-sm font-medium uppercase tracking-wider">Completadas Hoy</p>
              <p class="text-3xl font-extrabold text-slate-800 dark:text-white mt-1 group-hover:text-emerald-500 dark:group-hover:text-emerald-400 transition-colors">{{ stats.completedToday }}</p>
              <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 font-medium">{{ stats.completionRate }}% tasa de éxito</p>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-500/10 p-3 rounded-xl border border-emerald-100 dark:border-emerald-500/20 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-500/20 transition-colors">
              <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Tareas Vencidas -->
        <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 hover:border-rose-500/20 transition-all group">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-sm font-medium uppercase tracking-wider">Vencidas</p>
              <p class="text-3xl font-extrabold text-slate-800 dark:text-white mt-1 group-hover:text-rose-500 dark:group-hover:text-rose-400 transition-colors">{{ stats.overdueTasks }}</p>
              <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 font-medium">Acción requerida</p>
            </div>
            <div class="bg-rose-50 dark:bg-rose-500/10 p-3 rounded-xl border border-rose-100 dark:border-rose-500/20 group-hover:bg-rose-100 dark:group-hover:bg-rose-500/20 transition-colors">
              <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>


      <!-- Resumen de Productividad -->
      <!-- Resumen de Productividad -->
      <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-8 mb-8 border border-slate-200 dark:border-white/5 relative overflow-hidden group">
        <!-- Decorative Background Gradient -->
        <div class="absolute top-0 right-0 -mt-20 -mr-20 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl pointer-events-none group-hover:bg-blue-500/20 transition-all duration-700"></div>
        <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none group-hover:bg-indigo-500/20 transition-all duration-700"></div>

        <div class="flex flex-col md:flex-row items-center justify-between relative z-10">
          <div class="mb-6 md:mb-0 max-w-lg">
             <div class="flex items-center space-x-4 mb-3">
                <div class="p-3 bg-blue-50 dark:bg-blue-500/10 rounded-2xl border border-blue-100 dark:border-blue-500/20 shadow-sm">
                   <Rocket class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                   <h3 class="text-2xl font-bold text-slate-800 dark:text-white">Productividad Semanal</h3>
                   <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Tu rendimiento los últimos 7 días</p>
                </div>
             </div>
            <p class="text-slate-600 dark:text-slate-300 text-lg leading-relaxed">
              Has completado <strong class="text-blue-600 dark:text-blue-400 font-bold bg-blue-50 dark:bg-blue-500/10 px-2 py-0.5 rounded-md mx-1">{{ stats.completedThisWeek }}</strong> 
              tareas de <strong class="text-slate-800 dark:text-white font-bold">{{ stats.totalThisWeek }}</strong> asignadas.
              <span v-if="stats.completedThisWeek > 0" class="block mt-1 text-sm text-slate-500">¡Sigue así! Estás avanzando hacia tus objetivos.</span>
            </p>
          </div>

          <div class="flex items-center space-x-8">
             <div class="text-right">
                 <p class="text-6xl font-black tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400">
                   {{ Math.round((stats.completedThisWeek / stats.totalThisWeek) * 100) || 0 }}<span class="text-3xl font-bold text-slate-400">%</span>
                 </p>
                 <p class="text-slate-400 dark:text-slate-500 text-xs font-bold uppercase tracking-widest mt-1">Efectividad Global</p>
             </div>
          </div>
        </div>
        
        <!-- Modern Progress Bar -->
        <div class="mt-8 relative">
          <div class="flex justify-between mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
            <span>0%</span>
            <span>Meta: 100%</span>
          </div>
          <div class="w-full bg-slate-100 dark:bg-slate-700/50 rounded-full h-4 overflow-hidden shadow-inner">
            <div 
              class="h-full rounded-full transition-all duration-1000 ease-out relative overflow-hidden"
              :class="[
                'bg-gradient-to-r from-blue-500 to-indigo-600',
                Math.round((stats.completedThisWeek / stats.totalThisWeek) * 100) >= 100 ? 'shadow-[0_0_20px_rgba(79,70,229,0.5)]' : ''
              ]"
              :style="`width: ${Math.round((stats.completedThisWeek / stats.totalThisWeek) * 100) || 0}%`"
            >
              <!-- Shine effect -->
              <div class="absolute top-0 right-0 bottom-0 left-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -skew-x-12 animate-shimmer"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráficos y Métricas -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Tendencia de Tareas (Últimos 7 días) -->
        <div class="bg-white dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5">
          <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-6 flex items-center">
             <span class="w-2 h-6 bg-blue-500 rounded-sm mr-3"></span>
             Tendencia de Tareas
          </h3>
          <div class="h-64">
            <Line v-if="taskTrendData.datasets[0].data.length > 0" :data="taskTrendData" :options="chartOptions" />
            <p v-else class="text-slate-400 dark:text-slate-500 text-center pt-20">No hay datos disponibles</p>
          </div>
        </div>

        <!-- Estado de Tareas por Prioridad -->
        <div class="bg-white dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5">
          <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-6 flex items-center">
             <span class="w-2 h-6 bg-amber-500 rounded-sm mr-3"></span>
             Distribución por Prioridad
          </h3>
          <div class="h-64">
            <Doughnut v-if="priorityChartData.datasets[0].data.some(val => val > 0)" :data="priorityChartData" :options="doughnutOptions" />
            <p v-else class="text-slate-400 dark:text-slate-500 text-center pt-20">No hay datos disponibles</p>
          </div>
        </div>
      </div>



      <!-- Tareas -->
      <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg border border-slate-200 dark:border-white/5 mb-6">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-white/5 flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center">
            <span class="w-2 h-6 bg-blue-500 rounded-sm mr-3"></span>
            Tareas
          </h3>
          <span class="text-xs font-semibold bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 px-2 py-1 rounded-md border border-blue-100 dark:border-blue-500/20">
            {{ allTasks.length }} tareas
          </span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-white/5">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Tarea</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Proyecto</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Fecha Inicio</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Fecha Término</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Días Restantes</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
              <tr
                v-for="task in allTasks"
                :key="task.id"
                @click="$router.push(`/flows/${task.flow_id}`)"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 cursor-pointer transition-colors"
              >
                <td class="px-6 py-4">
                  <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ task.title }}</h4>
                </td>
                <td class="px-6 py-4">
                  <p class="text-xs text-slate-500">{{ task.flow?.name }}</p>
                </td>
                <td class="px-6 py-4">
                  <p class="text-xs text-slate-500">{{ formatDate(task.estimated_start_at) }}</p>
                </td>
                <td class="px-6 py-4">
                  <p class="text-xs text-slate-500">{{ formatDate(task.estimated_end_at) }}</p>
                </td>
                <td class="px-6 py-4">
                  <span class="px-2.5 py-1 text-xs font-bold rounded-lg" :class="getDaysRemainingClass(task.estimated_end_at)">
                    {{ getDaysRemaining(task.estimated_end_at) }}
                  </span>
                </td>
              </tr>
              <tr v-if="allTasks.length === 0">
                <td colspan="5" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                  No hay tareas disponibles.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tareas Urgentes y Flujos Recientes -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Tareas Urgentes -->
        <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg border border-slate-200 dark:border-white/5 flex flex-col">
          <div class="px-6 py-4 border-b border-slate-200 dark:border-white/5 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center">
                <Zap class="w-5 h-5 mr-2 text-rose-500 animate-pulse" :stroke-width="2.5" fill="currentColor" />
                Tareas Urgentes
            </h3>
            <span class="text-xs font-semibold bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 px-2 py-1 rounded-md border border-rose-100 dark:border-rose-500/20">
                {{ computedUrgentTasks.length }} pendientes
            </span>
          </div>
          <div class="divide-y divide-slate-100 dark:divide-white/5">
            <router-link
              v-for="task in computedUrgentTasks"
              :key="task.id"
              :to="`/flows/${task.flow_id}`"
              class="block px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 cursor-pointer transition-colors group"
            >
              <div class="flex items-start justify-between mb-2">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ task.title }}</h4>
                    <!-- Badge SLA si la tarea tiene SLA vencido -->
                    <SLAAlertBadge
                      v-if="getSLAStatus(task)"
                      :alert-type="getSLAStatus(task)"
                      :days-overdue="getDaysOverdue(task)"
                      class="shrink-0"
                    />
                    <!-- Badge de prioridad urgente (solo si no tiene SLA vencido) -->
                    <span
                      v-else-if="task.priority === 'urgent'"
                      class="px-2 py-0.5 bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 text-[10px] font-bold uppercase tracking-wider rounded-full border border-rose-200 dark:border-rose-500/20 inline-flex items-center gap-1"
                    >
                      <Flame :size="12" :stroke-width="2.5" />
                      <span>Urgente</span>
                    </span>
                  </div>
                  <p class="text-xs text-slate-500 mt-1 flex items-center">
                    <FolderOpen class="w-3 h-3 mr-1 opacity-50" stroke-width="2" />
                    {{ task.flow?.name }}
                  </p>
                </div>
                <span
                  :class="[
                    'px-2.5 py-1 text-xs font-bold rounded-lg border shadow-sm shrink-0 ml-3 inline-flex items-center gap-1',
                    getSLAStatus(task) === 'escalation' ? 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 border-red-200 dark:border-red-500/20' :
                    getSLAStatus(task) === 'warning' ? 'bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-500/20' :
                    'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-100 dark:border-rose-500/20'
                  ]"
                >
                  <AlertCircle v-if="getSLAStatus(task) === 'escalation'" :size="14" :stroke-width="2.5" />
                  <Clock v-else-if="getSLAStatus(task) === 'warning'" :size="14" :stroke-width="2.5" />
                  <CalendarClock v-else :size="14" :stroke-width="2.5" />
                  <span>{{ getDaysRemaining(task.estimated_end_at) }}</span>
                </span>
              </div>
              <div class="flex items-center gap-4 text-[10px] text-slate-400">
                <span>Inicio: {{ formatDate(task.estimated_start_at) }}</span>
                <span>•</span>
                <span>Término: {{ formatDate(task.estimated_end_at) }}</span>
              </div>
            </router-link>
            <div v-if="computedUrgentTasks.length === 0" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                ¡Todo bajo control! No hay tareas urgentes.
            </div>
          </div>
        </div>

        <!-- Flujos Recientes -->
        <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg border border-slate-200 dark:border-white/5 flex flex-col">
          <div class="px-6 py-4 border-b border-slate-200 dark:border-white/5">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center">
              <Folder class="w-5 h-5 mr-2 text-blue-500" />
              Flujos Recientes
            </h3>
          </div>
          <div class="divide-y divide-slate-100 dark:divide-white/5">
            <router-link
              v-for="flow in recentFlows"
              :key="flow.id"
              :to="`/flows/${flow.id}`"
              class="block px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors group"
            >
              <div class="flex items-center justify-between mb-2">
                <div class="flex-1 min-w-0 mr-4">
                  <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors truncate">{{ flow.name }}</h4>
                  <p class="text-xs text-slate-500 mt-1">{{ flow.tasks?.length || 0 }} tareas</p>
                </div>
                <div class="flex flex-col items-end space-y-2 shrink-0">
                  <span :class="getStatusClass(flow.status)" class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border border-current/20">
                    {{ getStatusText(flow.status) }}
                  </span>
                  <div class="w-24">
                    <div class="w-full bg-slate-200 dark:bg-slate-700/50 rounded-full h-1.5 overflow-hidden">
                      <div
                        class="bg-blue-500 h-1.5 rounded-full transition-all duration-500"
                        :style="`width: ${calculateProgress(flow)}%`"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-4 text-[10px] text-slate-400">
                <span>Inicio: {{ formatDate(flow.created_at) }}</span>
                <span>•</span>
                <span>Actualizado: {{ formatDate(flow.updated_at) }}</span>
              </div>
            </router-link>
            <div v-if="recentFlows.length === 0" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                No hay flujos recientes.
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUserNotifications } from '@/composables/useRealtime'
import { flowsAPI, tasksAPI } from '@/services/api'
import { Line, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title
} from 'chart.js'
import Navbar from '@/components/AppNavbar.vue'
import SLAAlertBadge from '@/components/SLAAlertBadge.vue'
import { Rocket, Folder, FolderOpen, Flame, Clock, AlertCircle, CalendarClock, Zap } from 'lucide-vue-next'

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, PointElement, LineElement, Title)

const authStore = useAuthStore()

const stats = ref({
  activeFlows: 0,
  pendingTasks: 0,
  completedToday: 0,
  overdueTasks: 0,
  urgentTasks: 0,
  flowsThisWeek: 0,
  completedThisWeek: 0,
  totalThisWeek: 0,
  completionRate: 0
})

const allTasksData = ref([]) // Todas las tareas sin filtrar
const recentFlows = ref([])
const allTasks = ref([])

// Computed para calcular el estado SLA de una tarea
const getSLAStatus = (task) => {
  if (!task.sla_due_date) return null
  if (task.status === 'completed' || task.status === 'cancelled') return null

  const now = new Date()
  const dueDate = new Date(task.sla_due_date)
  const hoursOverdue = (now - dueDate) / (1000 * 60 * 60)

  if (hoursOverdue < 0) return null
  if (hoursOverdue >= 48) return 'escalation'
  if (hoursOverdue >= 24) return 'warning'
  return null
}

// Computed para calcular días de atraso
const getDaysOverdue = (task) => {
  if (!task.sla_due_date) return 0

  const now = new Date()
  const dueDate = new Date(task.sla_due_date)
  const days = Math.floor((now - dueDate) / (1000 * 60 * 60 * 24))

  return Math.max(0, days)
}

// Computed para obtener tareas urgentes (incluyendo SLA atrasadas)
const computedUrgentTasks = computed(() => {
  const tasks = allTasksData.value.filter(t => {
    // Excluir tareas completadas
    if (t.status === 'completed' || t.status === 'cancelled') return false

    // Incluir tareas con SLA vencido
    const slaStatus = getSLAStatus(t)
    if (slaStatus) return true

    // Incluir tareas con prioridad urgente
    if (t.priority === 'urgent') return true

    return false
  })

  // Ordenar por criticidad:
  // 1. SLA escalation (48+ horas)
  // 2. SLA warning (24+ horas)
  // 3. Prioridad urgent
  return tasks.sort((a, b) => {
    const slaA = getSLAStatus(a)
    const slaB = getSLAStatus(b)

    // Prioridad a escalations
    if (slaA === 'escalation' && slaB !== 'escalation') return -1
    if (slaB === 'escalation' && slaA !== 'escalation') return 1

    // Luego warnings
    if (slaA === 'warning' && slaB !== 'warning') return -1
    if (slaB === 'warning' && slaA !== 'warning') return 1

    // Finalmente por prioridad
    const priorityOrder = { urgent: 0, high: 1, medium: 2, low: 3 }
    return (priorityOrder[a.priority] || 3) - (priorityOrder[b.priority] || 3)
  }).slice(0, 10) // Limitar a 10 tareas más urgentes
})

const taskTrendData = ref({
  labels: [],
  datasets: [{
    label: 'Completadas',
    data: [],
    borderColor: '#3B82F6',
    backgroundColor: (context) => {
      const ctx = context.chart.ctx;
      const gradient = ctx.createLinearGradient(0, 0, 0, 300);
      gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
      gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');
      return gradient;
    },
    tension: 0.4,
    fill: true,
    borderWidth: 3,
    pointBackgroundColor: '#3B82F6',
    pointBorderColor: '#fff',
    pointBorderWidth: 2,
    pointRadius: 5,
    pointHoverRadius: 8,
    pointHoverBackgroundColor: '#3B82F6',
    pointHoverBorderColor: '#fff',
    pointHoverBorderWidth: 3
  }]
})

const priorityChartData = ref({
  labels: ['Baja', 'Media', 'Alta', 'Urgente'],
  datasets: [{
    data: [0, 0, 0, 0],
    backgroundColor: [
      'rgba(59, 130, 246, 0.8)',   // Azul para Baja
      'rgba(252, 211, 77, 0.8)',   // Amarillo para Media
      'rgba(249, 115, 22, 0.8)',   // Naranja para Alta
      'rgba(239, 68, 68, 0.8)'     // Rojo para Urgente
    ],
    borderColor: [
      'rgba(59, 130, 246, 1)',
      'rgba(252, 211, 77, 1)',
      'rgba(249, 115, 22, 1)',
      'rgba(239, 68, 68, 1)'
    ],
    borderWidth: 2,
    hoverBackgroundColor: [
      'rgba(59, 130, 246, 1)',
      'rgba(252, 211, 77, 1)',
      'rgba(249, 115, 22, 1)',
      'rgba(239, 68, 68, 1)'
    ],
    hoverBorderColor: '#fff',
    hoverBorderWidth: 4
  }]
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  plugins: {
    legend: { 
      display: true,
      labels: {
        color: '#6B7280',
        font: {
          size: 12,
          weight: 'bold'
        },
        padding: 15,
        usePointStyle: true
      }
    },
    tooltip: {
      enabled: true,
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#fff',
      bodyColor: '#fff',
      borderColor: '#3B82F6',
      borderWidth: 2,
      padding: 12,
      displayColors: true,
      callbacks: {
        label: function(context) {
          return ` ${context.dataset.label}: ${context.parsed.y} tareas`;
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1,
        color: '#6B7280'
      },
      grid: {
        color: 'rgba(107, 114, 128, 0.1)',
        drawBorder: false
      }
    },
    x: {
      ticks: {
        color: '#6B7280'
      },
      grid: {
        display: false
      }
    }
  },
  animation: {
    duration: 2000,
    easing: 'easeInOutQuart',
    onProgress: function() {
      // Animación suave durante el progreso
    },
    onComplete: function() {
      // Animación completada
    }
  },
  hover: {
    mode: 'nearest',
    intersect: true,
    animationDuration: 400
  },
  elements: {
    line: {
      tension: 0.4,
      borderWidth: 3,
      borderCapStyle: 'round',
      borderJoinStyle: 'round',
      fill: true
    },
    point: {
      radius: 5,
      hoverRadius: 8,
      hitRadius: 10,
      borderWidth: 2,
      hoverBorderWidth: 3
    }
  }
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '65%',
  plugins: {
    legend: { 
      position: 'bottom',
      labels: {
        color: '#6B7280',
        font: {
          size: 12,
          weight: 'bold'
        },
        padding: 15,
        usePointStyle: true,
        pointStyle: 'circle'
      }
    },
    tooltip: {
      enabled: true,
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#fff',
      bodyColor: '#fff',
      borderColor: '#3B82F6',
      borderWidth: 2,
      padding: 12,
      callbacks: {
        label: function(context) {
          const label = context.label || '';
          const value = context.parsed || 0;
          const total = context.dataset.data.reduce((a, b) => a + b, 0);
          const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
          return ` ${label}: ${value} tareas (${percentage}%)`;
        }
      }
    }
  },
  animation: {
    animateRotate: true,
    animateScale: true,
    duration: 2000,
    easing: 'easeInOutQuart'
  },
  hover: {
    mode: 'nearest',
    animationDuration: 400
  },
  elements: {
    arc: {
      borderWidth: 3,
      borderColor: '#fff',
      hoverBorderWidth: 5,
      hoverOffset: 15
    }
  }
}

const getDaysRemaining = (date) => {
  if (!date) return 'Sin fecha'
  const days = Math.ceil((new Date(date) - new Date()) / (1000 * 60 * 60 * 24))
  if (days < 0) return `Vencida hace ${Math.abs(days)}d`
  if (days === 0) return 'Vence hoy'
  return `${days}d restantes`
}

const getDaysRemainingClass = (date) => {
  if (!date) return 'bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-400'
  const days = Math.ceil((new Date(date) - new Date()) / (1000 * 60 * 60 * 24))
  if (days < 0) return 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-500/20'
  if (days === 0) return 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-500/20'
  if (days <= 3) return 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-500/20'
  return 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-500/20'
}

const formatDate = (date) => {
  if (!date) return 'Sin fecha'
  const d = new Date(date)
  const day = d.getDate().toString().padStart(2, '0')
  const month = (d.getMonth() + 1).toString().padStart(2, '0')
  const year = d.getFullYear()
  return `${day}/${month}/${year}`
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400',
    paused: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400',
    completed: 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status) => {
  const texts = { active: 'Activo', paused: 'Pausado', completed: 'Completado' }
  return texts[status] || status
}

const calculateProgress = (flow) => {
  if (!flow.tasks?.length) return 0
  const completed = flow.tasks.filter(t => t.status === 'completed').length
  return Math.round((completed / flow.tasks.length) * 100)
}

const loadData = async () => {
  try {
    const [flowsRes, tasksRes, allTasksRes] = await Promise.all([
      flowsAPI.getAll(),
      tasksAPI.getAll({ assignee_id: authStore.currentUser?.id }),
      tasksAPI.getAll() // Obtener TODAS las tareas para el grid de "Tareas Urgentes"
    ])

    const flows = flowsRes.data.data
    const userTasks = tasksRes.data.data // Tareas del usuario actual (para stats y "Mis Tareas")
    const allTasksFromAPI = allTasksRes.data.data // Todas las tareas del sistema (para grid urgentes)

    stats.value = {
      activeFlows: flows.filter(f => f.status === 'active').length,
      pendingTasks: userTasks.filter(t => ['pending', 'in_progress'].includes(t.status)).length,
      completedToday: userTasks.filter(t => t.status === 'completed' && isToday(t.updated_at)).length,
      overdueTasks: userTasks.filter(t => t.estimated_end_at && new Date(t.estimated_end_at) < new Date() && t.status !== 'completed').length,
      // Incluir tareas con priority=urgent O con SLA vencido
      urgentTasks: userTasks.filter(t => {
        if (t.status === 'completed' || t.status === 'cancelled') return false
        return t.priority === 'urgent' || t.sla_breached === true || t.sla_days_overdue > 0
      }).length,
      flowsThisWeek: flows.filter(f => isThisWeek(f.created_at)).length,
      completedThisWeek: userTasks.filter(t => t.status === 'completed' && isThisWeek(t.updated_at)).length,
      totalThisWeek: userTasks.filter(t => isThisWeek(t.created_at)).length,
      completionRate: Math.round((userTasks.filter(t => t.status === 'completed').length / userTasks.length) * 100) || 0
    }

    // Guardar TODAS las tareas del sistema para computedUrgentTasks
    allTasksData.value = allTasksFromAPI

    recentFlows.value = flows.slice(0, 5)

    // Tareas del USUARIO para la tabla "Mis Tareas" (limitado a 20 para no sobrecargar)
    allTasks.value = userTasks
      .filter(t => t.status !== 'completed')
      .slice(0, 20)

    // Calcular datos reales para los últimos 7 días
    const last7Days = []
    const completedByDay = []

    for (let i = 6; i >= 0; i--) {
      const date = new Date()
      date.setDate(date.getDate() - i)
      date.setHours(0, 0, 0, 0)

      const nextDay = new Date(date)
      nextDay.setDate(nextDay.getDate() + 1)

      // Nombre del día
      const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']
      last7Days.push(dayNames[date.getDay()])

      // Contar tareas completadas ese día
      const completedCount = userTasks.filter(t => {
        if (t.status !== 'completed' || !t.updated_at) return false
        const taskDate = new Date(t.updated_at)
        return taskDate >= date && taskDate < nextDay
      }).length

      completedByDay.push(completedCount)
    }

    taskTrendData.value = {
      labels: last7Days,
      datasets: [{
        label: 'Completadas',
        data: completedByDay,
        borderColor: '#3B82F6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4
      }]
    }

    // Actualizar gráficos con datos reales (solo pendientes o en progreso)
    priorityChartData.value.datasets[0].data = [
      userTasks.filter(t => t.priority === 'low' && ['pending', 'in_progress'].includes(t.status)).length,
      userTasks.filter(t => t.priority === 'medium' && ['pending', 'in_progress'].includes(t.status)).length,
      userTasks.filter(t => t.priority === 'high' && ['pending', 'in_progress'].includes(t.status)).length,
      userTasks.filter(t => t.priority === 'urgent' && ['pending', 'in_progress'].includes(t.status)).length
    ]
  } catch (error) {
    console.error('Error cargando datos:', error)
  }
}

const isToday = (date) => {
  const today = new Date()
  const d = new Date(date)
  return d.toDateString() === today.toDateString()
}

const isThisWeek = (date) => {
  const d = new Date(date)
  const today = new Date()
  const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000)
  return d >= weekAgo && d <= today
}

// Handler para notificaciones en tiempo real que afectan el dashboard
const handleRealtimeNotification = (data) => {
  console.log('📬 Notificación recibida en Dashboard:', data)

  // Recargar datos del dashboard cuando llega una notificación relevante
  const relevantTypes = [
    'task_assigned',
    'task_completed',
    'sla_warning',
    'task_overdue',
    'task_date_changed',
    'task_blocked',
    'task_unblocked'
  ]

  if (data.notification && relevantTypes.includes(data.notification.type)) {
    console.log('🔄 Recargando dashboard por notificación:', data.notification.type)
    loadData()
  }
}

// Configurar WebSocket para auto-recarga
let realtimeConnection = null

onMounted(() => {
  loadData()

  // Conectar a WebSocket si el usuario está autenticado
  if (authStore.user?.id) {
    realtimeConnection = useUserNotifications(authStore.user.id, handleRealtimeNotification)
  }
})

onUnmounted(() => {
  if (realtimeConnection) {
    realtimeConnection.disconnect()
  }
})
</script>