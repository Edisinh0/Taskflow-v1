<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-900 relative overflow-hidden px-4">
    <!-- Background Glows -->
    <div class="absolute top-0 left-0 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none -translate-x-1/2 -translate-y-1/2"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl pointer-events-none translate-x-1/2 translate-y-1/2"></div>

    <div class="bg-slate-800/50 backdrop-blur-xl p-8 rounded-3xl shadow-2xl w-full max-w-md border border-white/5 relative z-10">
      <!-- Logo/Título -->
      <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-900/20">
          <span class="text-white font-extrabold text-2xl tracking-tighter">TF</span>
        </div>
        <h1 class="text-3xl font-bold text-white mb-2 tracking-tight">
          Bienvenido a TaskFlow
        </h1>
        <p class="text-slate-400">Sistema de Gestión de Tareas</p>
      </div>

      <!-- Formulario -->
      <form @submit.prevent="handleLogin" class="space-y-5">
        <!-- Email -->
        <div>
          <label for="email" class="block text-slate-300 font-medium mb-2 text-sm">
            Correo Electrónico
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
              </svg>
            </div>
            <input
              id="email"
              v-model="credentials.email"
              type="email"
              required
              class="w-full pl-10 pr-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-slate-500 transition-all font-medium"
              placeholder="usuario@taskflow.com"
            />
          </div>
        </div>

        <!-- Password -->
        <div>
          <label for="password" class="block text-slate-300 font-medium mb-2 text-sm">
            Contraseña
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
            <input
              id="password"
              v-model="credentials.password"
              type="password"
              required
              class="w-full pl-10 pr-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-slate-500 transition-all font-medium"
              placeholder="••••••••"
            />
          </div>
        </div>

        <!-- Error message -->
        <div v-if="authStore.error" class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl flex items-start text-sm">
          <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ authStore.error }}</span>
        </div>

        <!-- Botón Login -->
        <button
          type="submit"
          :disabled="authStore.isLoading"
          class="w-full bg-blue-600 hover:bg-blue-500 text-white py-3.5 rounded-xl font-bold transition-all shadow-lg shadow-blue-900/20 disabled:opacity-50 disabled:cursor-not-allowed transform hover:-translate-y-0.5"
        >
          <span v-if="!authStore.isLoading" class="flex items-center justify-center">
            Iniciar Sesión
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
          </span>
          <span v-else class="flex items-center justify-center">
            <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Validando...
          </span>
        </button>
      </form>

      <!-- Credenciales de prueba -->
      <div class="mt-8 p-4 bg-slate-900/50 rounded-xl border border-dashed border-slate-700">
        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-3 flex items-center">
          <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Credenciales Demo
        </p>
        <div class="space-y-1">
          <div class="flex justify-between text-sm">
            <span class="text-slate-400">Usuario:</span>
            <span class="text-slate-200 font-mono">admin@taskflow.com</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-slate-400">Clave:</span>
            <span class="text-slate-200 font-mono">password123</span>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-8 text-center">
        <p class="text-xs text-slate-500">© 2025 TaskFlow • TNA Group</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const credentials = ref({
  email: '',
  password: '',
})

const handleLogin = async () => {
  const result = await authStore.login(credentials.value)
  
  if (result.success) {
    router.push('/dashboard')
  }
}
</script>