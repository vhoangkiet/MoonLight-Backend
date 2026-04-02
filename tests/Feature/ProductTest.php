<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariant;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'api']);
        Role::create(['name' => 'customer', 'guard_name' => 'api']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    // ==========================================
    // INDEX TESTS
    // ==========================================

    public function test_can_list_products_with_pagination(): void
    {
        Product::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'status', 'category'],
                ],
            ]);
    }

    public function test_can_filter_products_by_status(): void
    {
        Product::factory()->create(['status' => ProductStatus::ACTIVE]);
        Product::factory()->create(['status' => ProductStatus::INACTIVE]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/products?status=active');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('active', $response->json('data.0.status'));
    }

    public function test_can_filter_products_by_category(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);
        Product::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/products?category_id={$category->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_search_products(): void
    {
        Product::factory()->create(['name' => 'iPhone 15']);
        Product::factory()->create(['name' => 'Samsung Galaxy']);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/products?search=iPhone');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsString('iPhone', $response->json('data.0.name'));
    }

    public function test_can_sort_products(): void
    {
        Product::factory()->create(['name' => 'Zebra']);
        Product::factory()->create(['name' => 'Apple']);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/products?sort_by=name&sort_order=asc');

        $response->assertStatus(200);
        $this->assertEquals('Apple', $response->json('data.0.name'));
    }

    public function test_index_validates_per_page_parameter(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/products?per_page=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_index_validates_sort_by_parameter(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/products?sort_by=invalid_column');

        $response->assertStatus(422)
            ->assertJsonValidationErrors('sort_by');
    }

    // ==========================================
    // STORE TESTS
    // ==========================================

    public function test_can_create_product(): void
    {
        $category = Category::factory()->create();

        $data = [
            'name' => 'Test Product',
            'description' => 'Test description',
            'category_id' => $category->id,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/products', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Test Product',
                    'slug' => 'test-product',
                    'status' => 'active',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'category_id' => $category->id,
        ]);
    }

    public function test_cannot_create_product_with_duplicate_slug(): void
    {
        Product::factory()->create(['slug' => 'test-product']);
        $category = Category::factory()->create();

        $data = [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'category_id' => $category->id,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/products', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    public function test_cannot_create_product_with_invalid_category(): void
    {
        $data = [
            'name' => 'Test Product',
            'category_id' => 99999,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/products', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('category_id');
    }

    // ==========================================
    // SHOW TESTS
    // ==========================================

    public function test_can_show_product_with_variants(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->count(3)->create(['product_id' => $product->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'variants' => [
                        '*' => ['id', 'sku', 'price', 'stock'],
                    ],
                ],
            ]);
    }

    public function test_returns_404_for_nonexistent_product(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/products/99999');

        $response->assertStatus(404);
    }

    // ==========================================
    // UPDATE TESTS
    // ==========================================

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create(['name' => 'Old Name']);

        $data = [
            'name' => 'New Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/products/{$product->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'New Name',
                    'slug' => 'new-name',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Name',
            'slug' => 'new-name',
        ]);
    }

    public function test_cannot_update_product_with_duplicate_slug(): void
    {
        $product1 = Product::factory()->create(['slug' => 'product-1']);
        Product::factory()->create(['slug' => 'product-2']);

        $data = ['slug' => 'product-2'];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/products/{$product1->id}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    // ==========================================
    // DELETE TESTS
    // ==========================================

    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/admin/products/{$product->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_returns_404_when_deleting_nonexistent_product(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson('/api/v1/admin/products/99999');

        $response->assertStatus(404);
    }

    // ==========================================
    // UPDATE STATUS TESTS (ValueError fix)
    // ==========================================

    public function test_can_update_product_status(): void
    {
        $product = Product::factory()->create(['status' => ProductStatus::ACTIVE]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/products/{$product->id}/status", [
                'status' => 'inactive',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'status' => 'inactive',
        ]);
    }

    public function test_update_status_validates_invalid_status(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/products/{$product->id}/status", [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid status value',
            ]);
    }

    public function test_update_status_validates_missing_status(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/products/{$product->id}/status", []);

        $response->assertStatus(422);
    }

    public function test_returns_404_when_updating_status_of_nonexistent_product(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->patchJson('/api/v1/admin/products/99999/status', [
                'status' => 'active',
            ]);

        $response->assertStatus(404);
    }

    // ==========================================
    // RELATIONSHIPS TESTS
    // ==========================================

    public function test_product_variants_use_enum_for_status(): void
    {
        $product = Product::factory()->create();

        // Create variant with active status
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'status' => 'active',
        ]);

        // Create variant with inactive status
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/products/{$product->id}");

        $response->assertStatus(200);

        // Check that variants() relationship only returns active ones
        $product->load('variants');
        $this->assertEquals(1, $product->variants->count());
        $this->assertEquals('active', $product->variants->first()->status->value);
    }
}
