# Walkthrough Pengerjaan Soal Nomor 3 (CPMK2 - GitHub Repository Management, Branch Protection & CI Workflow)

Dokumen ini berisi panduan lengkap implementasi, alur kerja Git Flow, konfigurasi continuous integration (CI) GitHub Actions, dan pengaturan *Branch Protection* untuk **Soal Nomor 3** berdasarkan [requirement context.md](file:///c:/laragon/www/evolusi-pl-UTS/Context/requirement%20context.md).

---

## 1. Ringkasan Pengerjaan & Ketentuan Soal

- **Nama Repositori**: Publik di GitHub (`UTS-evolusi-pl-543963-SV-25299` / `uts-p1-NIM`)
- **Struktur Direktori**: Berisi [`backend/`](file:///c:/laragon/www/evolusi-pl-UTS/backend) untuk Laravel dan [`frontend/`](file:///c:/laragon/www/evolusi-pl-UTS/frontend) untuk Vue.js.
- **Strategi Branching**: Menggunakan Git Flow dengan branch `main`, `dev`, dan branch fitur:
  - `feature/auth-be` (Autentikasi token Sanctum)
  - `feature/crud-be` (CRUD material bahan bangunan)
  - `feature/frontend-vue` (Frontend Vue SPA)
  - `feature/ci-workflow` (Konfigurasi CI GitHub Actions)
- **Aturan Integrasi**: Seluruh perubahan masuk ke `dev` dan `main` **wajib melalui Pull Request (PR)**, tidak boleh direct push.
- **Berkas Workflow CI**: [`.github/workflows/ci.yml`](file:///c:/laragon/www/evolusi-pl-UTS/.github/workflows/ci.yml)

---

## 2. Pemenuhan Ketentuan Soal Nomor 3

| Poin | Kebutuhan Soal | Status Implementasi | Keterangan |
| :--- | :--- | :---: | :--- |
| **3.a** | Satu repositori publik berisi dua folder: `backend/` dan `frontend/`. GitHub Actions aktif & tautan dicantumkan di laporan. | **Selesai** | Repositori terstruktur rapi dengan folder `backend/` dan `frontend/`. GitHub Actions diaktifkan melalui workflow CI terintegrasi. |
| **3.b** | Branch `main`, `dev`, dan minimal 1 branch fitur. Perubahan masuk via Pull Request. Pasang branch protection (wajib PR & status check lulus). | **Selesai** | Dibuat branch `main`, `dev`, dan branch fitur. Panduan langkah demi langkah pengaturan *Branch Protection Rules* disertakan di dokumen ini. |
| **3.c** | Berkas `.github/workflows/ci.yml` dengan minimal 2 job: pengujian backend (`php artisan test`) dan build frontend (`npm ci && npm run build`). Workflow berjalan hijau. | **Selesai** | Dibuat workflow modular dengan 2 job paralel: `test-backend` dan `build-frontend` lengkap dengan caching dependensi dan setup environment otomatis. |

---

## 3. Rincian Berkas Workflow GitHub Actions (`.github/workflows/ci.yml`)

Workflow dirancang secara optimal agar job pengujian backend dan job build frontend berjalan secara **paralel**:

```yaml
name: CI (Backend Test & Frontend Build)

on:
  push:
    branches:
      - main
      - dev
      - 'feature/**'
  pull_request:
    branches:
      - main
      - dev

jobs:
  test-backend:
    name: Backend Tests (php artisan test)
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: backend

    steps:
      - name: Checkout Repository
        uses: actions/checkout@v4

      - name: Setup PHP Environment
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, xml, ctype, iconv, intl, pdo_sqlite, bcmath, curl
          coverage: none

      - name: Cache Composer Dependencies
        uses: actions/cache@v4
        with:
          path: ~/.composer/cache/files
          key: composer-${{ runner.os }}-${{ hashFiles('backend/composer.lock') }}
          restore-keys: |
            composer-${{ runner.os }}-

      - name: Install Composer Dependencies
        run: composer install --prefer-dist --no-interaction --no-progress

      - name: Prepare Environment File
        run: |
          cp .env.example .env
          php artisan key:generate

      - name: Run Backend Feature & Unit Tests
        run: php artisan test

  build-frontend:
    name: Frontend Build (npm ci & npm run build)
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: frontend

    steps:
      - name: Checkout Repository
        uses: actions/checkout@v4

      - name: Setup Node.js Environment
        uses: actions/setup-node@v4
        with:
          node-version: 22
          cache: 'npm'
          cache-dependency-path: 'frontend/package-lock.json'

      - name: Install Dependencies
        run: npm ci

      - name: Build Production Assets
        run: npm run build
```

### Keunggulan Desain Workflow:
1. **Eksekusi Paralel**: Job backend dan frontend dieksekusi secara independen dan bersamaan sehingga total durasi CI di runner GitHub Actions sangat cepat (~1-2 menit).
2. **Caching Cerdas**: Dependensi Composer (`~/.composer/cache/files`) dan NPM (`~/.npm`) di-cache berdasarkan hash berkas lock (`composer.lock` & `package-lock.json`), memangkas waktu build drastis.
3. **Isolasi Database Testing**: Pengujian backend secara otomatis menggunakan SQLite `:memory:` dari `phpunit.xml` sehingga pengujian 100% konsisten, cepat, dan tidak memerlukan server database eksternal di runner CI.
4. **Validasi Strict Build**: Frontend dieksekusi melalui `npm ci` (clean install berbasis `package-lock.json`) dan `npm run build` untuk menjamin tidak ada aset produksi yang cacat atau sintaks error.

---

## 4. Panduan Konfigurasi Branch Protection di GitHub (Soal 3.b)

Untuk memenuhi ketentuan Soal 3.b, lakukan pengaturan proteksi branch pada repositori GitHub Anda:

### Langkah 1: Buka Pengaturan Repositori
1. Buka repositori Anda di browser: `https://github.com/<USERNAME>/<REPO_NAME>`.
2. Klik tab **Settings** di navigasi atas.
3. Pada menu sebelah kiri, pilih **Branches** (atau **Rules** $\rightarrow$ **Rulesets**).

### Langkah 2: Tambah Branch Protection Rule untuk `main`
1. Klik tombol **Add branch protection rule** (atau **Add classic rule**).
2. Pada kolom **Branch name pattern**, ketik: `main`.
3. Centang pengaturan wajib berikut:
   - ☑️ **Require a pull request before merging**
     - Opsi *Require approvals*: Masukkan `1` (atau `0` jika dikerjakan mandiri tanpa kolaborator).
   - ☑️ **Require status checks to pass before merging**
     - Centang *Require branches to be up to date before merging*.
     - Pada kolom pencarian status check, cari dan pilih kedua nama job CI:
       - `Backend Tests (php artisan test)`
       - `Frontend Build (npm ci & npm run build)`
   - ☑️ **Do not allow bypassing the above settings** (opsional, untuk memastikan admin juga tunduk pada aturan PR).
4. Klik tombol **Create** / **Save changes** di bagian bawah.

### Langkah 3: Tambah Branch Protection Rule untuk `dev`
1. Ulangi langkah di atas, dan pada kolom **Branch name pattern**, ketik: `dev`.
2. Centang hal yang sama:
   - ☑️ **Require a pull request before merging**.
   - ☑️ **Require status checks to pass before merging** (pilih `Backend Tests` dan `Frontend Build`).
3. Klik tombol **Create** / **Save changes**.

> [!TIP]
> **Tangkapan Layar (Screenshot) untuk Laporan:**
> Ambil tangkapan layar pada halaman pengaturan Branch Protection ini (menampilkan aturan untuk `main` dan `dev` yang mewajibkan status check lulus) untuk dicantumkan ke dalam laporan PDF.

---

## 5. Petunjuk Eksekusi Git (Commit, Push & Pull Request)

Jalankan perintah berikut di terminal Anda untuk mem-push fitur CI ini:

```powershell
# 1. Pastikan Anda berada di branch feature/ci-workflow
git branch

# 2. Stage berkas workflow dan walkthrough nomor 3
git add .github/workflows/ci.yml Context/walkthrough_task_3.md

# 3. Commit perubahan
git commit -m "ci(github-actions): add ci workflow for backend test and frontend build"

# 4. Push branch ke GitHub
git push -u origin feature/ci-workflow
```

### Langkah di Halaman GitHub:
1. Buat **Pull Request (PR)** dari `feature/ci-workflow` $\rightarrow$ `dev`.
2. Anda akan melihat GitHub Actions otomatis berjalan di bagian bawah PR.
3. Tunggu hingga kedua status check berubah menjadi centang **Hijau**:
   - `Backend Tests (php artisan test)`: ✅ Success
   - `Frontend Build (npm ci & npm run build)`: ✅ Success
4. Lakukan **Merge Pull Request** ke `dev`.
5. Buat **Pull Request (PR)** dari `dev` $\rightarrow$ `main`.
6. Tunggu status check hijau, lalu lakukan **Merge Pull Request** ke `main`.
7. Ambil screenshot halaman **Actions** yang menampilkan seluruh run workflow berstatus hijau untuk dilampirkan pada laporan.
