# Multi-stage build untuk production yang optimal & CEPAT
# Stage 1: Build dependencies
FROM php:8.3-fpm-alpine AS builder

WORKDIR /var/www

# Install build dependencies
# libzip-dev:   required for zip extension
# libxml2-dev:  required for xml extension
# oniguruma-dev: required for mbstring extension
# icu-dev:      required for intl extension
# freetype-dev, libjpeg-turbo-dev, libpng-dev, libwebp-dev: required for gd extension
# libexif-dev:  required for exif extension
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libzip-dev \
    zip \
    libxml2-dev \
    oniguruma-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libexif-dev

# Configure gd with freetype & jpeg support
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp

# Install PHP extensions
# Note: json, openssl, tokenizer, ctype, fileinfo are built-in in PHP 8.x
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    bcmath \
    mbstring \
    xml \
    zip \
    intl \
    gd \
    exif

# Install Redis extension
RUN apk add --no-cache $PHPIZE_DEPS linux-headers \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy composer files ONLY
COPY composer.json composer.lock ./

# Install PHP dependencies (production only, no dev)
# --no-scripts skips autoload dump di sini (dikerjakan di production stage)
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

# Stage 2: Production image
FROM php:8.3-fpm-alpine

WORKDIR /var/www

# Install ONLY production runtime dependencies
RUN apk add --no-cache \
    mysql-client \
    git \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    icu-libs \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libexif-dev

# Configure gd with freetype & jpeg support
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp

# Install PHP extensions
# Note: json, openssl, tokenizer, ctype, fileinfo are built-in in PHP 8.x
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    bcmath \
    mbstring \
    xml \
    zip \
    intl \
    gd \
    exif

# Install Redis extension
RUN apk add --no-cache $PHPIZE_DEPS linux-headers \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install opcache untuk performance
RUN docker-php-ext-install opcache

# Copy PHP configuration and fix permissions
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
RUN chmod 644 \
    /usr/local/etc/php/conf.d/99-custom.ini \
    /usr/local/etc/php/conf.d/opcache.ini \
    /usr/local/etc/php-fpm.d/www.conf

# Copy vendor dari builder stage (FAST!)
COPY --from=builder /var/www/vendor /var/www/vendor

# Copy aplikasi (tanpa vendor, tanpa node_modules, tanpa storage/framework/cache)
COPY --chown=www-data:www-data . .

# Create storage directories dengan permission langsung
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/app \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap \
    && chmod -R 755 storage \
    && chmod -R 755 bootstrap/cache

# Generate optimized autoloader (ini cepat, hanya artisan command)
RUN php artisan package:discover --ansi 2>/dev/null || true

# Set user
USER www-data

# Expose port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=5 \
    CMD php -r "exit((int)!file_exists('/var/www/bootstrap/app.php'));"

# Command
CMD ["php-fpm"]