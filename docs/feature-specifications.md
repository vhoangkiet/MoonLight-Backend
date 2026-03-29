# Đặc tả Tính năng - Feature Specifications

## 1. Product Management Module

### 1.1 Product CRUD

**Feature ID**: PROD-001
**Priority**: High
**Status**: Implemented

#### Mô tả
Cho phép Admin và Staff quản lý sản phẩm: tạo, xem, cập nhật, xóa (soft delete).

#### Yêu cầu Chức năng

**Create Product**:
- Input: name, slug, description, category_id, status
- Validation: name required, slug unique, category_id exists
- Output: Product object với đầy đủ thông tin
- Error cases: Duplicate slug, invalid category

**Read Product**:
- List: Pagination (15 items/page), filtering, sorting
- Detail: Full thông tin kèm category và variants
- Filters: search (name), status, category_id
- Sort: name, created_at, updated_at

**Update Product**:
- Input: Tương tự Create
- Validation: Slug unique nếu thay đổi
- Auto-refresh data sau update
- Clear cache liên quan

**Delete Product**:
- Soft delete (giữ lại dữ liệu)
- Cascade delete variants
- Chỉ Admin mới có quyền
- Kiểm tra không có đơn hàng active

#### API Endpoints
```
GET    /api/admin/products          - List
POST   /api/admin/products          - Create
GET    /api/admin/products/{id}     - Detail
PUT    /api/admin/products/{id}     - Update
DELETE /api/admin/products/{id}     - Delete
```

#### Data Model
```php
Product {
  id: bigint
  name: string(255)
  slug: string(255) - unique
  description: text - nullable
  category_id: bigint - foreign key
  status: enum('draft', 'active', 'inactive', 'archived')
  created_at: timestamp
  updated_at: timestamp
  deleted_at: timestamp - nullable
}
```

### 1.2 Product Variant Management

**Feature ID**: PROD-002
**Priority**: High
**Status**: Implemented

#### Mô tả
Quản lý biến thể sản phẩm với các thuộc tính (shape, length, tonal, size) và SKU auto-generation.

#### Yêu cầu Chức năng

**Create Variant**:
- Input: product_id, price, stock, shape, length, tonal_palette, size
- Auto-generate SKU từ 4 thuộc tính
- Kiểm tra duplicate SKU
- Kiểm tra duplicate combination trong cùng product

**Update Variant**:
- Update price, stock, status
- Không cho phép đổi thuộc tính (shape, length, tonal, size)
- Nếu cần đổi → Tạo variant mới

**Update Stock**:
- Dedicated endpoint: POST /variants/{id}/stock
- Validation: stock >= 0
- Ghi log thay đổi kho
- Có thể cập nhật hàng loạt (bulk update)

**SKU Generation**:
```
Format: {SHAPE}-{LENGTH}-{TONAL}-{SIZE}
Ví dụ: ROU-50C-WAR-MED

Abbreviations:
- Shape: First 3 chars uppercase (ROUND → ROU)
- Length: Remove 'cm', add 'C' (50cm → 50C)
- Tonal: First 3 chars uppercase (WARM → WAR)
- Size: First 3 chars uppercase (MEDIUM → MED)
```

#### API Endpoints
```
GET    /api/admin/variants          - List
POST   /api/admin/variants          - Create
PUT    /api/admin/variants/{id}     - Update
POST   /api/admin/variants/{id}/stock     - Update stock
POST   /api/admin/variants/{id}/status    - Update status
DELETE /api/admin/variants/{id}     - Delete
POST   /api/admin/variants/bulk-update    - Bulk operations
```

#### Data Model
```php
ProductVariant {
  id: bigint
  product_id: bigint - foreign key
  sku: string(255) - unique
  price: decimal(12,2)
  stock: integer - default 0
  shape: string(50)
  length: string(50)
  tonal_palette: string(50)
  size: string(50)
  status: enum('active', 'inactive', 'out_of_stock', 'discontinued')
  created_at: timestamp
  updated_at: timestamp
  deleted_at: timestamp - nullable
}
```

### 1.3 Category Management

**Feature ID**: PROD-003
**Priority**: Medium
**Status**: Implemented

#### Mô tả
Quản lý danh mục sản phẩm đơn giản (không nested).

#### Yêu cầu Chức năng

**Category CRUD**:
- Simple CRUD operations
- Name và slug required
- Slug unique
- Có thể active/inactive

**Product Count**:
- Hiển thị số lượng sản phẩm trong mỗi category
- Auto-update khi product thêm/xóa

#### API Endpoints
```
GET    /api/admin/categories        - List
POST   /api/admin/categories        - Create
PUT    /api/admin/categories/{id}   - Update
DELETE /api/admin/categories/{id}   - Delete
```

---

## 2. Pricing & Promotion Module

