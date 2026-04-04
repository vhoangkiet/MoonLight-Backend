# Development Guide

## Getting Started

### Prerequisites

- PHP 8.3+
- Composer 2.x
- Node.js 18+ (cho asset compilation)
- MySQL 8.x hoặc PostgreSQL 14+
- Git

### Environment Setup

#### 1. Clone Repository

```bash
git clone https://github.com/vhoangkiet/MoonLight-Backend.git
cd MoonLight-Backend
```

#### 2. Install PHP Dependencies

```bash
composer install
```

#### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Cấu hình `.env`:

```env
APP_NAME="MoonLight Backend"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=moonlight
DB_USERNAME=root
DB_PASSWORD=

# Hoặc sử dụng SQLite cho development
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file

# Mail (cho development)
MAIL_MAILER=log

# Passport
PASSPORT_PASSWORD_CLIENT_ID=your-client-id
PASSPORT_PASSWORD_CLIENT_SECRET=your-client-secret
```

#### 4. Database Setup

```bash
# Tạo database
touch database/database.sqlite  # Nếu dùng SQLite

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Hoặc fresh start
php artisan migrate:fresh --seed
```

#### 5. Passport Setup

```bash
php artisan passport:install
php artisan passport:keys
```

#### 6. Storage Link

```bash
php artisan storage:link
```

### Running Development Server

```bash
# PHP Built-in Server
php artisan serve

# Hoặc sử dụng Laravel Sail (Docker)
./vendor/bin/sail up

# Hoặc sử dụng Composer script
composer run dev
```

## Project Structure

### Modular Architecture

Hệ thống sử dụng **Laravel Modular** với cấu trúc:

```
├── Modules/                    # Business Modules
│   ├── Auth/                  # Authentication & User
│   ├── Product/               # Product Management
│   └── User/                  # User Profile & Address
├── app/                       # Core Application
├── bootstrap/                 # Bootstrap files
├── config/                    # Configuration
├── database/                  # Migrations, Seeders, Factories
├── public/                    # Public assets
├── resources/                 # Views, Language files
├── routes/                    # Route definitions
├── storage/                   # Storage (logs, cache, uploads)
└── tests/                     # Test suites
```

### Module Structure

Mỗi module tuân thủ **3-Layer Architecture**:

```
Modules/{Module}/
├── app/
│   ├── Http/
│   │   ├── Controllers/      # Handle HTTP requests
│   │   ├── Requests/         # Form validation
│   │   └── Resources/        # API response formatting
│   ├── Models/              # Eloquent models
│   ├── Services/            # Business logic
│   ├── Repositories/
│   │   ├── Contracts/       # Interfaces
│   │   └── Eloquent/        # Implementations
│   └── Providers/           # Service providers
├── config/                  # Module config
├── database/
│   ├── factories/          # Model factories
│   └── migrations/         # Module migrations
├── routes/                  # Module routes
└── module.json             # Module metadata
```

### Module Product — cấu trúc domain

