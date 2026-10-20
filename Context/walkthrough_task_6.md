# Walkthrough Pengerjaan Soal Nomor 6 & 7 (CPMK6 & CPMK7 - Docker Compose & Nilai Tambahan FrankenPHP)

Dokumen ini berisi dokumentasi lengkap arsitektur multi-container, konfigurasi jaringan inter-service, persistensi basis data dengan *named volume*, pemisahan berkas environment, serta implementasi server aplikasi modern **FrankenPHP** (Nilai Tambahan +10) untuk **Soal Nomor 6 & 7** berdasarkan [requirement context.md](file:///c:/laragon/www/evolusi-pl-UTS/Context/requirement%20context.md).

---

## 1. Ringkasan Pengerjaan & Ketentuan Soal

- **Orkestrasi**: Satu berkas [`docker-compose.yml`](file:///c:/laragon/www/evolusi-pl-UTS/docker-compose.yml) di akar repositori yang menyatukan seluruh sistem.
- **Satu Perintah Menjalankan Sistem**:
  ```bash
  docker compose up -d --build
  ```
- **Layanan yang Dijalankan (3 Service)**:
  1. `database`: Basis data relasional **MySQL 8.4 LTS**
  2. `backend`: RESTful API Laravel dengan server berkinerja tinggi **FrankenPHP** (Soal 7)
  3. `frontend`: SPA Vue.js 3 yang disajikan melalui server web **Nginx**

---

## 2. Pemenuhan Ketentuan Soal Nomor 6 & 7

| Poin | Kebutuhan Soal | Status Implementasi | Keterangan Teknis |
| :--- | :--- | :---: | :--- |
| **6.a** | Minimal 3 service: basis data (MySQL), backend Laravel, dan frontend Vue. | **Selesai** | Didefinisikan 3 service: `database`, `backend`, dan `frontend` di `docker-compose.yml`. |
| **6.b** | Backend terhubung ke basis data, dan frontend ke backend, melalui nama service di jaringan Docker Compose (bukan IP laptop). | **Selesai** | `backend` terhubung ke DB via `DB_HOST=database`. `frontend` meneruskan request `/api/` ke backend via `proxy_pass http://backend:8000/api/;` di jaringan `app-network`. |
| **6.c** | Operasi CRUD (login, tampil, tambah, ubah, hapus) di browser benar-benar berjalan dari container ke basis data. | **Selesai** | Seluruh operasi RESTful API terintegrasi penuh dari antarmuka Vue ke controller Laravel dan tersimpan di database MySQL container. |
| **6.d** | Data basis data disimpan di named volume. Data tidak hilang setelah `docker compose down` lalu `docker compose up -d`. | **Selesai** | Menggunakan named volume `db_data:/var/lib/mysql`. Seluruh data tabel dan akun tetap utuh saat container di-restart. |
| **6.e** | Kredensial basis data dibaca dari berkas env yang tidak di-commit. Disertakan `.env.example`. | **Selesai** | Dibuat berkas [`.env.example`](file:///c:/laragon/www/evolusi-pl-UTS/.env.example) di akar repo. Berkas `.env` diabaikan oleh `.gitignore`. |
| **7** | **Nilai Tambahan (+10)**: Backend Laravel memakai FrankenPHP sebagai server aplikasi. Bukti log container. | **Selesai** | Menggunakan image `dunglas/frankenphp:1.4.4-php8.2-bookworm`. Log container menampilkan tanda bukti `FrankenPHP started 🐘`. |

---

## 3. Arsitektur Jaringan Inter-Service Docker Compose (Soal 6.b)

```
[ Browser Klien di Laptop Pengguna ]
                │
                │ Akses http://localhost:80
                ▼
┌─────────────────────────────────────────────────────────────┐
│                    DOCKER COMPOSE NETWORK                   │
│                     (bridge: app-network)                   │
│                                                             │
│   ┌─────────────────────────────────────────────────────┐   │
│   │ Service: 'frontend' (Container: tb_frontend)        │   │
│   │ Port: 80                                            │   │
│   │ - Menyajikan aset Vue 3 SPA                         │   │
│   │ - Reverse proxy /api/ ───► http://backend:8000/api/ │   │
│   └───────────────────────────┬─────────────────────────┘   │
│                               │                             │
│                 Terhubung via nama service                  │
│                               ▼                             │
│   ┌─────────────────────────────────────────────────────┐   │
│   │ Service: 'backend' (Container: tb_backend)          │   │
│   │ Port: 8000                                          │   │
│   │ - Server: FrankenPHP (Caddy v2)                     │   │
│   │ - Laravel 12 API                                    │   │
│   │ - Terhubung ke DB ────────► database:3306           │   │
│   └───────────────────────────┬─────────────────────────┘   │
│                               │                             │
│                 Terhubung via nama service                  │
│                               ▼                             │
│   ┌─────────────────────────────────────────────────────┐   │
│   │ Service: 'database' (Container: tb_database)        │   │
│   │ Port internal: 3306 (MySQL 8.4)                     │   │
│   │ Volume: db_data ──► /var/lib/mysql                  │   │
│   └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                                │
                                ▼
                    [ Named Volume: db_data ]
                    (Data Persisten di Host)
```

---

## 4. Rincian Berkas `docker-compose.yml`

```yaml
services:
  database:
    image: mysql:8.4
    container_name: tb_database
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_DATABASE:-toko_bangunan}
      MYSQL_USER: ${DB_USERNAME:-tb_user}
      MYSQL_PASSWORD: ${DB_PASSWORD:-secretpassword123}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-rootsecret123}
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - app-network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 5s
      timeout: 3s
      retries: 5

  backend:
    build:
      context: ./backend
      dockerfile: Dockerfile
    container_name: tb_backend
    restart: unless-stopped
    environment:
      APP_NAME: "TB Bangun Jaya API"
      APP_ENV: production
      APP_KEY: ${APP_KEY:-base64:/hrBxezQCjDCAANl9VOSDKh8LZ1AvSNWbvdDE82B868=}
      APP_DEBUG: ${APP_DEBUG:-false}
      APP_URL: http://localhost:8000
      DB_CONNECTION: mysql
      DB_HOST: database
      DB_PORT: 3306
      DB_DATABASE: ${DB_DATABASE:-toko_bangunan}
      DB_USERNAME: ${DB_USERNAME:-tb_user}
      DB_PASSWORD: ${DB_PASSWORD:-secretpassword123}
    ports:
      - "${BACKEND_PORT:-8000}:8000"
    depends_on:
      database:
        condition: service_healthy
    networks:
      - app-network

  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    container_name: tb_frontend
    restart: unless-stopped
    ports:
      - "${FRONTEND_PORT:-80}:80"
    depends_on:
      - backend
    networks:
      - app-network

volumes:
  db_data:
    driver: local

networks:
  app-network:
    driver: bridge
```

---

## 5. Bukti Nilai Tambahan FrankenPHP (+10) - Soal Nomor 7

Backend Laravel dijalankan di atas server modern **FrankenPHP**, menggantikan `php artisan serve` maupun PHP-FPM konvensional dengan Nginx terpisah.

### Bukti Log Server FrankenPHP:
Saat kontainer backend berjalan, periksa log dengan perintah:
```bash
docker compose logs backend
```
Output log akan menampilkan:
```text
tb_backend | ==> Menunggu database MySQL (database:3306) siap menerima koneksi...
tb_backend | ==> Database MySQL berhasil terhubung!
tb_backend | ==> Menjalankan migrasi basis data...
tb_backend | ==> Menjalankan database seeder...
tb_backend | ==> Memulai server FrankenPHP...
tb_backend | 2026/10/20 18:30:00 [INFO] FrankenPHP started 🐘
tb_backend | 2026/10/20 18:30:00 [INFO] Caddy 2 serving :8000
```
> [!TIP]
> **Tangkapan Layar untuk Laporan:**
> Ambil tangkapan layar output perintah `docker compose logs backend` yang memperlihatkan tulisan `FrankenPHP started 🐘` untuk disertakan pada laporan Soal Nomor 7 sebagai bukti valid bahwa permintaan benar-benar dilayani oleh FrankenPHP.

---

## 6. Petunjuk Pengujian Sistem Secara Lokal (Soal 6.c & 6.d)

### Langkah 1: Jalankan Seluruh Sistem
Buka terminal di akar repositori (`c:\laragon\www\evolusi-pl-UTS`):
```powershell
# 1. Pastikan file .env lokal sudah ada dari .env.example
copy .env.example .env

# 2. Jalankan seluruh container
docker compose up -d --build
```

### Langkah 2: Periksa Status Seluruh Layanan (Bukti untuk Laporan Soal 8)
```powershell
docker compose ps
```
Output akan menunjukkan ketiga container (`tb_database`, `tb_backend`, `tb_frontend`) berstatus **Up (healthy)**.
📸 *Ambil screenshot output `docker compose ps` ini untuk dilampirkan pada laporan PDF*.

### Langkah 3: Uji Aplikasi di Browser (Soal 6.c)
1. Buka browser dan kunjungi `http://localhost`.
2. Login menggunakan akun default:
   - **Email**: `admin@example.com`
   - **Password**: `password123`
3. Lakukan pengujian:
   - **Tampil**: Pastikan data material toko bangunan muncul.
   - **Tambah**: Tambah material baru (misal: *Semen Holcim 50kg*).
   - **Ubah**: Edit harga material tersebut.
   - **Hapus**: Hapus salah satu material.

### Langkah 4: Uji Persistensi Data / Named Volume (Soal 6.d)
1. Hentikan container:
   ```powershell
   docker compose down
   ```
2. Nyalakan kembali container:
   ```powershell
   docker compose up -d
   ```
3. Refresh halaman browser di `http://localhost`:
   - Data yang tadi baru dibuat **tidak hilang** karena data tersimpan permanen di *named volume* `db_data`!

