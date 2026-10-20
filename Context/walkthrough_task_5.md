# Walkthrough Pengerjaan Soal Nomor 5 (CPMK4 & CPMK5 - Dockerfile Backend & Frontend)

Dokumen ini berisi dokumentasi lengkap arsitektur Dockerfile, strategi optimasi multi-stage build, efisiensi layer caching, dan pengabaian berkas sensitif untuk **Soal Nomor 5** berdasarkan [requirement context.md](file:///c:/laragon/www/evolusi-pl-UTS/Context/requirement%20context.md).

---

## 1. Ringkasan Pengerjaan & Ketentuan Soal

- **Tujuan**: Membangun berkas kontainerisasi mandiri untuk backend Laravel dan frontend Vue.js dengan mematuhi prinsip *best practice* Docker.
- **Lokasi Berkas**:
  - Backend: [`backend/Dockerfile`](file:///c:/laragon/www/evolusi-pl-UTS/backend/Dockerfile) & [`backend/.dockerignore`](file:///c:/laragon/www/evolusi-pl-UTS/backend/.dockerignore)
  - Frontend: [`frontend/Dockerfile`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/Dockerfile) & [`frontend/.dockerignore`](file:///c:/laragon/www/evolusi-pl-UTS/frontend/.dockerignore)

---

## 2. Pemenuhan Ketentuan Soal Nomor 5

| Poin | Kebutuhan Soal | Status Implementasi | Keterangan |
| :--- | :--- | :---: | :--- |
| **5.a** | `backend/Dockerfile` untuk Laravel. Image dasar versi jelas (bukan latest). `composer.json` & `lock` disalin dan dipasang sebelum kode aplikasi disalin (layer cache). | **Selesai** | Menggunakan `dunglas/frankenphp:1.4.4-php8.2-bookworm` (sekaligus memenuhi Nilai Tambahan Soal 7). Dependensi Composer di-install sebelum `COPY . .` untuk memaksimalkan layer caching. |
| **5.b** | `frontend/Dockerfile` untuk Vue memakai multi-stage build: tahap 1 build aplikasi dengan Node.js, tahap 2 serving dengan Nginx. Image akhir tidak boleh berisi `node_modules`. | **Selesai** | Menggunakan 2 stage: `node:22-alpine` (builder) dan `nginx:1.27-alpine` (production server). Hanya artefak `/app/dist` yang disalin ke Nginx; `node_modules` ditinggalkan di builder stage. |
| **5.c** | Berkas `.dockerignore` di masing-masing folder. | **Selesai** | Dibuat `backend/.dockerignore` dan `frontend/.dockerignore` untuk mencegah berkas lokal/rahasia tersalin ke image. |
| **5.d** | Tidak ada nilai rahasia (password, key, token) yang ditulis di dalam Dockerfile. | **Selesai** | Seluruh variabel rahasia (`APP_KEY`, `DB_PASSWORD`, dll) diinjeksi melalui runtime environment / `.env`, bukan hardcoded di Dockerfile. |

---

## 3. Rincian dan Arsitektur Dockerfile

### A. Backend Dockerfile (`backend/Dockerfile`)
```dockerfile
# 1. Base image dengan versi eksplisit (bukan latest)
FROM dunglas/frankenphp:1.4.4-php8.2-bookworm

ENV SERVER_NAME=":8000"
ENV COMPOSER_ALLOW_SUPERUSER=1

# Pasang Composer resmi versi 2.8 via multi-stage copy
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Install paket sistem pendukung (unzip & git)
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install ekstensi PHP untuk Laravel dan MySQL
RUN install-php-extensions \
    pdo_mysql \
    pdo_sqlite \
    bcmath \
    mbstring \
    zip \
    intl \
    opcache

WORKDIR /app

# 2. Layer Caching: Salin & pasang dependensi sebelum menyalin seluruh kode (Soal 5.a)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

# Salin konfigurasi Caddyfile FrankenPHP
COPY Caddyfile /etc/caddy/Caddyfile

# 3. Salin kode aplikasi
COPY . .

# Buat autoloader teroptimasi
RUN composer dump-autoload --optimize --no-dev

# Atur izin direktori storage dan cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Skrip entrypoint otomatis migrasi dan seeding database
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
```

### B. Frontend Dockerfile (`frontend/Dockerfile`)
```dockerfile
# ==========================================
# Tahap 1: Build Aplikasi dengan Node.js (Soal 5.b)
# Menggunakan versi eksplisit: node:22-alpine (bukan latest)
# ==========================================
FROM node:22-alpine AS build-stage

WORKDIR /app

# Salin package definitions untuk layer caching
COPY package*.json ./
RUN npm ci

# Salin source code dan compile produksi
COPY . .
RUN npm run build

# ==========================================
# Tahap 2: Serving Produksi dengan Nginx (Soal 5.b)
# Menggunakan versi eksplisit: nginx:1.27-alpine (bukan latest)
# Image akhir bersih dan TIDAK berisi node_modules
# ==========================================
FROM nginx:1.27-alpine AS production-stage

# Salin HANYA hasil build statis (dist) dari build-stage
COPY --from=build-stage /app/dist /usr/share/nginx/html

# Salin konfigurasi server Nginx (SPA fallback & reverse proxy)
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
```

---

## 4. Pengabaian Berkas Sensitif (`.dockerignore`)

- [**`backend/.dockerignore`**](file:///c:/laragon/www/evolusi-pl-UTS/backend/.dockerignore):
  Mengabaikan `vendor/`, `node_modules/`, `.git/`, `.env`, berkas log, dan basis data SQLite lokal agar tidak menimpa file di dalam image.
- [**`frontend/.dockerignore`**](file:///c:/laragon/www/evolusi-pl-UTS/frontend/.dockerignore):
  Mengabaikan `node_modules/`, `dist/`, `.git/`, dan `.env` lokal sehingga build konteks Docker ringan dan cepat.

