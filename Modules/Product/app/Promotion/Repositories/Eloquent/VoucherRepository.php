<?php

namespace Modules\Product\Promotion\Repositories\Eloquent;

use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Product\Promotion\Models\Voucher;
use Modules\Product\Promotion\Repositories\Interfaces\VoucherRepositoryInterface;

class VoucherRepository extends BaseRepository implements VoucherRepositoryInterface
{
    public function __construct(Voucher $model)
    {
        parent::__construct($model);
    }

    public function getActive(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->whereNull('deleted_at')
            ->get();
    }

    public function findByCode(string $code): ?Voucher
    {
        return $this->model
            ->where('code', $code)
            ->whereNull('deleted_at')
            ->first();
    }

    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->whereNull('deleted_at');

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $allowedSortFields = ['created_at', 'code', 'name', 'value', 'valid_from', 'valid_until', 'is_active'];
        $allowedSortOrders = ['asc', 'desc'];

        $sortBy = in_array($filters['sort_by'] ?? 'created_at', $allowedSortFields, true)
            ? $filters['sort_by']
            : 'created_at';
        $sortOrder = in_array($filters['sort_order'] ?? 'desc', $allowedSortOrders, true)
            ? $filters['sort_order']
            : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    public function hasUserUsed(int $voucherId, int $userId): bool
    {
        $voucher = $this->find($voucherId);

        if (! $voucher) {
            return false;
        }

        return $voucher->uses()
            ->where('user_id', $userId)
            ->exists();
    }

    public function recordUsage(int $voucherId, int $userId, float $orderAmount, float $discountAmount): void
    {
        $voucher = $this->find($voucherId);

        if (! $voucher) {
            return;
        }

        DB::transaction(function () use ($voucher, $userId, $orderAmount, $discountAmount): void {
            $voucher->uses()->create([
                'user_id' => $userId,
                'order_amount' => $orderAmount,
                'discount_amount' => $discountAmount,
                'used_at' => now(),
            ]);

            $voucher->increment('usage_count');
        });
    }
}
