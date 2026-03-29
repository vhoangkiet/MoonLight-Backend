# Luồng Xử lý - System Workflows

## Tổng quan

Tài liệu này mô tả chi tiết các luồng xử lý chính trong hệ thống MoonLight, bao gồm data flow, sequence diagrams và state transitions.

## 1. Luồng Quản lý Sản phẩm

### 1.1 Tạo Sản phẩm Mới

```mermaid
sequenceDiagram
    actor Admin
    participant Controller
    participant Service
    participant Repository
    participant DB
    
    Admin->>Controller: POST /products (data)
    Controller->>Service: createProduct(data)
    Service->>Service: Validate data
    Service->>Service: Check unique slug
    Service->>Repository: create(data)
    Repository->>DB: INSERT INTO products
    DB-->>Repository: Return product
    Repository-->>Service: Product model
    Service-->>Controller: Product model
    Controller->>Controller: Format response
    Controller-->>Admin: 201 Created + Product data
```

**Chi tiết xử lý**:
1. **Controller nhận request** với dữ liệu: name, slug, description, category_id, status
2. **Service validate dữ liệu**:
   - Slug có unique?
   - Category có tồn tại?
   - Name có rỗng?
3. **Repository lưu vào DB** thông qua Eloquent model
4. **Trả về response** với product mới được tạo

### 1.2 Cập nhật Sản phẩm

```mermaid
sequenceDiagram
    actor Staff
    participant Controller
    participant Service
    participant Repository
    participant DB
    participant Cache
    
    Staff->>Controller: PUT /products/{id} (data)
    Controller->>Service: updateProduct(id, data)
    Service->>Repository: find(id)
    Repository->>DB: SELECT * FROM products WHERE id = ?
    DB-->>Repository: Return product
    Repository-->>Service: Product model
    Service->>Service: Validate dữ liệu
    Service->>Service: Check unique slug (nếu đổi)
    Service->>Repository: update(id, data)
    Repository->>DB: UPDATE products SET ...
    Service->>Cache: Clear product cache
    Service-->>Controller: Updated product
    Controller-->>Staff: 200 OK + Product data
```

### 1.3 Xóa Sản phẩm (Soft Delete)

```mermaid
sequenceDiagram
    actor Admin
    participant Controller
    participant Service
    participant Repository
    participant DB
    participant Policy
    
    Admin->>Controller: DELETE /products/{id}
    Controller->>Policy: authorize('delete', Product)
    Policy-->>Controller: Approved (is admin)
    Controller->>Service: deleteProduct(id)
    Service->>Repository: find(id)
    Repository->>DB: SELECT * FROM products WHERE id = ?
    DB-->>Repository: Return product
    Service->>Service: Check có đơn hàng liên quan?
    Service->>Repository: delete(id)
    Repository->>DB: UPDATE products SET deleted_at = NOW()
    Repository->>DB: UPDATE product_variants SET deleted_at = NOW() WHERE product_id = ?
    Service-->>Controller: Success
    Controller-->>Admin: 204 No Content
```

**Lưu ý**:
- Chỉ Admin mới có quyền xóa
- Xóa sản phẩm cũng xóa tất cả variants (cascade soft delete)
- Kiểm tra không có đơn hàng active liên quan

## 2. Luồng Quản lý Biến thể

### 2.1 Tạo Biến thể

```mermaid
sequenceDiagram
    actor Staff
    participant VariantController
    participant VariantService
    participant ProductRepository
    participant VariantRepository
    participant DB
    
    Staff->>VariantController: POST /variants (data)
    VariantController->>VariantService: createVariant(data)
    VariantService->>ProductRepository: find(product_id)
    ProductRepository->>DB: SELECT * FROM products WHERE id = ?
    DB-->>ProductRepository: Return product
    VariantService->>VariantService: Validate required fields
    VariantService->>VariantService: Generate SKU (SHA-LEN-TON-SIZ)
    VariantService->>VariantRepository: checkDuplicateSKU(sku)
    VariantRepository->>DB: SELECT * FROM product_variants WHERE sku = ?
    DB-->>VariantRepository: No result
    VariantService->>VariantRepository: checkDuplicateCombination(product_id, shape, length, tonal, size)
    VariantRepository->>DB: SELECT * FROM product_variants WHERE ...
    DB-->>VariantRepository: No result
    VariantService->>VariantRepository: create(data)
    VariantRepository->>DB: INSERT INTO product_variants ...
    DB-->>VariantRepository: Return variant
    VariantRepository-->>VariantService: Variant model
    VariantService-->>VariantController: Variant model
    VariantController-->>Staff: 201 Created + Variant data
```

