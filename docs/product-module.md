# Product Module Documentation

## Overview

Module Product cung cấp các chức năng quản lý sản phẩm, danh mục, khuyến mãi, voucher và tính toán giá cho hệ thống MoonLight.

## Cấu trúc Module

```
Modules/Product/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── Admin/           # Admin API Controllers
│   │   │   │   │   ├── CategoryController.php
│   │   │   │   │   ├── DiscountController.php
│   │   │   │   │   ├── ProductController.php
│   │   │   │   │   ├── ProductVariantController.php
│   │   │   │   │   └── VoucherController.php
│   │   │   │   └── Customer/        # Customer API Controllers
│   │   │   │       └── ProductController.php
│   │   ├── Requests/               # Form Request Validation
│   │   │   ├── Admin/
│   │   │   │   ├── StoreCategoryRequest.php
│   │   │   │   ├── StoreDiscountRequest.php
│   │   │   │   ├── StoreProductRequest.php
│   │   │   │   ├── StoreProductVariantRequest.php
│   │   │   │   ├── StoreVoucherRequest.php
│   │   │   │   ├── UpdateCategoryRequest.php
│   │   │   │   ├── UpdateDiscountRequest.php
│   │   │   │   ├── UpdateProductRequest.php
│   │   │   │   ├── UpdateProductVariantRequest.php
│   │   │   │   └── UpdateVoucherRequest.php
│   │   │   └── Customer/
│   │   │       └── CalculatePriceRequest.php
│   │   └── Resources/              # API Resources
│   │       ├── CategoryResource.php
│   │       ├── DiscountResource.php
│   │       ├── ProductDetailResource.php
│   │       ├── ProductListResource.php
│   │       ├── ProductVariantResource.php
│   │       └── VoucherResource.php
│   ├── Models/                   # Eloquent Models
│   │   ├── Category.php
│   │   ├── Discount.php
│   │   ├── Product.php
│   │   ├── ProductVariant.php
│   │   ├── Voucher.php
│   │   └── VoucherUse.php
│   ├── Services/                 # Business Logic
│   │   ├── CategoryService.php
│   │   ├── DiscountService.php
│   │   ├── PricingService.php
│   │   ├── ProductService.php
│   │   ├── ProductVariantService.php
│   │   └── VoucherService.php
│   ├── Repositories/
│   │   ├── Contracts/           # Repository Interfaces
│   │   │   ├── CategoryRepositoryInterface.php
│   │   │   ├── DiscountRepositoryInterface.php
│   │   │   ├── ProductRepositoryInterface.php
│   │   │   ├── ProductVariantRepositoryInterface.php
│   │   │   └── VoucherRepositoryInterface.php
│   │   └── Eloquent/            # Repository Implementations
│   │       ├── CategoryRepository.php
│   │       ├── DiscountRepository.php
│   │       ├── ProductRepository.php
│   │       ├── ProductVariantRepository.php
│   │       └── VoucherRepository.php
│   └── Providers/               # Service Providers
│       └── ProductServiceProvider.php
├── config/                      # Module Configuration
│   └── config.php
├── database/
│   ├── factories/              # Model Factories
│   │   ├── CategoryFactory.php
│   │   ├── DiscountFactory.php
│   │   ├── ProductFactory.php
│   │   ├── ProductVariantFactory.php
│   │   ├── VoucherFactory.php
│   │   └── VoucherUseFactory.php
│   └── migrations/             # Module Migrations
│       ├── create_categories_table.php
│       ├── create_discounts_table.php
│       ├── create_discount_product_table.php
│       ├── create_products_table.php
│       ├── create_product_variants_table.php
│       ├── create_vouchers_table.php
│       └── create_voucher_uses_table.php
├── routes/                      # Route Definitions
│   └── api.php
└── module.json                 # Module Metadata
```

## Models

### Product
| Field | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| name | string | Product name |
| slug | string | URL-friendly name |
| description | text | Product description |
| category_id | bigint | Foreign key to categories |
| status | enum | `draft`, `active`, `inactive`, `archived` |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Last update time |
| deleted_at | timestamp | Soft delete timestamp |

### ProductVariant
| Field | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| product_id | bigint | Foreign key to products |
| sku | string | Stock keeping unit (unique) |
| price | decimal | Variant price |
| stock | integer | Available quantity |
| shape | string | Shape (e.g., 'Round', 'Oval') |
| length | string | Length (e.g., '50cm') |
| tonal_palette | string | Color palette (e.g., 'Warm', 'Cool') |
| size | string | Size (e.g., 'Small', 'Medium', 'Large') |
| status | enum | `active`, `inactive`, `out_of_stock`, `discontinued` |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Last update time |
| deleted_at | timestamp | Soft delete timestamp |

