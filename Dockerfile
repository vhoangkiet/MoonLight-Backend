FROM richarvey/nginx-php-fpm:latest

COPY . /var/www/html

# Cấu hình Laravel
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Cài đặt composer
RUN composer install --no-dev --optimize-autoloader

EXPOSE 80
