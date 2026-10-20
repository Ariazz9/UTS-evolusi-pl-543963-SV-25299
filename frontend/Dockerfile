# ==========================================
# Tahap 1: Build Aplikasi dengan Node.js (Soal 5.b)
# Menggunakan versi eksplisit: node:22-alpine (bukan latest)
# ==========================================
FROM node:22-alpine AS build-stage

WORKDIR /app

# Salin package.json dan package-lock.json terlebih dahulu untuk layer caching
COPY package*.json ./

# Install dependensi secara bersih
RUN npm ci

# Salin seluruh kode aplikasi frontend
COPY . .

# Build aset produksi statis ke folder /app/dist
RUN npm run build

# ==========================================
# Tahap 2: Serving Produksi dengan Nginx (Soal 5.b)
# Menggunakan versi eksplisit: nginx:1.27-alpine (bukan latest)
# Image akhir bersih dan TIDAK berisi node_modules
# ==========================================
FROM nginx:1.27-alpine AS production-stage

# Salin hasil kompilasi statis dari tahap build
COPY --from=build-stage /app/dist /usr/share/nginx/html

# Salin konfigurasi server Nginx (SPA routing)
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
