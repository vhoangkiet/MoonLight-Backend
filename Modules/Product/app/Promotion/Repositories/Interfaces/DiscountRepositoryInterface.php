<?php

namespace Modules\Product\Promotion\Repositories\Interfaces;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Product\Promotion\Models\Discount;

interface DiscountRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get active discounts.
     */
    public function getActive(): Collection;

    /**
     * Find discount by code.
     */
    public function findByCode(string $code): ?Discount;

    /**
     * Get discounts by product.
     */
    public function getByProduct(int $productId): Collection;

    /**
     * Apply discount to product.
     */
    public function applyToProduct(int $discountId, int $productId, ?int $variantId = null): bool;

    /**
     * Remove discount from product.
     */
    public function removeFromProduct(int $discountId, int $productId, ?int $variantId = null): bool;
}
