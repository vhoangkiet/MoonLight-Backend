# Deployment Guide

## Overview

Hướng dẫn triển khai MoonLight Backend trên các môi trường khác nhau: Local Development, Staging, và Production.

## Deployment Options

1. **Traditional VPS/Server** (Ubuntu/CentOS)
2. **Docker & Docker Compose**
3. **Laravel Sail** (Development)
4. **Cloud Platforms** (AWS, DigitalOcean, Laravel Forge)

---

## Option 1: VPS Deployment (Ubuntu 22.04)

### Prerequisites

- Ubuntu 22.04 LTS
- PHP 8.3+
- MySQL 8.0+ hoặc MariaDB 10.6+
- Nginx hoặc Apache
- Composer 2.x
- Redis (optional, cho caching/queues)
- Node.js 18+ (cho frontend build nếu cần)

### Step 1: Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y software-properties-common curl git unzip nginx

# Add PHP 8.3 repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP and extensions
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-xml php8.3-mbstring \
    php8.3-curl php8.3-zip php8.3-bcmath php8.3-json php8.3-tokenizer \
    php8.3-fileinfo php8.3-openssl php8.3-pdo php8.3-gd php8.3-intl

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install MySQL
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Install Redis (optional)
sudo apt install -y redis-server

# Install Node.js
sudo apt install -y nodejs npm
```

### Step 2: Database Setup

```bash
# Login to MySQL
sudo mysql -u root -p

# Create database and user
CREATE DATABASE moonlight CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'moonlight'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON moonlight.* TO 'moonlight'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 3: Application Setup

```bash
# Create application directory
sudo mkdir -p /var/www/moonlight-backend
sudo chown -R $USER:$USER /var/www/moonlight-backend

# Clone repository
cd /var/www/moonlight-backend
git clone https://github.com/vhoangkiet/MoonLight-Backend.git .

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
sudo chown -R www-data:www-data /var/www/moonlight-backend
sudo chmod -R 755 /var/www/moonlight-backend
sudo chmod -R 775 /var/www/moonlight-backend/storage
sudo chmod -R 775 /var/www/moonlight-backend/bootstrap/cache

# Create .env file
cp .env.example .env
nano .env
```

**Production .env:**
```env
APP_NAME="MoonLight API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.moonlight.com

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=moonlight
DB_USERNAME=moonlight
DB_PASSWORD=your_secure_password

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=file
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@mg.moonlight.com
MAIL_PASSWORD=your-mailgun-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@moonlight.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PASSPORT_PASSWORD_CLIENT_ID=your-client-id
PASSPORT_PASSWORD_CLIENT_SECRET=your-client-secret
```

```bash
# Generate application key
php artisan key:generate

# Create storage link
php artisan storage:link

# Run migrations
php artisan migrate --force

# Seed database (optional, first time only)
php artisan db:seed --force

# Passport setup
php artisan passport:install --force
php artisan passport:keys --force

# Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Step 4: Nginx Configuration

```bash
sudo nano /etc/nginx/sites-available/moonlight-api
```

**Nginx Config:**
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name api.moonlight.com;
    root /var/www/moonlight-backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req zone=api burst=20 nodelay;
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/moonlight-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Step 5: SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d api.moonlight.com

# Auto-renewal test
sudo certbot renew --dry-run
```

### Step 6: Queue Worker (Supervisor)

```bash
sudo apt install -y supervisor

sudo nano /etc/supervisor/conf.d/moonlight-worker.conf
```

**Worker Config:**
```ini
[program:moonlight-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/moonlight-backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/moonlight-backend/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start moonlight-worker:*
```

### Step 7: Scheduler (Cron)

```bash
sudo crontab -e
```

```
* * * * * cd /var/www/moonlight-backend && php artisan schedule:run >> /dev/null 2>&1
```

### Step 8: Log Rotation

```bash
sudo nano /etc/logrotate.d/moonlight
```

```
/var/www/moonlight-backend/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    sharedscripts
    postrotate
        /usr/sbin/service php8.3-fpm reload > /dev/null 2>&1
    endscript
}
```

---

## Option 2: Docker Deployment

### Dockerfile

```dockerfile
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    redis-tools

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY . /var/www

# Install dependencies
RUN composer install --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
```

### docker-compose.yml

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: moonlight-backend
    container_name: moonlight-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
    networks:
      - moonlight-network

  nginx:
    image: nginx:alpine
    container_name: moonlight-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx:/etc/nginx/conf.d
      - ./docker/ssl:/etc/nginx/ssl
    networks:
      - moonlight-network

  db:
    image: mysql:8.0
    container_name: moonlight-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: moonlight
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_PASSWORD: user_password
      MYSQL_USER: moonlight
    volumes:
      - dbdata:/var/lib/mysql
    networks:
      - moonlight-network

  redis:
    image: redis:alpine
    container_name: moonlight-redis
    restart: unless-stopped
    networks:
      - moonlight-network

networks:
  moonlight-network:
    driver: bridge

volumes:
  dbdata:
    driver: local
```

### Deploy with Docker

```bash
# Build and start
docker-compose up -d --build

# Run migrations
docker-compose exec app php artisan migrate --force

# Passport setup
docker-compose exec app php artisan passport:install --force

# Cache optimization
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

---

## Option 3: Laravel Forge

### Setup on Laravel Forge

1. **Connect Server**
   - Connect your VPS to Forge
   - Choose server type: VPS / Custom VPS

2. **Create Site**
   - Domain: `api.moonlight.com`
   - Project type: Laravel
   - PHP Version: 8.3