**SKU Generation Logic**:
```php
$shapeCode = substr(strtoupper($shape), 0, 3);       // ROU
$lengthCode = str_replace(['cm', 'mm'], '', $length); // 50C
$tonalCode = substr(strtoupper($tonal), 0, 3);       // WAR
$sizeCode = substr(strtoupper($size), 0, 3);        // MED

$sku = "{$shapeCode}-{$lengthCode}-{$tonalCode}-{$sizeCode}";
// Result: ROU-50C-WAR-MED
```

### 2.2 Cập nhật Kho (Stock)

```mermaid
sequenceDiagram
    actor Staff
    participant Controller
    participant Service
    participant Repository
    participant DB
    participant StockLog
    
    Staff->>Controller: POST /variants/{id}/stock (stock: 100)
    Controller->>Service: updateStock(id, 100)
    Service->>Repository: find(id)
    Repository->>DB: SELECT * FROM product_variants WHERE id = ?
    DB-->>Repository: Return variant
    Service->>Service: Validate stock >= 0
    Service->>Repository: updateStock(id, 100)
    Repository->>DB: UPDATE product_variants SET stock = 100 WHERE id = ?
    Service->>StockLog: Log stock change
    StockLog->>DB: INSERT INTO stock_logs ...
    Service-->>Controller: Updated variant
    Controller-->>Staff: 200 OK + Variant data
```

## 3. Luồng Tính toán Giá

### 3.1 Tính giá với Discount

```mermaid
sequenceDiagram
    actor Customer
    participant CustomerController
    participant PricingService
    participant ProductRepository
    participant DiscountRepository
    participant VoucherRepository
    participant DB
    
    Customer->>CustomerController: POST /calculate-price
    Note over Customer: {product_id, variant_id, voucher_code?}
    
    CustomerController->>PricingService: calculate(product_id, variant_id, options)
    
    PricingService->>ProductRepository: findProductWithVariant(product_id, variant_id)
    ProductRepository->>DB: SELECT p.*, pv.* FROM products p JOIN product_variants pv ON p.id = pv.product_id WHERE p.id = ? AND pv.id = ?
    DB-->>ProductRepository: Return product + variant
    ProductRepository-->>PricingService: Product model
    
    PricingService->>PricingService: base_price = variant.price
    
    PricingService->>DiscountRepository: getActiveDiscounts(product_id, variant_id)
    DiscountRepository->>DB: SELECT d.* FROM discounts d JOIN discount_product dp ON d.id = dp.discount_id WHERE dp.product_id = ? AND (dp.product_variant_id IS NULL OR dp.product_variant_id = ?) AND d.is_active = 1 AND d.start_date <= NOW() AND d.end_date >= NOW()
    DB-->>DiscountRepository: Return discounts
    DiscountRepository-->>PricingService: Collection of discounts
    
    loop Calculate each discount
        PricingService->>PricingService: Check discount.isActive()
        PricingService->>PricingService: Calculate discount amount
        Note over PricingService: percentage: amount * (value/100)<br/>fixed: min(value, amount)
    end
    
    PricingService->>PricingService: discount_amount = sum of all discounts
    PricingService->>PricingService: Apply max_discount_amount limit
    
    opt If voucher_code provided
        PricingService->>VoucherRepository: findByCode(voucher_code)
        VoucherRepository->>DB: SELECT * FROM vouchers WHERE code = ?
        DB-->>VoucherRepository: Return voucher
        VoucherRepository-->>PricingService: Voucher model
        
        PricingService->>PricingService: Validate voucher
        Note over PricingService: Check: exists, active,<br/>in date range, usage limit,
        Note over PricingService: min_order_amount
        
        PricingService->>PricingService: Calculate voucher_amount
        PricingService->>VoucherRepository: incrementUsage(voucher_id)
        VoucherRepository->>DB: UPDATE vouchers SET usage_count = usage_count + 1 WHERE id = ?
    end
    
    PricingService->>PricingService: final_price = base_price - discount_amount - voucher_amount
    PricingService->>PricingService: Ensure final_price >= 0
    
    PricingService-->>CustomerController: Price calculation result
    CustomerController-->>Customer: 200 OK + Price data
```

