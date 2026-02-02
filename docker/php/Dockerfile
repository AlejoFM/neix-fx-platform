FROM php:8.1-fpm

# Install system dependencies (optimizado: todo en un RUN para menos layers)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libevent-dev \
    && docker-php-ext-install pdo_mysql mysqli mbstring sockets \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files first (para aprovechar cache de Docker)
COPY composer.json composer.lock* ./

# Install dependencies (solo si composer.json cambia)
# NOTA: En desarrollo, las dependencias se instalan en runtime para mayor velocidad
# En producción, descomentar esta línea:
# RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application code
COPY . .

# Set permissions (optimizado: un solo RUN)
RUN chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www

EXPOSE 9000 8081 8082

# Configurar PHP-FPM para escuchar en TCP en lugar de socket
RUN sed -i 's/listen = \/run\/php\/php8.1-fpm.sock/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf || \
    echo "listen = 0.0.0.0:9000" >> /usr/local/etc/php-fpm.d/www.conf

# Composer install se ejecuta en runtime para desarrollo más rápido
# Para servicios WebSocket, el CMD se sobrescribe en docker-compose.yml
CMD ["sh", "-c", "composer install --no-interaction && php-fpm -F"]
