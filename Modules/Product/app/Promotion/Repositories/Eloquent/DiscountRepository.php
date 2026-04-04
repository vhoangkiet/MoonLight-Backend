<?php

namespace Modules\Product\Promotion\Repositories\Eloquent;

use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Product\Promotion\Models\Discount;
use Modules\Product\Promotion\Repositories\Interfaces\DiscountRepositoryInterface;

class DiscountRepository extends BaseRepository implements DiscountRepositoryInterface
{
    public function __construct(Discount $model)
    {
        parent::__construct($model);
    }

    public function getActive(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->whereNull('deleted_at')
            ->get();
    }

    public function findByCode(string $code): ?Discount
    {
        return $this->model
            ->where('code', $code)
            ->whereNull('deleted_at')
            ->first();
    }

    public function getByProduct(int $productId): Collection
    {
        return $this->model
            ->whereHas('products', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get();
    }

    public function applyToProduct(int $discountId, int $productId, ?int $variantId = null): bool
    {
        $discount = $this->find($discountId);

        if (! $discount) {
            return false;
        }

        // Check if relationship already exists
        $exists = $discount->products()
            ->wherePivot('product_id', $productId)
            ->wherePivot('product_variant_id', $variantId)
            ->exists();

        if ($exists) {
            return true; // Already applied, nothing to do
        }

        $discount->products()->attach($productId, ['product_variant_id' => $variantId]);

        return true;
    }

    public function removeFromProduct(int $discountId, int $productId, ?int $variantId = null): bool
    {
        $discount = $this->find($discountId);

        if (! $discount) {
            return false;
        }

        $query = $discount->products()
            ->wherePivot('product_id', $productId);

        if ($variantId) {
            $query->wherePivot('product_variant_id', $variantId);
        }

        $query->detach();

        return true;
    }
}