3. **Deploy Script**
```bash
cd /home/forge/api.moonlight.com
git pull origin $FORGE_SITE_BRANCH

# Install dependencies
$FORGE_COMPOSER install --no-dev --optimize-autoloader

# Run migrations
$FORGE_PHP artisan migrate --force

# Clear and cache optimizations
$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:cache
$FORGE_PHP artisan view:cache
$FORGE_PHP artisan event:cache

# Restart queue workers
$FORGE_PHP artisan queue:restart

# Reload PHP-FPM
(echo "reload" | sudo -S service $FORGE_PHP_FPM reload) > /dev/null 2>&1
```

4. **SSL Certificate**
   - Enable Let's Encrypt in Forge
   - Auto-renewal enabled

5. **Queue Workers**
   - Add queue worker in Forge dashboard
   - Driver: Redis
   - Processes: 2

6. **Scheduler**
   - Enable scheduler in Forge
   - Frequency: Every minute

---

## Environment-Specific Configurations

### Production Checklist

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] Database credentials secured
- [ ] Passport keys generated
- [ ] SSL certificate installed
- [ ] Queue workers configured
- [ ] Scheduler (cron) configured
- [ ] Log rotation configured
- [ ] Backup strategy in place
- [ ] Monitoring configured
- [ ] Error tracking (Sentry/Bugsnag)
- [ ] Rate limiting enabled
- [ ] CORS properly configured
- [ ] Cache driver set to Redis
- [ ] Queue driver set to Redis
- [ ] File uploads configured (S3/Local)

### Performance Optimization

```bash
# 1. PHP OPcache
# Edit /etc/php/8.3/fpm/php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1

# 2. PHP-FPM Configuration
# Edit /etc/php/8.3/fpm/pool.d/www.conf
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

# 3. Nginx Gzip
# Add to nginx.conf
gzip on;
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types text/plain text/css text/xml application/json application/javascript application/rss+xml application/atom+xml image/svg+xml;

# 4. Laravel Optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

### Security Headers

```nginx
# Add to nginx server block
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self' https:; media-src 'self'; object-src 'none'; frame-src 'none';" always;
```

---

## Monitoring & Logging

### Laravel Telescope (Local only)
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### Production Logging
```env
LOG_CHANNEL=daily
LOG_LEVEL=error
LOG_DEPRECATIONS_CHANNEL=null
```

### Health Checks

```bash
# Check application health
curl https://api.moonlight.com/api/health

# Check queue workers
php artisan queue:monitor redis

# Check scheduler
php artisan schedule:list
```

---

## Backup Strategy

### Database Backup

```bash
# Manual backup
mysqldump -u root -p moonlight > backup_$(date +%Y%m%d_%H%M%S).sql

# Automated backup (cron)
0 2 * * * mysqldump -u root -p moonlight > /backups/moonlight_$(date +\%Y\%m\%d).sql
```

### File Backup

```bash
# Storage files
rsync -avz /var/www/moonlight-backend/storage/app /backups/storage/

# .env file
rsync -avz /var/www/moonlight-backend/.env /backups/config/

# Passport keys
rsync -avz /var/www/moonlight-backend/storage/oauth-private.key /backups/keys/
rsync -avz /var/www/moonlight-backend/storage/oauth-public.key /backups/keys/
```

---

## Troubleshooting

### 500 Internal Server Error

```bash
# Check Laravel logs
tail -f /var/www/moonlight-backend/storage/logs/laravel.log

# Check Nginx error logs
tail -f /var/log/nginx/error.log

# Check PHP-FPM logs
tail -f /var/log/php8.3-fpm.log

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Database Connection Issues

```bash
# Test database connection
php artisan tinker --execute="dd(DB::connection()->getPdo())"

# Check MySQL status
sudo systemctl status mysql

# Restart MySQL
sudo systemctl restart mysql
```

### Permission Issues

```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/moonlight-backend
sudo chmod -R 755 /var/www/moonlight-backend
sudo chmod -R 775 /var/www/moonlight-backend/storage
sudo chmod -R 775 /var/www/moonlight-backend/bootstrap/cache

# Set ACL (if needed)
sudo setfacl -R -m u:www-data:rwX /var/www/moonlight-backend/storage
sudo setfacl -dR -m u:www-data:rwX /var/www/moonlight-backend/storage
```

### Queue Workers Not Processing

```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart moonlight-worker:*

# Check worker logs
tail -f /var/www/moonlight-backend/storage/logs/worker.log
```

---

## Rollback Strategy

### Database Rollback
```bash
# Rollback specific migration
php artisan migrate:rollback --step=1

# Rollback to specific batch
php artisan migrate:rollback --batch=3

# Reset all migrations
php artisan migrate:reset
```

### Code Rollback
```bash
# Revert to previous commit
git log --oneline -10
git revert HEAD
git push

# Or reset to specific commit
git reset --hard COMMIT_HASH
git push --force
```

---

## Post-Deployment Verification

```bash
# 1. Check application is running
curl -I https://api.moonlight.com

# 2. Test API endpoints
curl https://api.moonlight.com/api/v1/products

# 3. Check database connection
php artisan tinker --execute="dd(DB::connection()->getPdo())"

# 4. Verify queues are working
php artisan queue:work --once --tries=1

# 5. Test email delivery
php artisan tinker --execute="Mail::raw('Test', fn(\$m) => \$m->to('test@example.com')->subject('Test'));"

# 6. Check logs
tail -f storage/logs/laravel.log
```

---

## Support & Resources

- **Laravel Deployment Docs**: https://laravel.com/docs/12.x/deployment
- **Nginx Docs**: https://nginx.org/en/docs/
- **Docker Docs**: https://docs.docker.com/
- **Let's Encrypt**: https://letsencrypt.org/docs/
