# Use an official PHP image with Apache
FROM php:7.4-apache

# Install system dependencies (including JPEG, WebP, FreeType for GD)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Configure GD with JPEG, WebP, FreeType support, then install PHP extensions
RUN docker-php-ext-configure gd \
        --with-jpeg \
        --with-webp \
        --with-freetype \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_mysql mbstring exif pcntl bcmath gd zip intl

# PHP configuration: upload limits & security
RUN echo 'upload_max_filesize = 20M' >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo 'post_max_size = 25M' >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo 'memory_limit = 256M' >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo 'max_execution_time = 120' >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo 'expose_php = Off' >> /usr/local/etc/php/conf.d/security.ini

# Install Composer
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy custom Apache configuration
COPY ./docker/000-default.conf /etc/apache2/sites-available/yii2-app.conf
RUN a2ensite yii2-app

# Disable the default site to prevent conflicts
RUN a2dissite 000-default.conf

# Set working directory to Apache web root
WORKDIR /var/www/html/

# Copy entrypoint script and fix Windows line endings (CRLF → LF)
COPY ./docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]