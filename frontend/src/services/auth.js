import api from './api'

export const authService = {
  /**
   * Melakukan login ke backend Laravel Sanctum dan menyimpan token ke localStorage.
   */
  async login(credentials) {
    const response = await api.post('/login', credentials)
    if (response.data && response.data.token) {
      localStorage.setItem('token', response.data.token)
      if (response.data.user) {
        localStorage.setItem('user', JSON.stringify(response.data.user))
      }
    }
    return response.data
  },

  /**
   * Melakukan logout ke backend dan menghapus token dari localStorage.
   */
  async logout() {
    try {
      await api.post('/logout')
    } catch (error) {
      console.warn('Logout API request warning:', error)
    } finally {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  },

  /**
   * Mengambil token dari localStorage.
   */
  getToken() {
    return localStorage.getItem('token')
  },

  /**
   * Mengambil profil user yang sedang login dari localStorage.
   */
  getUser() {
    const user = localStorage.getItem('user')
    try {
      return user ? JSON.parse(user) : null
    } catch {
      return null
    }
  },

  /**
   * Memeriksa apakah user sedang terautentikasi (memiliki token).
   */
  isAuthenticated() {
    return !!localStorage.getItem('token')
  }
}

export default authService

