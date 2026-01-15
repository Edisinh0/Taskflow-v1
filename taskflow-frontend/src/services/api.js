import axios from 'axios'

// Configuración base de Axios
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true, // Para cookies de sesión
})

// Interceptor para agregar el token automáticamente
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    // Si los datos son FormData, no establecer Content-Type
    // Axios lo hará automáticamente con el boundary correcto
    if (config.data instanceof FormData) {
      delete config.headers['Content-Type']
    }

    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Interceptor para manejar errores de autenticación
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      // Token inválido o expirado
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// ========== AUTH ==========
export const authAPI = {
  login: (credentials) => api.post('/auth/login', credentials),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
  register: (userData) => api.post('/auth/register', userData),
}

// ========== TEMPLATES ==========
export const templatesAPI = {
  getAll: () => api.get('/templates'),
  getOne: (id) => api.get(`/templates/${id}`),
  create: (data) => api.post('/templates', data),
  createFromFlow: (flowId, data) => api.post(`/templates/from-flow/${flowId}`, data),
  update: (id, data) => api.put(`/templates/${id}`, data),
  delete: (id) => api.delete(`/templates/${id}`),
}

// ========== FLOWS ==========
export const flowsAPI = {
  getAll: (params) => api.get('/flows', { params }),
  getOne: (id) => api.get(`/flows/${id}`),
  create: (data) => api.post('/flows', data),
  update: (id, data) => api.put(`/flows/${id}`, data),
  delete: (id) => api.delete(`/flows/${id}`),
}

// ========== TASKS ==========
export const tasksAPI = {
  getAll: (params) => api.get('/tasks', { params }),
  getOne: (id) => api.get(`/tasks/${id}`),
  create: (data) => api.post('/tasks', data),
  update: (id, data) => api.put(`/tasks/${id}`, data),
  delete: (id) => api.delete(`/tasks/${id}`),
  uploadAttachment: (taskId, formData, onUploadProgress) => api.post(`/tasks/${taskId}/attachments`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
    onUploadProgress
  }),
  deleteAttachment: (fileId) => api.delete(`/attachments/${fileId}`),
  deleteAttachment: (fileId) => api.delete(`/attachments/${fileId}`),
}

// ========== USERS ==========
export const usersAPI = {
  getAll: () => api.get('/users'),
  getOne: (id) => api.get(`/users/${id}`),
  create: (data) => api.post('/users', data),
  update: (id, data) => api.put(`/users/${id}`, data),
  delete: (id) => api.delete(`/users/${id}`),
}

// ========== FLOW BUILDER MODULE (PM/Admin) ==========
export const flowBuilderAPI = {
  createFlow: (data) => api.post('/flow-builder/flows', data),
  updateFlow: (id, data) => api.put(`/flow-builder/flows/${id}`, data),
  deleteFlow: (id) => api.delete(`/flow-builder/flows/${id}`),

  createTask: (data) => api.post('/flow-builder/tasks', data),
  updateTaskStructure: (id, data) => api.put(`/flow-builder/tasks/${id}`, data),
  deleteTask: (id) => api.delete(`/flow-builder/tasks/${id}`),
  configureDependencies: (id, data) => api.put(`/flow-builder/tasks/${id}/dependencies`, data),
}

export default api