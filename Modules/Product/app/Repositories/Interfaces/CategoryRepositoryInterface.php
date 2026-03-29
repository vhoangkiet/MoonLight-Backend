<?php

namespace Modules\Product\Repositories\Interfaces;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Product\Models\Category;

interface CategoryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get category tree structure.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getTree(array $filters = []): Collection;

    /**
     * Get categories by parent ID.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getByParent(?int $parentId, array $filters = []): Collection;

    /**
     * Find category with all descendants.
     */
    public function findWithDescendants(int $id): ?Category;

    /**
     * Update products count for a category and its ancestors.
     */
    public function updateProductsCount(int $id): void;

    /**
     * Update category position/order.
     *
     * @param  array<int, int>  $orderedIds  Key: position, Value: category ID
     */
    public function reorder(array $orderedIds): bool;

    /**
     * Find category by slug.
     *
     * @param  string|null  $status  Optional status filter
     */
    public function getBySlug(string $slug, ?string $status = null): ?Category;

    /**
     * Check if slug exists (excluding optional ID).
     */
    public function existsBySlug(string $slug, ?int $excludeId = null): bool;

    /**
     * Get paginated categories with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredCategories(array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
