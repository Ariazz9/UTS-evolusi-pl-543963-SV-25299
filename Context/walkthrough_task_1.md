# Walkthrough Pengerjaan Soal Nomor 1 (CPMK1 - Backend Laravel RESTful API)

Dokumen ini berisi dokumentasi lengkap pengerjaan, workflow arsitektur, dan perubahan berkas untuk **Soal Nomor 1** berdasarkan [requirement context.md](file:///c:/laragon/www/evolusi-pl-UTS/Context/requirement%20context.md).

---

## 1. Ringkasan Pengerjaan & Tema Sistem

- **Tema API**: **Sistem Manajemen Bahan Bangunan / Inventaris Toko Bangunan (`Building Material Management API`)**
- **Framework**: Laravel 12.x dengan PHP 8.2+
- **Autentikasi**: Token-based menggunakan **Laravel Sanctum**
- **Penyimpanan Data**: SQLite (default lokal/testing) dan siap dialihkan ke MySQL/PostgreSQL via Docker.
- **Lokasi Folder**: `backend/`

---

## 2. Pemenuhan Ketentuan Soal Nomor 1

| Poin | Kebutuhan Soal | Status Implementasi | Keterangan |
| :--- | :--- | :---: | :--- |
| **1.a** | Login & logout memakai token (Sanctum). Seluruh endpoint selain login wajib token. | **Selesai** | `POST /api/login` bersifat publik; seluruh endpoint CRUD bahan bangunan, profil user, dan logout diproteksi middleware `auth:sanctum`. |
| **1.b** | Minimal satu resource dengan CRUD lengkap tersimpan di database. | **Selesai** | Resource `materials` (Bahan Bangunan) dengan operasi Index (semua), Show (satu), Store (tambah), Update (ubah), dan Destroy (hapus). Juga didukung alias `/api/products` untuk fleksibilitas konsumsi data. |
| **1.c** | Respon JSON dengan status HTTP tepat: 201 (dibuat), 422 (validasi gagal), 401 (tanpa token). | **Selesai** | Dikonfigurasi eksplisit pada controller & exception handler `bootstrap/app.php` sehingga request ke `/api/*` tanpa token selalu menghasilkan JSON 401. |
| **1.d** | Postman Collection diekspor & disimpan di repository. | **Selesai** | Tersedia berkas `backend/postman_collection.json` dan `postman_collection.json` (skema v2.1) lengkap dengan automasi variable token dan tema Toko Bangunan. |
| **1.e** | Minimal 2 feature test yang lulus saat `php artisan test`. | **Selesai** | Dibuat 2 berkas feature test (`AuthApiTest` & `MaterialApiTest`) dengan total 15 pengujian (81 assertions) lulus 100%. |

---

## 3. Daftar Endpoint API (Tema Toko Bangunan)

Semua endpoint memiliki base URL `http://127.0.0.1:8000/api`.

| Metode | URL Endpoint | Proteksi | HTTP Status Sukses | Deskripsi & Parameter |
| :--- | :--- | :---: | :---: | :--- |
| `POST` | `/api/login` | Publik | `200 OK` | Login user kasir/admin toko, mengembalikan Bearer Token Sanctum. Body: `email`, `password`. |
| `POST` | `/api/logout` | `auth:sanctum` | `200 OK` | Menghapus token aktif user saat ini. |
| `GET` | `/api/user` | `auth:sanctum` | `200 OK` | Mengambil data profil user yang sedang login. |
| `GET` | `/api/materials` | `auth:sanctum` | `200 OK` | Menampilkan seluruh data material toko bangunan (terurut dari yang terbaru). |
| `POST` | `/api/materials` | `auth:sanctum` | `201 Created` | Menambah material baru. Body: `name`, `category`, `unit`, `price`, `stock`, `description`. |
| `GET` | `/api/materials/{id}` | `auth:sanctum` | `200 OK` | Menampilkan detail satu material spesifik (404 jika ID tidak ditemukan). |
| `PUT` | `/api/materials/{id}` | `auth:sanctum` | `200 OK` | Memperbarui data material (validasi input diterapkan). |
| `DELETE` | `/api/materials/{id}` | `auth:sanctum` | `200 OK` | Menghapus data material dari database. |

---

## 4. Rincian Perubahan & Berkas yang Dibuat

### A. Konfigurasi Autentikasi & Model
1. [User.php](file:///c:/laragon/www/evolusi-pl-UTS/backend/app/Models/User.php)
   - Ditambahkan trait `Laravel\Sanctum\HasApiTokens` pada model `User`.
2. [Material.php](file:///c:/laragon/www/evolusi-pl-UTS/backend/app/Models/Material.php)
   - Ditambahkan properti `$fillable` (`name`, `category`, `unit`, `price`, `stock`, `description`).
   - Ditambahkan casting data numerik (`price` => float, `stock` => integer).
3. [MaterialFactory.php](file:///c:/laragon/www/evolusi-pl-UTS/backend/database/factories/MaterialFactory.php)
   - Factory untuk pengujian otomatis menggunakan Faker dengan kategori dan satuan khas toko bangunan (Sak, Batang, Pail, Dus, Lembar, dll).

### B. Migrasi & Seeder Database
1. [2026_10_19_130000_create_materials_table.php](file:///c:/laragon/www/evolusi-pl-UTS/backend/database/migrations/2026_10_19_130000_create_materials_table.php)
   - Skema tabel `materials`:
     - `id`: Primary key bigint unsigned auto-increment.
     - `name`: string nama barang / material (misal: "Semen Tiga Roda 50kg").
     - `category`: string kategori (misal: "Semen & Mortar", "Besi & Baja", "Cat & Pelapis").
     - `unit`: string satuan (misal: "Sak", "Batang", "Pail", "Dus").
     - `price`: decimal(12, 2) harga jual.
     - `stock`: integer jumlah stok fisik.
     - `description`: text nullable spesifikasi teknis barang.
     - `timestamps`: `created_at` dan `updated_at`.
2. [DatabaseSeeder.php](file:///c:/laragon/www/evolusi-pl-UTS/backend/database/seeders/DatabaseSeeder.php)
   - Mengisi user admin default:
     - **Email**: `admin@example.com`
     - **Password**: `password123`
   - Mengisi 6 sampel barang toko bangunan awal:
     1. Semen Tiga Roda 50kg (Sak - Rp 68.000)
     2. Besi Beton Polos 10mm SNI (Batang - Rp 75.000)
     3. Cat Tembok Dulux Catylac Putih 5kg (Pail - Rp 145.000)
     4. Pipa PVC Rucika AW 1/2 Inch (Batang - Rp 32.000)
     5. Keramik Lantai Roman 40x40 Bianco (Dus - Rp 88.000)
     6. Triplek Meranti 12mm 4x8 (Lembar - Rp 165.000)

### C. Controller & Routing
1. [AuthController.php](file:///c:/laragon/www/evolusi-pl-UTS/backend/app/Http/Controllers/Api/AuthController.php)
   - `login()`: Validasi request (422 jika gagal), verifikasi kredensial via `Hash::check()` (401 jika salah), dan generate Sanctum token.
   - `logout()`: Menghapus `currentAccessToken()` pengguna aktif.
   - `me()`: Mengembalikan payload user yang terautentikasi.
2. [MaterialController.php](file:///c:/laragon/www/evolusi-pl-UTS/backend/app/Http/Controllers/Api/MaterialController.php)
   - `index()`: Mengambil daftar material toko bangunan secara descending.
   - `store()`: Validasi field wajib, menyimpan record baru ke database, mengembalikan kode **`201 Created`**.
   - `show()`: Mengembalikan data tunggal atau **`404 Not Found`** jika tidak ada.
   - `update()`: Memperbarui record dan mengembalikan status **`200 OK`**.
   - `destroy()`: Menghapus record dan mengembalikan status **`200 OK`**.
3. [api.php](file:///c:/laragon/www/evolusi-pl-UTS/backend/routes/api.php)
   - Mengatur pemisahan rute publik (`/login`) dan rute privat di dalam grup `Route::middleware('auth:sanctum')`.
   - Mendaftarkan resource `materials` dan alias `products`.
4. [app.php](file:///c:/laragon/www/evolusi-pl-UTS/backend/bootstrap/app.php)
   - Menambahkan konfigurasi handler `AuthenticationException` dan `shouldRenderJsonWhen` khusus untuk rute `api/*` sehingga request tanpa header atau tanpa token dipastikan mengembalikan format JSON dengan kode status HTTP **401 Unauthorized**.

### D. Pengujian Otomatis (Feature Tests)
1. [AuthApiTest.php](file:///c:/laragon/www/evolusi-pl-UTS/backend/tests/Feature/AuthApiTest.php)
   - Pengujian login sukses (status 200 & menerima token).
   - Pengujian login gagal karena kredensial salah (status 401).
   - Pengujian validasi input login kosong (status 422).
   - Pengujian akses tanpa token ke route terproteksi (status 401).
   - Pengujian pengambilan profil dan proses logout (status 200 & token terhapus).
2. [MaterialApiTest.php](file:///c:/laragon/www/evolusi-pl-UTS/backend/tests/Feature/MaterialApiTest.php)
   - Pengujian blokir akses unauthenticated (status 401).
   - Pengujian read all materials (status 200).
   - Pengujian create material sukses (status 201 & tersimpan di DB).
   - Pengujian create material data tidak valid (status 422).
   - Pengujian show single material (status 200).
   - Pengujian show ID fiktif (status 404).
   - Pengujian update material (status 200 & perubahan tersimpan).
   - Pengujian delete material (status 200 & record hilang dari DB).

### E. Postman Collection
- Berkas: [postman_collection.json](file:///c:/laragon/www/evolusi-pl-UTS/postman_collection.json) dan [backend/postman_collection.json](file:///c:/laragon/www/evolusi-pl-UTS/backend/postman_collection.json)
- Skrip tes otomatis disematkan pada setiap request:
  - Pada request login, nilai token otomatis diekstrak dan disimpan ke collection variable `{{token}}`.
  - Pada request create material, `id` material yang terbentuk disimpan ke collection variable `{{materialId}}` untuk pengujian get single, update, dan delete.

---

## 5. Bukti Hasil Eksekusi Pengujian (`php artisan test`)

Hasil eksekusi seluruh unit & feature test:

```text
   PASS  Tests\Unit\ExampleTest
  ✓ that true is true

   PASS  Tests\Feature\AuthApiTest
  ✓ user can login with valid credentials and receive token                                                      0.28s  
  ✓ user cannot login with invalid credentials                                                                   0.02s  
  ✓ login validation fails with 422                                                                              0.01s  
  ✓ unauthenticated request to protected route returns 401                                                       0.01s  
  ✓ authenticated user can fetch profile and logout                                                              0.02s  

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                                                                0.02s  

   PASS  Tests\Feature\MaterialApiTest
  ✓ unauthenticated request cannot access material endpoints                                                     0.01s  
  ✓ can list all materials                                                                                       0.02s  
  ✓ can create material successfully returns 201                                                                 0.02s  
  ✓ create material validation fails with 422                                                                     0.01s  
  ✓ can show single material                                                                                     0.01s  
  ✓ show non existent material returns 404                                                                        0.01s  
  ✓ can update material                                                                                          0.02s  
  ✓ can delete material                                                                                          0.01s  

  Tests:    15 passed (81 assertions)
  Duration: 0.68s
```

---

## 6. Petunjuk Menjalankan Backend Secara Mandiri

1. Masuk ke direktori `backend`:
   ```bash
   cd backend
   ```
2. Jalankan migrasi dan seeder:
   ```bash
   php artisan migrate:fresh --seed
   ```
3. Jalankan server lokal:
   ```bash
   php artisan serve
   ```
4. Buka Postman, impor [postman_collection.json](file:///c:/laragon/www/evolusi-pl-UTS/postman_collection.json), lalu jalankan request `1. Login (Success -> Store Token)` dengan akun default:
   - **Email**: `admin@example.com`
   - **Password**: `password123`
