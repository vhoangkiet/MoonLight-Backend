<?php

namespace Modules\Product\Catalog\Repositories\Interfaces;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Product\Catalog\Models\ProductVariant;

interface ProductVariantRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get variants by product ID.
     */
    public function getByProduct(int $productId): Collection;

    /**
     * Get variant by SKU.
     */
    public function findBySku(string $sku): ?ProductVariant;

    /**
     * Check if SKU exists.
     */
    public function existsBySku(string $sku, ?int $excludeId = null): bool;

    /**
     * Get variants in stock.
     */
    public function getInStock(): Collection;

    /**
     * Get variants by attributes.
     *
     * @param  array<string, string>  $attributes
     */
    public function getByAttributes(array $attributes): Collection;

    /**
     * Update stock quantity.
     */
    public function updateStock(int $id, int $quantity): bool;

    /**
     * Check if variant with given combination exists for product.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function existsByCombination(int $productId, array $attributes, ?int $excludeId = null): bool;
}