### 2.1 Discount Management

**Feature ID**: PRICE-001
**Priority**: High
**Status**: Implemented

#### Mô tả
Tạo và quản lý chương trình giảm giá cho sản phẩm.

#### Yêu cầu Chức năng

**Create Discount**:
- Input: code, name, description, type, value, min_order_amount, max_discount_amount, start_date, end_date, is_active
- Validation: code unique, end_date > start_date
- Type: percentage | fixed
- Có thể áp dụng cho toàn bộ sản phẩm hoặc chỉ định cụ thể

**Apply Discount**:
- Discount chỉ áp dụng khi active và trong thời hạn
- Có thể áp dụng nhiều discount cùng lúc
- Tổng discount = sum của tất cả discount
- Respect max_discount_amount limit

**Discount Types**:
- **Percentage**: Giảm theo % (20% → giảm 20% giá gốc)
- **Fixed**: Giảm số tiền cố định (50000 → giảm 50,000 VNĐ)

#### API Endpoints
```
GET    /api/admin/discounts         - List
POST   /api/admin/discounts         - Create
PUT    /api/admin/discounts/{id}    - Update
DELETE /api/admin/discounts/{id}   - Delete
POST   /api/admin/discounts/{id}/products   - Attach products
DELETE /api/admin/discounts/{id}/products/{product_id}  - Detach product
```

#### Data Model
```php
Discount {
  id: bigint
  code: string(50) - unique
  name: string(255)
  description: text - nullable
  type: enum('percentage', 'fixed')
  value: decimal(12,2)
  min_order_amount: decimal(12,2) - nullable
  max_discount_amount: decimal(12,2) - nullable
  start_date: datetime
  end_date: datetime
  is_active: boolean - default true
  created_at: timestamp
  updated_at: timestamp
}

// Pivot table: discount_product
discount_product {
  discount_id: bigint
  product_id: bigint
  product_variant_id: bigint - nullable
}
```

### 2.2 Voucher Management

**Feature ID**: PRICE-002
**Priority**: High
**Status**: Implemented

#### Mô tả
Tạo và quản lý voucher (mã giảm giá) cho khách hàng.

#### Yêu cầu Chức năng

**Create Voucher**:
- Input: code, name, type, value, min_order_amount, max_discount_amount, usage_limit, valid_from, valid_until, is_active
- Validation: code unique, valid_until > valid_from
- usage_limit: null = unlimited

**Use Voucher**:
- Kiểm tra code tồn tại
- Kiểm tra còn hiệu lực (valid_from <= now <= valid_until)
- Kiểm tra còn lượt sử dụng (usage_count < usage_limit)
- Kiểm tra đạt min_order_amount
- Tăng usage_count khi sử dụng
- Ghi log vào voucher_uses

**Voucher Types**:
- **Percentage**: Giảm theo %
- **Fixed**: Giảm số tiền cố định

#### API Endpoints
```
GET    /api/admin/vouchers          - List
POST   /api/admin/vouchers          - Create
PUT    /api/admin/vouchers/{id}     - Update
DELETE /api/admin/vouchers/{id}     - Delete
GET    /api/v1/vouchers/validate    - Validate voucher (customer)
```

#### Data Model
```php
Voucher {
  id: bigint
  code: string(50) - unique
  name: string(255)
  type: enum('percentage', 'fixed')
  value: decimal(12,2)
  min_order_amount: decimal(12,2) - nullable
  max_discount_amount: decimal(12,2) - nullable
  usage_limit: integer - nullable
  usage_count: integer - default 0
  valid_from: datetime
  valid_until: datetime
  is_active: boolean - default true
  created_at: timestamp
  updated_at: timestamp
}

VoucherUse {
  id: bigint
  voucher_id: bigint - foreign key
  user_id: bigint - foreign key
  order_id: bigint - nullable
  discount_amount: decimal(12,2)
  used_at: timestamp
}
```

### 2.3 Price Calculation

**Feature ID**: PRICE-003
**Priority**: High
**Status**: Implemented

#### Mô tả
Tính toán giá cuối cùng của sản phẩm dựa trên base price, discount, và voucher.

#### Yêu cầu Chức năng

**Calculate Price**:
```
Input: product_id, variant_id, voucher_code (optional)
Output: {
  base_price,
  discount_amount,
  voucher_amount,
  final_price,
  applied_discounts[],
  applied_voucher
}
```

**Algorithm**:
1. Lấy base_price từ variant
2. Tìm tất cả active discounts cho product/variant
3. Tính discount_amount cho mỗi discount
4. Áp dụng max_discount_amount limit
5. Nếu có voucher → validate và tính voucher_amount
6. final_price = base_price - discount_amount - voucher_amount
7. Đảm bảo final_price >= 0

