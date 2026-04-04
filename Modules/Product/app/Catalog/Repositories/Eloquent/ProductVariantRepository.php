<?php

namespace Modules\Product\Catalog\Repositories\Eloquent;

use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Product\Catalog\Enums\VariantStatus;
use Modules\Product\Catalog\Models\ProductVariant;
use Modules\Product\Catalog\Repositories\Interfaces\ProductVariantRepositoryInterface;

class ProductVariantRepository extends BaseRepository implements ProductVariantRepositoryInterface
{
    public function __construct(ProductVariant $model)
    {
        parent::__construct($model);
    }

    public function getByProduct(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->get();
    }

    public function findBySku(string $sku): ?ProductVariant
    {
        return $this->model->where('sku', $sku)
            ->whereNull('deleted_at')
            ->first();
    }

    public function existsBySku(string $sku, ?int $excludeId = null): bool
    {
        $query = $this->model->where('sku', $sku)->whereNull('deleted_at');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function getInStock(): Collection
    {
        return $this->model->where('stock', '>', 0)
            ->where('status', VariantStatus::ACTIVE->value)
            ->whereNull('deleted_at')
            ->with('product')
            ->get();
    }

    public function getByAttributes(array $attributes): Collection
    {
        $query = $this->model->whereNull('deleted_at');

        foreach ($attributes as $key => $value) {
            if (in_array($key, ['shape', 'length', 'tonal_palette', 'size'])) {
                $query->where($key, $value);
            }
        }

        return $query->get();
    }

    public function updateStock(int $id, int $quantity): bool
    {
        $variant = $this->find($id);

        if (! $variant) {
            return false;
        }

        $variant->stock = $quantity;

        return $variant->save();
    }

    public function existsByCombination(int $productId, array $attributes, ?int $excludeId = null): bool
    {
        $query = $this->model->where('product_id', $productId)
            ->whereNull('deleted_at');

        foreach (['shape', 'length', 'tonal_palette', 'size'] as $field) {
            if (isset($attributes[$field])) {
                $query->where($field, $attributes[$field]);
            }
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
