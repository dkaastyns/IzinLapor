#!/bin/bash
# Script build Vercel — dipanggil otomatis saat deploy
set -e

echo "=== [1/5] Download Composer ==="
# Vercel build env tidak punya composer — download langsung dari getcomposer.org
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --quiet
rm composer-setup.php
echo "Composer berhasil didownload."

echo "=== [2/5] Install composer dependencies ==="
php composer.phar install --no-dev --optimize-autoloader --no-interaction --no-progress

echo "=== [3/5] Install npm dependencies ==="
npm ci --prefer-offline

echo "=== [4/5] Build frontend assets (Vite) ==="
npm run build

echo "=== [5/5] Selesai! ==="
echo "Build sukses — siap deploy ke Vercel."
