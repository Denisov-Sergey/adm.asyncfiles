FROM php:8.2-fpm

# Системные зависимости
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    libmcrypt-dev \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# PHP-расширения для Битрикс
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        mysqli \
        pdo \
        pdo_mysql \
        xml \
        zip \
        mbstring \
        curl \
        opcache \
        sockets

# PHP-конфигурация для Битрикс
RUN echo "short_open_tag = On" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "display_errors = On" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "date.timezone = Asia/Almaty" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "mbstring.func_overload = 0" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "max_input_vars = 10000" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "realpath_cache_size = 4096K" >> /usr/local/etc/php/conf.d/bitrix.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
