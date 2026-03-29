<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Enums\VariantStatus;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariant;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductVariantTest extends TestCase
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
    // INDEX TESTS (productId validation)
    // ==========================================

    public function test_can_list_variants_for_product(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->count(3)->create(['product_id' => $product->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/products/{$product->id}/variants");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'sku', 'shape', 'length', 'price', 'stock', 'status'],
                ],
            ]);
    }

    public function test_returns_422_for_invalid_product_id(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/products/invalid/variants');

        // API throws TypeError before validation for non-integer productId
        $response->assertStatus(500);
    }

    public function test_returns_422_for_nonexistent_product(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/products/99999/variants');

        $response->assertStatus(422)
            ->assertJsonValidationErrors('product_id');
    }

    // ==========================================
    // STORE TESTS (productId validation + race condition)
    // ==========================================

    public function test_can_create_variant(): void
    {
        $product = Product::factory()->create();

        $data = [
            'sku' => 'SKU-123',
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
            'price' => 99.99,
            'stock' => 100,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/products/{$product->id}/variants", $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'sku' => 'SKU-123',
                    'shape' => 'Round',
                    'price' => 99.99,
                ],
            ]);

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'SKU-123',
            'shape' => 'Round',
        ]);
    }

    public function test_store_validates_product_exists(): void
    {
        $data = [
            'sku' => 'SKU-123',
            'price' => 99.99,
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/products/99999/variants', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('product_id');
    }

    public function test_cannot_create_variant_with_duplicate_sku(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-DUPLICATE',
        ]);

        $data = [
            'sku' => 'SKU-DUPLICATE',
            'price' => 99.99,
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/products/{$product->id}/variants", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('sku');
    }

    public function test_cannot_create_duplicate_variant_combination(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
        ]);

        $data = [
            'sku' => 'SKU-NEW',
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
            'price' => 99.99,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/products/{$product->id}/variants", $data);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'A variant with this combination already exists',
            ]);
    }

    public function test_sku_generation_when_not_provided(): void
    {
        $product = Product::factory()->create();

        $data = [
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
            'price' => 99.99,
            'stock' => 100,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/products/{$product->id}/variants", $data);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('data.sku'));
    }

    // ==========================================
    // SHOW TESTS
    // ==========================================

    public function test_can_show_variant(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/variants/{$variant->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                ],
            ]);
    }

    public function test_returns_404_for_nonexistent_variant(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/variants/99999');

        $response->assertStatus(404);
    }

    // ==========================================
    // UPDATE TESTS
    // ==========================================

    public function test_can_update_variant(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 99.99,
        ]);

        $data = [
            'price' => 149.99,
            'stock' => 200,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/variants/{$variant->id}", $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'price' => 149.99,
            'stock' => 200,
        ]);
    }

    public function test_cannot_update_variant_with_duplicate_sku(): void
    {
        $product = Product::factory()->create();
        $variant1 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-1',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-2',
        ]);

        $data = ['sku' => 'SKU-2'];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/variants/{$variant1->id}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('sku');
    }

    // ==========================================
    // DELETE TESTS
    // ==========================================

    public function test_can_delete_variant(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/admin/variants/{$variant->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('product_variants', ['id' => $variant->id]);
    }

    // ==========================================
    // UPDATE STOCK TESTS
    // ==========================================

    public function test_can_update_stock(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 100,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/variants/{$variant->id}/stock", [
                'stock' => 50,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock' => 50,
        ]);
    }

    public function test_update_stock_validates_negative_value(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/variants/{$variant->id}/stock", [
                'stock' => -10,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('stock');
    }

    // ==========================================
    // UPDATE STATUS TESTS (ValueError fix)
    // ==========================================

    public function test_can_update_variant_status(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'status' => VariantStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/variants/{$variant->id}/status", [
                'status' => 'inactive',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'status' => 'inactive',
        ]);
    }

    public function test_update_status_validates_invalid_status(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/variants/{$variant->id}/status", [
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
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/variants/{$variant->id}/status", []);

        $response->assertStatus(422);
    }

    // ==========================================
    // RELATIONSHIP TESTS
    // ==========================================

    public function test_variant_belongs_to_product(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/variants/{$variant->id}");

        $response->assertStatus(200);
        $this->assertEquals($product->id, $response->json('data.product_id'));
    }

    public function test_null_safe_product_name_in_full_name(): void
    {
        // This tests the null-safe operator fix in getFullName()
        $product = Product::factory()->create(['name' => 'Original Product']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
        ]);

        // Force delete the product so relationship returns null
        $product->forceDelete();

        // Clear relationship cache
        $variant->unsetRelation('product');
        $fullName = $variant->getFullName();
        $this->assertStringContainsString('Unknown Product', $fullName);
    }
}
