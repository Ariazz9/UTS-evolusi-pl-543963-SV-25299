# Base image dengan versi eksplisit (bukan latest) - dunglas/frankenphp:1.4.4-php8.2-bookworm
# Memenuhi Soal 5.a dan Soal 7 (Nilai Tambahan FrankenPHP Server)
FROM dunglas/frankenphp:1.4.4-php8.2-bookworm

# Environment variables dasar (tanpa rahasia/password/token sesuai Soal 5.d)
ENV SERVER_NAME=":8000"
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install ekstensi PHP yang dibutuhkan untuk Laravel dan basis data MySQL/SQLite
RUN install-php-extensions \
    pdo_mysql \
    pdo_sqlite \
    bcmath \
    mbstring \
    zip \
    intl \
    opcache

WORKDIR /app

# Salin composer.json dan composer.lock terlebih dahulu agar cache layer Docker bekerja efektif (Soal 5.a)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

# Salin konfigurasi Caddyfile untuk server FrankenPHP
COPY Caddyfile /etc/caddy/Caddyfile

# Salin seluruh kode aplikasi
COPY . .

# Buat autoloader teroptimasi
RUN composer dump-autoload --optimize --no-dev

# Atur izin kepemilikan folder storage dan cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
