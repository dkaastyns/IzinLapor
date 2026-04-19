#!/bin/bash
set -e

# Logging fungsi pembantu
echo "[Entrypoint] Memulai persiapan server Laravel..."

# Membuat symlink storage jika belum ada
if [ ! -L /var/www/html/public/storage ]; then
    echo "[Entrypoint] Membuat storage link..."
    php artisan storage:link || true
fi

# Mengoptimalkan cache config, view, routing, dll
echo "[Entrypoint] Mem-build cache aplikasi (config, route, event, views)..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Menjalankan migrasi database ke struktur terbaru (aman untuk environment production dengan flag --force)
echo "[Entrypoint] Menjalankan migrasi database..."
php artisan migrate --force

echo "[Entrypoint] Semuanya siap! Menjalankan server Apache..."
# Menonaktifkan modul MPM yang berkonflik (sering terjadi di environment cloud)
a2dismod mpm_event mpm_worker || true
a2enmod mpm_prefork || true

# Eksekusi proses Apache sehingga terikat (bind) dengan lifecycle container
exec apache2-foreground
