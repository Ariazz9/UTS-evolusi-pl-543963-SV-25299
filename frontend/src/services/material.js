import api from './api'

export const materialService = {
  /**
   * Menampilkan semua data material toko bangunan (GET /api/materials).
   */
  async getAll() {
    const response = await api.get('/materials')
    return response.data.data || []
  },

  /**
   * Menampilkan detail satu material toko bangunan (GET /api/materials/:id).
   */
  async getById(id) {
    const response = await api.get(`/materials/${id}`)
    return response.data.data
  },

  /**
   * Menambahkan data material baru (POST /api/materials).
   */
  async create(payload) {
    const response = await api.post('/materials', payload)
    return response.data
  },

  /**
   * Mengubah data material (PUT /api/materials/:id).
   */
  async update(id, payload) {
    const response = await api.put(`/materials/${id}`, payload)
    return response.data
  },

  /**
   * Menghapus data material (DELETE /api/materials/:id).
   */
  async delete(id) {
    const response = await api.delete(`/materials/${id}`)
    return response.data
  }
}

export default materialService

