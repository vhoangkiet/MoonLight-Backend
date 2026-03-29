# Phân quyền Người dùng - User Roles & Permissions

## Tổng quan

Hệ thống sử dụng **Spatie Laravel Permission** để quản lý roles và permissions với cơ chế **Role-Based Access Control (RBAC)**.

## Cấu trúc Phân quyền

```
User (Người dùng)
    ↓
Role (Vai trò)          Permission (Quyền hạn)
    ↓                           ↓
- Admin                       - products.create
- Staff                       - products.read
- Customer                    - products.update
                              - products.delete
                              - ...
```

## Roles (Vai trò)

### 1. Admin (Quản trị viên cấp cao)

**Mô tả**: Người dùng có toàn quyền quản trị hệ thống

**Quyền hạn**:
```php
[
    // User Management
    'users.view',
    'users.create',
    'users.update',
    'users.delete',
    'users.manage_roles',
    
    // Product Management (Full)
    'products.view',
    'products.create',
    'products.update',
    'products.delete',
    'products.archive',
    'products.manage_variants',
    'products.manage_stock',
    
    // Category Management
    'categories.view',
    'categories.create',
    'categories.update',
    'categories.delete',
    
    // Discount Management (Full)
    'discounts.view',
    'discounts.create',
    'discounts.update',
    'discounts.delete',
    'discounts.activate',
    
    // Voucher Management (Full)
    'vouchers.view',
    'vouchers.create',
    'vouchers.update',
    'vouchers.delete',
    'vouchers.activate',
    
    // Reports
    'reports.view',
    'reports.export',
    
    // System
    'system.settings',
    'system.logs',
]
```

**Chức năng chính**:
- Quản lý toàn bộ người dùng và phân quyền
- CRUD tất cả sản phẩm, danh mục
- Quản lý giảm giá và voucher
- Xem báo cáo, thống kê
- Cấu hình hệ thống
- Xem logs

### 2. Staff (Nhân viên)

**Mô tả**: Nhân viên vận hành, quản lý nội dung và đơn hàng

**Quyền hạn**:
```php
[
    // Product Management (Limited)
    'products.view',
    'products.create',
    'products.update',
    // 'products.delete' - KHÔNG có quyền xóa
    'products.manage_variants',
    'products.manage_stock',
    
    // Category Management (View only)
    'categories.view',
    
    // Discount Management (Limited)
    'discounts.view',
    'discounts.create',
    'discounts.update',
    // 'discounts.delete' - KHÔNG có quyền xóa
    
    // Voucher Management (Limited)
    'vouchers.view',
    'vouchers.create',
    'vouchers.update',
    // 'vouchers.delete' - KHÔNG có quyền xóa
    
    // Reports (View only)
    'reports.view',
    // 'reports.export' - KHÔNG có quyền export
]
```

**Chức năng chính**:
- Quản lý sản phẩm (thêm, sửa, cập nhật kho)
- Không thể xóa sản phẩm đã tạo
- Quản lý discount/voucher (thêm, sửa)
- Xem báo cáo nhưng không export
- Không quản lý users

### 3. Customer (Khách hàng)

**Mô tả**: Người dùng cuối, khách hàng mua sắm

**Quyền hạn**:
```php
[
    // Public Product Access
    'products.public_view',
    'products.calculate_price',
    
    // Profile Management
    'profile.view',
    'profile.update',
    'profile.manage_addresses',
    
    // Orders (nếu có module Order)
    'orders.create',
    'orders.view_own',
]
```

**Chức năng chính**:
- Xem sản phẩm công khai
- Tính toán giá với discount/voucher
- Quản lý thông tin cá nhân
- Đặt hàng (nếu có)

## Chi tiết Permissions

### Product Permissions

| Permission | Mô tả | Admin | Staff | Customer |
|------------|-------|-------|-------|----------|
| `products.view` | Xem danh sách sản phẩm | ✅ | ✅ | ❌ |
| `products.public_view` | Xem sản phẩm công khai | ✅ | ✅ | ✅ |
| `products.create` | Tạo sản phẩm mới | ✅ | ✅ | ❌ |
| `products.update` | Cập nhật sản phẩm | ✅ | ✅ | ❌ |
| `products.delete` | Xóa sản phẩm | ✅ | ❌ | ❌ |
| `products.archive` | Lưu trữ sản phẩm | ✅ | ✅ | ❌ |
| `products.manage_variants` | Quản lý biến thể | ✅ | ✅ | ❌ |
| `products.manage_stock` | Quản lý tồn kho | ✅ | ✅ | ❌ |

