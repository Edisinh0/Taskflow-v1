import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useThemeStore = defineStore('theme', () => {
  // Estado del tema ('light' o 'dark')
  const currentTheme = ref('light')

  // Cargar tema guardado del localStorage
  const loadTheme = () => {
    const savedTheme = localStorage.getItem('theme')
    if (savedTheme) {
      currentTheme.value = savedTheme
    } else {
      // Detectar preferencia del sistema
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
      currentTheme.value = prefersDark ? 'dark' : 'light'
    }
    applyTheme()
  }

  // Aplicar el tema al HTML
  const applyTheme = () => {
    if (currentTheme.value === 'dark') {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }

  // Alternar entre claro y oscuro
  const toggleTheme = () => {
    currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light'
  }

  // Guardar en localStorage cuando cambie
  watch(currentTheme, (newTheme) => {
    localStorage.setItem('theme', newTheme)
    applyTheme()
  })

  return {
    currentTheme,
    loadTheme,
    toggleTheme
  }
})