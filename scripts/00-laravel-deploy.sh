#!/bin/bash
php artisan config:cache
php artisan route:cache
# Nếu có migrate thì thêm dòng dưới
php artisan migrate --force
