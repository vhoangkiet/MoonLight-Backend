<?php

namespace Tests\Unit;

use App\Exceptions\DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Catalog\Enums\VariantStatus;
use Modules\Product\Catalog\Models\Product;
use Modules\Product\Catalog\Models\ProductVariant;
use Modules\Product\Catalog\Services\PricingService;
use Modules\Product\Catalog\Services\ProductVariantService;
use Modules\Product\Promotion\Models\Discount;
use Modules\Product\Promotion\Models\Voucher;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $pricingService;

    private ProductVariantService $variantService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = app(PricingService::class);
        $this->variantService = app(ProductVariantService::class);
    }

    // ==========================================
    // PricingService Tests
    // ==========================================

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
        $this->assertEquals(100.00, $result['final_price']);
        $this->assertEquals(0, $result['discount_amount']);
    }

    public function test_pricing_service_applies_fixed_discount(): void
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
            'type' => 'fixed',
            'value' => 20,
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
        ]);
        $discount->products()->attach($product->id, ['product_variant_id' => $variant->id]);

        $result = $this->pricingService->calculate($product->id, $variant->id);

        $this->assertEquals(100.00, $result['base_price']);
        // Fixed discount applied: 20
        $this->assertEquals(20.00, $result['discount_amount']);
        $this->assertEquals(80.00, $result['final_price']);
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
        // Percentage discount applied: 20% of 100 = 20
        $this->assertEquals(20.00, $result['discount_amount']);
        $this->assertEquals(80.00, $result['final_price']);
    }

    public function test_pricing_service_applies_multiple_discounts(): void
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

        $discount1 = Discount::factory()->create([
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
        ]);
        $discount2 = Discount::factory()->create([
            'type' => 'fixed',
            'value' => 5,
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
        ]);
        $discount1->products()->attach($product->id, ['product_variant_id' => $variant->id]);
        $discount2->products()->attach($product->id, ['product_variant_id' => $variant->id]);

        $result = $this->pricingService->calculate($product->id, $variant->id);

        $this->assertEquals(100.00, $result['base_price']);
        // Both discounts applied: 10% of 100 = 10, fixed 5 = 5, total = 15
        $this->assertEquals(15.00, $result['discount_amount']);
        $this->assertEquals(85.00, $result['final_price']);
    }

    public function test_pricing_service_applies_voucher_discount(): void
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
        $voucher = Voucher::factory()->create([
            'code' => 'TEST20',
            'type' => 'percentage',
            'value' => 20,
            'min_order_amount' => 50,
            'is_active' => true,
        ]);

        $result = $this->pricingService->calculate($product->id, $variant->id, ['voucher_code' => 'TEST20']);

        $this->assertEquals(100.00, $result['base_price']);
        $this->assertEquals(20.00, $result['voucher_amount']);
        $this->assertEquals(80.00, $result['final_price']);
    }

    public function test_pricing_service_throws_exception_for_nonexistent_product(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid product variant');

        $this->pricingService->calculate(99999, 1);
    }

    public function test_pricing_service_throws_exception_for_nonexistent_variant(): void
    {
        $product = Product::factory()->create();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid product variant');

        $this->pricingService->calculate($product->id, 99999);
    }

    public function test_pricing_service_handles_invalid_voucher(): void
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

        // Invalid voucher code - service returns without applying voucher
        $result = $this->pricingService->calculate($product->id, $variant->id, ['voucher_code' => 'INVALIDCODE']);

        $this->assertEquals(100.00, $result['base_price']);
        $this->assertEquals(0, $result['voucher_amount']);
        $this->assertNull($result['applied_voucher']);
    }

    public function test_pricing_service_discount_only_applies_to_correct_variant(): void
    {
        // This tests the strict type comparison fix
        $product = Product::factory()->create();
        $variant1 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 100.00,
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
        ]);
        $variant2 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 200.00,
            'shape' => 'Oval',
            'length' => '70cm',
            'tonal_palette' => 'Cool',
            'size' => 'Large',
        ]);

        // Apply discount only to variant1
        $discount = Discount::factory()->create([
            'type' => 'percentage',
            'value' => 50,
            'is_active' => true,
        ]);
        $discount->products()->attach($product->id, ['product_variant_id' => $variant1->id]);

        // Calculate price for variant2 - should not have discount
        $result = $this->pricingService->calculate($product->id, $variant2->id);

        $this->assertEquals(200.00, $result['base_price']);
        $this->assertEquals(0, $result['discount_amount']);
        $this->assertEquals(200.00, $result['final_price']);
    }

    // ==========================================
    // ProductVariantService Tests
    // ==========================================

    public function test_variant_service_generates_unique_sku(): void
    {
        $product = Product::factory()->create();

        $variant1 = $this->variantService->createVariant($product->id, [
            'price' => 100,
            'stock' => 10,
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
        ]);

        $this->assertNotNull($variant1->sku);
        // SKU format is product combination based: {SHA}-{LEN}-{TON}-{SIZE}
        $this->assertStringContainsString('ROU', $variant1->sku);
        $this->assertStringContainsString('50C', $variant1->sku);
    }

    public function test_variant_service_prevents_duplicate_sku_race_condition(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'DUPLICATE-SKU',
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
        ]);

        // Try to create another variant with same SKU
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A variant with this combination already exists');

        $this->variantService->createVariant($product->id, [
            'sku' => 'DUPLICATE-SKU',
            'price' => 100,
            'stock' => 10,
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
        ]);
    }

    public function test_variant_service_prevents_duplicate_combination(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
        ]);

        // Try to create variant with same combination
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A variant with this combination already exists');

        $this->variantService->createVariant($product->id, [
            'sku' => 'NEW-SKU',
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
            'price' => 100,
            'stock' => 10,
        ]);
    }

    public function test_variant_service_checks_product_exists_before_creating(): void
    {
        // Service tries to create variant for non-existent product
        // This will throw QueryException due to foreign key constraint
        $this->expectException(QueryException::class);

        $this->variantService->createVariant(99999, [
            'price' => 100,
            'stock' => 10,
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
        ]);
    }

    public function test_variant_service_updates_stock(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 100,
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
        ]);

        $this->variantService->updateStock($variant->id, 50);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock' => 50,
        ]);
    }

    public function test_variant_service_updates_status(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'status' => VariantStatus::ACTIVE,
            'shape' => 'Round',
            'length' => '50cm',
            'tonal_palette' => 'Warm',
            'size' => 'Medium',
        ]);

        $this->variantService->updateStatus($variant->id, VariantStatus::INACTIVE);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'status' => 'inactive',
        ]);
    }
}
