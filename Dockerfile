# Stage 1: Chỉ kéo thư viện PHP
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Stage 2: Runtime chính
FROM richarvey/nginx-php-fpm:latest

ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1
ENV LOG_CHANNEL stderr
ENV APP_ENV production
ENV APP_DEBUG false

WORKDIR /var/www/html

# Chỉ copy code PHP, không copy node_modules hay rác frontend
COPY . .
COPY --from=vendor /app/vendor /var/www/html/vendor

# Tối ưu hóa Autoloader
RUN composer dump-autoload --no-dev --optimize

# Quyền hạn storage
RUN chmod -R 775 storage bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache

# Opcache giúp API phản hồi cực nhanh (Latency thấp)
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

EXPOSE 80
