# MoonLight Backend - Documentation Index

## Tài liệu hệ thống

### 1. [README.md](./README.md)
Tổng quan về hệ thống MoonLight Backend, bao gồm:
- Giới thiệu công nghệ
- Kiến trúc hệ thống
- Cài đặt nhanh
- API Overview
- Security

### 2. [Auth Module](./auth-module.md)
Tài liệu chi tiết về module Authentication:
- API endpoints (Register, Login, Forgot Password)
- Roles & Permissions
- Email templates
- Passport OAuth2 setup

### 3. [Product Module](./product-module.md)
Tài liệu chi tiết về module Product:
- Cấu trúc module
- Models & Relationships
- API Endpoints (Admin & Customer)
- Business Logic (Pricing Service, SKU Generation)
- Validation Rules
- Enums

### 4. [Testing Guide](./testing-guide.md)
Hướng dẫn viết và chạy tests:
- Test structure
- Feature Tests pattern
- Unit Tests pattern
- Factories & Assertions
- Authentication in Tests
- CI/CD Integration

### 5. [Development Guide](./development-guide.md)
Hướng dẫn phát triển:
- Environment setup
- Project structure
- Development workflow
- Coding standards (PSR-12)
- Git workflow
- Debugging
- Performance optimization

### 6. [Deployment Guide](./deployment-guide.md)
Hướng dẫn triển khai:
- VPS Deployment (Ubuntu)
- Docker Deployment
- Laravel Forge
- SSL Certificates
- Queue Workers
- Monitoring & Logging
- Backup Strategy

### 7. [Git Convention](./git-convention.md)
Quy tắc commit và làm việc với Git:
- Conventional Commits
- Branch naming
- Commit message format

## Quick Reference

### Chạy hệ thống
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Chạy tests
```bash
php artisan test --compact
```

### Code style
```bash
vendor/bin/pint
```

### Deploy
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## API Endpoints

### Admin (Prefix: `/api/admin`)
- `GET /products` - List products
- `POST /products` - Create product
- `PUT /products/{id}` - Update product
- `DELETE /products/{id}` - Delete product
- `GET /categories` - List categories
- `GET /discounts` - List discounts
- `GET /vouchers` - List vouchers
- `GET /variants` - List variants

### Customer (Prefix: `/api/v1`)
- `GET /products` - List products
- `GET /products/{id}` - Product detail
- `POST /products/calculate-price` - Calculate price

### Auth (Prefix: `/api/v1/auth`)
- `POST /register` - Register
- `POST /login` - Login
- `POST /password/forgot` - Forgot password
- `PUT /profile` - Update profile

## Liên hệ

- **Issues**: GitHub Issues
- **Email**: support@moonlight.com
