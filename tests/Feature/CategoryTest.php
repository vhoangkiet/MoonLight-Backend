<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Catalog\Enums\CategoryStatus;
use Modules\Product\Catalog\Models\Category;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryTest extends TestCase
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

    public function test_can_list_categories(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'status', 'products_count', 'has_children', 'parent_id'],
                ],
            ]);
    }

    public function test_can_create_category(): void
    {
        $data = [
            'name' => 'Electronics',
            'description' => 'Electronic devices',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/categories', $data);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Electronics',
                    'slug' => 'electronics',
                    'status' => 'active',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);
    }

    public function test_can_create_sub_category(): void
    {
        $parent = Category::factory()->create();

        $data = [
            'name' => 'Smartphones',
            'parent_id' => $parent->id,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/categories', $data);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Smartphones',
                    'parent_id' => $parent->id,
                ],
            ]);

        $this->assertDatabaseHas('category_closure', [
            'ancestor_id' => $parent->id,
            'descendant_id' => $response->json('data.id'),
            'depth' => 1,
        ]);
    }

    public function test_cannot_update_category_with_circular_parent(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $data = ['parent_id' => $child->id];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/categories/{$parent->id}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_cannot_delete_category_with_children(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/admin/categories/{$parent->id}");

        $response->assertStatus(409)
            ->assertJson(['message' => 'Cannot delete category with sub-categories']);
    }

    public function test_can_filter_by_parent_id_null(): void
    {
        $root = Category::factory()->create(['parent_id' => null]);
        Category::factory()->create(['parent_id' => $root->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/categories?parent_id=');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertNull($response->json('data.0.parent_id'));
    }

    public function test_closure_table_updated_for_descendants_when_parent_changes(): void
    {
        // Create hierarchy: grandparent -> parent -> child
        $grandparent = Category::factory()->create();
        $parent = Category::factory()->create(['parent_id' => $grandparent->id]);
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        // Move parent under a new root (not grandparent)
        $newRoot = Category::factory()->create();
        $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/categories/{$parent->id}", ['parent_id' => $newRoot->id])
            ->assertStatus(200);

        // Verify child's closure table entries are updated
        // Child should now have newRoot as ancestor (depth 2), not grandparent
        $this->assertDatabaseHas('category_closure', [
            'ancestor_id' => $newRoot->id,
            'descendant_id' => $child->id,
            'depth' => 2,
        ]);
        $this->assertDatabaseMissing('category_closure', [
            'ancestor_id' => $grandparent->id,
            'descendant_id' => $child->id,
        ]);
    }

    public function test_public_can_list_active_categories(): void
    {
        Category::factory()->create(['status' => CategoryStatus::ACTIVE]);
        Category::factory()->create(['status' => CategoryStatus::INACTIVE]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }
}
