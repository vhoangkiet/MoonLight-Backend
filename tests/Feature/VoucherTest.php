<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Promotion\Models\Voucher;
use Modules\Product\Promotion\Models\VoucherUse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VoucherTest extends TestCase
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
    // INDEX TESTS (sort validation)
    // ==========================================

    public function test_can_list_vouchers(): void
    {
        Voucher::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/vouchers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'code', 'name', 'type', 'value', 'is_active'],
                ],
            ]);
    }

    public function test_can_sort_vouchers_by_code(): void
    {
        Voucher::factory()->create(['code' => 'ZEBRA']);
        Voucher::factory()->create(['code' => 'APPLE']);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/vouchers?sort_by=code&sort_order=asc');

        $response->assertStatus(200);
        $this->assertEquals('APPLE', $response->json('data.0.code'));
    }

    public function test_index_validates_sort_by_parameter(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/vouchers?sort_by=invalid_column');

        $response->assertStatus(422)
            ->assertJsonValidationErrors('sort_by');
    }

    public function test_index_validates_sort_order_parameter(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/vouchers?sort_order=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors('sort_order');
    }

    // ==========================================
    // STORE TESTS (percentage validation)
    // ==========================================

    public function test_can_create_percentage_voucher(): void
    {
        $data = [
            'name' => 'Summer Sale',
            'code' => 'SUMMER20',
            'type' => 'percentage',
            'value' => 20,
            'min_order_amount' => 100,
            'max_discount_amount' => 50,
            'usage_limit' => 100,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/vouchers', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'code' => 'SUMMER20',
                    'type' => 'percentage',
                    'value' => 20,
                ],
            ]);

        $this->assertDatabaseHas('vouchers', [
            'code' => 'SUMMER20',
            'type' => 'percentage',
        ]);
    }

    public function test_can_create_fixed_voucher(): void
    {
        $data = [
            'name' => 'Fixed Discount',
            'code' => 'FIXED50',
            'type' => 'fixed',
            'value' => 50,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/vouchers', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('vouchers', ['code' => 'FIXED50']);
    }

    public function test_cannot_create_percentage_voucher_over_100(): void
    {
        $data = [
            'name' => 'Invalid Voucher',
            'code' => 'INVALID',
            'type' => 'percentage',
            'value' => 150,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/vouchers', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('value');
    }

    public function test_cannot_create_voucher_with_duplicate_code(): void
    {
        Voucher::factory()->create(['code' => 'UNIQUE']);

        $data = [
            'name' => 'Duplicate',
            'code' => 'UNIQUE',
            'type' => 'fixed',
            'value' => 10,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/vouchers', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_valid_until_must_be_after_valid_from(): void
    {
        $data = [
            'name' => 'Test Voucher',
            'code' => 'TEST',
            'type' => 'fixed',
            'value' => 10,
            'valid_from' => now()->addDays(10)->toDateString(),
            'valid_until' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/vouchers', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('valid_until');
    }

    // ==========================================
    // SHOW TESTS
    // ==========================================

    public function test_can_show_voucher(): void
    {
        $voucher = Voucher::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/vouchers/{$voucher->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $voucher->id,
                    'code' => $voucher->code,
                ],
            ]);
    }

    public function test_returns_404_for_nonexistent_voucher(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/vouchers/99999');

        $response->assertStatus(404);
    }

    // ==========================================
    // UPDATE TESTS
    // ==========================================

    public function test_can_update_voucher(): void
    {
        $voucher = Voucher::factory()->create(['name' => 'Old Name']);

        $data = [
            'name' => 'New Name',
            'value' => 30,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/vouchers/{$voucher->id}", $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'name' => 'New Name',
        ]);
    }

    public function test_cannot_update_voucher_percentage_over_100(): void
    {
        $voucher = Voucher::factory()->create([
            'type' => 'percentage',
            'value' => 10,
        ]);

        $data = [
            'type' => 'percentage',
            'value' => 150,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/vouchers/{$voucher->id}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('value');
    }

    public function test_cannot_update_voucher_with_duplicate_code(): void
    {
        $voucher1 = Voucher::factory()->create(['code' => 'CODE1']);
        Voucher::factory()->create(['code' => 'CODE2']);

        $data = ['code' => 'CODE2'];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/vouchers/{$voucher1->id}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_update_validates_valid_until_against_existing_valid_from(): void
    {
        $voucher = Voucher::factory()->create([
            'valid_from' => now()->addDays(5)->toDateString(),
            'valid_until' => now()->addDays(10)->toDateString(),
        ]);

        $data = [
            'valid_until' => now()->toDateString(), // Before valid_from
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/vouchers/{$voucher->id}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('valid_until');
    }

    // ==========================================
    // DELETE TESTS
    // ==========================================

    public function test_can_delete_voucher(): void
    {
        $voucher = Voucher::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/admin/vouchers/{$voucher->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('vouchers', ['id' => $voucher->id]);
    }

    public function test_cannot_delete_voucher_with_usage_history(): void
    {
        $voucher = Voucher::factory()->create();
        $user = User::factory()->create();
        VoucherUse::factory()->create([
            'voucher_id' => $voucher->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/admin/vouchers/{$voucher->id}");

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Cannot delete voucher with usage history',
            ]);
    }

    // ==========================================
    // REMAINING USAGE TESTS
    // ==========================================

    public function test_remaining_usage_never_negative(): void
    {
        $voucher = Voucher::factory()->create([
            'usage_limit' => 5,
            'usage_count' => 3,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/vouchers/{$voucher->id}");

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data'));
        $this->assertArrayHasKey('remaining_usage', $response->json('data'));
    }

    public function test_remaining_usage_is_zero_when_limit_reached(): void
    {
        $voucher = Voucher::factory()->create([
            'usage_limit' => 5,
            'usage_count' => 5,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/vouchers/{$voucher->id}");

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('data.remaining_usage'));
    }

    public function test_remaining_usage_never_negative_when_count_exceeds_limit(): void
    {
        // This tests the max(0, ...) fix in VoucherResource
        $voucher = Voucher::factory()->create([
            'usage_limit' => 5,
            'usage_count' => 8, // Exceeds limit
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/vouchers/{$voucher->id}");

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('data.remaining_usage'));
        $this->assertGreaterThanOrEqual(0, $response->json('data.remaining_usage'));
    }

    public function test_remaining_usage_is_null_for_unlimited_vouchers(): void
    {
        $voucher = Voucher::factory()->create([
            'usage_limit' => null,
            'usage_count' => 100,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/vouchers/{$voucher->id}");

        $response->assertStatus(200);
        $this->assertNull($response->json('data.remaining_usage'));
    }
}
