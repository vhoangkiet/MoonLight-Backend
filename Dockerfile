# Stage 1: Install vendor
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist



# Stage 2: Runtime
FROM richarvey/nginx-php-fpm:latest

ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1
ENV LOG_CHANNEL=stderr
ENV APP_ENV=production
ENV APP_DEBUG=false

WORKDIR /var/www/html


# Copy source code
COPY . /var/www/html


# Copy vendor từ stage 1
COPY --from=vendor /app/vendor /var/www/html/vendor


# Optimize autoload
RUN composer dump-autoload \
    --no-dev \
    --optimize


# Tạo các folder Laravel cần thiết
RUN mkdir -p storage/logs \
    && mkdir -p bootstrap/cache


# Sửa quyền cho toàn bộ project
RUN chown -R www-data:www-data /var/www/html


# Laravel writable directories
RUN chmod -R 775 storage \
    && chmod -R 775 bootstrap/cache


# Opcache
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
 && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
 && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
 && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini


EXPOSE 80
