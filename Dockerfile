# 1. Base Image: Menggunakan image resmi PHP 8.2 terintegrasi dengan web server Apache (berbasis Linux Debian)
FROM php:8.2-apache

# 2. Instalasi Dependensi Sistem, Ekstensi PHP, Modul Apache, dan Pembersihan Cache:
#    - apt-get update & install: Mengunduh library C (libonig, libxml2) serta tool sistem (unzip, git) yang dibutuhkan Composer.
#    - --no-install-recommends: Mencegah instalasi paket opsional agar image tetap ringan.
#    - docker-php-ext-install: Mengompilasi dan mengaktifkan ekstensi PHP (pdo_mysql untuk DB, mbstring, dan XML parsing).
#    - a2enmod rewrite headers: Mengaktifkan modul Apache untuk clean URL routing (index.php) dan HTTP security headers.
#    - rm -rf /var/lib/apt/lists/*: Menghapus cache repositori Debian untuk menghemat ukuran image.
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev libxml2-dev unzip git \
    && docker-php-ext-install pdo_mysql mbstring dom xml xmlwriter \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# 3. Multi-Stage Build Composer: Menyalin binary resmi Composer v2 langsung dari image composer:2
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 4. Konfigurasi VirtualHost Apache: Mengatur DocumentRoot ke folder public/ dan fallback routing URL
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# 5. Konfigurasi Runtime PHP: Menerapkan pengaturan batas memori, zona waktu, dan pelaporan error aplikasi
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini

# 6. Working Directory: Menentukan direktori kerja utama aplikasi di dalam container
WORKDIR /var/www/html

# 7. Salin Kode Aplikasi: Menyalin seluruh file proyek lokal ke /var/www/html (disaring oleh .dockerignore)
COPY . .

# 8. Instalasi Dependensi PHP: Menginstal pustaka vendor di dalam container tanpa mode interaktif
RUN composer install --no-interaction --prefer-dist