### Category
| Field | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| name | string | Category name |
| slug | string | URL-friendly name |
| description | text | Category description |
| is_active | boolean | Active status |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Last update time |

### Discount
| Field | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| code | string | Unique discount code |
| name | string | Discount name |
| description | text | Description |
| type | enum | `percentage`, `fixed` |
| value | decimal | Discount value |
| min_order_amount | decimal | Minimum order to apply |
| max_discount_amount | decimal | Maximum discount cap |
| start_date | datetime | Discount start time |
| end_date | datetime | Discount end time |
| is_active | boolean | Active status |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Last update time |

### Voucher
| Field | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| code | string | Unique voucher code |
| name | string | Voucher name |
| type | enum | `percentage`, `fixed` |
| value | decimal | Voucher value |
| min_order_amount | decimal | Minimum order to apply |
| max_discount_amount | decimal | Maximum discount cap |
| usage_limit | integer | Max usage count (null = unlimited) |
| usage_count | integer | Current usage count |
| valid_from | datetime | Valid from |
| valid_until | datetime | Valid until |
| is_active | boolean | Active status |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Last update time |

### VoucherUse
| Field | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| voucher_id | bigint | Foreign key to vouchers |
| user_id | bigint | User who used the voucher |
| order_id | bigint | Related order |
| discount_amount | decimal | Discount amount applied |
| used_at | timestamp | Usage timestamp |

## API Endpoints - Admin

### Products

#### List Products
```
GET /api/admin/products
```
**Query Parameters:**
- `search` (string) - Search by name
- `status` (string) - Filter by status
- `category_id` (integer) - Filter by category
- `sort_by` (string) - Sort field: `name`, `price`, `created_at`
- `sort_order` (string) - `asc` or `desc`
- `per_page` (integer) - Items per page (default: 15)