**Price Calculation Algorithm**:
```php
function calculatePrice($basePrice, $discounts, $voucher = null): array
{
    $discountAmount = 0;
    
    // Calculate discounts
    foreach ($discounts as $discount) {
        if ($discount->isActive()) {
            if ($discount->type === 'percentage') {
                $discountAmount += $basePrice * ($discount->value / 100);
            } else {
                $discountAmount += min($discount->value, $basePrice);
            }
        }
    }
    
    // Apply max discount limit
    if (isset($discount->max_discount_amount)) {
        $discountAmount = min($discountAmount, $discount->max_discount_amount);
    }
    
    // Calculate voucher
    $voucherAmount = 0;
    if ($voucher && $voucher->isValid()) {
        if ($voucher->type === 'percentage') {
            $voucherAmount = $basePrice * ($voucher->value / 100);
        } else {
            $voucherAmount = $voucher->value;
        }
        
        // Check min order amount
        if ($basePrice < $voucher->min_order_amount) {
            $voucherAmount = 0;
        }
    }
    
    $finalPrice = max(0, $basePrice - $discountAmount - $voucherAmount);
    
    return [
        'base_price' => $basePrice,
        'discount_amount' => $discountAmount,
        'voucher_amount' => $voucherAmount,
        'final_price' => $finalPrice,
    ];
}
```

## 4. Luồng Quản lý Voucher

### 4.1 Sử dụng Voucher

```mermaid
sequenceDiagram
    actor Customer
    participant Controller
    participant VoucherService
    participant VoucherRepository
    participant VoucherUseRepository
    participant DB
    
    Customer->>Controller: POST /calculate-price (voucher_code: "WELCOME20")
    Controller->>VoucherService: validateAndApply(code, order_amount)
    
    VoucherService->>VoucherRepository: findByCode(code)
    VoucherRepository->>DB: SELECT * FROM vouchers WHERE code = 'WELCOME20'
    DB-->>VoucherRepository: Return voucher
    
    alt Voucher not found
        VoucherRepository-->>VoucherService: null
        VoucherService-->>Controller: Invalid voucher
        Controller-->>Customer: 400 Bad Request
    else Voucher found
        VoucherService->>VoucherService: Check is_active
        VoucherService->>VoucherService: Check valid_from <= NOW <= valid_until
        VoucherService->>VoucherService: Check usage_count < usage_limit
        VoucherService->>VoucherService: Check order_amount >= min_order_amount
        
        alt Validation failed
            VoucherService-->>Controller: Voucher not applicable
            Controller-->>Customer: 400 Bad Request + Reason
        else Validation passed
            VoucherService->>VoucherService: Calculate discount amount
            VoucherService->>VoucherRepository: incrementUsage(voucher_id)
            VoucherRepository->>DB: UPDATE vouchers SET usage_count = usage_count + 1
            
            opt If order completed
                VoucherService->>VoucherUseRepository: createUseRecord(voucher_id, user_id, order_id, amount)
                VoucherUseRepository->>DB: INSERT INTO voucher_uses ...
            end
            
            VoucherService-->>Controller: Voucher applied
            Controller-->>Customer: 200 OK + Discount info
        end
    end
```

### 4.2 State Diagram - Voucher Lifecycle

```mermaid
stateDiagram-v2
    [*] --> Created : Tạo voucher
    Created --> Active : Đến valid_from
    Active --> Used : Customer sử dụng
    Used --> Active : Còn lượt sử dụng
    Active --> Expired : Quá valid_until
    Active --> Exhausted : Hết usage_limit
    Expired --> [*]
    Exhausted --> [*]
    Created --> Inactive : Admin tắt
    Active --> Inactive : Admin tắt
    Inactive --> Active : Admin bật lại
    Inactive --> [*]
```

## 5. Luồng Xác thực (Authentication)

### 5.1 Đăng nhập

