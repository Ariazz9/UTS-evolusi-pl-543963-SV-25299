# Walkthrough Pengerjaan Soal Nomor 4 (CPMK3 - Continuous Deployment ke GHCR)

Dokumen ini berisi dokumentasi lengkap pengerjaan, arsitektur pipeline continuous deployment, konfigurasi Dockerfile, dan verifikasi GitHub Container Registry (`ghcr.io`) untuk **Soal Nomor 4** berdasarkan [requirement context.md](file:///c:/laragon/www/evolusi-pl-UTS/Context/requirement%20context.md).

---

## 1. Ringkasan Pengerjaan & Ketentuan Soal

- **Tujuan**: Menerapkan Continuous Deployment otomatis pada repositori GitHub Actions dengan menambahkan job `deploy`.
- **Target Container Registry**: GitHub Container Registry (`ghcr.io`).
- **Tag Image**: Nomor commit singkat (*short commit hash*), bukan `latest`.
- **Kondisi Eksekusi**:
  - Push/Merge ke branch `main` $\rightarrow$ Menjalankan pengujian, build, dan **job deploy**.
  - Pull Request atau push ke branch `dev`/fitur $\rightarrow$ Hanya menjalankan pengujian dan build (**job deploy otomatis dilewati/skipped**).
- **Nilai Rahasia (*Secrets*)**: Tidak ada nilai rahasia di dalam repositori atau Dockerfile; kredensial disimpan pada **GitHub Actions Secrets**.

---

## 2. Pemenuhan Ketentuan Soal Nomor 4

| Poin | Kebutuhan Soal | Status Implementasi | Keterangan |
| :--- | :--- | :---: | :--- |
| **4.a** | Job deploy hanya berjalan untuk push ke branch `main`. Pada PR, `dev`, dan fitur berhenti setelah test & build. | **Selesai** | Dikonfigurasi kondisi `if: github.ref == 'refs/heads/main' && github.event_name == 'push'` pada job `deploy`. |
| **4.b** | Nilai rahasia seperti `APP_KEY` dan `DB_PASSWORD` disimpan di GitHub Secrets dan tidak boleh ada di repo. | **Selesai** | Dockerfile dan repositori bebas dari hardcoded secret. Nilai rahasia diatur melalui **Settings $\rightarrow$ Secrets and variables $\rightarrow$ Actions**. |
| **4.c** | Job deploy membangun image Docker backend dan frontend dari soal 5, lalu push ke `ghcr.io` dengan tag nomor commit. | **Selesai** | Image backend (FrankenPHP) dan frontend (Vue + Nginx) dibangun dan diunggah ke `ghcr.io/<repo>/backend:<commit>` dan `frontend:<commit>`. |
| **4.d** | Bukti: Run di `main` dengan deploy hijau, run di `dev`/PR tanpa deploy, dan halaman GitHub Packages. | **Panduan Tersedia** | Diberikan checklist tangkapan layar (screenshot) yang perlu diambil untuk laporan PDF. |

---

## 3. Arsitektur Pipeline CI/CD

```
========================================================================
ALUR PULL REQUEST / BRANCH DEV / BRANCH FITUR:
========================================================================
   [ Push ke dev / PR ]
            │
            ├───► [ Job: test-backend ] ──────► (Lulus) ✅
            └───► [ Job: build-frontend ] ────► (Lulus) ✅
            │
            └───► [ Job: deploy ] ────────────► (SKIPPED / DILEWATI) ⏭️

========================================================================
ALUR PUSH / MERGE KE BRANCH MAIN:
========================================================================
   [ Push / Merge ke main ]
            │
            ├───► [ Job: test-backend ] ──────► (Lulus) ✅
            └───► [ Job: build-frontend ] ────► (Lulus) ✅
                        │
                        ▼ (needs: [test-backend, build-frontend])
            ┌──────────────────────────────────────────────┐
            │             [ Job: deploy ]                  │
            │  1. Login ke ghcr.io (GITHUB_TOKEN)          │
            │  2. Build & Push Backend Docker Image        │
            │  3. Build & Push Frontend Docker Image       │
            │  Tag: ghcr.io/<repo>/{backend,frontend}:<sha>│
            └──────────────────────────────────────────────┘
                        │
                        ▼
            (Hijau / Sukses di ghcr.io) ✅
```

---

## 4. Rincian Konfigurasi Workflow (`.github/workflows/ci.yml`)

Bagian job `deploy` yang ditambahkan pada workflow:

```yaml
  deploy:
    name: Deploy Images to GHCR
    needs: [test-backend, build-frontend]
    # Sesuai Soal 4.a: Job deploy HANYA berjalan untuk push ke branch main
    if: github.ref == 'refs/heads/main' && github.event_name == 'push'
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write

    steps:
      - name: Checkout Repository
        uses: actions/checkout@v4

      - name: Set Environment & Lowercase Repository Name
        run: |
          echo "REPO_LOWER=${GITHUB_REPOSITORY,,}" >> $GITHUB_ENV
          echo "COMMIT_SHA=${GITHUB_SHA::7}" >> $GITHUB_ENV

      - name: Log in to GitHub Container Registry (ghcr.io)
        uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Build and Push Backend Docker Image
        uses: docker/build-push-action@v5
        with:
          context: ./backend
          file: ./backend/Dockerfile
          push: true
          tags: ghcr.io/${{ env.REPO_LOWER }}/backend:${{ env.COMMIT_SHA }}

      - name: Build and Push Frontend Docker Image
        uses: docker/build-push-action@v5
        with:
          context: ./frontend
          file: ./frontend/Dockerfile
          push: true
          tags: ghcr.io/${{ env.REPO_LOWER }}/frontend:${{ env.COMMIT_SHA }}
```

### Penanganan Teknis Penting:
1. **Lowercase Repository Name (`REPO_LOWER`)**: Docker Registry (`ghcr.io`) menolak nama repositori yang mengandung huruf kapital. Perintah `${GITHUB_REPOSITORY,,}` memastikan nama repositori selalu huruf kecil sehingga proses push tidak pernah gagal (*invalid reference format*).
2. **Short Commit Hash (`COMMIT_SHA`)**: Mengambil 7 karakter pertama dari commit SHA (`${GITHUB_SHA::7}`) untuk dijadikan tag image (misal: `ghcr.io/username/repo/backend:a1b2c3d`), memenuhi syarat **Soal 4.c** secara presisi.
3. **Autentikasi Otomatis (`GITHUB_TOKEN`)**: Memanfaatkan token bawaan GitHub Actions dengan hak akses `packages: write`, sehingga tidak perlu membuat personal access token (PAT) manual yang rentan bocor.

---

## 5. Berkas Docker Terkait yang Disiapkan (Soal 4.c & Soal 5)

Agar job deploy dapat membangun image Docker secara nyata:
1. [`backend/Dockerfile`](file:///c:/laragon/www/evolusi-pl-UTS/backend/Dockerfile):
   - Image dasar: `dunglas/frankenphp:1.4.4-php8.2-bookworm` (versi jelas, bukan latest).
   - Menjawab Soal 5.a, 5.d, dan Soal 7 (FrankenPHP server).
   - Optimasi cache layer: `composer.json` dan `composer.lock` disalin dan dipasang terlebih dahulu sebelum kode aplikasi.
2. [`backend/.dockerignore`](file:///c:/laragon/www/evolusi-pl-UTS/backend/.dockerignore):
   - Mengabaikan `vendor/`, `node_modules/`, `.git/`, `.env`, dan log database lokal.
3. [`backend/Caddyfile`](file:///c:/laragon/www/evolusi-pl-UTS/backend/Caddyfile):
   - Konfigurasi server FrankenPHP di port 8000.
4. [`frontend/Dockerfile`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/Dockerfile):
   - Multi-stage build (Tahap 1: `node:22-alpine` untuk build; Tahap 2: `nginx:1.27-alpine` untuk menyajikan aset).
   - Image akhir bersih dari `node_modules`.
5. [`frontend/nginx.conf`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/nginx.conf):
   - Konfigurasi SPA routing (`try_files $uri $uri/ /index.html`).
6. [`frontend/.dockerignore`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/.dockerignore):
   - Mengabaikan `node_modules/`, `dist/`, `.git/`, dan `.env`.

---

## 6. Pengaturan Rahasia di GitHub Secrets (Soal 4.b)

Untuk memenuhi Soal 4.b, pastikan nilai rahasia disimpan di GitHub Secrets:
1. Buka repositori Anda di browser: **Settings $\rightarrow$ Secrets and variables $\rightarrow$ Actions**.
2. Klik tombol **New repository secret**.
3. Tambahkan secret berikut:
   - **Name**: `APP_KEY`
     - **Secret**: Masukkan key Laravel (misal dari berkas `.env` lokal Anda: `base64:...`).
   - **Name**: `DB_PASSWORD`
     - **Secret**: Masukkan password database (misal: `rahasia123` atau `rootpassword`).
4. **Pengaturan Workflow Permissions**:
   - Di menu kiri, buka **Actions $\rightarrow$ General**.
   - Gulir ke bawah ke bagian **Workflow permissions**.
   - Pilih opsi **Read and write permissions** (agar workflow diizinkan push image ke GitHub Packages / `ghcr.io`).
   - Klik **Save**.

---

## 7. Bukti Tangkapan Layar yang Harus Disertakan di Laporan (Soal 4.d)

Sesuai ketentuan Soal 4.d, sertakan 3 tangkapan layar berikut di laporan PDF:
1. **Screenshot Run Workflow di `main`**:
   - Menampilkan ketiga job berstatus hijau: `Backend Tests` ✅, `Frontend Build` ✅, dan `Deploy Images to GHCR` ✅.
2. **Screenshot Run Workflow dari PR atau `dev`**:
   - Menampilkan `Backend Tests` ✅, `Frontend Build` ✅, dan `Deploy Images to GHCR` berstatus **Skipped** (dilewati).
3. **Screenshot Halaman GitHub Packages**:
   - Buka profil GitHub atau tab Packages pada repositori: menampilkan dua package image:
     - `.../backend` bertag nomor commit.
     - `.../frontend` bertag nomor commit.

---

## 8. Petunjuk Eksekusi Git (Commit & Push)

Jalankan perintah berikut di terminal Anda:

```powershell
# 1. Pastikan Anda berada di branch feature/deploy-ghcr
git branch

# 2. Stage seluruh berkas Docker dan workflow
git add .github/workflows/ci.yml `
  backend/Dockerfile backend/Caddyfile backend/.dockerignore `
  frontend/Dockerfile frontend/nginx.conf frontend/.dockerignore `
  Context/walkthrough_task_4.md

# 3. Commit perubahan
git commit -m "ci(deploy): add continuous deployment job to ghcr for main branch"

# 4. Push branch ke GitHub
git push -u origin feature/deploy-ghcr
```

### Langkah Selanjutnya di GitHub:
1. Buat **Pull Request** dari `feature/deploy-ghcr` $\rightarrow$ `dev`.
2. Amati bahwa di PR ini: `test-backend` dan `build-frontend` berjalan hijau, sedangkan job `deploy` **skipped** (tidak jalan). Ambil screenshot sebagai bukti Soal 4.d!
3. Merge PR ke `dev`.
4. Buat **Pull Request** dari `dev` $\rightarrow$ `main`, lalu **Merge PR ke `main`**.
5. Begitu PR masuk ke `main`, GitHub Actions akan menjalankan **job `deploy`** hingga **hijau (Success)** dan kedua image otomatis ter-publish di tab **Packages** repositori Anda. Ambil screenshot run hijau dan halaman Packages!
