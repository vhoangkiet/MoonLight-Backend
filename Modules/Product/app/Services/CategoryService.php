<?php

namespace Modules\Product\Services;

use App\Exceptions\DomainException;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\Product\Enums\CategoryStatus;
use Modules\Product\Models\Category;
use Modules\Product\Repositories\Interfaces\CategoryRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property CategoryRepositoryInterface $repository
 */
class CategoryService extends BaseService
{
    public function __construct(CategoryRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Get category tree.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getCategoryTree(array $filters = []): Collection
    {
        return $this->repository->getTree($filters);
    }

    /**
     * Get categories by parent.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getByParent(?int $parentId, array $filters = []): Collection
    {
        return $this->repository->getByParent($parentId, $filters);
    }

    /**
     * Create a new category with slug generation.
     *
     * @param  array<string, mixed>  $data
     * @throws \App\Exceptions\DomainException
     */
    public function createCategory(array $data): Model
    {
        $data['slug'] = $this->generateUniqueSlug($data['name']);

        return $this->repository->create($data);
    }

    /**
     * Update category with circular parent check.
     *
     * @param  array<string, mixed>  $data
     * @throws \App\Exceptions\DomainException
     */
    public function updateCategory(int $id, array $data): bool
    {
        $category = $this->repository->find($id);

        if (! $category) {
            return false;
        }

        // Check for circular parent reference (only self-reference here, descendant check is in FormRequest)
        if (isset($data['parent_id']) && (int) $data['parent_id'] === $id) {
            throw new DomainException('Category cannot be its own parent', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Generate new slug if name changed
        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $id);
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Delete category if no children and no products.
     *
     * @throws \App\Exceptions\DomainException
     */
    public function deleteCategory(int $id): bool
    {
        $category = $this->repository->find($id);

        if (! $category) {
            return false;
        }

        if ($category->hasChildren()) {
            throw new DomainException('Cannot delete category with sub-categories', Response::HTTP_CONFLICT);
        }

        if ($category->hasProducts()) {
            throw new DomainException('Cannot delete category with products', Response::HTTP_CONFLICT);
        }

        return $this->repository->delete($id);
    }

    /**
     * Reorder categories.
     *
     * @param  array<int, int>  $orders  Key: position, Value: category ID
     * @throws \App\Exceptions\DomainException
     */
    public function reorderCategories(array $orders): bool
    {
        return $this->repository->reorder($orders);
    }

    /**
     * Update category status.
     */
    public function updateStatus(int $id, CategoryStatus $status): bool
    {
        return $this->repository->update($id, ['status' => $status]);
    }

    /**
     * Sync products count for category and ancestors.
     */
    public function syncProductsCount(int $categoryId): void
    {
        $this->repository->updateProductsCount($categoryId);
    }

    /**
     * Get paginated categories with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredCategories(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getFilteredCategories($filters, $perPage);
    }

    /**
     * Find category with relations.
     */
    public function findWithRelations(int $id, array $relations = []): ?Category
    {
        return $this->repository->find($id, ['*'], $relations);
    }

    /**
     * Find category by slug.
     *
     * @param  string|null  $status  Optional status filter
     */
    public function findBySlug(string $slug, ?string $status = null): ?Category
    {
        return $this->repository->getBySlug($slug, $status);
    }

    /**
     * Generate unique slug from name.
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->repository->existsBySlug($slug, $excludeId)) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
