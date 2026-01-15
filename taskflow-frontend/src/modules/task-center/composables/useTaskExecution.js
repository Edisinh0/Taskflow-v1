import { ref, computed } from 'vue'
import { taskCenterAPI } from '@/services/api'

/**
 * Composable para gestionar la ejecución de tareas
 *
 * Responsabilidades:
 * - Obtener tareas asignadas al usuario
 * - Actualizar estado y progreso
 * - Calcular alertas de SLA
 * - Gestionar errores
 */
export function useTaskExecution() {
  const tasks = ref([])
  const loading = ref(false)
  const error = ref(null)

  /**
   * Obtener tareas asignadas al usuario actual
   */
  async function fetchMyTasks(filters = {}) {
    loading.value = true
    error.value = null

    try {
      const response = await taskCenterAPI.getMyTasks(filters)

      tasks.value = response.data.data

      return tasks.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al cargar las tareas'
      console.error('Error fetching tasks:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Obtener detalle de una tarea
   */
  async function fetchTaskDetail(taskId) {
    loading.value = true
    error.value = null

    try {
      const response = await taskCenterAPI.getTask(taskId)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al cargar la tarea'
      console.error('Error fetching task detail:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Ejecutar tarea (cambiar estado o progreso)
   */
  async function executeTask(taskId, updates) {
    loading.value = true
    error.value = null

    try {
      const response = await taskCenterAPI.executeTask(taskId, updates)

      // Actualizar tarea en la lista local
      const index = tasks.value.findIndex(t => t.id === taskId)
      if (index !== -1) {
        tasks.value[index] = response.data.data
      }

      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al actualizar la tarea'
      console.error('Error executing task:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Iniciar tarea
   */
  async function startTask(taskId) {
    return executeTask(taskId, {
      status: 'in_progress'
    })
  }

  /**
   * Pausar tarea
   */
  async function pauseTask(taskId) {
    return executeTask(taskId, {
      status: 'paused'
    })
  }

  /**
   * Completar tarea
   */
  async function completeTask(taskId) {
    return executeTask(taskId, {
      status: 'completed'
    })
  }

  /**
   * Actualizar progreso
   */
  async function updateProgress(taskId, progress) {
    return executeTask(taskId, {
      progress
    })
  }

  /**
   * Tareas filtradas por estado
   */
  const pendingTasks = computed(() =>
    tasks.value.filter(t => t.status === 'pending')
  )

  const inProgressTasks = computed(() =>
    tasks.value.filter(t => t.status === 'in_progress')
  )

  const completedTasks = computed(() =>
    tasks.value.filter(t => t.status === 'completed')
  )

  const blockedTasks = computed(() =>
    tasks.value.filter(t => t.is_blocked || t.status === 'blocked')
  )

  /**
   * Tareas con alertas de SLA
   */
  const overdueTasks = computed(() =>
    tasks.value.filter(t => t.sla_status?.level === 'critical')
  )

  const urgentTasks = computed(() =>
    tasks.value.filter(t =>
      t.sla_status?.level === 'warning' ||
      t.priority === 'urgent'
    )
  )

  return {
    // Estado
    tasks,
    loading,
    error,

    // Métodos
    fetchMyTasks,
    fetchTaskDetail,
    executeTask,
    startTask,
    pauseTask,
    completeTask,
    updateProgress,

    // Computed
    pendingTasks,
    inProgressTasks,
    completedTasks,
    blockedTasks,
    overdueTasks,
    urgentTasks
  }
}
