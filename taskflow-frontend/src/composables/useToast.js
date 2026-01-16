import Swal from 'sweetalert2'

/**
 * Composable para mostrar notificaciones toast usando SweetAlert2
 * Reemplaza los alert() nativos por notificaciones más elegantes
 */
export function useToast() {
  // Configuración base para todos los toasts
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.addEventListener('mouseenter', Swal.stopTimer)
      toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
  })

  /**
   * Muestra un mensaje de éxito
   * @param {string} message - Mensaje a mostrar
   * @param {number} timer - Duración en ms (default: 3000)
   */
  const showSuccess = (message, timer = 3000) => {
    return Toast.fire({
      icon: 'success',
      title: message,
      timer
    })
  }

  /**
   * Muestra un mensaje de error
   * @param {string} message - Mensaje a mostrar
   * @param {number} timer - Duración en ms (default: 4000)
   */
  const showError = (message, timer = 4000) => {
    return Toast.fire({
      icon: 'error',
      title: message,
      timer
    })
  }

  /**
   * Muestra un mensaje de advertencia
   * @param {string} message - Mensaje a mostrar
   * @param {number} timer - Duración en ms (default: 4000)
   */
  const showWarning = (message, timer = 4000) => {
    return Toast.fire({
      icon: 'warning',
      title: message,
      timer
    })
  }

  /**
   * Muestra un mensaje informativo
   * @param {string} message - Mensaje a mostrar
   * @param {number} timer - Duración en ms (default: 3000)
   */
  const showInfo = (message, timer = 3000) => {
    return Toast.fire({
      icon: 'info',
      title: message,
      timer
    })
  }

  /**
   * Muestra un diálogo de confirmación
   * @param {string} title - Título del diálogo
   * @param {string} text - Texto descriptivo
   * @param {string} confirmButtonText - Texto del botón de confirmación (default: 'Sí, continuar')
   * @param {string} cancelButtonText - Texto del botón de cancelar (default: 'Cancelar')
   * @returns {Promise<boolean>} True si el usuario confirmó, false si canceló
   */
  const showConfirm = async (
    title,
    text = '',
    confirmButtonText = 'Sí, continuar',
    cancelButtonText = 'Cancelar'
  ) => {
    const result = await Swal.fire({
      title,
      text,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText,
      cancelButtonText,
      reverseButtons: true
    })

    return result.isConfirmed
  }

  /**
   * Muestra un diálogo modal centrado (para mensajes más largos o complejos)
   * @param {string} title - Título del modal
   * @param {string} text - Texto del modal
   * @param {string} icon - Icono: 'success', 'error', 'warning', 'info', 'question'
   */
  const showModal = (title, text, icon = 'info') => {
    return Swal.fire({
      title,
      text,
      icon,
      confirmButtonText: 'Entendido',
      confirmButtonColor: '#3085d6'
    })
  }

  return {
    showSuccess,
    showError,
    showWarning,
    showInfo,
    showConfirm,
    showModal
  }
}