**Rules**:
- Discount áp dụng theo thứ tự: percentage trước, fixed sau
- Có thể có nhiều discount cùng lúc
- Chỉ có thể dùng 1 voucher mỗi lần tính giá

#### API Endpoints
```
POST   /api/v1/products/calculate-price     - Calculate price
```

#### Response Format
```json
{
  "success": true,
  "message": "Price calculated successfully",
  "data": {
    "product_id": 1,
    "variant_id": 1,
    "base_price": "1000000.00",
    "discount_amount": "200000.00",
    "voucher_amount": "50000.00",
    "final_price": "750000.00",
    "currency": "VND",
    "applied_discounts": [
      {
        "id": 1,
        "code": "SALE20",
        "name": "Spring Sale",
        "type": "percentage",
        "value": "20.00",
        "amount": "200000.00"
      }
    ],
    "applied_voucher": {
      "id": 1,
      "code": "WELCOME",
      "name": "Welcome Voucher",
      "type": "fixed",
      "value": "50000.00",
      "amount": "50000.00"
    }
  }
}
```

---

## 3. Customer Module

### 3.1 Product Browsing

**Feature ID**: CUST-001
**Priority**: High
**Status**: Implemented

#### Mô tả
Khách hàng xem và tìm kiếm sản phẩm công khai.

#### Yêu cầu Chức năng

**List Products**:
- Chỉ hiển thị products có status = 'active'
- Chỉ hiển thị variants có status = 'active'
- Có thể filter theo category
- Có thể search theo name
- Có thể sort theo giá, tên, mới nhất
- Hiển thị giá sau discount (nếu có)

**Product Detail**:
- Hiển thị thông tin sản phẩm
- Hiển thị tất cả variants
- Hiển thị giá cho mỗi variant
- Hiển thị available discounts

**Filters**:
- category_id
- search (name)
- sort_by: price, name, created_at
- sort_order: asc, desc
- price_min, price_max

#### API Endpoints
```
GET    /api/v1/products             - List products
GET    /api/v1/products/{id}        - Product detail
GET    /api/v1/categories           - List categories
```

---

## 4. Authentication & Authorization Module

### 4.1 User Authentication

**Feature ID**: AUTH-001
**Priority**: High
**Status**: Implemented

#### Mô tả
Xác thực người dùng sử dụng Laravel Passport (OAuth2).

#### Yêu cầu Chức năng

**Register**:
- Input: first_name, last_name, email, password
- Validation: email unique, password min 8 chars
- Auto-assign role 'customer'
- Trả về user info + access token

**Login**:
- Input: email, password
- Validation: email exists, password correct
- Trả về access token + refresh token
- Token expiration: 15 days (configurable)

**Refresh Token**:
- Input: refresh_token
- Trả về access token mới
- Revoke old token

**Logout**:
- Revoke current access token
- Có thể revoke tất cả tokens

**Password Reset**:
- Gửi email reset link
- Validate token
- Update password

#### API Endpoints
```
POST   /api/v1/auth/register         - Register
POST   /api/v1/auth/login            - Login
POST   /api/v1/auth/refresh          - Refresh token
POST   /api/v1/auth/logout           - Logout
POST   /api/v1/password/forgot       - Forgot password
POST   /api/v1/password/reset        - Reset password
```

### 4.2 User Profile

**Feature ID**: AUTH-002
**Priority**: Medium
**Status**: Implemented

#### Mô tả
Quản lý thông tin cá nhân và địa chỉ.

#### Yêu cầu Chức năng

**Profile Management**:
- View profile
- Update profile (first_name, last_name, avatar)
- Upload avatar (sử dụng Spatie Media Library)
- Change password

**Address Management**:
- Thêm địa chỉ mới
- Cập nhật địa chỉ
- Xóa địa chỉ
- Đặt địa chỉ mặc định

#### API Endpoints
```
GET    /api/v1/auth/profile          - View profile
PUT    /api/v1/auth/profile          - Update profile
POST   /api/v1/auth/avatar           - Upload avatar
PUT    /api/v1/auth/password         - Change password
GET    /api/v1/auth/addresses        - List addresses
POST   /api/v1/auth/addresses        - Add address
PUT    /api/v1/auth/addresses/{id}   - Update address
DELETE /api/v1/auth/addresses/{id}   - Delete address
```

### 4.3 Role-Based Access Control

**Feature ID**: AUTH-003
**Priority**: High
**Status**: Implemented

#### Mô tả
Phân quyền dựa trên roles và permissions sử dụng Spatie Permission.

#### Yêu cầu Chức năng

**Roles**:
- admin: Full permissions
- staff: Limited permissions (không xóa)
- customer: Public access only

