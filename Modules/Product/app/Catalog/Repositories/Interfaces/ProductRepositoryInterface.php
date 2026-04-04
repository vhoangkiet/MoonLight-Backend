<?php

namespace Modules\Product\Catalog\Repositories\Interfaces;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Product\Catalog\Models\Product;

interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get products with category and variants.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getWithRelations(array $filters = [], array $relations = []): Collection;

    /**
     * Get paginated products with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find product by slug.
     */
    public function findBySlug(string $slug, ?string $status = null): ?Product;

    /**
     * Check if slug exists.
     */
    public function existsBySlug(string $slug, ?int $excludeId = null): bool;

    /**
     * Get active products by category.
     */
    public function getByCategory(int $categoryId): Collection;

    /**
     * Get products with active discounts.
     */
    public function getWithActiveDiscounts(): Collection;
}
