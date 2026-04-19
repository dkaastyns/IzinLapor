# Menggunakan image PHP 8.2 dengan Apache sebagai base image
FROM php:8.2-apache

# Mengatur environment variables untuk mengurangi interaksi interaktif saat instalasi
ENV DEBIAN_FRONTEND=noninteractive
ENV NODE_VERSION=20.x

# Menentukan working directory di dalam container
WORKDIR /var/www/html

# 1. Menginstal dependensi sistem (unzip, git, curl, lib-lib untuk PHP, dsb)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    curl \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# 2. Menginstal dan mengonfigurasi ekstensi PHP yang dibutuhkan Laravel dan Supabase
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql zip bcmath pcntl opcache

# 2b. Mengonfigurasi PHP untuk upload gambar dan performa produksi
RUN { \
    echo 'upload_max_filesize = 10M'; \
    echo 'post_max_size = 12M'; \
    echo 'memory_limit = 256M'; \
    echo 'max_execution_time = 60'; \
    echo 'max_input_time = 60'; \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=60'; \
    echo 'opcache.fast_shutdown=1'; \
} > /usr/local/etc/php/conf.d/laravel-production.ini

# 3. Mengaktifkan mod_rewrite Apache untuk routing Laravel
RUN a2enmod rewrite

# 4. Mengonfigurasi DocumentRoot Apache agar mengarah ke folder /public Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4b. Menambahkan ServerName untuk menghilangkan warning AH00558 di log Railway
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 5. Mengubah port Apache agar mendengarkan PORT dari Render (Render/Railway menyuntikkan env var $PORT)
ENV PORT="80"
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

# 6. Menginstal Node.js (untuk mem-build asset Vite/Vue)
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION} | bash - \
    && apt-get install -y nodejs

# 7. Menginstal Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 8. Menyalin file meta dependensi (caching layer Docker yang baik)
# Menyalin composer.json dan package.json terlebih dahulu agar Docker dapat melakukan cache instalasi dependensi
COPY composer.json composer.lock package.json package-lock.json ./

# Mengeksekusi instalasi dependensi (PHP dan Node)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist
RUN npm ci

# 9. Menyalin seluruh source code aplikasi ke dalam container
COPY . .

# 10. Membangun aset dan dump-autoload
RUN composer dump-autoload --optimize --no-dev \
    && npm run build \
    && rm -rf node_modules # Menghapus node_modules setelah build untuk menghemat space

# 11. Mengatur ownership dan permission file
# Memberikan akses kepada user www-data (user bawaan Apache)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# 12. Menambahkan script entrypoint kustom
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Render akan menggunakan port ini secara default jika ENV PORT tidak ditemukan
EXPOSE 80

# Menentukan perintah yang dijalankan saat container start
CMD ["docker-entrypoint.sh"]
