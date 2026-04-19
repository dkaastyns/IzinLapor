#!/bin/bash
# =============================================================
# Build script untuk deploy Pengaduan SMAN 11 di Render
# =============================================================

set -e

echo ">>> [1/7] Menginstal dependensi PHP..."
composer install --no-dev --optimize-autoloader --no-interaction

echo ">>> [2/7] Menginstal dependensi Node.js..."
npm ci --production=false

echo ">>> [3/7] Membuild aset frontend (Vite)..."
npm run build

echo ">>> [4/7] Menjalankan migrasi database..."
php artisan migrate --force

echo ">>> [5/7] Menjalankan seeder (Admin & Kategori)..."
php artisan db:seed --force

echo ">>> [6/7] Mengoptimasi cache Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo ">>> [7/7] Membuat symbolic link storage..."
php artisan storage:link || true

echo ">>> Build selesai!"
