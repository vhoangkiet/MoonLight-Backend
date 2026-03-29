<?php

namespace Modules\Product\Repositories\Eloquent;

use App\Exceptions\DomainException;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Category;
use Modules\Product\Repositories\Interfaces\CategoryRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function getTree(array $filters = []): Collection
    {
        // Load all categories with their relationships for proper tree building
        $query = $this->model->with(['parent'])->whereNull('deleted_at');

        if (isset($filters['status'])) {
            $status = $filters['status'];
            $query->where('status', $status instanceof \BackedEnum ? $status->value : $status);
        }

        $allCategories = $query->ordered()->get();

        // Build tree structure by attaching children relationship manually
        $categoriesByParent = $allCategories->groupBy('parent_id');
        $rootCategories = $allCategories->whereNull('parent_id');

        // Set children on each category for eager loading simulation
        foreach ($allCategories as $category) {
            $children = $categoriesByParent->get($category->id, collect());
            $category->setRelation('children', $children);
        }

        return $rootCategories->values();
    }

    public function getByParent(?int $parentId, array $filters = []): Collection
    {
        $query = $this->model->where('parent_id', $parentId)->whereNull('deleted_at');

        if (isset($filters['status'])) {
            $status = $filters['status'];
            $query->where('status', $status instanceof \BackedEnum ? $status->value : $status);
        }

        return $query->ordered()->get();
    }

    public function findWithDescendants(int $id): ?Category
    {
        return $this->model->with(['children.children', 'parent'])
            ->whereNull('deleted_at')
            ->find($id);
    }

    public function updateProductsCount(int $id): void
    {
        $category = $this->find($id);

        if (! $category) {
            return;
        }

        $category->updateProductsCount();

        // Update ancestors' counts - batch load to avoid N+1
        $ancestorIds = $category->getAllAncestorIds();
        if (! empty($ancestorIds)) {
            $ancestors = $this->model->whereIn('id', $ancestorIds)->whereNull('deleted_at')->get();
            foreach ($ancestors as $ancestor) {
                $ancestor->updateProductsCount();
            }
        }
    }

    public function reorder(array $orderedIds): bool
    {
        return DB::transaction(function () use ($orderedIds) {
            // Validate all IDs exist before updating
            $ids = array_values($orderedIds);
            $existingCount = $this->model->whereIn('id', $ids)->whereNull('deleted_at')->count();

            if ($existingCount !== count($ids)) {
                throw new DomainException('One or more category IDs do not exist', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Build CASE statement for batch update
            $cases = [];
            foreach ($orderedIds as $position => $id) {
                $cases[] = "WHEN id = {$id} THEN {$position}";
            }

            $tableName = $this->model->getTable();
            $caseStatement = implode(' ', $cases);
            $idList = implode(',', $ids);

            DB::update("UPDATE {$tableName} SET position = CASE {$caseStatement} ELSE position END WHERE id IN ({$idList})");

            return true;
        });
    }

    public function existsBySlug(string $slug, ?int $excludeId = null): bool
    {
        $query = $this->model->where('slug', $slug)->whereNull('deleted_at');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function getBySlug(string $slug, ?string $status = null): ?Category
    {
        $query = $this->model->where('slug', $slug)->whereNull('deleted_at');

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->first();
    }

    public function getFilteredCategories(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->whereNull('deleted_at');

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('slug', 'like', "%{$filters['search']}%");
            });
        }

        if (! empty($filters['status'])) {
            $status = $filters['status'];
            $query->where('status', $status instanceof \BackedEnum ? $status->value : $status);
        }

        if (array_key_exists('parent_id', $filters)) {
            $query->where('parent_id', $filters['parent_id']);
        }

        $allowedSortFields = ['position', 'name', 'created_at', 'id'];
        $allowedSortOrders = ['asc', 'desc'];

        $sortBy = in_array($filters['sort_by'] ?? 'position', $allowedSortFields, true)
            ? $filters['sort_by']
            : 'position';
        $sortOrder = in_array($filters['sort_order'] ?? 'asc', $allowedSortOrders, true)
            ? $filters['sort_order']
            : 'asc';

        $query->orderBy($sortBy, $sortOrder)->orderBy('name');

        return $query->with('parent', 'children')->paginate($perPage);
    }
}
