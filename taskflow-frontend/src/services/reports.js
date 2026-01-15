import axios from 'axios'

const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api/v1'

// Obtener token del localStorage
const getAuthHeader = () => {
    const token = localStorage.getItem('token')
    return token ? { Authorization: `Bearer ${token}` } : {}
}

export const reportsAPI = {
    /**
     * Obtener reporte con filtros
     */
    async getReport(filters = {}, page = 1, perPage = 50) {
        const response = await axios.get(`${API_URL}/reports`, {
            headers: getAuthHeader(),
            params: {
                ...filters,
                page,
                per_page: perPage
            }
        })
        return response.data
    },

    /**
     * Obtener estadísticas del reporte
     */
    async getStats(filters = {}) {
        const response = await axios.get(`${API_URL}/reports/stats`, {
            headers: getAuthHeader(),
            params: filters
        })
        return response.data
    },

    /**
     * Exportar a CSV
     */
    async exportCsv(filters = {}) {
        const response = await axios.get(`${API_URL}/reports/export/csv`, {
            headers: getAuthHeader(),
            params: filters,
            responseType: 'blob'
        })

        // Crear enlace de descarga
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `reporte_tareas_${new Date().toISOString().split('T')[0]}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
    },

    /**
     * Exportar a PDF
     */
    async exportPdf(filters = {}) {
        const response = await axios.get(`${API_URL}/reports/export/pdf`, {
            headers: getAuthHeader(),
            params: filters,
            responseType: 'blob'
        })

        // Crear enlace de descarga
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `reporte_tareas_${new Date().toISOString().split('T')[0]}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
    }
}