```mermaid
sequenceDiagram
    actor User
    participant AuthController
    participant AuthService
    participant UserRepository
    participant Passport
    participant DB
    
    User->>AuthController: POST /login (email, password)
    AuthController->>AuthService: authenticate(email, password)
    
    AuthService->>UserRepository: findByEmail(email)
    UserRepository->>DB: SELECT * FROM users WHERE email = ?
    DB-->>UserRepository: Return user
    
    alt User not found
        UserRepository-->>AuthService: null
        AuthService-->>AuthController: Invalid credentials
        AuthController-->>User: 401 Unauthorized
    else User found
        AuthService->>AuthService: Verify password
        
        alt Password incorrect
            AuthService-->>AuthController: Invalid credentials
            AuthController-->>User: 401 Unauthorized
        else Password correct
            AuthService->>Passport: Create access token
            Passport->>DB: INSERT INTO oauth_access_tokens ...
            Passport-->>AuthService: Token data
            AuthService-->>AuthController: User + Token
            AuthController-->>User: 200 OK + Token info
        end
    end
```

### 5.2 Refresh Token

```mermaid
sequenceDiagram
    actor User
    participant AuthController
    participant Passport
    participant DB
    
    User->>AuthController: POST /refresh (refresh_token)
    AuthController->>Passport: Exchange refresh token
    Passport->>DB: SELECT * FROM oauth_refresh_tokens WHERE id = ?
    DB-->>Passport: Return token
    
    alt Token expired or invalid
        Passport-->>AuthController: Invalid token
        AuthController-->>User: 401 Unauthorized
    else Token valid
        Passport->>DB: Create new access token
        Passport-->>AuthController: New token data
        AuthController-->>User: 200 OK + New token
    end
```

## 6. Luồng Cache Management

### 6.1 Cache Invalidation

```mermaid
sequenceDiagram
    actor Admin
    participant Controller
    participant Service
    participant Repository
    participant Cache
    participant DB
    
    Admin->>Controller: PUT /products/{id} (update data)
    Controller->>Service: updateProduct(id, data)
    Service->>Repository: update(id, data)
    Repository->>DB: UPDATE products SET ...
    
    Service->>Cache: Clear product:{id} cache
    Cache->>Cache: DELETE key
    
    alt Category changed
        Service->>Cache: Clear category:{old_cat_id} cache
        Service->>Cache: Clear category:{new_cat_id} cache
    end
    
    Service->>Cache: Clear products:list cache
    Service-->>Controller: Updated product
    Controller-->>Admin: 200 OK
```

### 6.2 Cache Strategy

```mermaid
flowchart TD
    A[Request] --> B{Cache Hit?}
    B -->|Yes| C[Return Cached Data]
    B -->|No| D[Query Database]
    D --> E[Process Data]
    E --> F[Store in Cache]
    F --> G[Return Data]
    
    H[Data Updated] --> I[Clear Relevant Cache]
    I --> J{Cache Tags}
    J --> K[product:{id}]
    J --> L[category:{id}]
    J --> M[products:list]
```

**Cache TTLs**:
- Product detail: 30 minutes
- Product list: 15 minutes
- Category: 1 hour
- Active discounts: 10 minutes
- User profile: 1 hour

## 7. Error Handling Flow

### 7.1 Validation Error

```mermaid
sequenceDiagram
    actor User
    participant Controller
    participant FormRequest
    participant ExceptionHandler
    
    User->>Controller: POST /products (invalid data)
    Controller->>FormRequest: validate()
    FormRequest->>FormRequest: Check rules
    
    alt Validation failed
        FormRequest->>ExceptionHandler: Throw ValidationException
        ExceptionHandler->>ExceptionHandler: Format error response
        ExceptionHandler-->>User: 422 Unprocessable Entity + Errors
    else Validation passed
        FormRequest-->>Controller: Continue processing
    end
```

### 7.2 Domain Exception

```mermaid
sequenceDiagram
    actor User
    participant Controller
    participant Service
    participant ExceptionHandler
    
    User->>Controller: POST /products
    Controller->>Service: createProduct(data)
    Service->>Service: Check duplicate slug
    
    alt Duplicate found
        Service->>ExceptionHandler: throw DomainException('Slug exists')
        ExceptionHandler->>ExceptionHandler: Log error
        ExceptionHandler-->>User: 400 Bad Request + Message
    else No duplicate
        Service-->>Controller: Continue
    end
```

