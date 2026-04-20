#!/bin/bash
# Script build Vercel — dipanggil otomatis saat deploy
# Script ini: install deps → build Vite assets → optimize Laravel

set -e  # Stop jika ada error

echo "=== [1/4] Install composer dependencies ==="
composer install --no-dev --optimize-autoloader --no-interaction

echo "=== [2/4] Install npm dependencies ==="
npm ci

echo "=== [3/4] Build frontend assets (Vite) ==="
npm run build

echo "=== [4/4] Cache config dan routes Laravel ==="
# Catatan: php artisan config:cache tidak bisa dijalankan di sini karena env vars
# belum tersedia saat build. Vercel akan set env vars di runtime, bukan build time.
# File .env tidak ada — Laravel akan baca env vars dari system langsung.

echo "=== Build selesai! ==="