### Category Permissions

| Permission | Mô tả | Admin | Staff | Customer |
|------------|-------|-------|-------|----------|
| `categories.view` | Xem danh mục | ✅ | ✅ | ✅ |
| `categories.create` | Tạo danh mục | ✅ | ❌ | ❌ |
| `categories.update` | Cập nhật danh mục | ✅ | ❌ | ❌ |
| `categories.delete` | Xóa danh mục | ✅ | ❌ | ❌ |

### Discount Permissions

| Permission | Mô tả | Admin | Staff | Customer |
|------------|-------|-------|-------|----------|
| `discounts.view` | Xem giảm giá | ✅ | ✅ | ❌ |
| `discounts.create` | Tạo giảm giá | ✅ | ✅ | ❌ |
| `discounts.update` | Cập nhật giảm giá | ✅ | ✅ | ❌ |
| `discounts.delete` | Xóa giảm giá | ✅ | ❌ | ❌ |
| `discounts.activate` | Kích hoạt/tắt giảm giá | ✅ | ✅ | ❌ |

### Voucher Permissions

| Permission | Mô tả | Admin | Staff | Customer |
|------------|-------|-------|-------|----------|
| `vouchers.view` | Xem voucher | ✅ | ✅ | ❌ |
| `vouchers.create` | Tạo voucher | ✅ | ✅ | ❌ |
| `vouchers.update` | Cập nhật voucher | ✅ | ✅ | ❌ |
| `vouchers.delete` | Xóa voucher | ✅ | ❌ | ❌ |
| `vouchers.activate` | Kích hoạt/tắt voucher | ✅ | ✅ | ❌ |

### User Management Permissions

| Permission | Mô tả | Admin | Staff | Customer |
|------------|-------|-------|-------|----------|
| `users.view` | Xem users | ✅ | ❌ | ❌ |
| `users.create` | Tạo user | ✅ | ❌ | ❌ |
| `users.update` | Cập nhật user | ✅ | ❌ | ❌ |
| `users.delete` | Xóa user | ✅ | ❌ | ❌ |
| `users.manage_roles` | Phân quyền | ✅ | ❌ | ❌ |

### Report Permissions

| Permission | Mô tả | Admin | Staff | Customer |
|------------|-------|-------|-------|----------|
| `reports.view` | Xem báo cáo | ✅ | ✅ | ❌ |
| `reports.export` | Export báo cáo | ✅ | ❌ | ❌ |

## Middleware & Authorization

### 1. Route Middleware

```php
// routes/api.php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Api\Admin\ProductController;

// Admin only routes
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::delete('products/{id}', [ProductController::class, 'destroy']);
    Route::apiResource('users', UserController::class);
});

// Admin & Staff routes
Route::middleware(['auth:api', 'role:admin|staff'])->group(function () {
    Route::apiResource('products', ProductController::class)->except(['destroy']);
    Route::apiResource('discounts', DiscountController::class)->except(['destroy']);
    Route::apiResource('vouchers', VoucherController::class)->except(['destroy']);
});

// Customer routes (public + auth)
Route::middleware(['auth:api'])->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
});

// Public routes (no auth required)
Route::get('products', [CustomerProductController::class, 'index']);
Route::get('products/{id}', [CustomerProductController::class, 'show']);
```

### 2. Controller Authorization

```php
<?php

namespace Modules\Product\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Product\Http\Requests\Admin\StoreProductRequest;
use Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends BaseController
{
    public function store(StoreProductRequest $request): JsonResponse
    {
        // Check permission
        $this->authorize('create', Product::class);
        
        // ... create product
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        
        // Check permission
        $this->authorize('delete', $product);
        
        // Only admin can delete
        if (! auth()->user()->hasRole('admin')) {
            return $this->errorResponse(
                'Only admin can delete products',
                Response::HTTP_FORBIDDEN
            );
        }
        
        // ... delete product
    }
}
```

### 3. Policy Authorization

```php
<?php

namespace Modules\Product\Policies;

use App\Models\User;
use Modules\Product\Models\Product;

class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $user->hasRole(['admin', 'staff']) || 
               $product->status === 'active';
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'staff']);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasRole(['admin', 'staff']);
    }

    public function delete(User $user, Product $product): bool
    {
        // Only admin can delete
        return $user->hasRole('admin');
    }

    public function manageStock(User $user, Product $product): bool
    {
        return $user->hasRole(['admin', 'staff']);
    }
}
```

