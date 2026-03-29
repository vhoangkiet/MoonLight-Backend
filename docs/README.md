# MoonLight Backend - Hệ thống Quản lý Sản phẩm & Bán hàng

## Tổng quan

MoonLight Backend là hệ thống API được xây dựng trên **Laravel 12** với kiến trúc **Modular Monolith**, cung cấp các tính năng quản lý sản phẩm, khuyến mãi, voucher và tính toán giá.

## Công nghệ sử dụng

| Công nghệ | Phiên bản | Mục đích |
|-----------|-----------|----------|
| Laravel Framework | 12.x | Core framework |
| PHP | 8.3 | Ngôn ngữ lập trình |
| Laravel Passport | 13.x | OAuth2 Authentication |
| Spatie Permission | 6.x | Role-based access control |
| Spatie Media Library | 11.x | Quản lý media files |
| SQLite | 3.x | Database (testing) |
| MySQL | 8.x | Database (production) |

## Kiến trúc hệ thống

```
MoonLight-Backend/
├── Modules/                    # Modular Architecture
│   ├── Auth/                  # Authentication & Authorization
│   ├── Product/               # Product, Category, Discount, Voucher
│   └── User/                # User management
├── app/
│   ├── Http/Controllers/    # Base Controllers
│   ├── Models/             # Shared Models (User, Address)
│   └── Traits/             # Shared Traits (ApiResponse)
├── database/
│   ├── factories/          # Model Factories
│   ├── migrations/         # Database Migrations
│   └── seeders/           # Database Seeders
├── tests/
│   ├── Feature/           # Feature Tests
│   └── Unit/             # Unit Tests
└── docs/                  # Documentation
```

## Module Structure

Mỗi module tuân thủ cấu trúc **3 lớp (3-Layer Architecture)**:

```
Modules/{ModuleName}/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/    # API Controllers
│   │   ├── Requests/           # Form Request Validation
│   │   └── Resources/          # API Resources
│   ├── Models/                # Eloquent Models
│   ├── Services/              # Business Logic
│   ├── Repositories/
│   │   ├── Contracts/        # Repository Interfaces
│   │   └── Eloquent/         # Repository Implementations
│   └── Providers/             # Service Providers
├── config/                    # Module Configuration
├── database/
│   ├── factories/            # Model Factories
│   └── migrations/           # Module Migrations
├── routes/                    # Route Definitions
└── module.json               # Module Metadata
```

## API Response Format

Tất cả API responses tuân thủ format chuẩn:

### Success Response
```json
{
  "success": true,
  "message": "Action completed successfully",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": { ... }  // Validation errors (optional)
}
```

## Các Module chính

### 1. Auth Module
- **Mục đích**: Xác thực và phân quyền người dùng
- **Features**:
  - Đăng ký/Đăng nhập (Passport OAuth2)
  - Quản lý roles và permissions
  - Quên mật khẩu, reset password
  - Quản lý profile người dùng
- **Tài liệu**: [auth-module.md](./auth-module.md)

### 2. Product Module
- **Mục đích**: Quản lý sản phẩm, danh mục, khuyến mãi
- **Features**:
  - Quản lý sản phẩm (CRUD)
  - Quản lý danh mục (Category)
  - Quản lý biến thể sản phẩm (Product Variant)
  - Quản lý giảm giá (Discount)
  - Quản lý voucher
  - Tính toán giá (Pricing Service)
- **Tài liệu**: [product-module.md](./product-module.md)

### 3. User Module
- **Mục đích**: Quản lý thông tin người dùng
- **Features**:
  - Quản lý địa chỉ
  - Thông tin cá nhân

## Cài đặt & Chạy

### Requirements
- PHP 8.3+
- Composer 2.x
- MySQL 8.x hoặc SQLite 3.x
- Node.js 18+ (cho frontend build)

### Installation

```bash
# Clone repository
git clone https://github.com/vhoangkiet/MoonLight-Backend.git
cd MoonLight-Backend

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate --seed

# Passport setup
php artisan passport:install

# Run development server
php artisan serve
# hoặc
composer run dev
```

### Testing

```bash
# Run all tests
php artisan test --compact

# Run specific test file
php artisan test --filter=ProductTest

# Run with coverage
php artisan test --coverage
```

## Quy ước phát triển

### Git Conventions
Xem [git-convention.md](./git-convention.md)

### Code Style
- Tuân thủ **PSR-12**
- Sử dụng **Laravel Pint** cho code formatting
- Chạy `vendor/bin/pint` trước khi commit

### Testing
- **Feature Tests**: Kiểm tra API endpoints
- **Unit Tests**: Kiểm tra business logic
- Sử dụng `RefreshDatabase` trait cho tất cả tests
- Đặt tên test method rõ ràng: `test_{action}_{expected_result}`

## API Documentation

### Admin Endpoints (Prefix: `/api/admin`)

| Module | Endpoint | Mô tả |
|--------|----------|-------|
| Auth | `POST /auth/login` | Admin login |
| Product | `GET /products` | List products |
| Product | `POST /products` | Create product |
| Product | `PUT /products/{id}` | Update product |
| Product | `DELETE /products/{id}` | Delete product |
| Category | `GET /categories` | List categories |
| Discount | `GET /discounts` | List discounts |
| Voucher | `GET /vouchers` | List vouchers |
| Variant | `GET /variants` | List variants |

### Customer Endpoints (Prefix: `/api/v1`)

| Endpoint | Mô tả |
|----------|-------|
| `GET /products` | List products (public) |
| `GET /products/{id}` | Product detail |
| `POST /products/calculate-price` | Calculate price with discount/voucher |
| `GET /categories` | List categories (public) |

## Security

- **Authentication**: Laravel Passport (OAuth2)
- **Authorization**: Spatie Permission (RBAC)
- **Rate Limiting**: 5 requests/phút cho login
- **CORS**: Configured for frontend access
- **Validation**: Form Request validation cho tất cả inputs

## Logs & Monitoring

- **Laravel Telescope**: Debug và monitoring (local)
- **Laravel Pail**: Real-time log monitoring
- **Log Viewer**: Web-based log viewer tại `/log-viewer`

## Deployment

### Docker
```bash
docker build -t moonlight-backend .
docker run -p 8000:8000 moonlight-backend
```

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure database connection
- [ ] Set up queue worker (nếu cần)
- [ ] Configure mail driver
- [ ] Set up SSL certificate
- [ ] Configure rate limiting

## Tài liệu liên quan

- [Auth Module](./auth-module.md) - Chi tiết về authentication
- [Product Module](./product-module.md) - Chi tiết về product management
- [Git Convention](./git-convention.md) - Quy tắc commit
- [API Documentation](./api-documentation.md) - Chi tiết API endpoints

## Support

- **Issues**: [GitHub Issues](https://github.com/vhoangkiet/MoonLight-Backend/issues)
- **Email**: support@moonlight.com

## License

MIT License - Copyright (c) 2024 MoonLight Team
