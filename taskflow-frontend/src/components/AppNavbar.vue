<template>
  <nav class="sticky top-0 z-50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-white/5 transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <!-- Logo y nombre -->
        <div class="flex items-center space-x-8">
          <router-link to="/dashboard" class="flex items-center space-x-3 group">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20 dark:shadow-blue-900/20 group-hover:scale-105 transition-transform">
              <span class="text-white font-extrabold text-xl tracking-tighter">TF</span>
            </div>
            <span class="text-xl font-bold text-slate-800 dark:text-white tracking-tight group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
              TaskFlow
            </span>
          </router-link>

          <!-- Links de navegación (Desktop) -->
          <div class="hidden md:flex items-center space-x-1">
            <router-link
              to="/dashboard"
              class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300"
              :class="isActive('/dashboard') 
                ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 shadow-[0_0_10px_rgba(59,130,246,0.1)]' 
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5'"
            >
              <span class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>Dashboard</span>
              </span>
            </router-link>
            
            <router-link
              to="/flows"
              class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300"
              :class="isActive('/flows')
                ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 shadow-[0_0_10px_rgba(59,130,246,0.1)]'
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5'"
            >
              <span class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span>Proyectos</span>
              </span>
            </router-link>

<!-- 
            <router-link
              to="/clients"
              class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300"
              :class="isActive('/clients')
                ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 shadow-[0_0_10px_rgba(59,130,246,0.1)]'
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5'"
            >
              <span class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span>Clientes</span>
              </span>
            </router-link>
            -->

            <router-link
              v-if="canManageTemplates"
              to="/templates"
              class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300"
              :class="isActive('/templates') 
                ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 shadow-[0_0_10px_rgba(59,130,246,0.1)]' 
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5'"
            >
              <span class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                </svg>
                <span>Plantillas</span>
              </span>
            </router-link>

            <router-link
              to="/reports"
              class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300"
              :class="isActive('/reports') 
                ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 shadow-[0_0_10px_rgba(59,130,246,0.1)]' 
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5'"
            >
              <span class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Reportes</span>
              </span>
            </router-link>
          </div>
        </div>

        <!-- Usuario y acciones (Desktop) -->
        <div class="hidden md:flex items-center space-x-4">
          <!-- Botón Toggle Tema -->
          <button 
            @click="themeStore.toggleTheme" 
            class="p-2 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5 rounded-lg transition-all"
            :title="themeStore.currentTheme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
          >
            <!-- Icono Sol (para mostrar en dark mode) -->
            <svg v-if="themeStore.currentTheme === 'dark'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <!-- Icono Luna (para mostrar en light mode) -->
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
          </button>

          <NotificationCenter />
          
          <!-- Separador -->
          <div class="h-8 w-px bg-white/10"></div>
          
          <!-- Usuario -->
          <div class="flex items-center space-x-3 group cursor-pointer">
             <div class="hidden sm:block text-right">
              <p class="text-sm font-bold text-slate-700 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                {{ authStore.currentUser?.name }}
              </p>
              <p class="text-xs text-slate-500 group-hover:text-slate-400 transition-colors">
                {{ authStore.currentUser?.email }}
              </p>
            </div>
              <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700/50 border border-slate-300 dark:border-white/10 flex items-center justify-center text-slate-700 dark:text-white font-bold text-sm shadow-inner group-hover:bg-blue-600 group-hover:text-white transition-all">
              {{ getInitials(authStore.currentUser?.name) }}
            </div>
          </div>

          <!-- Botón logout -->
          <button
            @click="handleLogout"
            class="p-2 text-slate-500 hover:text-slate-900 dark:text-slate-500 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5 rounded-lg transition-all"
            title="Cerrar sesión"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>

        <!-- Botón de menú móvil -->
        <div class="flex md:hidden items-center space-x-2">
          <!-- Hamburguesa -->
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-all"
          >
            <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Menú móvil -->
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 -translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-1"
      >
        <div v-if="mobileMenuOpen" class="md:hidden py-4 border-t border-white/5 bg-slate-900 absolute top-16 left-0 w-full shadow-2xl">
          <!-- Usuario info -->
          <div class="flex items-center space-x-3 px-6 py-4 mb-2 bg-white/5 mx-4 rounded-xl">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white font-bold shadow-md">
              {{ getInitials(authStore.currentUser?.name) }}
            </div>
            <div>
              <p class="text-sm font-bold text-white">
                {{ authStore.currentUser?.name }}
              </p>
              <p class="text-xs text-slate-400">
                {{ authStore.currentUser?.email }}
              </p>
            </div>
          </div>

          <!-- Links de navegación -->
          <div class="space-y-1 px-4">
            <router-link
              to="/dashboard"
              @click="mobileMenuOpen = false"
              class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition-all"
              :class="isActive('/dashboard') 
                ? 'bg-blue-500/10 text-blue-400' 
                : 'text-slate-400 hover:text-white hover:bg-white/5'"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
              <span>Dashboard</span>
            </router-link>
            
            <router-link
              to="/flows"
              @click="mobileMenuOpen = false"
              class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition-all"
              :class="isActive('/flows') 
                ? 'bg-blue-500/10 text-blue-400' 
                : 'text-slate-400 hover:text-white hover:bg-white/5'"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              <span>Flujos</span>
            </router-link>

<!-- 
            <router-link
              to="/clients"
              @click="mobileMenuOpen = false"
              class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition-all"
              :class="isActive('/clients') 
                ? 'bg-blue-500/10 text-blue-400' 
                : 'text-slate-400 hover:text-white hover:bg-white/5'"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <span>Clientes</span>
            </router-link>
            -->



            <router-link
              v-if="canManageTemplates"
              to="/templates"
              @click="mobileMenuOpen = false"
              class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition-all"
              :class="isActive('/templates') 
                 ? 'bg-blue-500/10 text-blue-400' 
                : 'text-slate-400 hover:text-white hover:bg-white/5'"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
              </svg>
              <span>Plantillas</span>
            </router-link>

             <router-link
              to="/reports"
              @click="mobileMenuOpen = false"
              class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition-all"
              :class="isActive('/reports') 
                ? 'bg-blue-500/10 text-blue-400' 
                : 'text-slate-400 hover:text-white hover:bg-white/5'"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <span>Reportes</span>
            </router-link>
          </div>

          <!-- Botón logout móvil -->
          <div class="px-6 mt-6">
            <button
              @click="handleLogout"
              class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white rounded-xl transition-all font-medium border border-red-500/20"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              <span>Cerrar Sesión</span>
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </nav>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useThemeStore } from '@/stores/theme'
import NotificationCenter from './NotificationCenter.vue'
import { computed } from 'vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const themeStore = useThemeStore()

const mobileMenuOpen = ref(false)

const canManageTemplates = computed(() => {
  const role = authStore.user?.role
  return ['admin', 'project_manager', 'pm'].includes(role)
})

const isActive = (path) => {
  return route.path.startsWith(path)
}

const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const handleLogout = async () => {
  mobileMenuOpen.value = false
  await authStore.logout()
  router.push('/login')
}
</script>