## Kiểm tra Quyền hạn

### 1. Blade Views (nếu có)

```blade
@can('create', App\Models\Product::class)
    <a href="{{ route('products.create') }}">Create Product</a>
@endcan

@role('admin')
    <a href="{{ route('users.index') }}">Manage Users</a>
@endrole

@hasrole('staff')
    <span>Staff Panel</span>
@endhasrole
```

### 2. API Responses

```php
// Check permission and return appropriate response
if (! $user->can('view', $product)) {
    return response()->json([
        'success' => false,
        'message' => 'You do not have permission to view this product',
    ], 403);
}
```

### 3. Direct Checks

```php
// Check role
if (auth()->user()->hasRole('admin')) {
    // Do admin stuff
}

// Check any role
if (auth()->user()->hasAnyRole(['admin', 'staff'])) {
    // Do admin or staff stuff
}

// Check permission
if (auth()->user()->can('products.delete')) {
    // Delete product
}

// Check permission via gate
if (Gate::allows('delete', $product)) {
    // Delete product
}
```

## Thiết lập Ban đầu

### 1. Tạo Roles

```bash
php artisan tinker
```

```php
use Spatie\Permission\Models\Role;

// Create roles
Role::create(['name' => 'admin']);
Role::create(['name' => 'staff']);
Role::create(['name' => 'customer']);
```

### 2. Tạo Permissions

```php
use Spatie\Permission\Models\Permission;

// Product permissions
Permission::create(['name' => 'products.view']);
Permission::create(['name' => 'products.create']);
Permission::create(['name' => 'products.update']);
Permission::create(['name' => 'products.delete']);
Permission::create(['name' => 'products.archive']);
Permission::create(['name' => 'products.manage_variants']);
Permission::create(['name' => 'products.manage_stock']);
Permission::create(['name' => 'products.public_view']);

// Category permissions
Permission::create(['name' => 'categories.view']);
Permission::create(['name' => 'categories.create']);
Permission::create(['name' => 'categories.update']);
Permission::create(['name' => 'categories.delete']);

// Discount permissions
Permission::create(['name' => 'discounts.view']);
Permission::create(['name' => 'discounts.create']);
Permission::create(['name' => 'discounts.update']);
Permission::create(['name' => 'discounts.delete']);
Permission::create(['name' => 'discounts.activate']);

// Voucher permissions
Permission::create(['name' => 'vouchers.view']);
Permission::create(['name' => 'vouchers.create']);
Permission::create(['name' => 'vouchers.update']);
Permission::create(['name' => 'vouchers.delete']);
Permission::create(['name' => 'vouchers.activate']);

// User permissions
Permission::create(['name' => 'users.view']);
Permission::create(['name' => 'users.create']);
Permission::create(['name' => 'users.update']);
Permission::create(['name' => 'users.delete']);
Permission::create(['name' => 'users.manage_roles']);

// Report permissions
Permission::create(['name' => 'reports.view']);
Permission::create(['name' => 'reports.export']);

// Profile permissions
Permission::create(['name' => 'profile.view']);
Permission::create(['name' => 'profile.update']);
Permission::create(['name' => 'profile.manage_addresses']);
```

### 3. Gán Permissions cho Roles

```php
use Spatie\Permission\Models\Role;

// Admin permissions
$adminRole = Role::findByName('admin');
$adminRole->givePermissionTo([
    'products.view', 'products.create', 'products.update', 'products.delete', 
    'products.archive', 'products.manage_variants', 'products.manage_stock', 'products.public_view',
    'categories.view', 'categories.create', 'categories.update', 'categories.delete',
    'discounts.view', 'discounts.create', 'discounts.update', 'discounts.delete', 'discounts.activate',
    'vouchers.view', 'vouchers.create', 'vouchers.update', 'vouchers.delete', 'vouchers.activate',
    'users.view', 'users.create', 'users.update', 'users.delete', 'users.manage_roles',
    'reports.view', 'reports.export',
    'profile.view', 'profile.update', 'profile.manage_addresses',
]);

// Staff permissions
$staffRole = Role::findByName('staff');
$staffRole->givePermissionTo([
    'products.view', 'products.create', 'products.update', 'products.archive', 
    'products.manage_variants', 'products.manage_stock', 'products.public_view',
    'categories.view',
    'discounts.view', 'discounts.create', 'discounts.update', 'discounts.activate',
    'vouchers.view', 'vouchers.create', 'vouchers.update', 'vouchers.activate',
    'reports.view',
    'profile.view', 'profile.update', 'profile.manage_addresses',
]);

// Customer permissions
$customerRole = Role::findByName('customer');
$customerRole->givePermissionTo([
    'products.public_view',
    'profile.view', 'profile.update', 'profile.manage_addresses',
]);
```