**Response:**
```json
{
  "success": true,
  "message": "Products retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Product Name",
      "slug": "product-name",
      "description": "Description",
      "status": "active",
      "category": { ... },
      "variants": [ ... ],
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

#### Create Product
```
POST /api/admin/products
```
**Request Body:**
```json
{
  "name": "Product Name",
  "slug": "product-name",
  "description": "Description",
  "category_id": 1,
  "status": "active"
}
```

#### Update Product
```
PUT /api/admin/products/{id}
```
**Request Body:**
```json
{
  "name": "Updated Name",
  "slug": "updated-name",
  "description": "Updated description",
  "category_id": 2,
  "status": "active"
}
```

#### Delete Product
```
DELETE /api/admin/products/{id}
```

### Product Variants

#### List Variants
```
GET /api/admin/variants
```

#### Create Variant
```
POST /api/admin/variants
```
**Request Body:**
```json
{
  "product_id": 1,
  "price": 100.00,
  "stock": 50,
  "shape": "Round",
  "length": "50cm",
  "tonal_palette": "Warm",
  "size": "Medium"
}
```

#### Update Variant
```
PUT /api/admin/variants/{id}
```

#### Update Stock
```
POST /api/admin/variants/{id}/stock
```
**Request Body:**
```json
{
  "stock": 100
}
```

#### Update Status
```
POST /api/admin/variants/{id}/status
```
**Request Body:**
```json
{
  "status": "active"
}
```

### Categories

#### List Categories
```
GET /api/admin/categories
```

#### Create Category
```
POST /api/admin/categories
```
**Request Body:**
```json
{
  "name": "Category Name",
  "slug": "category-name",
  "description": "Description",
  "is_active": true
}
```

### Discounts

#### List Discounts
```
GET /api/admin/discounts
```

#### Create Discount
```
POST /api/admin/discounts
```
**Request Body:**
```json
{
  "code": "SALE20",
  "name": "Spring Sale",
  "description": "20% off",
  "type": "percentage",
  "value": 20,
  "min_order_amount": 100,
  "max_discount_amount": 50,
  "start_date": "2024-01-01 00:00:00",
  "end_date": "2024-12-31 23:59:59",
  "is_active": true,
  "product_ids": [1, 2, 3],
  "variant_ids": [1, 2]
}
```

### Vouchers

#### List Vouchers
```
GET /api/admin/vouchers
```

#### Create Voucher
```
POST /api/admin/vouchers
```
**Request Body:**
```json
{
  "code": "VOUCHER2024",
  "name": "New Year Voucher",
  "type": "fixed",
  "value": 50,
  "min_order_amount": 200,
  "max_discount_amount": 50,
  "usage_limit": 100,
  "valid_from": "2024-01-01 00:00:00",
  "valid_until": "2024-12-31 23:59:59",
  "is_active": true
}
```

## API Endpoints - Customer

### Calculate Price
```
POST /api/v1/products/calculate-price
```
**Request Body:**
```json
{
  "product_id": 1,
  "variant_id": 1,
  "voucher_code": "VOUCHER2024"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Price calculated successfully",
  "data": {
    "product_id": 1,
    "variant_id": 1,
    "base_price": 100.00,
    "discount_amount": 20.00,
    "voucher_amount": 10.00,
    "final_price": 70.00,
    "currency": "VND",
    "applied_discounts": [...],
    "applied_voucher": { ... }
  }
}
```

## Business Logic

### Pricing Service

PricingService tính toán giá cuối cùng của sản phẩm dựa trên:

1. **Base Price**: Giá gốc của variant
2. **Discounts**: Các chương trình giảm giá đang active
3. **Voucher**: Mã giảm giá (nếu có)

**Rules:**
- Discount chỉ áp dụng khi `is_active = true` và trong thời gian hiệu lực
- Voucher chỉ áp dụng khi `is_active = true`, trong thời gian hiệu lực, và chưa hết số lần sử dụng
- Tổng discount = tổng các discount + voucher
- Final price = base price - total discount

### SKU Generation

SKU của variant được tự động generate theo format:
```
{SHAPE_ABBREV}-{LENGTH_ABBREV}-{TONAL_ABBREV}-{SIZE_ABBREV}
```

Ví dụ: `ROU-50C-WAR-MED` = Round 50cm Warm Medium

### Unique Constraints

- **Product**: `slug` phải unique
- **ProductVariant**: `sku` phải unique
- **Category**: `slug` phải unique
- **Discount**: `code` phải unique
- **Voucher**: `code` phải unique

## Validation Rules

### Product
- `name`: required, string, max 255
- `slug`: required, string, max 255, unique
- `category_id`: required, exists:categories,id
- `status`: required, in:draft,active,inactive,archived

### ProductVariant
- `product_id`: required, exists:products,id
- `price`: required, numeric, min 0
- `stock`: required, integer, min 0
- `shape`: required, string
- `length`: required, string
- `tonal_palette`: required, string
- `size`: required, string
- `sku`: unique (auto-generated nếu không cung cấp)

### Discount
- `code`: required, string, max 50, unique
- `type`: required, in:percentage,fixed
- `value`: required, numeric, min 0
- `start_date`: required, date
- `end_date`: required, date, after_or_equal:start_date

### Voucher
- `code`: required, string, max 50, unique
- `type`: required, in:percentage,fixed
- `value`: required, numeric, min 0
- `valid_from`: required, date
- `valid_until`: required, date, after_or_equal:valid_from

## Enums

### ProductStatus
- `draft` - Đang soạn thảo
- `active` - Đang bán
- `inactive` - Tạm ngừng
- `archived` - Đã lưu trữ

### VariantStatus
- `active` - Đang bán
- `inactive` - Tạm ngừng
- `out_of_stock` - Hết hàng
- `discontinued` - Ngừng sản xuất

### DiscountType
- `percentage` - Giảm theo phần trăm
- `fixed` - Giảm số tiền cố định

## Testing

### Run Product Module Tests
```bash
# All Product tests
php artisan test --filter=Product

# Specific test files
php artisan test --filter=ProductTest
php artisan test --filter=ProductVariantTest
php artisan test --filter=DiscountTest
php artisan test --filter=VoucherTest
php artisan test --filter=CustomerProductTest
php artisan test --filter=ProductServiceTest
```

### Test Coverage
- Feature Tests: API endpoints, validation, response structure
- Unit Tests: Service logic, repository queries, pricing calculation

## Events

### Product Events
- `ProductCreated` - Khi tạo sản phẩm mới
- `ProductUpdated` - Khi cập nhật sản phẩm
- `ProductDeleted` - Khi xóa sản phẩm

### Voucher Events
- `VoucherUsed` - Khi voucher được sử dụng
- `VoucherExpired` - Khi voucher hết hạn

## Queues

Các tác vụ nặng được xử lý qua Queue:
- Tính toán giá cho nhiều sản phẩm
- Generate báo cáo doanh thu
- Cleanup dữ liệu cũ

## Caching

Cache được sử dụng cho:
- Danh sách categories (TTL: 1 giờ)
- Product details (TTL: 30 phút)
- Active discounts (TTL: 10 phút)

Xóa cache khi:
- Cập nhật category
- Cập nhật product
- Thay đổi discount/voucher

## Changelog

### 2024-03-29
- Fix test cases cho tất cả module
- Cập nhật Pricing Service logic
- Thêm validation cho stock không âm

### 2024-03-28
- Thêm Product Variant management
- Thêm SKU auto-generation
- Thêm bulk operations cho products

### 2024-03-27
- Initial release
- Product CRUD
- Category management
- Discount & Voucher system
