import { onMounted, onUnmounted } from 'vue'
import Sortable from 'sortablejs'

export function useDragAndDrop(elementRef, options = {}) {
  let sortableInstance = null

  onMounted(() => {
    if (!elementRef.value) return

    sortableInstance = new Sortable(elementRef.value, {
      animation: 150,
      ghostClass: 'sortable-ghost',
      dragClass: 'sortable-drag',
      handle: '.drag-handle',
      disabled: options.disabled || false,
      group: options.group || 'tasks',

      // Eventos mejorados
      onStart: (evt) => {
        evt.item.classList.add('dragging')
      },

      onEnd: async (evt) => {
        evt.item.classList.remove('dragging')

        // Obtener el nuevo orden de todas las tareas visibles
        const taskElements = Array.from(evt.to.children)
        const tasksToUpdate = []

        taskElements.forEach((el, index) => {
          const taskId = el.dataset.taskId
          const parentId = evt.to.dataset.parentId || null

          if (taskId) {
            tasksToUpdate.push({
              id: parseInt(taskId),
              order: index,
              parent_task_id: parentId ? parseInt(parentId) : null
            })
          }
        })

        // Llamar al callback con los datos actualizados
        if (options.onEnd && tasksToUpdate.length > 0) {
          try {
            await options.onEnd({ tasks: tasksToUpdate })
          } catch (error) {
            console.error('Error en drag & drop:', error)
            // Revertir cambios en caso de error
            sortableInstance.option('disabled', true)
            setTimeout(() => {
              sortableInstance.option('disabled', false)
            }, 100)
          }
        }
      },

      onChange: (evt) => {
        console.log('Tarea movida:', evt.item.dataset.taskId)
      }
    })
  })

  onUnmounted(() => {
    if (sortableInstance) {
      sortableInstance.destroy()
    }
  })

  return {
    sortableInstance
  }
}