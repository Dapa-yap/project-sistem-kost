FROM php:8.3-apache

# Ekstensi PostgreSQL untuk PDO (Supabase)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

# Batas upload (aplikasi mengizinkan foto sampai 5 MB)
RUN printf "upload_max_filesize=5M\npost_max_size=12M\nexpose_php=Off\n" \
    > /usr/local/etc/php/conf.d/kostku.ini

# Bukti bayar & foto aduan hanya boleh lewat api/berkas.php (dicek login),
# jadi akses langsung ke file-nya diblokir. Foto kamar tetap publik.
RUN printf '<Directory /var/www/html/uploads>\n  <FilesMatch "^(bukti|aduan)-">\n    Require all denied\n  </FilesMatch>\n</Directory>\n' \
    > /etc/apache2/conf-available/kostku.conf \
    && a2enconf kostku

COPY . /var/www/html/
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads

# Apache harus mendengarkan di port dari variabel PORT
COPY start.sh /usr/local/bin/start.sh
RUN sed -i 's/\r$//' /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

CMD ["start.sh"]
