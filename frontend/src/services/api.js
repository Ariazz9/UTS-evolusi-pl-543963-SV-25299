import axios from 'axios'

/**
 * Axios instance terpusat.
 * Base URL dibaca dari variabel lingkungan VITE_API_BASE_URL (Requirement 2.c).
 */
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

/**
 * Request Interceptor:
 * Menyelipkan Sanctum Bearer Token pada setiap permintaan berikutnya jika token tersedia (Requirement 2.a).
 */
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

/**
 * Response Interceptor:
 * Menangani respon 401 Unauthorized secara otomatis (menghapus token dan mengalihkan ke login).
 */
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

export default api

