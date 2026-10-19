Prosedur Ujian:

Ujian dilaksanakan secara take-home dan dikerjakan secara mandiri. Waktu pengerjaan adalah 10 hari terhitung sejak tanggal pelaksanaan ujian.

Jawaban ujian disusun dalam bentuk laporan dan dikumpulkan dalam format PDF melalui Google Classroom.

Soal Ujian:

1. Materi Uji CPMK1 (Nilai 15): Buatlah backend berbasis Laravel dengan tema bebas (tidak boleh sama dengan tugas sebelumnya) yang menyediakan RESTful API, dengan ketentuan sebagai berikut:

a. Login dan logout memakai token (misalnya Laravel Sanctum). Seluruh endpoint selain login hanya dapat diakses dengan token.

b. Minimal satu resource dengan CRUD lengkap (tampil semua, tampil satu, tambah, ubah, hapus) yang datanya tersimpan di basis data.

c. Seluruh permintaan dan balasan berformat JSON, dengan kode status HTTP yang sesuai: 201 saat data dibuat, 422 saat validasi gagal, dan 401 saat token tidak ada.

d. Seluruh endpoint dapat diuji dengan Postman. Ekspor Postman Collection-nya dan simpan di dalam repository.
e. Minimal dua feature test untuk endpoint API yang lulus saat php artisan test dijalankan.

2. Materi Uji CPMK1 (Nilai 10): Buatlah frontend Vue.js yang memakai API dari soal

nomor 1:

a. Halaman login yang menyimpan token, lalu mengirimkannya pada setiap permintaan berikutnya.

b. Halaman untuk menampilkan, menambah, mengubah, dan menghapus data melalui Axios.

c. Alamat API tidak ditulis mati di dalam kode komponen, melainkan diatur lewat konfigurasi atau variabel lingkungan.

3. Materi Uji CPMK2 (Nilai 15): Kelola seluruh kode sistem di GitHub dengan

ketentuan sebagai berikut:

a. Satu repository publik bernama uts-p1-NIM, boleh di organization KEPL2026 maupun di akun GitHub pribadi, berisi dua folder: backend/ untuk Laravel dan frontend/ untuk Vue. GitHub Actions harus aktif dan bisa berjalan di repository tersebut, dan tautannya dicantumkan di laporan.

b. Buat branch main, dev, dan minimal satu branch fitur. Perubahan masuk ke dev dan main melalui Pull Request, tidak boleh push langsung. Pasang branch protection pada main dan dev yang mewajibkan Pull Request dan status check lulus sebelum merge, lalu sertakan screenshot halaman pengaturannya (Settings →Branches atau Settings Rules) di laporan.

c. Buat berkas .github/workflows/ci.yml dengan minimal dua job: job pengujian backend yang menjalankan php artisan test, dan job build frontend yang menjalankan npm ci dan npm run build. Workflow harus berjalan sampai hijau di tab Actions.

4. Materi Uji CPMK3 (Nilai 15): Terapkan continuous deployment dengan menambahkan job deploy pada workflow GitHub Actions:

a. Job deploy hanya berjalan untuk push ke branch main. Workflow pada Pull Request, branch dev, dan branch fitur berhenti setelah job pengujian dan build.

b. Nilai rahasia seperti APP_KEY dan DB_PASSWORD disimpan di GitHub Secrets (Settings Secrets and variables Actions) dan tidak boleh ada di dalam repository.

c. Job deploy membangun image Docker backend dan frontend dari soal nomor 5, lalu mengunggahnya ke GitHub Container Registry (gher.io). Tag image memakai nomor commit, bukan latest.

d. Sertakan bukti: run workflow di main dengan job deploy yang hijau, run workflow dari dev atau Pull Request yang tidak menjalankan deploy, dan halaman Packages yang berisi kedua image.

5. Materi Uji CPMK4 dan CPMK5 (Nilai 20): Buatlah Dockerfile untuk backend dan frontend:

a. backend/Dockerfile untuk Laravel. Image dasar memakai versi yang jelas (bukan latest). composer.json dan composer.lock disalin dan dipasang sebelum kode aplikasi disalin, supaya cache lapisan bekerja.

b. frontend/Dockerfile untuk Vue memakai multi-stage build: tahap pertama membangun aplikasi dengan Node.js, tahap kedua menyajikan hasil build dengan Nginx. Image akhir tidak boleh berisi node_modules.
c. Berkas.dockerignore di masing-masing folder.

d. Tidak ada nilai rahasia (password, key, token) yang ditulis di dalam Dockerfile.

6. Materi Uji CPMK6 dan CPMK7 (Nilai 25): Buatlah berkas docker-compose.yml di akar repository yang menjalankan seluruh sistem dengan satu perintah docker compose up-d-build:

a. Minimal tiga service: basis data (MySQL atau PostgreSQL), backend Laravel, dan frontend Vue.

b. Backend terhubung ke basis data, dan frontend terhubung ke backend, melalui nama service di jaringan Docker Compose, bukan melalui alamat IP laptop.

c. Login, tampil, tambah, ubah, dan hapus data dari halaman Vue di browser benar-benar berjalan: data yang tampil berasal dari Laravel di container lain dan tersimpan di basis data.

d. Data basis data disimpan di named volume. Setelah docker compose down lalu docker compose up-d, data yang sudah dibuat tidak hilang.

e. Kredensial basis data dibaca dari berkas env yang tidak ikut di-commit. Sertakan.env.example sebagai contoh.

7. Nilai Tambahan (+10, di luar nilai 100): Jalankan backend Laravel memakai FrankenPHP sebagai server aplikasinya, menggantikan php artisan serve atau PHP-FPM dengan Nginx. Image dasar tetap memakai versi yang jelas, bukan latest, dan backend tetap berjalan bersama service lain di docker-compose.yml. Sertakan bukti di laporan bahwa permintaan ke API benar-benar dilayani oleh FrankenPHP, misalnya dari log container backend.

8. Buatlah laporan dalam format PDF dan unggah ke Google Classroom. Laporan berisi langkah pengerjaan dan screenshot tiap soal, tautan repository GitHub, Postman Collection, output docker compose ps, serta penjelasan singkat bagaimana data mengalir dari Vue ke Laravel lalu ke basis data. Ceritakan juga kendala yang kalian temui dan cara mengatasinya. Pekerjaan tanpa laporan tidak dinilai.

