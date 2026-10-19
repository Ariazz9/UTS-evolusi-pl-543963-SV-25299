<template>
  <div class="app-layout">
    <!-- Navbar / Header -->
    <header class="navbar">
      <div class="nav-brand">
        <span class="brand-logo">🏗️</span>
        <div>
          <h2 class="brand-title">TB Bangun Jaya</h2>
          <span class="brand-tagline">Inventaris Toko Bangunan & Material</span>
        </div>
      </div>

      <div class="nav-user">
        <div class="user-info">
          <span class="user-name">{{ currentUser?.name || 'Kasir / Admin' }}</span>
          <span class="user-email">{{ currentUser?.email || 'admin@example.com' }}</span>
        </div>
        <button class="btn btn-logout" @click="handleLogout" :disabled="loggingOut">
          <span>{{ loggingOut ? 'Keluar...' : 'Keluar' }}</span>
        </button>
      </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon bg-blue">📦</div>
          <div>
            <div class="stat-value">{{ materials.length }}</div>
            <div class="stat-label">Jenis Material</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-green">📊</div>
          <div>
            <div class="stat-value">{{ totalStock }}</div>
            <div class="stat-label">Total Unit Fisik</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-purple">💰</div>
          <div>
            <div class="stat-value">{{ formatRupiah(totalAssetValue) }}</div>
            <div class="stat-label">Nilai Aset Stok</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-amber">🏷️</div>
          <div>
            <div class="stat-value">{{ uniqueCategoriesCount }}</div>
            <div class="stat-label">Kategori Produk</div>
          </div>
        </div>
      </div>

      <!-- Action & Filter Bar -->
      <div class="action-bar">
        <div class="search-filter">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari nama material / spesifikasi..."
            />
          </div>

          <select v-model="selectedCategory" class="filter-select">
            <option value="">Semua Kategori</option>
            <option v-for="cat in availableCategories" :key="cat" :value="cat">
              {{ cat }}
            </option>
          </select>
        </div>

        <button class="btn btn-add" @click="openCreateModal">
          <span>+ Tambah Material</span>
        </button>
      </div>

      <!-- Notification Toast / Alert -->
      <div v-if="toastMessage" :class="['toast-banner', `toast-${toastType}`]">
        <span>{{ toastMessage }}</span>
        <button class="toast-close" @click="toastMessage = ''">×</button>
      </div>

      <!-- Data Table Card -->
      <div class="table-card">
        <div v-if="loading" class="loading-state">
          <div class="spinner-large"></div>
          <p>Memuat data bahan bangunan dari API...</p>
        </div>

        <div v-else-if="filteredMaterials.length === 0" class="empty-state">
          <div class="empty-icon">📋</div>
          <h3>Tidak Ada Data Material</h3>
          <p v-if="searchQuery || selectedCategory">
            Tidak ditemukan data yang cocok dengan kriteria pencarian Anda.
          </p>
          <p v-else>
            Belum ada bahan bangunan yang terdaftar di basis data. Klik tombol di atas untuk menambah data.
          </p>
        </div>

        <div v-else class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th style="width: 50px;">No</th>
                <th>Nama Material</th>
                <th>Kategori</th>
                <th>Satuan</th>
                <th>Harga Satuan</th>
                <th>Stok</th>
                <th>Deskripsi & Spesifikasi</th>
                <th style="width: 140px; text-align: center;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in filteredMaterials" :key="item.id">
                <td class="text-center font-mono">{{ index + 1 }}</td>
                <td class="font-bold text-dark">{{ item.name }}</td>
                <td>
                  <span :class="['badge', getCategoryBadgeClass(item.category)]">
                    {{ item.category }}
                  </span>
                </td>
                <td>
                  <span class="unit-pill">{{ item.unit || 'Pcs' }}</span>
                </td>
                <td class="font-mono text-dark">{{ formatRupiah(item.price) }}</td>
                <td>
                  <span :class="['stock-tag', item.stock <= 10 ? 'stock-low' : 'stock-ok']">
                    {{ item.stock }} {{ item.unit || 'Pcs' }}
                  </span>
                </td>
                <td class="text-muted text-sm">{{ item.description || '-' }}</td>
                <td class="text-center">
                  <div class="action-buttons">
                    <button
                      class="btn-action btn-edit"
                      @click="openEditModal(item)"
                      title="Ubah Material"
                    >
                      ✏️ Edit
                    </button>
                    <button
                      class="btn-action btn-delete"
                      @click="confirmDelete(item)"
                      title="Hapus Material"
                    >
                      🗑️ Hapus
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- Modal Form Tambah / Edit -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-card">
        <div class="modal-header">
          <h3>{{ isEditing ? 'Ubah Data Bahan Bangunan' : 'Tambah Bahan Bangunan Baru' }}</h3>
          <button class="modal-close" @click="closeModal">×</button>
        </div>

        <div v-if="formErrors.general" class="alert alert-error">
          <span>{{ formErrors.general }}</span>
        </div>

        <form @submit.prevent="handleSave">
          <div class="modal-body">
            <div class="form-group">
              <label for="form-name">Nama Material <span class="required">*</span></label>
              <input
                id="form-name"
                v-model="formData.name"
                type="text"
                placeholder="Misal: Semen Tiga Roda 50kg"
                required
              />
              <span v-if="formErrors.name" class="field-error">{{ formErrors.name[0] }}</span>
            </div>

            <div class="form-row">
              <div class="form-group col-6">
                <label for="form-category">Kategori <span class="required">*</span></label>
                <select id="form-category" v-model="formData.category" required>
                  <option value="" disabled>Pilih Kategori</option>
                  <option v-for="cat in availableCategories" :key="cat" :value="cat">
                    {{ cat }}
                  </option>
                </select>
                <span v-if="formErrors.category" class="field-error">{{ formErrors.category[0] }}</span>
              </div>

              <div class="form-group col-6">
                <label for="form-unit">Satuan Barang <span class="required">*</span></label>
                <select id="form-unit" v-model="formData.unit" required>
                  <option v-for="u in availableUnits" :key="u" :value="u">
                    {{ u }}
                  </option>
                </select>
                <span v-if="formErrors.unit" class="field-error">{{ formErrors.unit[0] }}</span>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-6">
                <label for="form-price">Harga Satuan (Rp) <span class="required">*</span></label>
                <input
                  id="form-price"
                  v-model.number="formData.price"
                  type="number"
                  min="0"
                  step="500"
                  placeholder="0"
                  required
                />
                <span v-if="formErrors.price" class="field-error">{{ formErrors.price[0] }}</span>
              </div>

              <div class="form-group col-6">
                <label for="form-stock">Jumlah Stok <span class="required">*</span></label>
                <input
                  id="form-stock"
                  v-model.number="formData.stock"
                  type="number"
                  min="0"
                  step="1"
                  placeholder="0"
                  required
                />
                <span v-if="formErrors.stock" class="field-error">{{ formErrors.stock[0] }}</span>
              </div>
            </div>

            <div class="form-group">
              <label for="form-desc">Deskripsi & Spesifikasi Teknis</label>
              <textarea
                id="form-desc"
                v-model="formData.description"
                rows="3"
                placeholder="Catatan spesifikasi, dimensi ukuran, merek, atau lokasi rak gudang..."
              ></textarea>
              <span v-if="formErrors.description" class="field-error">{{ formErrors.description[0] }}</span>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModal" :disabled="saving">
              Batal
            </button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span v-if="saving" class="spinner"></span>
              <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Material') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div v-if="showDeleteModal" class="modal-backdrop" @click.self="showDeleteModal = false">
      <div class="modal-card modal-sm">
        <div class="modal-header">
          <h3>Konfirmasi Hapus</h3>
          <button class="modal-close" @click="showDeleteModal = false">×</button>
        </div>
        <div class="modal-body text-center">
          <div class="delete-icon">⚠️</div>
          <p>Apakah Anda yakin ingin menghapus data material:</p>
          <p class="font-bold text-dark">{{ itemToDelete?.name }}</p>
          <p class="text-muted text-sm">Data yang dihapus dari database tidak dapat dikembalikan.</p>
        </div>
        <div class="modal-footer justify-center">
          <button class="btn btn-secondary" @click="showDeleteModal = false" :disabled="deleting">
            Batal
          </button>
          <button class="btn btn-danger" @click="handleDelete" :disabled="deleting">
            <span v-if="deleting" class="spinner"></span>
            <span>{{ deleting ? 'Menghapus...' : 'Ya, Hapus Data' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import authService from '../services/auth'
import materialService from '../services/material'

const router = useRouter()

const currentUser = ref(authService.getUser())
const materials = ref([])
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const loggingOut = ref(false)

const searchQuery = ref('')
const selectedCategory = ref('')

const toastMessage = ref('')
const toastType = ref('success')

const showModal = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

const showDeleteModal = ref(false)
const itemToDelete = ref(null)

const availableCategories = [
  'Semen & Mortar',
  'Besi & Baja',
  'Cat & Pelapis',
  'Pipa & Plumbing',
  'Keramik & Granit',
  'Kayu & Triplek',
  'Alat & Perkakas',
  'Lainnya'
]

const availableUnits = ['Sak', 'Batang', 'Pail', 'Kaleng', 'Dus', 'Lembar', 'Pcs', 'Meter', 'Roll']

const initialFormData = {
  name: '',
  category: 'Semen & Mortar',
  unit: 'Sak',
  price: 0,
  stock: 0,
  description: ''
}

const formData = ref({ ...initialFormData })
const formErrors = ref({})

const fetchMaterials = async () => {
  loading.value = true
  try {
    materials.value = await materialService.getAll()
  } catch (error) {
    showToast('Gagal memuat data dari server backend.', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchMaterials()
})

const filteredMaterials = computed(() => {
  return materials.value.filter((item) => {
    const matchesSearch =
      !searchQuery.value ||
      item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (item.description && item.description.toLowerCase().includes(searchQuery.value.toLowerCase()))

    const matchesCategory =
      !selectedCategory.value || item.category === selectedCategory.value

    return matchesSearch && matchesCategory
  })
})

const totalStock = computed(() => {
  return materials.value.reduce((acc, curr) => acc + (Number(curr.stock) || 0), 0)
})

const totalAssetValue = computed(() => {
  return materials.value.reduce(
    (acc, curr) => acc + (Number(curr.price) || 0) * (Number(curr.stock) || 0),
    0
  )
})

const uniqueCategoriesCount = computed(() => {
  const set = new Set(materials.value.map((m) => m.category))
  return set.size
})

const formatRupiah = (number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(number || 0)
}

const getCategoryBadgeClass = (category) => {
  switch (category) {
    case 'Semen & Mortar':
      return 'badge-blue'
    case 'Besi & Baja':
      return 'badge-gray'
    case 'Cat & Pelapis':
      return 'badge-purple'
    case 'Pipa & Plumbing':
      return 'badge-cyan'
    case 'Keramik & Granit':
      return 'badge-amber'
    case 'Kayu & Triplek':
      return 'badge-orange'
    default:
      return 'badge-green'
  }
}

const showToast = (message, type = 'success') => {
  toastMessage.value = message
  toastType.value = type
  setTimeout(() => {
    if (toastMessage.value === message) {
      toastMessage.value = ''
    }
  }, 4000)
}

const openCreateModal = () => {
  isEditing.value = false
  editingId.value = null
  formData.value = { ...initialFormData }
  formErrors.value = {}
  showModal.value = true
}

const openEditModal = (item) => {
  isEditing.value = true
  editingId.value = item.id
  formData.value = {
    name: item.name,
    category: item.category,
    unit: item.unit || 'Pcs',
    price: Number(item.price),
    stock: Number(item.stock),
    description: item.description || ''
  }
  formErrors.value = {}
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  formErrors.value = {}
}

const handleSave = async () => {
  saving.value = true
  formErrors.value = {}

  try {
    if (isEditing.value) {
      await materialService.update(editingId.value, formData.value)
      showToast('Bahan bangunan berhasil diperbarui!')
    } else {
      await materialService.create(formData.value)
      showToast('Bahan bangunan baru berhasil ditambahkan!')
    }
    closeModal()
    await fetchMaterials()
  } catch (error) {
    if (error.response && error.response.status === 422) {
      formErrors.value = error.response.data.errors || {}
    } else {
      formErrors.value = {
        general: error.response?.data?.message || 'Gagal menyimpan data.'
      }
    }
  } finally {
    saving.value = false
  }
}

const confirmDelete = (item) => {
  itemToDelete.value = item
  showDeleteModal.value = true
}

const handleDelete = async () => {
  if (!itemToDelete.value) return
  deleting.value = true

  try {
    await materialService.delete(itemToDelete.value.id)
    showToast(`Material "${itemToDelete.value.name}" berhasil dihapus.`)
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchMaterials()
  } catch (error) {
    showToast('Gagal menghapus data material.', 'error')
  } finally {
    deleting.value = false
  }
}

const handleLogout = async () => {
  loggingOut.value = true
  try {
    await authService.logout()
    router.push({ name: 'login' })
  } finally {
    loggingOut.value = false
  }
}
</script>

<style scoped>
.app-layout {
  min-height: 100vh;
  background-color: #f1f5f9;
  color: #1e293b;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

/* Navbar */
.navbar {
  background-color: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  padding: 14px 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.brand-logo {
  font-size: 32px;
}

.brand-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
}

.brand-tagline {
  font-size: 11px;
  color: #64748b;
}

.nav-user {
  display: flex;
  align-items: center;
  gap: 16px;
}

.user-info {
  text-align: right;
}

.user-name {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
}

.user-email {
  display: block;
  font-size: 11px;
  color: #64748b;
}

.btn-logout {
  background-color: #f1f5f9;
  color: #475569;
  border: 1px solid #cbd5e1;
  padding: 7px 14px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-logout:hover:not(:disabled) {
  background-color: #fee2e2;
  color: #dc2626;
  border-color: #fca5a5;
}

/* Main */
.main-content {
  max-width: 1280px;
  margin: 0 auto;
  padding: 24px 20px;
}

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background-color: #ffffff;
  padding: 18px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.bg-blue { background-color: #eff6ff; }
.bg-green { background-color: #ecfdf5; }
.bg-purple { background-color: #f5f3ff; }
.bg-amber { background-color: #fffbeb; }

.stat-value {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
}

.stat-label {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
}

/* Action Bar */
.action-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 18px;
}

.search-filter {
  display: flex;
  gap: 12px;
  flex: 1;
  max-width: 600px;
}

.search-box {
  position: relative;
  flex: 1;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 14px;
}

.search-box input {
  width: 100%;
  padding: 10px 12px 10px 36px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13px;
  background-color: #ffffff;
  box-sizing: border-box;
}

.search-box input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.filter-select {
  padding: 10px 14px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13px;
  background-color: #ffffff;
  color: #334155;
  cursor: pointer;
}

.btn-add {
  background-color: #2563eb;
  color: #ffffff;
  border: none;
  padding: 10px 18px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
  transition: all 0.2s;
}

.btn-add:hover {
  background-color: #1d4ed8;
}

/* Toast */
.toast-banner {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
  font-weight: 500;
}

.toast-success {
  background-color: #ecfdf5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.toast-error {
  background-color: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.toast-close {
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  color: inherit;
  line-height: 1;
}

/* Table Card */
.table-card {
  background-color: #ffffff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.table-responsive {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 13px;
}

.data-table th {
  background-color: #f8fafc;
  padding: 12px 16px;
  font-weight: 600;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 0.04em;
}

.data-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.data-table tr:hover {
  background-color: #f8fafc;
}

.font-mono { font-family: monospace; }
.font-bold { font-weight: 600; }
.text-dark { color: #0f172a; }
.text-muted { color: #64748b; }
.text-sm { font-size: 12px; }
.text-center { text-align: center; }

/* Badges */
.badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
}

.badge-blue { background-color: #dbeafe; color: #1e40af; }
.badge-gray { background-color: #e2e8f0; color: #334155; }
.badge-purple { background-color: #ede9fe; color: #5b21b6; }
.badge-cyan { background-color: #cffafe; color: #155e75; }
.badge-amber { background-color: #fef3c7; color: #92400e; }
.badge-orange { background-color: #ffedd5; color: #9a3412; }
.badge-green { background-color: #d1fae5; color: #065f46; }

.unit-pill {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 12px;
  background-color: #f1f5f9;
  color: #475569;
  font-size: 11px;
  font-weight: 500;
}

.stock-tag {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 12px;
}

.stock-ok { background-color: #ecfdf5; color: #059669; }
.stock-low { background-color: #fef2f2; color: #dc2626; }

/* Actions */
.action-buttons {
  display: flex;
  gap: 6px;
  justify-content: center;
}

.btn-action {
  padding: 5px 9px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all 0.15s;
}

.btn-edit {
  background-color: #f0fdf4;
  color: #16a34a;
  border-color: #bbf7d0;
}

.btn-edit:hover {
  background-color: #dcfce7;
}

.btn-delete {
  background-color: #fef2f2;
  color: #dc2626;
  border-color: #fecaca;
}

.btn-delete:hover {
  background-color: #fee2e2;
}

/* States */
.loading-state,
.empty-state {
  text-align: center;
  padding: 48px 20px;
  color: #64748b;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 12px;
}

.spinner-large {
  width: 36px;
  height: 36px;
  border: 3px solid #cbd5e1;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 16px auto;
}

/* Modals */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(15, 23, 42, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-card {
  background-color: #ffffff;
  border-radius: 14px;
  width: 100%;
  max-width: 580px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
  overflow: hidden;
  animation: modalIn 0.2s ease-out;
}

.modal-sm {
  max-width: 420px;
}

@keyframes modalIn {
  from {
    opacity: 0;
    transform: scale(0.96);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.modal-header {
  padding: 18px 22px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  font-size: 16px;
  color: #0f172a;
  font-weight: 700;
}

.modal-close {
  background: none;
  border: none;
  font-size: 22px;
  cursor: pointer;
  color: #94a3b8;
  line-height: 1;
}

.modal-close:hover {
  color: #1e293b;
}

.modal-body {
  padding: 20px 22px;
}

.modal-footer {
  padding: 14px 22px;
  border-top: 1px solid #e2e8f0;
  background-color: #f8fafc;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.justify-center {
  justify-content: center;
}

.delete-icon {
  font-size: 42px;
  margin-bottom: 10px;
}

/* Form inputs */
.form-group {
  margin-bottom: 14px;
  text-align: left;
}

.form-group label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 5px;
}

.required {
  color: #dc2626;
}

.form-row {
  display: flex;
  gap: 12px;
}

.col-6 {
  flex: 1;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 9px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 13px;
  color: #1e293b;
  background-color: #ffffff;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}

.field-error {
  display: block;
  font-size: 11px;
  color: #dc2626;
  margin-top: 4px;
}

/* Buttons */
.btn {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.15s;
}

.btn-secondary {
  background-color: #f1f5f9;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #e2e8f0;
}

.btn-primary {
  background-color: #2563eb;
  color: #ffffff;
  border: none;
}

.btn-primary:hover:not(:disabled) {
  background-color: #1d4ed8;
}

.btn-danger {
  background-color: #dc2626;
  color: #ffffff;
  border: none;
}

.btn-danger:hover:not(:disabled) {
  background-color: #b91c1c;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner {
  width: 14px;
  height: 14px;
  border: 2px solid #ffffff;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>

