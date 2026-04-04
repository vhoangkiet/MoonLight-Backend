# Testing Guide

## Overview

Hệ thống MoonLight Backend sử dụng **PHPUnit** cho testing với cấu trúc test rõ ràng chia thành Feature Tests và Unit Tests.

## Test Structure

```
tests/
├── Feature/               # Integration/API Tests
│   ├── CategoryTest.php
│   ├── CustomerProductTest.php
│   ├── DiscountTest.php
│   ├── ProductTest.php
│   ├── ProductVariantTest.php
│   └── VoucherTest.php
├── Unit/                 # Unit Tests
│   └── ProductServiceTest.php
└── TestCase.php         # Base Test Case
```

## Configuration

### phpunit.xml

```xml
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">./app</directory>
            <directory suffix=".php">./Modules</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
```

## TestCase Base Class

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
}
```

Tất cả test classes kế thừa từ `TestCase` đều có:

- **RefreshDatabase**: Database được refresh trước mỗi test
- **SQLite in-memory**: Fast testing database
- **Testing environment**: Isolated from production

## Running Tests

### Run All Tests

```bash
php artisan test --compact
```

### Run Specific Test Suite

```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature
```

### Run Specific Test File

```bash
php artisan test --filter=ProductTest
php artisan test --filter=ProductVariantTest
php artisan test --filter=DiscountTest
php artisan test --filter=VoucherTest
php artisan test --filter=ProductServiceTest
```

### Run Specific Test Method

```bash
php artisan test --filter=test_can_create_product
```

### Run With Coverage

```bash
php artisan test --coverage
```

### Run and Stop on First Failure

```bash
php artisan test --stop-on-failure
```

## Feature Tests

### Pattern

Feature tests kiểm tra API endpoints từ đầu đến cuối:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\Product\Catalog\Models\Category;
use Modules\Product\Catalog\Models\Product;

class ProductTest extends TestCase
{
    public function test_can_list_products(): void
    {
        // Arrange
        $products = Product::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/admin/products');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Products retrieved successfully',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_product(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/admin/products', [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'Test description',
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Product created successfully',
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'slug' => 'test-product',
        ]);
    }

    public function test_cannot_create_product_with_invalid_data(): void
    {
        $response = $this->postJson('/api/admin/products', [
            'name' => '',  // Required field empty
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug', 'category_id']);
    }
}
```

### Common Assertions

#### Status Codes

```php
$response->assertStatus(200);        // OK
$response->assertStatus(201);        // Created
$response->assertStatus(204);        // No Content
$response->assertStatus(400);        // Bad Request
$response->assertStatus(401);        // Unauthorized
$response->assertStatus(403);        // Forbidden
$response->assertStatus(404);        // Not Found
$response->assertStatus(422);        // Validation Error
$response->assertStatus(500);        // Server Error
```

#### JSON Structure

```php
$response->assertJson([
    'success' => true,
    'message' => 'Success message',
    'data' => [
        'id' => 1,
        'name' => 'Product Name',
    ],
]);

$response->assertJsonPath('data.name', 'Product Name');
$response->assertJsonCount(3, 'data');
$response->assertJsonStructure([
    'success',
    'message',
    'data' => [
        'id',
        'name',
        'created_at',
    ],
]);
```

#### Validation Errors

```php
$response->assertJsonValidationErrors('name');
$response->assertJsonValidationErrors(['name', 'email']);
$response->assertJsonMissingValidationErrors('description');
```

#### Database Assertions

```php
$this->assertDatabaseHas('products', [
    'name' => 'Test Product',
    'status' => 'active',
]);

$this->assertDatabaseMissing('products', [
    'name' => 'Deleted Product',
]);

$this->assertDatabaseCount('products', 5);

$this->assertSoftDeleted('products', [
    'id' => 1,
]);
```

## Unit Tests

### Pattern

Unit tests kiểm tra business logic riêng lẻ:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use Modules\Product\Catalog\Models\Product;
use Modules\Product\Catalog\Models\ProductVariant;
use Modules\Product\Catalog\Services\PricingService;
use Modules\Product\Promotion\Models\Discount;

class ProductServiceTest extends TestCase
{
    protected PricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = app(PricingService::class);
    }

    public function test_pricing_service_calculates_base_price(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 100.00,
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
        ]);

        $result = $this->pricingService->calculate($product->id, $variant->id);

        $this->assertEquals(100.00, $result['base_price']);
        $this->assertEquals(0, $result['discount_amount']);
        $this->assertEquals(100.00, $result['final_price']);
    }

    public function test_pricing_service_applies_percentage_discount(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 100.00,
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
        ]);
        
        $discount = Discount::factory()->create([
            'type' => 'percentage',
            'value' => 20,
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
        ]);
        $discount->products()->attach($product->id, ['product_variant_id' => $variant->id]);

        $result = $this->pricingService->calculate($product->id, $variant->id);

        $this->assertEquals(100.00, $result['base_price']);
        $this->assertEquals(20.00, $result['discount_amount']);
        $this->assertEquals(80.00, $result['final_price']);
    }

    public function test_pricing_service_throws_exception_for_nonexistent_product(): void
    {
        $this->expectException(\App\Exceptions\DomainException::class);
        $this->expectExceptionMessage('Invalid product variant');

        $this->pricingService->calculate(99999, 1);
    }
}
```

### Testing Services

```php
public function test_service_creates_product_with_valid_data(): void
{
    $service = app(ProductService::class);
    $category = Category::factory()->create();

    $product = $service->createProduct([
        'name' => 'New Product',
        'slug' => 'new-product',
        'category_id' => $category->id,
        'status' => 'active',
    ]);

    $this->assertInstanceOf(Product::class, $product);
    $this->assertEquals('New Product', $product->name);
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'New Product',
    ]);
}

