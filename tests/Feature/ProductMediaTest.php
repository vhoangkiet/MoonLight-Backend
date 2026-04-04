<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Product\Catalog\Enums\ProductStatus;
use Modules\Product\Catalog\Models\Category;
use Modules\Product\Catalog\Models\Product;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductMediaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_upload_requires_authentication(): void
    {
        $this->postJson('/api/v1/admin/product-media/upload')
            ->assertStatus(401);
    }

    public function test_admin_can_upload_product_media_and_receive_uuid(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $response = $this->actingAs($this->admin, 'api')
            ->post('/api/v1/admin/product-media/upload', [
                'file' => $file,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('data.uuid'));
        $this->assertStringContainsString('image/', (string) $response->json('data.mime_type'));
    }

    public function test_can_create_product_with_media_uuids(): void
    {
        $category = Category::factory()->create();
        $file = UploadedFile::fake()->image('a.jpg', 50, 50);
        $upload = $this->actingAs($this->admin, 'api')
            ->post('/api/v1/admin/product-media/upload', ['file' => $file]);
        $uuid = $upload->json('data.uuid');

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/products', [
                'name' => 'With Media',
                'status' => 'active',
                'category_id' => $category->id,
                'media_uuids' => [$uuid],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.media.0.uuid', $uuid);
        $this->assertCount(1, $response->json('data.media'));
    }

    public function test_create_product_rejects_unknown_media_uuid(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/products', [
                'name' => 'Bad Media',
                'status' => 'active',
                'category_id' => $category->id,
                'media_uuids' => ['550e8400-e29b-41d4-a716-446655440000'],
            ]);

        $response->assertStatus(422);
    }

    public function test_public_product_show_includes_gallery_media(): void
    {
        $product = Product::factory()->create([
            'slug' => 'public-media-product',
            'status' => ProductStatus::ACTIVE,
        ]);
        $media = $product->addMedia(UploadedFile::fake()->image('store.jpg', 40, 40))
            ->toMediaCollection('gallery');

        $response = $this->getJson('/api/v1/products/public-media-product');

        $response->assertOk()
            ->assertJsonPath('data.media.0.uuid', $media->uuid)
            ->assertJsonStructure([
                'data' => [
                    'media' => [
                        '*' => ['uuid', 'mime_type', 'type', 'url'],
                    ],
                ],
            ]);
    }

    public function test_update_with_empty_media_uuids_clears_gallery(): void
    {
        $category = Category::factory()->create();
        $file = UploadedFile::fake()->image('b.jpg', 50, 50);
        $upload = $this->actingAs($this->admin, 'api')
            ->post('/api/v1/admin/product-media/upload', ['file' => $file]);
        $uuid = $upload->json('data.uuid');

        $create = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/products', [
                'name' => 'P1',
                'status' => 'active',
                'category_id' => $category->id,
                'media_uuids' => [$uuid],
            ]);
        $id = $create->json('data.id');

        $clear = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/products/{$id}", [
                'media_uuids' => [],
            ]);

        $clear->assertOk();
        $this->assertCount(0, $clear->json('data.media'));
    }

    public function test_update_without_media_uuids_preserves_gallery(): void
    {
        $category = Category::factory()->create();
        $file = UploadedFile::fake()->image('c.jpg', 50, 50);
        $uuid = $this->actingAs($this->admin, 'api')
            ->post('/api/v1/admin/product-media/upload', ['file' => $file])
            ->json('data.uuid');

        $create = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/products', [
                'name' => 'P2',
                'status' => 'active',
                'category_id' => $category->id,
                'media_uuids' => [$uuid],
            ]);
        $id = $create->json('data.id');

        $upd = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/products/{$id}", [
                'name' => 'P2 Renamed',
            ]);

        $upd->assertOk();
        $this->assertCount(1, $upd->json('data.media'));
        $this->assertEquals($uuid, $upd->json('data.media.0.uuid'));
    }
}