**Permissions**:
- products.view, products.create, products.update, products.delete
- categories.view, categories.create, categories.update, categories.delete
- discounts.view, discounts.create, discounts.update, discounts.delete
- vouchers.view, vouchers.create, vouchers.update, vouchers.delete
- users.view, users.create, users.update, users.delete
- reports.view, reports.export

**Authorization**:
- Route middleware: role:admin|staff
- Controller authorization: $this->authorize('update', $product)
- Blade directive: @can('delete', $product)

---

## 5. Reporting Module (Future)

### 5.1 Sales Reports

**Feature ID**: RPT-001
**Priority**: Low
**Status**: Planned

#### Mô tả
Báo cáo doanh thu và bán hàng.

#### Yêu cầu Chức năng (Dự kiến)

**Sales Summary**:
- Doanh thu theo ngày/tuần/tháng/năm
- So sánh với kỳ trước
- Top sản phẩm bán chạy

**Product Performance**:
- Số lượng bán theo sản phẩm
- Doanh thu theo category
- Tồn kho và tốc độ bán

**Discount Effectiveness**:
- Số lượng discount được sử dụng
- Doanh thu từ discount
- Conversion rate

#### API Endpoints (Dự kiến)
```
GET    /api/admin/reports/sales            - Sales report
GET    /api/admin/reports/products         - Product report
GET    /api/admin/reports/discounts        - Discount report
GET    /api/admin/reports/export           - Export report
```

---

## 6. System Features

### 6.1 Error Handling

**Feature ID**: SYS-001
**Priority**: High
**Status**: Implemented

#### Yêu cầu
- Consistent error response format
- Proper HTTP status codes
- Detailed error messages (dev mode)
- Generic error messages (production)
- Error logging

#### Error Response Format
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message"]
  },
  "error_code": "ERROR_CODE"
}
```

#### Status Codes
- 200: OK
- 201: Created
- 204: No Content
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 500: Server Error

### 6.2 Logging

**Feature ID**: SYS-002
**Priority**: Medium
**Status**: Implemented

#### Yêu cầu
- Application logs (daily rotation)
- Error logs
- Query logs (slow queries)
- API access logs
- Authentication logs

#### Tools
- Laravel Telescope (local debugging)
- Laravel Pail (real-time monitoring)
- Log Viewer (web-based log viewer)

### 6.3 Caching

**Feature ID**: SYS-003
**Priority**: Medium
**Status**: Implemented

#### Yêu cầu
- Cache danh sách categories (1 giờ)
- Cache product details (30 phút)
- Cache active discounts (10 phút)
- Cache user profile (1 giờ)
- Redis driver cho production
- File driver cho local development

### 6.4 API Rate Limiting

**Feature ID**: SYS-004
**Priority**: Medium
**Status**: Implemented

#### Yêu cầu
- Admin endpoints: 100 requests/phút
- Customer endpoints: 60 requests/phút
- Auth endpoints: 5 requests/phút (chống brute force)
- Rate limit headers trong response

---

## Feature Matrix

| Feature | Priority | Status | API Count | Tests |
|---------|----------|--------|-----------|-------|
| Product CRUD | High | Done | 5 | 15 |
| Variant Management | High | Done | 6 | 20 |
| Category CRUD | Medium | Done | 4 | 8 |
| Discount Management | High | Done | 5 | 12 |
| Voucher Management | High | Done | 4 | 10 |
| Price Calculation | High | Done | 1 | 8 |
| Customer Browsing | High | Done | 3 | 6 |
| Authentication | High | Done | 6 | 10 |
| Profile Management | Medium | Done | 7 | 5 |
| RBAC | High | Done | - | - |
| Reports | Low | Planned | 4 | - |

---

## Non-Functional Requirements

### Performance
- API response time < 500ms (95th percentile)
- Support 1000 concurrent users
- Database queries < 100ms
- Cache hit rate > 80%

### Security
- All inputs validated
- SQL injection prevention (prepared statements)
- XSS protection
- CSRF protection (for web routes)
- Rate limiting
- Password hashing (bcrypt)
- HTTPS only

### Scalability
- Horizontal scaling ready
- Stateless API design
- Queue system for heavy tasks
- Read replicas for database (future)

### Reliability
- 99.9% uptime SLA
- Database backups daily
- Error monitoring và alerting
- Graceful degradation

## API Versioning Strategy

### Current Version
- Base URL: `/api/v1/`
- Admin prefix: `/api/admin/`

### Future Versions
- v2: Breaking changes sẽ được đánh version mới
- Maintain backward compatibility trong 6 tháng
- Deprecation warnings trong response headers

## Mobile App Support (Future)

### Planned Features
- Mobile-optimized API responses
- Push notifications
- Offline support (caching)
- Deep linking

### API Adaptations
- Pagination tối ưu cho mobile
- Image resizing on-the-fly
- Minimal response payload
