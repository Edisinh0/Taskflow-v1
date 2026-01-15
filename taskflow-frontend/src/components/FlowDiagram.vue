<template>
  <div class="flow-diagram-container h-full w-full relative">
    <!-- Vue Flow Canvas -->
    <VueFlow
      v-model:nodes="nodes"
      v-model:edges="edges"
      :default-zoom="0.8"
      :min-zoom="0.1"
      :max-zoom="2"
      class="flow-canvas"
      :fit-view-on-init="true"
      @node-click="onNodeClick"
    >
      <!-- Background Pattern -->
      <Background pattern-color="#e2e8f0" :gap="16" />

      <!-- Controls (Zoom, fit view, etc) -->
      <Controls />

      <!-- Mini Map -->
      <MiniMap />

      <!-- Custom Node Templates -->
      <template #node-milestone="{ data }">
        <div class="milestone-node" :class="{'milestone-completed': data.completed}">
          <div class="node-header milestone-header">
            <div class="flex items-center gap-2">
              <Flag :size="16" class="text-purple-600" />
              <span class="font-bold text-sm">MILESTONE</span>
            </div>
            <CheckCircle v-if="data.completed" :size="18" class="text-green-500" />
          </div>
          <div class="node-body">
            <p class="font-semibold text-sm mb-1">{{ data.label }}</p>
            <div class="flex items-center justify-between text-xs text-slate-500">
              <span>{{ data.subtaskCount }} subtareas</span>
              <span>{{ data.progress }}%</span>
            </div>
          </div>
        </div>
      </template>

      <template #node-task="{ data }">
        <div class="task-node" :class="getTaskClass(data.status)">
          <div class="node-header">
            <span class="text-xs font-medium uppercase">{{ getStatusText(data.status) }}</span>
            <CheckCircle v-if="data.status === 'completed'" :size="16" class="text-green-500" />
            <Lock v-else-if="data.status === 'blocked'" :size="16" class="text-red-500" />
            <AlertCircle v-else-if="data.status === 'in_progress'" :size="16" class="text-blue-500" />
          </div>
          <div class="node-body">
            <p class="font-medium text-sm mb-1">{{ data.label }}</p>
            <div class="flex items-center gap-2 text-xs text-slate-500">
              <div v-if="data.assignee" class="flex items-center gap-1">
                <User :size="12" />
                <span>{{ data.assignee }}</span>
              </div>
              <div v-if="data.priority" class="flex items-center gap-1" :class="getPriorityClass(data.priority)">
                <Circle :size="8" :fill="getPriorityColor(data.priority)" :stroke="getPriorityColor(data.priority)" />
                <span>{{ getPriorityText(data.priority) }}</span>
              </div>
            </div>
          </div>
        </div>
      </template>
    </VueFlow>

    <!-- Legend -->
    <div class="absolute top-4 right-4 bg-white dark:bg-slate-800 rounded-lg shadow-lg p-4 border border-slate-200 dark:border-slate-700 z-10">
      <h3 class="font-bold text-sm mb-3 text-slate-900 dark:text-white">Leyenda</h3>
      <div class="space-y-2 text-xs">
        <div class="flex items-center gap-2">
          <div class="w-4 h-4 rounded bg-purple-100 border-2 border-purple-500"></div>
          <span class="text-slate-700 dark:text-slate-300">Milestone</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-4 h-4 rounded bg-blue-100 border-2 border-blue-500"></div>
          <span class="text-slate-700 dark:text-slate-300">Tarea Activa</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-4 h-4 rounded bg-green-100 border-2 border-green-500"></div>
          <span class="text-slate-700 dark:text-slate-300">Completada</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-4 h-4 rounded bg-red-100 border-2 border-red-500"></div>
          <span class="text-slate-700 dark:text-slate-300">Bloqueada</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-4 h-4 rounded bg-slate-100 border-2 border-slate-400"></div>
          <span class="text-slate-700 dark:text-slate-300">Pendiente</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { VueFlow, useVueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { MiniMap } from '@vue-flow/minimap'
import { Flag, CheckCircle, Lock, User, AlertCircle, Circle } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'
import '@vue-flow/controls/dist/style.css'
import '@vue-flow/minimap/dist/style.css'

const props = defineProps({
  flow: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['node-click'])
const authStore = useAuthStore()

const nodes = ref([])
const edges = ref([])

// Verificar si el usuario tiene permisos de edición
const canEdit = computed(() => {
  const role = authStore.user?.role
  return ['admin', 'project_manager', 'pm'].includes(role)
})

// Generar nodos y conexiones desde el flujo
const generateDiagram = () => {
  if (!props.flow || !props.flow.tasks) return

  const newNodes = []
  const newEdges = []
  let yPosition = 0
  const levelSpacing = 200
  const nodeSpacing = 250

  // Obtener milestones y sus subtareas
  const milestones = props.flow.tasks.filter(t => t.is_milestone)

  milestones.forEach((milestone, milestoneIndex) => {
    const subtasks = props.flow.tasks.filter(t =>
      t.parent_task_id === milestone.id ||
      t.depends_on_milestone_id === milestone.id
    )

    // Agregar nodo milestone
    newNodes.push({
      id: `milestone-${milestone.id}`,
      type: 'milestone',
      position: { x: 50, y: yPosition },
      data: {
        label: milestone.title,
        completed: milestone.status === 'completed',
        subtaskCount: subtasks.length,
        progress: milestone.progress || 0,
        taskId: milestone.id
      }
    })

    // Agregar subtareas
    subtasks.forEach((task, taskIndex) => {
      const xPosition = 400 + (taskIndex * nodeSpacing)

      newNodes.push({
        id: `task-${task.id}`,
        type: 'task',
        position: { x: xPosition, y: yPosition },
        data: {
          label: task.title,
          status: task.status,
          assignee: task.assignee?.name || null,
          priority: task.priority,
          taskId: task.id
        }
      })

      // Conexión milestone -> tarea
      newEdges.push({
        id: `e-milestone-${milestone.id}-task-${task.id}`,
        source: `milestone-${milestone.id}`,
        target: `task-${task.id}`,
        type: 'smoothstep',
        animated: task.status === 'in_progress',
        style: { stroke: getEdgeColor(task.status) }
      })

      // Conexiones entre tareas (dependencias)
      if (task.depends_on_task_id) {
        newEdges.push({
          id: `e-task-${task.depends_on_task_id}-task-${task.id}`,
          source: `task-${task.depends_on_task_id}`,
          target: `task-${task.id}`,
          type: 'smoothstep',
          animated: false,
          style: { stroke: '#94a3b8', strokeDasharray: '5,5' },
          label: 'depende de'
        })
      }
    })

    yPosition += levelSpacing

    // Conexión entre milestones consecutivos
    if (milestoneIndex < milestones.length - 1) {
      const nextMilestone = milestones[milestoneIndex + 1]
      newEdges.push({
        id: `e-milestone-${milestone.id}-milestone-${nextMilestone.id}`,
        source: `milestone-${milestone.id}`,
        target: `milestone-${nextMilestone.id}`,
        type: 'step',
        animated: false,
        style: { stroke: '#8b5cf6', strokeWidth: 3 }
      })
    }
  })

  nodes.value = newNodes
  edges.value = newEdges
}

// Colores de conexiones según estado
const getEdgeColor = (status) => {
  const colors = {
    'completed': '#22c55e',
    'in_progress': '#3b82f6',
    'blocked': '#ef4444',
    'pending': '#94a3b8',
    'paused': '#f59e0b'
  }
  return colors[status] || '#94a3b8'
}

// Clases CSS para tareas
const getTaskClass = (status) => {
  const classes = {
    'completed': 'task-completed',
    'in_progress': 'task-active',
    'blocked': 'task-blocked',
    'pending': 'task-pending',
    'paused': 'task-paused'
  }
  return classes[status] || 'task-pending'
}

// Textos de estado
const getStatusText = (status) => {
  const texts = {
    'completed': 'Completada',
    'in_progress': 'En Progreso',
    'blocked': 'Bloqueada',
    'pending': 'Pendiente',
    'paused': 'Pausada',
    'cancelled': 'Cancelada'
  }
  return texts[status] || status
}

// Clases de prioridad
const getPriorityClass = (priority) => {
  const classes = {
    'urgent': 'text-red-600 font-bold',
    'high': 'text-orange-600 font-semibold',
    'medium': 'text-yellow-600',
    'low': 'text-slate-500'
  }
  return classes[priority] || ''
}

// Textos de prioridad
const getPriorityText = (priority) => {
  const texts = {
    'urgent': 'Urgente',
    'high': 'Alta',
    'medium': 'Media',
    'low': 'Baja'
  }
  return texts[priority] || priority
}

// Colores de prioridad
const getPriorityColor = (priority) => {
  const colors = {
    'urgent': '#dc2626',
    'high': '#ea580c',
    'medium': '#ca8a04',
    'low': '#64748b'
  }
  return colors[priority] || '#64748b'
}

// Manejar click en nodo
const onNodeClick = ({ node }) => {
  // Solo permitir edición si el usuario tiene permisos de administración
  if (!canEdit.value) {
    alert('⚠️ Acción no permitida\n\nSolo los administradores y project managers pueden editar tareas desde el diagrama.')
    return
  }
  emit('node-click', node.data.taskId)
}

// Regenerar diagrama cuando cambia el flujo
watch(() => props.flow, () => {
  generateDiagram()
}, { immediate: true, deep: true })
</script>

<style scoped>
.flow-diagram-container {
  background: #f8fafc;
}

.dark .flow-diagram-container {
  background: #0f172a;
}

/* Nodos Milestone */
.milestone-node {
  background: white;
  border: 3px solid #8b5cf6;
  border-radius: 12px;
  padding: 12px;
  min-width: 280px;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  transition: all 0.3s ease;
}

.milestone-node:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}

.milestone-completed {
  border-color: #22c55e;
  background: #f0fdf4;
}

.milestone-header {
  background: #f3e8ff;
  margin: -12px -12px 8px -12px;
  padding: 8px 12px;
  border-radius: 9px 9px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.milestone-completed .milestone-header {
  background: #dcfce7;
}

/* Nodos Tarea */
.task-node {
  background: white;
  border: 2px solid #cbd5e1;
  border-radius: 8px;
  padding: 10px;
  min-width: 200px;
  box-shadow: 0 2px 4px rgb(0 0 0 / 0.05);
  transition: all 0.3s ease;
}

.task-node:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 10px rgb(0 0 0 / 0.1);
}

.task-completed {
  border-color: #22c55e;
  background: #f0fdf4;
}

.task-active {
  border-color: #3b82f6;
  background: #eff6ff;
}

.task-blocked {
  border-color: #ef4444;
  background: #fef2f2;
}

.task-pending {
  border-color: #94a3b8;
  background: #f8fafc;
}

.task-paused {
  border-color: #f59e0b;
  background: #fffbeb;
}

.node-header {
  padding-bottom: 8px;
  border-bottom: 1px solid #e2e8f0;
  margin-bottom: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.node-body {
  color: #334155;
}

/* Dark mode */
.dark .milestone-node,
.dark .task-node {
  background: #1e293b;
  color: #e2e8f0;
}

.dark .node-body {
  color: #cbd5e1;
}

.dark .milestone-header {
  background: #2d1b4e;
}

.dark .milestone-completed .milestone-header {
  background: #14532d;
}
</style>