## 8. Batch Operations

### 8.1 Bulk Update Stock

```mermaid
sequenceDiagram
    actor Staff
    participant Controller
    participant BatchService
    participant VariantRepository
    participant DB
    participant Queue
    
    Staff->>Controller: POST /variants/bulk-update-stock
    Note over Staff: {updates: [{id: 1, stock: 100}, {id: 2, stock: 50}]}
    
    Controller->>BatchService: processBulkUpdate(updates)
    
    alt Sync processing
        BatchService->>VariantRepository: updateStock(id, stock)
        VariantRepository->>DB: UPDATE product_variants SET stock = ? WHERE id = ?
        DB-->>VariantRepository: OK
        VariantRepository-->>BatchService: Result
    else Async processing (queue)
        BatchService->>Queue: Dispatch UpdateStockJob
        Queue-->>BatchService: Job dispatched
    end
    
    BatchService-->>Controller: Results
    Controller-->>Staff: 200 OK + Update summary
```

## 9. Event-Driven Workflows

### 9.1 Product Created Event

```mermaid
sequenceDiagram
    participant Controller
    participant Service
    participant Repository
    participant Event
    participant Listener1
    participant Listener2
    participant Queue
    
    Controller->>Service: createProduct(data)
    Service->>Repository: create(data)
    Repository-->>Service: Product created
    
    Service->>Event: dispatch(ProductCreated, product)
    
    par Async listeners
        Event->>Listener1: Clear cache
        Listener1->>Listener1: Cache::forget('products:list')
        
        Event->>Listener2: Send notification
        Listener2->>Queue: Queue notification email
    end
    
    Service-->>Controller: Product model
```

## 10. Import/Export Flow

### 10.1 Export Products

```mermaid
sequenceDiagram
    actor Admin
    participant Controller
    participant ExportService
    participant Repository
    participant Storage
    participant Queue
    
    Admin->>Controller: GET /products/export
    Controller->>ExportService: exportProducts(filters)
    
    alt Small dataset (sync)
        ExportService->>Repository: getProducts(filters)
        Repository-->>ExportService: Products collection
        ExportService->>ExportService: Generate CSV/Excel
        ExportService->>Storage: Store file
        Storage-->>ExportService: File path
        ExportService-->>Controller: Download URL
        Controller-->>Admin: 200 OK + Download link
    else Large dataset (async)
        ExportService->>Queue: Dispatch ExportJob
        Queue-->>ExportService: Job queued
        ExportService-->>Controller: Job ID
        Controller-->>Admin: 202 Accepted + Job status
    end
```

## Event Listeners Reference

| Event | Listeners | Mô tả |
|-------|-----------|-------|
| ProductCreated | ClearProductCache | Xóa cache danh sách |
| ProductUpdated | ClearProductCache, NotifyStaff | Xóa cache, thông báo |
| ProductDeleted | ClearProductCache, ArchiveRelatedData | Xóa cache, lưu trữ |
| DiscountActivated | ClearDiscountCache | Xóa cache discount |
| VoucherUsed | IncrementUsageCount, LogVoucherUse | Tăng count, ghi log |
| StockUpdated | CheckLowStock, NotifyStaff | Kiểm tra kho thấp |

## Caching Strategy

### Cache Keys Pattern
```
products:{id}                    # Product detail
products:list:{filters}          # Product list with filters
categories:{id}                 # Category detail
categories:list                 # Category list
discounts:active                # Active discounts
user:{id}:profile               # User profile
```

### Cache Invalidation Triggers
- Product updated → Clear `products:{id}`, `products:list`
- Category updated → Clear `categories:{id}`, `products:list`
- Discount created/updated → Clear `discounts:active`
- Stock changed → Clear `products:{id}`

## Performance Optimization

### Database Optimization
- **Indexing**: `products.status`, `products.category_id`, `variants.sku`
- **Eager Loading**: Luôn load relationships cần thiết
- **Query Caching**: Cache các query thường xuyên
- **Pagination**: Luôn paginate khi list dữ liệu

### API Optimization
- **Rate Limiting**: 60 requests/phút cho customer, 100 cho admin
- **Response Caching**: Cache GET requests (30s - 5min)
- **Compression**: Gzip response
- **Partial Response**: Cho phép chọn fields cần thiết