Module **Product** gom domain trong `Modules/Product/app/` theo ba vùng (vẫn là **một** module Laravel; autoload `Modules\Product\` → `app/`). HTTP (`Http/`), Providers và factories trong `database/factories/` giữ namespace `Modules\Product\Http\...`, `Modules\Product\Providers\...`, `Modules\Product\Database\Factories\...`.

```
Modules/Product/app/
├── Catalog/                 # Sản phẩm, danh mục, biến thể, giá
│   ├── Enums/               # ProductStatus, VariantStatus, CategoryStatus
│   ├── Models/              # Product, Category, ProductVariant
│   ├── Services/            # ProductService, CategoryService, …
│   └── Repositories/
│       ├── Interfaces/
│       └── Eloquent/
├── Promotion/               # Giảm giá, voucher
│   ├── Models/              # Discount, Voucher, VoucherUse
│   ├── Services/
│   └── Repositories/
├── Media/                   # Upload staging + đồng bộ gallery Spatie
│   ├── Models/              # ProductMediaStaging
│   └── Services/            # ProductMediaService
├── Http/
└── Providers/
```

**Namespace:** `Modules\Product\Catalog\...`, `Modules\Product\Promotion\...`, `Modules\Product\Media\...`. Ví dụ model sản phẩm: `Modules\Product\Catalog\Models\Product`.

## Development Workflow

### 1. Tạo Module Mới

```bash
php artisan make:module NewModule
```

### 2. Tạo Model với Factory, Migration, Seeder

```bash
php artisan module:make-model Product Product --factory --migration --seed
```

### 3. Tạo Repository Pattern

```bash
# Interface
php artisan module:make-repository-contract ProductRepository Product

# Implementation
php artisan module:make-repository ProductRepository Product
```

### 4. Tạo Service

```bash
php artisan module:make-service ProductService Product
```

### 5. Tạo Controller

```bash
php artisan module:make-controller Api/Admin/ProductController Product
```

### 6. Tạo Form Request

```bash
php artisan module:make-request Admin/StoreProductRequest Product
php artisan module:make-request Admin/UpdateProductRequest Product
```

### 7. Tạo API Resource

```bash
php artisan module:make-resource ProductResource Product
```

## Coding Standards

### PHP Standards

Tuân thủ **PSR-12**:

```bash
# Check code style
vendor/bin/pint --test

# Fix code style
vendor/bin/pint

# Fix specific file
vendor/bin/pint app/Models/User.php
```

### Naming Conventions


| Type           | Convention           | Example                      |
| -------------- | -------------------- | ---------------------------- |
| Class          | PascalCase           | `ProductController`          |
| Method         | camelCase            | `calculatePrice()`           |
| Variable       | camelCase            | `$productVariant`            |
| Constant       | UPPER_CASE           | `MAX_PRODUCT_LIMIT`          |
| Database Table | snake_case, plural   | `product_variants`           |
| Model          | PascalCase, singular | `ProductVariant`             |
| Trait          | PascalCase           | `HasDiscounts`               |
| Interface      | PascalCase           | `ProductRepositoryInterface` |


### Code Structure

#### Controllers

```php
<?php

namespace Modules\Product\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Product\Catalog\Services\ProductService;
use Modules\Product\Http\Requests\Admin\StoreProductRequest;
use Modules\Product\Http\Requests\Admin\UpdateProductRequest;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends BaseController
{
    public function __construct(
        private readonly ProductService $productService
    ) {
    }

    public function index(): JsonResponse
    {
        return $this->execute(function (): JsonResponse {
            $products = $this->productService->getProducts();
            
            return $this->successResponse(
                ProductResource::collection($products),
                'Products retrieved successfully'
            );
        });
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $product = $this->productService->createProduct($request->validated());
            
            return $this->successResponse(
                new ProductResource($product),
                'Product created successfully',
                Response::HTTP_CREATED
            );
        });
    }

    public function show(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            $product = $this->productService->find($id);
            
            if (! $product) {
                return $this->errorResponse(
                    'Product not found',
                    Response::HTTP_NOT_FOUND
                );
            }
            
            return $this->successResponse(
                new ProductResource($product),
                'Product retrieved successfully'
            );
        });
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $product = $this->productService->updateProduct($id, $request->validated());
            
            return $this->successResponse(
                new ProductResource($product),
                'Product updated successfully'
            );
        });
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            $this->productService->deleteProduct($id);
            
            return $this->successResponse(
                null,
                'Product deleted successfully',
                Response::HTTP_NO_CONTENT
            );
        });
    }
}
```

#### Services

```php
<?php

namespace Modules\Product\Catalog\Services;

