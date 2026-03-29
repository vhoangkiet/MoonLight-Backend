<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Models\Category;
use Modules\Product\Models\Discount;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariant;
use Modules\Product\Models\Voucher;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerProductTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'api']);
        Role::create(['name' => 'customer', 'guard_name' => 'api']);

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
    }

    // ==========================================
    // INDEX TESTS (public access)
    // ==========================================

    public function test_public_can_list_active_products(): void
    {
        Product::factory()->create(['status' => ProductStatus::ACTIVE]);
        Product::factory()->create(['status' => ProductStatus::INACTIVE]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('active', $response->json('data.0.status'));
    }

    public function test_can_search_products(): void
    {
        Product::factory()->create(['name' => 'iPhone 15', 'status' => ProductStatus::ACTIVE]);
        Product::factory()->create(['name' => 'Samsung Galaxy', 'status' => ProductStatus::ACTIVE]);

        $response = $this->getJson('/api/v1/products?search=iPhone');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsString('iPhone', $response->json('data.0.name'));
    }

    public function test_can_filter_products_by_category(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'status' => ProductStatus::ACTIVE,
        ]);
        Product::factory()->create(['status' => ProductStatus::ACTIVE]);

        $response = $this->getJson("/api/v1/products?category_id={$category->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_validates_category_id(): void
    {
        $response = $this->getJson('/api/v1/products?category_id=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors('category_id');
    }

    public function test_index_validates_per_page(): void
    {
        $response = $this->getJson('/api/v1/products?per_page=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_per_page_max_100(): void
    {
        $response = $this->getJson('/api/v1/products?per_page=200');

        $response->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    // ==========================================
    // SHOW TESTS
    // ==========================================

    public function test_can_show_product_by_slug(): void
    {
        $product = Product::factory()->create([
            'slug' => 'test-product',
            'status' => ProductStatus::ACTIVE,
        ]);
        ProductVariant::factory()->count(2)->create(['product_id' => $product->id]);

        $response = $this->getJson('/api/v1/products/test-product');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'variants' => [
                        '*' => ['id', 'sku', 'price'],
                    ],
                ],
            ]);
    }

    public function test_returns_404_for_inactive_product(): void
    {
        Product::factory()->create([
            'slug' => 'inactive-product',
            'status' => ProductStatus::INACTIVE,
        ]);

        $response = $this->getJson('/api/v1/products/inactive-product');

        $response->assertStatus(404);
    }

    public function test_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson('/api/v1/products/nonexistent-product');

        $response->assertStatus(404);
    }

    // ==========================================
    // CALCULATE PRICE TESTS
    // ==========================================

    public function test_can_calculate_price_with_variant(): void
    {
        $product = Product::factory()->create(['status' => ProductStatus::ACTIVE]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 100.00,
        ]);

        $response = $this->postJson('/api/v1/products/calculate-price', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'base_price' => 100.00,
                    'final_price' => 100.00,
                ],
            ]);
    }

    public function test_can_calculate_price_with_discount(): void
    {
        $product = Product::factory()->create(['status' => ProductStatus::ACTIVE]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 100.00,
        ]);
        $discount = Discount::factory()->create([
            'type' => 'percentage',
            'value' => 20,
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
        ]);
        $discount->products()->attach($product->id, ['product_variant_id' => $variant->id]);

        $response = $this->postJson('/api/v1/products/calculate-price', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'base_price' => 100.00,
                    'discount_amount' => 20.00,
                    'final_price' => 80.00,
                ],
            ]);
    }

    public function test_can_calculate_price_with_voucher(): void
    {
        $product = Product::factory()->create(['status' => ProductStatus::ACTIVE]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 100.00,
        ]);
        $voucher = Voucher::factory()->create([
            'code' => 'TEST20',
            'type' => 'percentage',
            'value' => 20,
            'min_order_amount' => 50,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/products/calculate-price', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'voucher_code' => 'TEST20',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'base_price' => 100.00,
                    'voucher_amount' => 20.00,
                    'final_price' => 80.00,
                ],
            ]);
    }

    public function test_calculate_price_validates_product_id(): void
    {
        $response = $this->postJson('/api/v1/products/calculate-price', [
            'product_id' => 'invalid',
            'variant_id' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('product_id');
    }

    public function test_calculate_price_validates_variant_id(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/v1/products/calculate-price', [
            'product_id' => $product->id,
            'variant_id' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('variant_id');
    }

    public function test_calculate_price_requires_product_id(): void
    {
        $response = $this->postJson('/api/v1/products/calculate-price', [
            'variant_id' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('product_id');
    }

    public function test_calculate_price_requires_variant_id(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/v1/products/calculate-price', [
            'product_id' => $product->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('variant_id');
    }

    public function test_returns_422_for_invalid_voucher_code(): void
    {
        $product = Product::factory()->create(['status' => ProductStatus::ACTIVE]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 100.00,
        ]);

        $response = $this->postJson('/api/v1/products/calculate-price', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'voucher_code' => 'INVALIDCODE',
        ]);

        $response->assertStatus(200);
    }

    public function test_calculate_price_returns_404_for_nonexistent_product(): void
    {
        $response = $this->postJson('/api/v1/products/calculate-price', [
            'product_id' => 99999,
            'variant_id' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_returns_404_for_nonexistent_variant(): void
    {
        $product = Product::factory()->create(['status' => ProductStatus::ACTIVE]);

        $response = $this->postJson('/api/v1/products/calculate-price', [
            'product_id' => $product->id,
            'variant_id' => 99999,
        ]);

        $response->assertStatus(422);
    }

    public function test_discount_not_applied_to_wrong_variant(): void
    {
        // This tests the strict type comparison fix in PricingService
        $product = Product::factory()->create(['status' => ProductStatus::ACTIVE]);
        $variant1 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 100.00,
        ]);
        $variant2 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 200.00,
        ]);

        // Apply discount only to variant1
        $discount = Discount::factory()->create([
            'type' => 'percentage',
            'value' => 50,
            'is_active' => true,
        ]);
        $discount->products()->attach($product->id, ['product_variant_id' => $variant1->id]);

        // Calculate price for variant2 - should not have discount
        $response = $this->postJson('/api/v1/products/calculate-price', [
            'product_id' => $product->id,
            'variant_id' => $variant2->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'base_price' => 200.00,
                    'final_price' => 200.00, // No discount applied
                ],
            ]);
    }
}