### 4. Gán Role cho User

```php
use App\Models\User;

$user = User::find(1);
$user->assignRole('admin');

$user = User::find(2);
$user->assignRole('staff');

$user = User::find(3);
$user->assignRole('customer');
```

## Seeder mẫu

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $staffRole = Role::create(['name' => 'staff']);
        $customerRole = Role::create(['name' => 'customer']);

        // Create permissions
        $permissions = [
            // Product
            'products.view', 'products.create', 'products.update', 'products.delete',
            'products.archive', 'products.manage_variants', 'products.manage_stock', 'products.public_view',
            // Category
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            // Discount
            'discounts.view', 'discounts.create', 'discounts.update', 'discounts.delete', 'discounts.activate',
            // Voucher
            'vouchers.view', 'vouchers.create', 'vouchers.update', 'vouchers.delete', 'vouchers.activate',
            // User
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.manage_roles',
            // Reports
            'reports.view', 'reports.export',
            // Profile
            'profile.view', 'profile.update', 'profile.manage_addresses',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign permissions to roles
        $adminRole->givePermissionTo($permissions);
        
        $staffRole->givePermissionTo([
            'products.view', 'products.create', 'products.update', 'products.archive', 
            'products.manage_variants', 'products.manage_stock', 'products.public_view',
            'categories.view',
            'discounts.view', 'discounts.create', 'discounts.update', 'discounts.activate',
            'vouchers.view', 'vouchers.create', 'vouchers.update', 'vouchers.activate',
            'reports.view',
            'profile.view', 'profile.update', 'profile.manage_addresses',
        ]);
        
        $customerRole->givePermissionTo([
            'products.public_view',
            'profile.view', 'profile.update', 'profile.manage_addresses',
        ]);

        // Create default admin user
        $admin = User::factory()->create([
            'email' => 'admin@moonlight.com',
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);
        $admin->assignRole('admin');

        // Create default staff user
        $staff = User::factory()->create([
            'email' => 'staff@moonlight.com',
            'first_name' => 'Staff',
            'last_name' => 'User',
        ]);
        $staff->assignRole('staff');
    }
}
```

## Xử lý Lỗi Phân quyền

### 1. Unauthorized (401)

```json
{
  "success": false,
  "message": "Unauthorized. Please login."
}
```

### 2. Forbidden (403)

```json
{
  "success": false,
  "message": "You do not have permission to perform this action."
}
```

### 3. Custom Error Message

```php
public function destroy(int $id): JsonResponse
{
    $product = Product::findOrFail($id);
    
    if (! auth()->user()->can('delete', $product)) {
        return response()->json([
            'success' => false,
            'message' => 'Only administrators can delete products. Please contact your admin.',
            'error_code' => 'INSUFFICIENT_PERMISSIONS',
        ], 403);
    }
    
    // ... proceed with deletion
}
```

## Best Practices

1. **Least Privilege**: Gán ít quyền nhất cần thiết cho mỗi role
2. **Explicit Permission**: Luôn kiểm tra permission rõ ràng trong controller
3. **Policy Classes**: Sử dụng Policy để tập trung logic authorization
4. **Middleware**: Sử dụng middleware cho route-level protection
5. **Caching**: Permission được cache tự động bởi Spatie
6. **Testing**: Luôn test authorization trong feature tests

## Troubleshooting

### Permission not working
```bash
# Clear permission cache
php artisan permission:cache-reset

# Or in code
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

### Role assignment not working
```bash
# Check user has role
php artisan tinker
>>> $user = User::find(1);
>>> $user->hasRole('admin');
>>> $user->roles;
>>> $user->permissions;
```

## Tài liệu Tham khảo

- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v6/introduction)
- [Laravel Authorization](https://laravel.com/docs/12.x/authorization)
- [Laravel Policies](https://laravel.com/docs/12.x/authorization#creating-policies)
