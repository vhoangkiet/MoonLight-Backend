<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Discount;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariant;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DiscountTest extends TestCase
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

    public function test_can_list_discounts(): void
    {
        Discount::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/discounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'code', 'type', 'value', 'is_active'],
                ],
            ]);
    }

    // ==========================================
    // STORE TESTS (percentage validation)
    // ==========================================

    public function test_can_create_percentage_discount(): void
    {
        $data = [
            'name' => 'Summer Sale',
            'code' => 'SUMMER20',
            'type' => 'percentage',
            'value' => 20,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/discounts', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Summer Sale',
                    'type' => 'percentage',
                    'value' => 20,
                ],
            ]);

        $this->assertDatabaseHas('discounts', [
            'code' => 'SUMMER20',
            'type' => 'percentage',
        ]);
    }

    public function test_can_create_fixed_discount(): void
    {
        $data = [
            'name' => 'Fixed Discount',
            'code' => 'FIXED50',
            'type' => 'fixed',
            'value' => 50,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/discounts', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('discounts', ['code' => 'FIXED50']);
    }

    public function test_cannot_create_percentage_discount_over_100(): void
    {
        $data = [
            'name' => 'Invalid Discount',
            'type' => 'percentage',
            'value' => 150,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/discounts', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('value');
    }

    public function test_cannot_create_discount_with_duplicate_code(): void
    {
        Discount::factory()->create(['code' => 'UNIQUE']);

        $data = [
            'name' => 'Duplicate',
            'code' => 'UNIQUE',
            'type' => 'fixed',
            'value' => 10,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/discounts', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    // ==========================================
    // SHOW TESTS
    // ==========================================

    public function test_can_show_discount(): void
    {
        $discount = Discount::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/discounts/{$discount->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $discount->id,
                    'name' => $discount->name,
                ],
            ]);
    }

    public function test_returns_404_for_nonexistent_discount(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/discounts/99999');

        $response->assertStatus(404);
    }

    // ==========================================
    // UPDATE TESTS (percentage validation)
    // ==========================================

    public function test_can_update_discount(): void
    {
        $discount = Discount::factory()->create(['name' => 'Old Name']);

        $data = [
            'name' => 'New Name',
            'value' => 30,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/discounts/{$discount->id}", $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('discounts', [
            'id' => $discount->id,
            'name' => 'New Name',
        ]);
    }

    public function test_cannot_update_discount_percentage_over_100(): void
    {
        $discount = Discount::factory()->create([
            'type' => 'percentage',
            'value' => 10,
        ]);

        $data = [
            'type' => 'percentage',
            'value' => 150,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/discounts/{$discount->id}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('value');
    }

    public function test_end_date_must_be_after_start_date(): void
    {
        $discount = Discount::factory()->create();

        $data = [
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/discounts/{$discount->id}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('end_date');
    }

    // ==========================================
    // DELETE TESTS
    // ==========================================

    public function test_can_delete_discount(): void
    {
        $discount = Discount::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/admin/discounts/{$discount->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('discounts', ['id' => $discount->id]);
    }

    // ==========================================
    // APPLY TO PRODUCT TESTS (validation fix)
    // ==========================================

    public function test_can_apply_discount_to_product(): void
    {
        $discount = Discount::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/discounts/{$discount->id}/apply", [
                'product_id' => $product->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Discount applied to product successfully',
            ]);

        $this->assertDatabaseHas('discount_product', [
            'discount_id' => $discount->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_can_apply_discount_to_product_variant(): void
    {
        $discount = Discount::factory()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/discounts/{$discount->id}/apply", [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('discount_product', [
            'discount_id' => $discount->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
        ]);
    }

    public function test_apply_discount_validates_product_id(): void
    {
        $discount = Discount::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/discounts/{$discount->id}/apply", [
                'product_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('product_id');
    }

    public function test_apply_discount_validates_variant_id(): void
    {
        $discount = Discount::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/discounts/{$discount->id}/apply", [
                'product_id' => $product->id,
                'variant_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('variant_id');
    }

    public function test_cannot_apply_same_discount_twice(): void
    {
        $discount = Discount::factory()->create();
        $product = Product::factory()->create();

        // Apply first time
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/discounts/{$discount->id}/apply", [
                'product_id' => $product->id,
            ]);

        // Try to apply second time
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/discounts/{$discount->id}/apply", [
                'product_id' => $product->id,
            ]);

        $response->assertStatus(200); // Should succeed, already applied

        // Verify only one pivot record exists
        $count = \DB::table('discount_product')
            ->where('discount_id', $discount->id)
            ->where('product_id', $product->id)
            ->count();
        $this->assertEquals(1, $count);
    }

    public function test_returns_404_when_applying_nonexistent_discount(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/discounts/99999/apply', [
                'product_id' => $product->id,
            ]);

        $response->assertStatus(404);
    }

    // ==========================================
    // REMOVE FROM PRODUCT TESTS
    // ==========================================

    public function test_can_remove_discount_from_product(): void
    {
        $discount = Discount::factory()->create();
        $product = Product::factory()->create();
        $discount->products()->attach($product->id);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/discounts/{$discount->id}/remove", [
                'product_id' => $product->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Discount removed from product successfully',
            ]);

        $this->assertDatabaseMissing('discount_product', [
            'discount_id' => $discount->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_remove_discount_validates_product_id(): void
    {
        $discount = Discount::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/discounts/{$discount->id}/remove", [
                'product_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('product_id');
    }
}