use App\Exceptions\DomainException;
use Modules\Product\Catalog\Models\Product;
use Modules\Product\Catalog\Repositories\Interfaces\ProductRepositoryInterface;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {
    }

    public function createProduct(array $data): Product
    {
        $this->validateUniqueSlug($data['slug'] ?? '');
        
        return $this->repository->create($data);
    }

    public function updateProduct(int $id, array $data): Product
    {
        $product = $this->repository->find($id);
        
        if (! $product) {
            throw new DomainException('Product not found');
        }
        
        if (isset($data['slug']) && $data['slug'] !== $product->slug) {
            $this->validateUniqueSlug($data['slug']);
        }
        
        $this->repository->update($id, $data);
        
        return $this->repository->find($id);
    }

    private function validateUniqueSlug(string $slug): void
    {
        if ($this->repository->findBySlug($slug)) {
            throw new DomainException('Slug already exists');
        }
    }
}
```

#### Repositories

```php
<?php

namespace Modules\Product\Catalog\Repositories\Interfaces;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Product\Catalog\Models\Product;

interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    public function getFiltered(array $filters, ?int $perPage = 15): LengthAwarePaginator;
    
    public function find(int $id): ?Product;
    
    public function findBySlug(string $slug): ?Product;
    
    public function create(array $data): Product;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
}
```

```php
<?php

namespace Modules\Product\Catalog\Repositories\Eloquent;

use App\Repositories\Eloquent\BaseRepository;
use Modules\Product\Catalog\Models\Product;
use Modules\Product\Catalog\Repositories\Interfaces\ProductRepositoryInterface;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function getFiltered(array $filters, ?int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        
        // Apply filters
        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }
        
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
        
        return $query->paginate($perPage);
    }

    public function find(int $id): ?Product
    {
        return $this->model->find($id);
    }

    public function findBySlug(string $slug): ?Product
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->model->where('id', $id)->delete();
    }
}
```

## Validation

### Form Requests

```php
<?php

namespace Modules\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Product\Catalog\Models\Product;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'status' => ['required', 'in:draft,active,inactive,archived'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'slug.unique' => 'This slug is already taken',
            'category_id.exists' => 'Selected category does not exist',
        ];
    }
}
```

## API Resources

```php
<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

## Routes

### Module Routes

```php
<?php

// Modules/Product/routes/api.php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Api\Admin\ProductController;

Route::prefix('admin')->middleware(['auth:api', 'role:admin'])->group(function () {
    
    // Products
    Route::apiResource('products', ProductController::class);
    
    // Custom routes
    Route::post('products/{id}/activate', [ProductController::class, 'activate']);
    Route::post('products/{id}/deactivate', [ProductController::class, 'deactivate']);
    
});

Route::prefix('v1')->group(function () {
    
    // Public routes
    Route::get('products', [CustomerProductController::class, 'index']);
    Route::get('products/{id}', [CustomerProductController::class, 'show']);
    Route::post('products/calculate-price', [CustomerProductController::class, 'calculatePrice']);
    
});
```

## Error Handling

### Custom Exceptions

```php
<?php

namespace App\Exceptions;

use Exception;

class DomainException extends Exception
{
    protected int $statusCode = 400;

    public function __construct(
        string $message = 'Domain error',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
```

### Exception Handler

```php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (DomainException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e->getStatusCode());
            }
        });
    }
}
```

## Database

### Migrations

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained();
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])
                ->default('draft');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'category_id']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

### Factories

```php
<?php

namespace Modules\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Catalog\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->paragraph(),
            'category_id' => Category::factory(),
            'status' => $this->faker->randomElement(['draft', 'active', 'inactive']),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }
}
```

## Git Workflow

### Branch Naming

```
feature/TICKET-123-add-product-variant
bugfix/TICKET-456-fix-discount-calculation
hotfix/TICKET-789-critical-fix
refactor/TICKET-321-optimize-queries
docs/TICKET-654-update-api-docs
```

### Commit Messages

