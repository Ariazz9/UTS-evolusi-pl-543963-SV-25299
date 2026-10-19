<template>
  <div class="login-container">
    <div class="login-card">
      <div class="brand-header">
        <div class="brand-icon">🏗️</div>
        <h1>TB Bangun Jaya</h1>
        <p class="subtitle">Sistem Manajemen & Inventaris Toko Bangunan</p>
      </div>

      <div v-if="errorMessage" class="alert alert-error">
        <span>⚠️ {{ errorMessage }}</span>
      </div>

      <div v-if="validationErrors.length > 0" class="alert alert-error">
        <ul>
          <li v-for="(err, idx) in validationErrors" :key="idx">{{ err }}</li>
        </ul>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="email">Alamat Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            placeholder="admin@example.com"
            required
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label for="password">Kata Sandi</label>
          <input
            id="password"
            v-model="password"
            type="password"
            placeholder="••••••••"
            required
            :disabled="loading"
          />
        </div>

        <button type="submit" class="btn btn-primary" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          <span>{{ loading ? 'Memproses...' : 'Masuk ke Sistem' }}</span>
        </button>

        <div class="demo-box">
          <p class="demo-title">Akun Default Pengujian:</p>
          <p class="demo-info"><strong>Email:</strong> admin@example.com | <strong>Pass:</strong> password123</p>
          <button
            type="button"
            class="btn btn-outline"
            @click="fillDefaultAccount"
            :disabled="loading"
          >
            Gunakan Akun Default
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import authService from '../services/auth'

const router = useRouter()

const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMessage = ref('')
const validationErrors = ref([])

const fillDefaultAccount = () => {
  email.value = 'admin@example.com'
  password.value = 'password123'
  errorMessage.value = ''
  validationErrors.value = []
}

const handleLogin = async () => {
  loading.value = true
  errorMessage.value = ''
  validationErrors.value = []

  try {
    await authService.login({
      email: email.value,
      password: password.value
    })

    // Berhasil login dan token tersimpan, arahkan ke halaman utama
    router.push({ name: 'materials' })
  } catch (error) {
    if (error.response) {
      if (error.response.status === 401) {
        errorMessage.value = error.response.data.message || 'Email atau kata sandi tidak valid.'
      } else if (error.response.status === 422) {
        const errors = error.response.data.errors || {}
        validationErrors.value = Object.values(errors).flat()
      } else {
        errorMessage.value = 'Terjadi kesalahan pada server. Pastikan backend aktif.'
      }
    } else {
      errorMessage.value = 'Tidak dapat terhubung ke server backend API.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  padding: 20px;
}

.login-card {
  width: 100%;
  max-width: 440px;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
  padding: 36px 32px;
  box-sizing: border-box;
}

.brand-header {
  text-align: center;
  margin-bottom: 24px;
}

.brand-icon {
  font-size: 42px;
  margin-bottom: 8px;
}

.brand-header h1 {
  font-size: 24px;
  color: #0f172a;
  margin: 0 0 6px 0;
  font-weight: 700;
}

.subtitle {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

.alert {
  padding: 12px 14px;
  border-radius: 8px;
  margin-bottom: 18px;
  font-size: 13px;
  line-height: 1.4;
}

.alert-error {
  background-color: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.alert-error ul {
  margin: 0;
  padding-left: 18px;
}

.form-group {
  margin-bottom: 16px;
  text-align: left;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
}

.form-group input {
  width: 100%;
  padding: 11px 14px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 14px;
  box-sizing: border-box;
  transition: border-color 0.2s, box-shadow 0.2s;
  color: #1e293b;
  background-color: #f8fafc;
}

.form-group input:focus {
  outline: none;
  border-color: #2563eb;
  background-color: #ffffff;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.btn {
  width: 100%;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-primary {
  background-color: #2563eb;
  color: #ffffff;
  border: none;
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
}

.btn-primary:hover:not(:disabled) {
  background-color: #1d4ed8;
}

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.demo-box {
  margin-top: 24px;
  padding: 14px;
  background-color: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 10px;
  text-align: center;
}

.demo-title {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  margin: 0 0 4px 0;
}

.demo-info {
  font-size: 12px;
  color: #64748b;
  margin: 0 0 10px 0;
}

.btn-outline {
  background-color: #ffffff;
  color: #2563eb;
  border: 1px solid #2563eb;
  padding: 8px 12px;
  font-size: 12px;
}

.btn-outline:hover:not(:disabled) {
  background-color: #eff6ff;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid #ffffff;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>

