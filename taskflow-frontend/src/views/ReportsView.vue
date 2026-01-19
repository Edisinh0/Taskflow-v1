<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors">
    <Navbar />
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-slate-800 dark:text-white tracking-tight flex items-center">
            <BarChart3 class="w-8 h-8 mr-3 text-blue-500" />
            Reportes Operativos
        </h2>
        <p class="text-slate-500 dark:text-slate-400 mt-1 text-lg">Analiza el rendimiento de tus flujos con métricas detalladas</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar de Filtros -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 sticky top-4">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-6 flex items-center">
              <Filter class="w-5 h-5 mr-2 text-blue-500" />
              Filtros
            </h3>
            
            <form @submit.prevent="applyFilters" class="space-y-5">
              <!-- Estado -->
              <div>
                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                  Estado
                </label>
                <select
                  v-model="filters.status"
                  multiple
                  class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-700 scrollbar-track-transparent"
                  size="5"
                >
                  <option value="pending" class="py-1">Pendiente</option>
                  <option value="in_progress" class="py-1">En Progreso</option>
                  <option value="completed" class="py-1">Completada</option>
                  <option value="paused" class="py-1">Pausada</option>
                  <option value="cancelled" class="py-1">Cancelada</option>
                </select>
              </div>

              <!-- Prioridad -->
              <div>
                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                  Prioridad
                </label>
                <select
                  v-model="filters.priority"
                  multiple
                  class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-700 scrollbar-track-transparent"
                  size="4"
                >
                  <option value="low" class="py-1">Baja</option>
                  <option value="medium" class="py-1">Media</option>
                  <option value="high" class="py-1">Alta</option>
                  <option value="urgent" class="py-1">Urgente</option>
                </select>
              </div>

              <!-- Usuario Asignado -->
              <div>
                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                  Usuario Asignado
                </label>
                <select
                  v-model="filters.assignee_id"
                  class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                >
                  <option :value="null">Todos</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }}
                  </option>
                </select>
              </div>

              <!-- Flujo -->
              <div>
                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                  Flujo
                </label>
                <select
                  v-model="filters.flow_id"
                  class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                >
                  <option :value="null">Todos</option>
                  <option v-for="flow in flows" :key="flow.id" :value="flow.id">
                    {{ flow.name }}
                  </option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <!-- Fecha Desde -->
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
                    Desde
                  </label>
                  <input
                    v-model="filters.date_from"
                    type="date"
                    class="w-full px-2 py-2 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-700 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>

                <!-- Fecha Hasta -->
                <div>
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
                    Hasta
                  </label>
                  <input
                    v-model="filters.date_to"
                    type="date"
                    class="w-full px-2 py-2 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-700 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>

              <!-- Solo Milestones -->
              <div>
                <label class="flex items-center cursor-pointer group">
                  <input
                    v-model="filters.is_milestone"
                    type="checkbox"
                    class="w-4 h-4 text-blue-600 border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 rounded focus:ring-blue-500 focus:ring-offset-slate-100 dark:focus:ring-offset-slate-800"
                  />
                  <span class="ml-2 text-sm font-medium text-slate-500 dark:text-slate-400 group-hover:text-slate-800 dark:group-hover:text-slate-200 transition-colors">
                    Solo Milestones
                  </span>
                </label>
              </div>

              <!-- Botones -->
              <div class="space-y-3 pt-4 border-t border-slate-200 dark:border-white/5">
                <button
                  type="submit"
                  class="w-full px-4 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-bold transition-all shadow-lg shadow-blue-500/20 dark:shadow-blue-900/20"
                >
                  Aplicar Filtros
                </button>
                <button
                  type="button"
                  @click="clearFilters"
                  class="w-full px-4 py-2.5 bg-slate-100 dark:bg-slate-700/50 text-slate-500 dark:text-slate-300 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white font-medium transition-colors border border-slate-200 dark:border-white/5"
                >
                  Limpiar Todo
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Contenido Principal -->
        <div class="lg:col-span-3 space-y-6">
          <!-- Métricas de SLA (Analytics) -->
          <div v-if="analytics?.summary?.total_tasks > 0" class="space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-bold text-slate-800 dark:text-white flex items-center">
                <TrendingUp class="w-6 h-6 mr-2 text-emerald-500" />
                Métricas de Cumplimiento SLA
              </h3>
            </div>

            <!-- Cards de SLA -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
              <!-- Total Tareas Completadas -->
              <div class="bg-gradient-to-br from-blue-500/10 to-blue-600/5 dark:from-blue-500/20 dark:to-blue-600/10 rounded-2xl shadow-sm dark:shadow-lg p-5 border border-blue-500/20 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/10 rounded-bl-full -mr-8 -mt-8"></div>
                <Clock class="w-8 h-8 text-blue-500 mb-3" />
                <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Completadas</p>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ analytics?.summary?.total_completed ?? 0 }}</p>
              </div>

              <!-- A Tiempo -->
              <div class="bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 dark:from-emerald-500/20 dark:to-emerald-600/10 rounded-2xl shadow-sm dark:shadow-lg p-5 border border-emerald-500/20 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/10 rounded-bl-full -mr-8 -mt-8"></div>
                <CheckCircle2 class="w-8 h-8 text-emerald-500 mb-3" />
                <p class="text-sm font-medium text-slate-600 dark:text-slate-400">A Tiempo</p>
                <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ analytics?.summary?.completed_on_time ?? 0 }}</p>
              </div>

              <!-- Tarde -->
              <div class="bg-gradient-to-br from-rose-500/10 to-rose-600/5 dark:from-rose-500/20 dark:to-rose-600/10 rounded-2xl shadow-sm dark:shadow-lg p-5 border border-rose-500/20 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-24 h-24 bg-rose-500/10 rounded-bl-full -mr-8 -mt-8"></div>
                <XCircle class="w-8 h-8 text-rose-500 mb-3" />
                <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Completadas Tarde</p>
                <p class="text-3xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ analytics?.summary?.completed_late ?? 0 }}</p>
              </div>

              <!-- Tasa de Cumplimiento -->
              <div class="bg-gradient-to-br from-violet-500/10 to-violet-600/5 dark:from-violet-500/20 dark:to-violet-600/10 rounded-2xl shadow-sm dark:shadow-lg p-5 border border-violet-500/20 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-24 h-24 bg-violet-500/10 rounded-bl-full -mr-8 -mt-8"></div>
                <TrendingUp class="w-8 h-8 text-violet-500 mb-3" />
                <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Tasa Cumplimiento</p>
                <p class="text-3xl font-bold text-violet-600 dark:text-violet-400 mt-1">{{ analytics?.summary?.sla_compliance_rate ?? 0 }}%</p>
              </div>

              <!-- Activas con SLA Incumplido -->
              <div class="bg-gradient-to-br from-orange-500/10 to-orange-600/5 dark:from-orange-500/20 dark:to-orange-600/10 rounded-2xl shadow-sm dark:shadow-lg p-5 border border-orange-500/20 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-24 h-24 bg-orange-500/10 rounded-bl-full -mr-8 -mt-8"></div>
                <AlertTriangle class="w-8 h-8 text-orange-500 mb-3" />
                <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Activas Incumplidas</p>
                <p class="text-3xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ analytics?.summary?.active_with_sla_breach ?? 0 }}</p>
              </div>
            </div>

            <!-- Promedio Días de Retraso (si existe) -->
            <div v-if="(analytics?.summary?.avg_days_overdue ?? 0) > 0" class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/30 rounded-xl p-4">
              <div class="flex items-center">
                <AlertTriangle class="w-5 h-5 text-amber-600 dark:text-amber-500 mr-3" />
                <div>
                  <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">Promedio de Retraso</p>
                  <p class="text-xs text-amber-700 dark:text-amber-300">Las tareas tardías tienen un promedio de <span class="font-bold">{{ analytics?.summary?.avg_days_overdue ?? 0 }} días</span> de retraso</p>
                </div>
              </div>
            </div>

            <!-- Rendimiento Temporal (Últimos 30 días) -->
            <div v-if="validPerformanceDays.length > 0" class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg border border-slate-200 dark:border-white/5 p-6">
              <h4 class="text-lg font-bold text-slate-800 dark:text-white mb-4 flex items-center">
                <BarChart3 class="w-5 h-5 mr-2 text-blue-500" />
                Rendimiento Últimos 30 Días
              </h4>

              <div class="space-y-2">
                <div v-for="day in validPerformanceDays.slice(-10)" :key="day.date" class="flex items-center">
                  <div class="w-24 text-sm text-slate-600 dark:text-slate-400 font-medium">
                    {{ formatDate(day.date) }}
                  </div>
                  <div class="flex-1 flex items-center space-x-2">
                    <!-- Barra de Completadas -->
                    <div class="flex-1 bg-slate-100 dark:bg-slate-900 rounded-full h-6 overflow-hidden relative">
                      <div
                        v-if="day.completed > 0"
                        class="h-full bg-gradient-to-r from-emerald-500 to-emerald-600 flex items-center justify-end px-2 transition-all duration-500"
                        :style="{ width: `${getBarWidth(day.completed)}%` }"
                      >
                        <span class="text-xs font-bold text-white">{{ day.completed }}</span>
                      </div>
                    </div>
                    <div class="w-20 text-xs text-slate-500 dark:text-slate-400">
                      <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ day.on_time || 0 }}</span> a tiempo
                    </div>
                    <div class="w-20 text-xs text-slate-500 dark:text-slate-400" v-if="day.late > 0">
                      <span class="font-bold text-rose-600 dark:text-rose-400">{{ day.late }}</span> tarde
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-4 pt-4 border-t border-slate-200 dark:border-white/5 text-xs text-slate-500 dark:text-slate-400">
                Mostrando los últimos 10 días con actividad de {{ validPerformanceDays.length }} días totales
              </div>
            </div>
          </div>

          <!-- Estadísticas -->
          <div v-if="stats?.total >= 0" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-5 border border-slate-200 dark:border-white/5 relative overflow-hidden group">
              <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 dark:bg-blue-500/10 rounded-bl-full -mr-4 -mt-4 transition-all group-hover:bg-blue-500/10 dark:group-hover:bg-blue-500/20"></div>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Tareas</p>
              <p class="text-3xl font-bold text-slate-800 dark:text-white mt-2">{{ stats?.total ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-5 border border-slate-200 dark:border-white/5 relative overflow-hidden group">
               <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-bl-full -mr-4 -mt-4 transition-all group-hover:bg-emerald-500/10 dark:group-hover:bg-emerald-500/20"></div>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Completadas</p>
              <p class="text-3xl font-bold text-emerald-500 dark:text-emerald-400 mt-2">{{ stats?.by_status?.completed ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-5 border border-slate-200 dark:border-white/5 relative overflow-hidden group">
               <div class="absolute top-0 right-0 w-20 h-20 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-bl-full -mr-4 -mt-4 transition-all group-hover:bg-indigo-500/10 dark:group-hover:bg-indigo-500/20"></div>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Progreso Promedio</p>
              <p class="text-3xl font-bold text-indigo-500 dark:text-indigo-400 mt-2">{{ stats?.avg_progress ?? 0 }}%</p>
            </div>
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-5 border border-slate-200 dark:border-white/5 relative overflow-hidden group">
               <div class="absolute top-0 right-0 w-20 h-20 bg-rose-500/5 dark:bg-rose-500/10 rounded-bl-full -mr-4 -mt-4 transition-all group-hover:bg-rose-500/10 dark:group-hover:bg-rose-500/20"></div>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Bloqueadas</p>
              <p class="text-3xl font-bold text-rose-500 dark:text-rose-400 mt-2">{{ stats?.blocked ?? 0 }}</p>
            </div>
          </div>

          <!-- Acciones de Exportación -->
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
              <h3 class="text-lg font-bold text-slate-800 dark:text-white">Exportar Reporte</h3>
              <p class="text-sm text-slate-500 dark:text-slate-400">Descarga los resultados actuales en tu formato preferido</p>
            </div>
            <div class="flex space-x-3 w-full sm:w-auto">
              <button
                @click="exportToCsv"
                :disabled="loading || !tasks.length"
                class="flex-1 sm:flex-none px-4 py-2.5 bg-emerald-600/10 text-emerald-400 border border-emerald-600/20 rounded-xl hover:bg-emerald-600 hover:text-white font-bold transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
              >
                <FileText class="w-5 h-5" />
                <span>CSV</span>
              </button>
              <button
                @click="exportToPdf"
                :disabled="loading || !tasks.length"
                class="flex-1 sm:flex-none px-4 py-2.5 bg-rose-600/10 text-rose-400 border border-rose-600/20 rounded-xl hover:bg-rose-600 hover:text-white font-bold transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
              >
                <FileDown class="w-5 h-5" />
                <span>PDF</span>
              </button>
            </div>
          </div>

          <!-- Tabla de Resultados -->
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg border border-slate-200 dark:border-white/5 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-white/5 bg-slate-50 dark:bg-slate-800/50">
              <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                Resultados <span class="text-sm font-normal text-slate-500 dark:text-slate-400 ml-2">({{ meta.total || 0 }} registros)</span>
              </h3>
            </div>

            <div v-if="loading" class="p-12 text-center">
              <div class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-blue-500"></div>
              <p class="text-slate-500 dark:text-slate-400 mt-4 font-medium">Generando reporte...</p>
            </div>

            <div v-else-if="tasks.length === 0" class="p-12 text-center">
              <div class="bg-slate-100 dark:bg-slate-800/50 p-4 rounded-full inline-block mb-4">
                 <Inbox class="w-12 h-12 text-slate-500 dark:text-slate-600" />
              </div>
              <p class="text-slate-700 dark:text-slate-300 text-lg font-medium">No se encontraron datos</p>
              <p class="text-slate-500 text-sm mt-1">Intenta ajustar los filtros de búsqueda</p>
            </div>

            <div v-else class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-900/50">
                  <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Título</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Prioridad</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Asignado</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Flujo</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Progreso</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-white/5">
                  <tr v-for="task in tasks" :key="task.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-slate-500">#{{ task.id }}</td>
                    <td class="px-6 py-4 text-sm text-slate-700 dark:text-slate-200 font-medium">{{ task.title }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getStatusClass(task.status)" class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border border-current/20">
                        {{ getStatusText(task.status) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getPriorityClass(task.priority)" class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border border-current/20">
                        {{ getPriorityText(task.priority) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                      {{ task.assignee?.name || 'Sin asignar' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 font-medium cursor-pointer">{{ task.flow?.name || '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-700 dark:text-slate-200">{{ task.progress }}%</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Paginación -->
            <div v-if="meta.last_page > 1" class="px-6 py-4 border-t border-slate-200 dark:border-white/5 flex items-center justify-between bg-slate-50 dark:bg-slate-800/30">
              <div class="text-sm text-slate-500 dark:text-slate-400">
                Página <span class="text-slate-800 dark:text-white font-bold">{{ meta.current_page }}</span> de <span class="text-slate-800 dark:text-white font-bold">{{ meta.last_page }}</span>
              </div>
              <div class="flex space-x-3">
                <button
                  @click="changePage(meta.current_page - 1)"
                  :disabled="meta.current_page === 1"
                  class="px-4 py-2 bg-slate-700 text-white rounded-lg text-sm font-medium hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  Anterior
                </button>
                <button
                  @click="changePage(meta.current_page + 1)"
                  :disabled="meta.current_page === meta.last_page"
                  class="px-4 py-2 bg-slate-700 text-white rounded-lg text-sm font-medium hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  Siguiente
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { reportsAPI, flowsAPI } from '@/services/api'
import { useToast } from '@/composables/useToast'
import axios from 'axios'
import Navbar from '@/components/AppNavbar.vue'
import { Filter, FileText, FileDown, Inbox, BarChart3, TrendingUp, Clock, CheckCircle2, XCircle, AlertTriangle } from 'lucide-vue-next'

const { showError } = useToast()
const loading = ref(false)
const loadingAnalytics = ref(false)
const tasks = ref([])
const stats = ref({
  total: 0,
  by_status: {
    pending: 0,
    in_progress: 0,
    completed: 0,
    paused: 0,
    cancelled: 0
  },
  by_priority: {
    low: 0,
    medium: 0,
    high: 0,
    urgent: 0
  },
  avg_progress: 0,
  milestones: 0,
  blocked: 0
})
const analytics = ref({
  summary: {
    total_tasks: 0,
    completed_tasks: 0,
    total_completed: 0,
    completed_on_time: 0,
    completed_late: 0,
    active_with_sla_breach: 0,
    avg_days_overdue: 0,
    avg_progress: 0,
    sla_compliance_rate: 0
  },
  tasks_by_status: {
    pending: 0,
    in_progress: 0,
    completed: 0,
    paused: 0,
    cancelled: 0,
    blocked: 0
  },
  tasks_by_priority: {
    low: 0,
    medium: 0,
    high: 0,
    urgent: 0
  },
  performance_by_day: [],
  milestones: {
    total: 0,
    completed: 0
  }
})
const users = ref([])
const flows = ref([])
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 50,
  total: 0
})

// Computed property para filtrar días válidos
const validPerformanceDays = computed(() => {
  if (!analytics.value?.performance_by_day) return []
  return analytics.value.performance_by_day.filter(day => day && day.date)
})

const filters = ref({
  status: [],
  priority: [],
  assignee_id: null,
  flow_id: null,
  date_from: '',
  date_to: '',
  is_milestone: false
})

const loadAnalytics = async () => {
  loadingAnalytics.value = true
  try {
    const activeFilters = {}
    if (filters.value.assignee_id) activeFilters.assignee_id = filters.value.assignee_id
    if (filters.value.flow_id) activeFilters.flow_id = filters.value.flow_id
    if (filters.value.date_from) activeFilters.date_from = filters.value.date_from
    if (filters.value.date_to) activeFilters.date_to = filters.value.date_to

    const response = await reportsAPI.getAnalytics(activeFilters)
    analytics.value = response.data.data || analytics.value
  } catch (error) {
    console.error('Error al cargar analytics:', error)
    analytics.value = null
  } finally {
    loadingAnalytics.value = false
  }
}

const applyFilters = async () => {
  loading.value = true
  try {
    // Preparar filtros (solo enviar los que tienen valor)
    const activeFilters = {}
    if (filters.value.status.length) activeFilters.status = filters.value.status
    if (filters.value.priority.length) activeFilters.priority = filters.value.priority
    if (filters.value.assignee_id) activeFilters.assignee_id = filters.value.assignee_id
    if (filters.value.flow_id) activeFilters.flow_id = filters.value.flow_id
    if (filters.value.date_from) activeFilters.date_from = filters.value.date_from
    if (filters.value.date_to) activeFilters.date_to = filters.value.date_to
    if (filters.value.is_milestone) activeFilters.is_milestone = 1

    // Obtener datos
    const [reportData, statsData] = await Promise.all([
      reportsAPI.getAll(activeFilters),
      reportsAPI.getStats(activeFilters)
    ])

    tasks.value = reportData.data.data || []
    meta.value = reportData.data.meta || { current_page: 1, last_page: 1, total: 0, per_page: 50 }
    stats.value = statsData.data.data || stats.value

    // Cargar analytics también
    await loadAnalytics()
  } catch (error) {
    console.error('Error al cargar reporte:', error)
    tasks.value = []
    // alert('Error al cargar el reporte')
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filters.value = {
    status: [],
    priority: [],
    assignee_id: null,
    flow_id: null,
    date_from: '',
    date_to: '',
    is_milestone: false
  }
  applyFilters()
}

const changePage = async (page) => {
  if (page < 1 || page > meta.value.last_page) return

  loading.value = true
  try {
    const activeFilters = {}
    if (filters.value.status.length) activeFilters.status = filters.value.status
    if (filters.value.priority.length) activeFilters.priority = filters.value.priority
    if (filters.value.assignee_id) activeFilters.assignee_id = filters.value.assignee_id
    if (filters.value.flow_id) activeFilters.flow_id = filters.value.flow_id
    if (filters.value.date_from) activeFilters.date_from = filters.value.date_from
    if (filters.value.date_to) activeFilters.date_to = filters.value.date_to
    if (filters.value.is_milestone) activeFilters.is_milestone = 1
    activeFilters.page = page

    const reportData = await reportsAPI.getAll(activeFilters)
    tasks.value = reportData.data.data || []
    meta.value = reportData.data.meta || { current_page: 1, last_page: 1, total: 0, per_page: 50 }
  } catch (error) {
    console.error('Error al cambiar página:', error)
    tasks.value = []
  } finally {
    loading.value = false
  }
}

const exportToCsv = async () => {
  try {
    const activeFilters = {}
    if (filters.value.status.length) activeFilters.status = filters.value.status
    if (filters.value.priority.length) activeFilters.priority = filters.value.priority
    if (filters.value.assignee_id) activeFilters.assignee_id = filters.value.assignee_id
    if (filters.value.flow_id) activeFilters.flow_id = filters.value.flow_id
    if (filters.value.date_from) activeFilters.date_from = filters.value.date_from
    if (filters.value.date_to) activeFilters.date_to = filters.value.date_to
    if (filters.value.is_milestone) activeFilters.is_milestone = 1

    await reportsAPI.exportCsv(activeFilters)
  } catch (error) {
    console.error('Error al exportar CSV:', error)
    showError('Error al exportar a CSV')
  }
}

const exportToPdf = async () => {
  try {
    const activeFilters = {}
    if (filters.value.status.length) activeFilters.status = filters.value.status
    if (filters.value.priority.length) activeFilters.priority = filters.value.priority
    if (filters.value.assignee_id) activeFilters.assignee_id = filters.value.assignee_id
    if (filters.value.flow_id) activeFilters.flow_id = filters.value.flow_id
    if (filters.value.date_from) activeFilters.date_from = filters.value.date_from
    if (filters.value.date_to) activeFilters.date_to = filters.value.date_to
    if (filters.value.is_milestone) activeFilters.is_milestone = 1

    await reportsAPI.exportPdf(activeFilters)
  } catch (error) {
    console.error('Error al exportar PDF:', error)
    showError('Error al exportar a PDF')
  }
}

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
    in_progress: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    completed: 'bg-green-500/10 text-green-400 border-green-500/20',
    paused: 'bg-orange-500/10 text-orange-400 border-orange-500/20',
    cancelled: 'bg-slate-500/10 text-slate-400 border-slate-500/20',
    blocked: 'bg-rose-500/10 text-rose-400 border-rose-500/20'
  }
  return classes[status] || 'bg-slate-700/50 text-slate-400 border-slate-600/20'
}

const getStatusText = (status) => {
  const texts = {
    pending: 'Pendiente',
    in_progress: 'En Progreso',
    completed: 'Completada',
    paused: 'Pausada',
    cancelled: 'Cancelada',
    blocked: 'Bloqueada'
  }
  return texts[status] || status
}

const getPriorityClass = (priority) => {
  const classes = {
    low: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    medium: 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
    high: 'bg-orange-500/10 text-orange-400 border-orange-500/20',
    urgent: 'bg-rose-500/10 text-rose-400 border-rose-500/20'
  }
  return classes[priority] || 'bg-slate-700/50 text-slate-400 border-slate-600/20'
}

const getPriorityText = (priority) => {
  const texts = {
    low: 'Baja',
    medium: 'Media',
    high: 'Alta',
    urgent: 'Urgente'
  }
  return texts[priority] || priority
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${month}/${day}`
}

const getBarWidth = (completed) => {
  if (!analytics.value?.performance_by_day || analytics.value.performance_by_day.length === 0) {
    return 0
  }
  const maxCompleted = Math.max(...analytics.value.performance_by_day.map(d => d.completed || 0))
  if (maxCompleted === 0) return 0
  return (completed / maxCompleted) * 100
}

const loadInitialData = async () => {
  try {
    const token = localStorage.getItem('token')
    
    // Cargar usuarios y flujos para los filtros
    const apiUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api/v1'
    const [usersRes, flowsRes] = await Promise.all([
      axios.get(`${apiUrl}/users`, {
        headers: { Authorization: `Bearer ${token}` }
      }).catch(() => ({ data: { data: [] } })),
      flowsAPI.getAll().catch(() => ({ data: { data: [] } }))
    ])

    users.value = usersRes.data.data || []
    flows.value = flowsRes.data.data || []

    // Cargar reporte inicial
    await applyFilters()
  } catch (error) {
    console.error('Error al cargar datos iniciales:', error)
  }
}

onMounted(() => {
  loadInitialData()
})
</script>