Tuân thủ [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(product): add product variant management

- Add ProductVariant model, migration
- Add ProductVariantController with CRUD
- Add SKU auto-generation

Refs: TICKET-123
```

```
fix(discount): correct percentage calculation

- Fix division by zero error
- Add validation for negative values

Fixes: TICKET-456
```

```
docs(api): update product module documentation

- Add API endpoint examples
- Update request/response schemas
```

### Pull Request Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Checklist
- [ ] Code follows style guidelines
- [ ] Tests added/updated
- [ ] All tests passing
- [ ] Documentation updated
- [ ] No breaking changes (or documented)

## Testing
How to test these changes

## Screenshots (if applicable)
```

## Debugging

### Laravel Telescope

```bash
# Access Telescope
curl http://localhost:8000/telescope
```

### Laravel Pail

```bash
# Real-time log monitoring
php artisan pail

# Filter by type
php artisan pail --filter=error
```

### Tinker

```bash
php artisan tinker

# Test code
>>> $product = \Modules\Product\Catalog\Models\Product::first();
>>> $product->variants;
```

## Performance Optimization

### Caching

```php
// Cache query results
use Modules\Product\Catalog\Models\Product;

$products = Cache::remember('products.active', 3600, function () {
    return Product::where('status', 'active')->get();
});

// Cache configuration
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
```

### Eager Loading

```php
use Modules\Product\Catalog\Models\Product;

// Good - Eager load relationships
$products = Product::with(['category', 'variants', 'discounts'])->get();

// Bad - N+1 query problem
$products = Product::all();
foreach ($products as $product) {
    echo $product->category->name;  // Query executed for each product
}
```

### Query Optimization

```php
use Modules\Product\Catalog\Models\Product;

// Good - Select specific columns
Product::select('id', 'name', 'slug')->get();

// Good - Use indexes
Product::where('status', 'active')
    ->where('category_id', 1)
    ->get();
```

## Security

### Input Validation

- Validate tất cả inputs qua Form Requests
- Sử dụng Laravel Validation rules
- Custom validation messages

### Authorization

- Sử dụng Policies cho authorization
- Check permissions trong controllers
- Route middleware cho roles

### SQL Injection Prevention

- Sử dụng Eloquent ORM (prepared statements)
- Không dùng raw queries với user input
- Validate tất cả parameters

## Environment Variables

### Required

```env
APP_KEY=
DB_CONNECTION=
DB_HOST=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
PASSPORT_PASSWORD_CLIENT_ID=
PASSPORT_PASSWORD_CLIENT_SECRET=
```

### Optional

```env
REDIS_HOST=
MAIL_MAILER=
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
S3_BUCKET=
```

## Troubleshooting

### Common Issues

#### 1. Class Not Found

```bash
composer dump-autoload
```

#### 2. Route Not Found

```bash
php artisan route:clear
php artisan route:cache
```

#### 3. Config Not Updated

```bash
php artisan config:clear
```

#### 4. Migration Failed

```bash
# Fresh database
php artisan migrate:fresh --seed

# Specific migration
php artisan migrate --path=database/migrations/2024_01_01_000001_create_products_table.php
```

#### 5. Permission Denied (Storage)

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage
```

## Useful Commands

```bash
# Artisan
php artisan route:list
php artisan route:list --path=api/admin
php artisan tinker
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan event:clear

# Testing
php artisan test --filter=ProductTest
php artisan test --coverage

# Database
php artisan migrate:status
php artisan migrate:fresh --seed
php artisan db:seed --class=ProductSeeder

# Module
php artisan module:list
php artisan module:migrate Product
php artisan module:seed Product

# Debug
php artisan pail
php artisan tinker --execute="dd(User::first())"
```

## Resources

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [Laravel API Resources](https://laravel.com/docs/12.x/eloquent-resources)
- [Laravel Validation](https://laravel.com/docs/12.x/validation)
- [Laravel Testing](https://laravel.com/docs/12.x/testing)
- [PHP PSR-12](https://www.php-fig.org/psr/psr-12/)

