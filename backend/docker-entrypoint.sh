#!/bin/sh
set -e

# Jika koneksi menggunakan MySQL, tunggu hingga database siap
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "==> Menunggu database MySQL ($DB_HOST:$DB_PORT) siap menerima koneksi..."
    until php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); } catch (Exception \$e) { exit(1); }"; do
        echo "==> Database belum siap, mencoba kembali dalam 2 detik..."
        sleep 2
    done
    echo "==> Database MySQL berhasil terhubung!"

    echo "==> Menjalankan migrasi basis data..."
    php artisan migrate --force

    echo "==> Menjalankan database seeder..."
    php artisan db:seed --force
fi

# Optimasi konfigurasi Laravel di environment container
php artisan config:clear
php artisan route:clear

echo "==> Memulai server FrankenPHP..."
exec "$@"

