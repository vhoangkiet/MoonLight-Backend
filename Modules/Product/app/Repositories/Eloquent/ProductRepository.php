<?php

namespace Modules\Product\Repositories\Eloquent;

use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Models\Product;
use Modules\Product\Repositories\Interfaces\ProductRepositoryInterface;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function getWithRelations(array $filters = [], array $relations = []): Collection
    {
        $query = $this->model->with($relations)->whereNull('deleted_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->get();
    }

    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->whereNull('deleted_at');

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('slug', 'like', "%{$filters['search']}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        $allowedSortFields = ['name', 'created_at', 'id'];
        $sortBy = in_array($filters['sort_by'] ?? 'created_at', $allowedSortFields, true)
            ? $filters['sort_by']
            : 'created_at';
        $sortOrder = in_array($filters['sort_order'] ?? 'desc', ['asc', 'desc'], true)
            ? $filters['sort_order']
            : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        return $query->with('category', 'variants')->paginate($perPage);
    }

    public function findBySlug(string $slug, ?string $status = null): ?Product
    {
        $query = $this->model->where('slug', $slug)->whereNull('deleted_at');

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->first();
    }

    public function existsBySlug(string $slug, ?int $excludeId = null): bool
    {
        $query = $this->model->where('slug', $slug)->whereNull('deleted_at');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function getByCategory(int $categoryId): Collection
    {
        return $this->model->where('category_id', $categoryId)
            ->where('status', ProductStatus::ACTIVE->value)
            ->with('variants')
            ->get();
    }

    public function getWithActiveDiscounts(): Collection
    {
        return $this->model->where('status', ProductStatus::ACTIVE->value)
            ->whereHas('discounts', function ($query) {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('start_date')
                            ->orWhere('start_date', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    });
            })
            ->with(['variants', 'discounts'])
            ->get();
    }
}
