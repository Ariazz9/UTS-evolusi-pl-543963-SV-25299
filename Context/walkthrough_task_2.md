# Walkthrough Pengerjaan Soal Nomor 2 (CPMK1 - Frontend Vue.js)

Dokumen ini berisi dokumentasi lengkap pengerjaan, arsitektur alur kerja, dan perubahan berkas untuk **Soal Nomor 2** berdasarkan [requirement context.md](file:///c:/laragon/www/evolusi-pl-UTS/Context/requirement%20context.md).

---

## 1. Ringkasan Pengerjaan & Tema Sistem

- **Tema Frontend**: **Sistem Manajemen & Inventaris Toko Bangunan (TB Bangun Jaya)**
- **Framework & Tooling**: **Vue 3** (Composition API / `<script setup>`) + **Vite 8**
- **Library Tambahan**: **Vue Router 4** (routing & auth guard) dan **Axios** (HTTP client terpusat)
- **Lokasi Folder**: [`frontend/`](file:///c:/laragon/www/evolusi-pl-UTS/frontend)
- **Terintegrasi dengan Backend**: Memakai seluruh RESTful API Laravel Sanctum dari **Soal Nomor 1**.

---

## 2. Pemenuhan Ketentuan Soal Nomor 2

| Poin | Kebutuhan Soal | Status Implementasi | Keterangan |
| :--- | :--- | :---: | :--- |
| **2.a** | Halaman login yang menyimpan token, lalu mengirimkannya pada setiap permintaan berikutnya. | **Selesai** | Dibuat [`LoginPage.vue`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/src/views/LoginPage.vue). Saat login sukses, Sanctum Token disimpan ke `localStorage`. Request Interceptor pada Axios ([`api.js`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/src/services/api.js)) secara otomatis menyisipkan header `Authorization: Bearer <token>` pada setiap request berikutnya. |
| **2.b** | Halaman untuk menampilkan, menambah, mengubah, dan menghapus data melalui Axios. | **Selesai** | Dibuat [`MaterialsPage.vue`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/src/views/MaterialsPage.vue) dan [`material.js`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/src/services/material.js). Menyediakan tabel data bahan bangunan, filter pencarian & kategori, ringkasan metrik inventaris, modal formulir tambah/ubah dengan validasi 422, serta dialog konfirmasi hapus data. |
| **2.c** | Alamat API tidak ditulis mati di dalam kode komponen, melainkan diatur lewat konfigurasi atau variabel lingkungan. | **Selesai** | Dikonfigurasi berkas [`.env`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/.env) dan [`.env.example`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/.env.example) dengan variabel `VITE_API_BASE_URL`. Axios membaca base URL melalui `import.meta.env.VITE_API_BASE_URL`. |

---

## 3. Arsitektur & Alur Kerja (Workflow) Frontend

```
+-------------------------------------------------------+
|              Browser Client (Vue 3 SPA)               |
+-------------------------------------------------------+
        |                                       ^
   Form Submissions / User Clicks         JSON Response
        v                                       |
+-------------------------------------------------------+
|  Vue Views (LoginPage.vue / MaterialsPage.vue)        |
+-------------------------------------------------------+
        |                                       ^
  Method Calls                             Data Models
        v                                       |
+-------------------------------------------------------+
|  Services Layer (auth.js / material.js)               |
+-------------------------------------------------------+
        |                                       ^
  Axios Request                             Axios Catch
        v                                       |
+-------------------------------------------------------+
|  Centralized Axios Instance (api.js)                  |
|  - Reads: import.meta.env.VITE_API_BASE_URL           |
|  - Request Interceptor: Attach Bearer <token>         |
|  - Response Interceptor: 401 Auto-Logout Handler      |
+-------------------------------------------------------+
        |                                       ^
  HTTP Request (Header: Auth Bearer)      JSON Response (200, 201, 422, 401)
        v                                       |
+-------------------------------------------------------+
|              Laravel Backend RESTful API              |
|              (http://localhost:8000/api)              |
+-------------------------------------------------------+
```

### A. Mekanisme Penyimpanan dan Pengiriman Token (Poin 2.a)
1. Pengguna memasukkan email dan password di `LoginPage.vue`.
2. `authService.login()` memanggil endpoint `POST /api/login`.
3. Token plain text dari Laravel Sanctum disimpan ke `localStorage.setItem('token', token)`.
4. Instance Axios pada `frontend/src/services/api.js` dilengkapi **Request Interceptor**:
   ```javascript
   api.interceptors.request.use((config) => {
     const token = localStorage.getItem('token')
     if (token) {
       config.headers.Authorization = `Bearer ${token}`
     }
     return config
   })
   ```
5. Setiap panggilan CRUD (GET, POST, PUT, DELETE) otomatis membawa token valid tanpa perlu disisipkan manual di komponen.
6. Apabila token kadaluarsa atau tidak valid (HTTP 401), **Response Interceptor** otomatis membersihkan `localStorage` dan mengarahkan pengguna kembali ke `/login`.

### B. Proteksi Navigasi (Navigation Guards)
Pada [`frontend/src/router/index.js`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/src/router/index.js):
- Rute `/materials` diproteksi meta `requiresAuth: true`. Pengguna tanpa token otomatis dialihkan ke `/login`.
- Rute `/login` memiliki meta `guestOnly: true`. Pengguna yang sudah memiliki token otomatis dialihkan langsung ke dashboard `/materials`.

---

## 4. Rincian Berkas yang Dibuat

### A. Konfigurasi Lingkungan & Tooling
1. [`.env`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/.env) & [`.env.example`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/.env.example)
   - Menyimpan `VITE_API_BASE_URL=http://localhost:8000/api` sehingga URL API tidak di-hardcode.
2. [`vite.config.js`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/vite.config.js)
   - Konfigurasi plugin `@vitejs/plugin-vue`, alias `@` menuju folder `src`, serta dev server port `5173`.
3. [`.gitignore`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/.gitignore)
   - Mengabaikan `node_modules/`, `dist/`, dan `.env` lokal, namun tetap melacak `.env.example`.
4. [`backend/config/cors.php`](file:///c:/laragon/www/evolusi-pl-UTS/backend/config/cors.php)
   - Memastikan rute API Laravel mengizinkan komunikasi lintas domain (*Cross-Origin Resource Sharing*) dari aplikasi Vue di port `5173`.

### B. Layer Komunikasi API (Services)
1. [`frontend/src/services/api.js`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/src/services/api.js)
   - Inisialisasi Axios client berbasis `import.meta.env.VITE_API_BASE_URL`.
   - Request Interceptor untuk menyematkan Bearer token.
   - Response Interceptor untuk penanganan global error 401 Unauthorized.
2. [`frontend/src/services/auth.js`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/src/services/auth.js)
   - Abstraksi logika autentikasi: `login()`, `logout()`, `getToken()`, `getUser()`, dan `isAuthenticated()`.
3. [`frontend/src/services/material.js`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/src/services/material.js)
   - Abstraksi logika CRUD bahan bangunan:
     - `getAll()`: `GET /api/materials`
     - `getById(id)`: `GET /api/materials/:id`
     - `create(payload)`: `POST /api/materials` (status 201)
     - `update(id, payload)`: `PUT /api/materials/:id` (status 200)
     - `delete(id)`: `DELETE /api/materials/:id` (status 200)

### C. Halaman Antarmuka (Views) & Komponen
1. [`frontend/src/views/LoginPage.vue`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/src/views/LoginPage.vue)
   - Antarmuka login dengan tema TB Bangun Jaya.
   - Form input email & kata sandi dengan feedback status loading.
   - Tombol shortcut *"Gunakan Akun Default"* (`admin@example.com` / `password123`) untuk kemudahan pengujian.
   - Kotak peringatan error jika kredensial tidak valid (401) atau input kosong (422).
2. [`frontend/src/views/MaterialsPage.vue`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/src/views/MaterialsPage.vue)
   - **Header Navigasi**: Logo toko, identitas pengguna aktif, dan tombol Logout.
   - **Kartu Ringkasan Metrik (KPI)**:
     - Jumlah Jenis Material
     - Total Stok Fisik
     - Estimasi Nilai Aset Stok (terformat Rupiah `Rp xx.xxx.xxx`)
     - Jumlah Kategori Terdaftar
   - **Bilah Aksi & Filter**: Pencarian teks *real-time* (nama & deskripsi) dan dropdown penyaring kategori.
   - **Tabel Data Bahan Bangunan**: Menampilkan nomor, nama barang, badge kategori, satuan, harga satuan, stok (dengan indikator warna stok menipis jika $\le 10$), deskripsi, dan tombol aksi.
   - **Modal Form Tambah / Ubah**: Form input dinamis dengan dropdown kategori & satuan khas toko bangunan, validasi field numerik, dan pemetaan error validasi 422 langsung di bawah input terkait.
   - **Modal Konfirmasi Hapus**: Dialog konfirmasi penghapusan aman untuk mencegah klik tidak sengaja.
   - **Toast Notifikasi**: Notifikasi mengambang saat data berhasil ditambah, diperbarui, atau dihapus.
3. [`frontend/src/App.vue`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/src/App.vue) & [`frontend/src/main.js`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/src/main.js)
   - Mounting router ke instance aplikasi Vue.

---

## 5. Bukti Keberhasilan Build (`npm run build`)

Menjalankan `npm run build` di direktori `frontend`:

```text
> frontend@0.0.0 build
> vite build

vite v8.3.4 building client environment for production...
transforming...
✓ 85 modules transformed.
rendering chunks...
computing gzip size...
dist/index.html                   0.49 kB │ gzip:  0.32 kB
dist/assets/index-SAKQojIC.css   12.32 kB │ gzip:  2.81 kB
dist/assets/index-BWrlqPY6.js   156.68 kB │ gzip: 58.29 kB

✓ built in 189ms
```

> [!NOTE]
> Keberhasilan `npm run build` dan keberadaan berkas `package-lock.json` menjamin bahwa job frontend pada GitHub Actions CI/CD (`npm ci && npm run build` di **Soal Nomor 3.c**) akan berjalan hijau tanpa kendala dependensi.

---

## 6. Petunjuk Menjalankan & Menguji Sistem Secara Lokal

### Langkah 1: Jalankan Backend Laravel
Buka terminal pertama:
```powershell
cd c:\laragon\www\evolusi-pl-UTS\backend
php artisan serve
# Server aktif di http://127.0.0.1:8000
```

### Langkah 2: Jalankan Frontend Vue
Buka terminal kedua:
```powershell
cd c:\laragon\www\evolusi-pl-UTS\frontend
npm run dev
# Aplikasi aktif di http://localhost:5173
```

### Langkah 3: Pengujian di Browser
1. Buka browser dan akses `http://localhost:5173`.
2. Anda akan otomatis dialihkan ke halaman `/login`.
3. Klik tombol **"Gunakan Akun Default"** atau ketik email `admin@example.com` dan password `password123`.
4. Klik **"Masuk ke Sistem"**.
5. Setelah login berhasil, Anda akan masuk ke dashboard bahan bangunan:
   - Data awal dari database seeder akan tampil di tabel.
   - Uji penambahan data baru dengan mengklik **"+ Tambah Material"**.
   - Uji pengubahan data dengan mengklik tombol **"✏️ Edit"**.
   - Uji penghapusan data dengan mengklik tombol **"🗑️ Hapus"**.
6. Uji proses logout dengan mengklik tombol **"Keluar"** di pojok kanan atas.