public function test_service_throws_exception_for_duplicate_slug(): void
{
    $service = app(ProductService::class);
    $existingProduct = Product::factory()->create(['slug' => 'existing-slug']);

    $this->expectException(DomainException::class);

    $service->createProduct([
        'name' => 'New Product',
        'slug' => 'existing-slug',  // Duplicate
        'category_id' => 1,
    ]);
}
```

## Factories

### Creating Test Data

```php
// Simple factory
$product = Product::factory()->create();

// With specific attributes
$product = Product::factory()->create([
    'name' => 'Custom Name',
    'status' => 'active',
]);

// Multiple records
$products = Product::factory()->count(5)->create();

// With relationships
$product = Product::factory()
    ->has(Category::factory())
    ->has(ProductVariant::factory()->count(3))
    ->create();

// With states
$product = Product::factory()->active()->create();
$product = Product::factory()->draft()->create();
```

### Factory States

```php
// In ProductFactory.php
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
```

## Authentication in Tests

### Acting As User

```php
use App\Models\User;

public function test_admin_can_create_product(): void
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)
        ->postJson('/api/admin/products', [
            'name' => 'New Product',
            // ...
        ]);

    $response->assertStatus(201);
}

public function test_customer_cannot_access_admin_endpoints(): void
{
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $response = $this->actingAs($customer)
        ->getJson('/api/admin/products');

    $response->assertStatus(403);
}
```

### Sanctum/Passport Tokens

```php
use Laravel\Passport\Passport;

public function test_api_with_token(): void
{
    $user = User::factory()->create();
    $token = $user->createToken('TestToken')->accessToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/admin/products');

    $response->assertStatus(200);
}
```

## Testing Best Practices

### 1. Test Naming

```php
// Good
public function test_can_create_product_with_valid_data(): void
public function test_cannot_create_product_with_duplicate_slug(): void
public function test_returns_404_for_nonexistent_product(): void

// Bad
public function testCreate(): void
public function test_product(): void
public function test1(): void
```

### 2. Arrange-Act-Assert Pattern

```php
public function test_can_update_product(): void
{
    // Arrange
    $product = Product::factory()->create(['name' => 'Old Name']);
    $category = Category::factory()->create();

    // Act
    $response = $this->putJson("/api/admin/products/{$product->id}", [
        'name' => 'New Name',
        'category_id' => $category->id,
    ]);

    // Assert
    $response->assertStatus(200);
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'New Name',
    ]);
}
```

### 3. One Concept Per Test

```php
// Good - Test one thing
public function test_validation_fails_without_name(): void
{
    $response = $this->postJson('/api/admin/products', []);
    $response->assertJsonValidationErrors('name');
}

// Good - Test one thing
public function test_validation_fails_without_category(): void
{
    $response = $this->postJson('/api/admin/products', ['name' => 'Test']);
    $response->assertJsonValidationErrors('category_id');
}

// Bad - Testing multiple things
public function test_validation(): void
{
    $response = $this->postJson('/api/admin/products', []);
    $response->assertJsonValidationErrors(['name', 'category_id', 'slug']);
}
```

### 4. Use Factories, Not Fixtures

```php
// Good - Dynamic data
$product = Product::factory()->create();

// Bad - Static data, hard to maintain
$this->postJson('/api/admin/products', [
    'name' => 'Test Product 123',
    'slug' => 'test-product-123',
]);
```

### 5. Clean Up After Tests

```php
// Already handled by RefreshDatabase trait
// No manual cleanup needed
```

### 6. Test Edge Cases

```php
public function test_can_handle_empty_search_results(): void
{
    $response = $this->getJson('/api/admin/products?search=nonexistent');
    
    $response->assertStatus(200)
        ->assertJsonCount(0, 'data');
}

public function test_handles_very_long_product_name(): void
{
    $longName = str_repeat('a', 255);
    
    $response = $this->postJson('/api/admin/products', [
        'name' => $longName,
        'slug' => 'test-slug',
        'category_id' => 1,
    ]);
    
    $response->assertStatus(201);
}
```

## Debugging Tests

### Dump Response

```php
$response = $this->getJson('/api/admin/products');
dd($response->json());  // or dump($response->json())
```

### Dump Database

```php
$this->postJson('/api/admin/products', $data);
db::table('products')->dump();
```

### Stop on Failure

```bash
php artisan test --stop-on-failure
```

### Verbose Output

```bash
php artisan test --verbose
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
        
    - name: Install dependencies
      run: composer install
      
    - name: Run tests
      run: php artisan test --compact
      env:
        DB_CONNECTION: sqlite
        DB_DATABASE: ':memory:'
```

## Troubleshooting

### Common Issues

#### 1. Database Locked (SQLite)

```bash
# Solution: Use in-memory database
DB_DATABASE=":memory:"
```

#### 2. Foreign Key Constraints

```php
// Ensure factories create related data
$product = Product::factory()->create();  // Creates category automatically
```

#### 3. Time-based Tests

```php
// Use Carbon for time manipulation
use Illuminate\Support\Carbon;

Carbon::setTestNow('2024-01-01 12:00:00');
// ... run test
Carbon::setTestNow();
```

#### 4. Async Jobs

```php
// Use Queue fake for testing jobs
use Illuminate\Support\Facades\Queue;

Queue::fake();

// Run test

Queue::assertPushed(ProcessPodcast::class);
```

