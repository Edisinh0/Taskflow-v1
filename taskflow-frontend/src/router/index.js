import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/login'
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/DashboardView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/flows',
      name: 'flows',
      component: () => import('../views/FlowsView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/flows/:id',
      name: 'flow-detail',
      component: () => import('../views/FlowDetailView.vue'),
      meta: { requiresAuth: true }
    },

    {
      path: '/templates',
      name: 'templates',
      component: () => import('../views/TemplatesView.vue'),
      meta: { requiresAuth: true, roles: ['admin', 'project_manager', 'pm'] }
    },
    {
      path: '/notifications',
      name: 'notifications',
      component: () => import('../views/NotificationsView.vue'),
      meta: { requiresAuth: true }
    },
    /*
        {
          path: '/clients',
          name: 'clients',
          component: () => import('../views/ClientsView.vue'),
          meta: { requiresAuth: true }
        },
        {
          path: '/clients/:id',
          name: 'client-detail',
          component: () => import('../views/ClientDetailView.vue'),
          meta: { requiresAuth: true }
        },
    */
    {
      path: '/reports',
      name: 'reports',
      component: () => import('../views/ReportsView.vue'),
      meta: { requiresAuth: true }
    },
    // === MÓDULOS DE NEGOCIO ===
    {
      path: '/flow-builder',
      name: 'flow-builder',
      component: () => import('@/modules/flow-builder/views/FlowBuilderView.vue'),
      meta: { requiresAuth: true, roles: ['admin', 'project_manager', 'pm', 'user'] }
    }
  ]
})

// Guard de navegación
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  // Cargar datos del localStorage si existen
  if (!authStore.token) {
    authStore.loadFromStorage()
  }

  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresGuest = to.matched.some(record => record.meta.requiresGuest)
  const requiredRoles = to.meta.roles

  if (requiresAuth && !authStore.isAuthenticated) {
    // Ruta protegida y no autenticado → ir a login
    next('/login')
  } else if (requiresGuest && authStore.isAuthenticated) {
    // Ruta de invitado y autenticado → Redirigir según rol
    const role = authStore.user?.role
    if (['admin', 'project_manager', 'pm'].includes(role)) {
      next('/dashboard')
    } else {
      next('/flows')
    }
  } else if (requiredRoles && authStore.isAuthenticated) {
    // Verificar roles
    const userRole = authStore.user?.role
    if (requiredRoles.includes(userRole)) {
      next()
    } else {
      // Rol no autorizado -> Redirigir a Flows (vista segura por defecto)
      next('/flows')
    }
  } else {
    next()
  }
})

export default router