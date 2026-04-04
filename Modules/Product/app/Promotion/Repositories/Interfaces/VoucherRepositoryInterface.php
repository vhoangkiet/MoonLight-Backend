<?php

namespace Modules\Product\Promotion\Repositories\Interfaces;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Product\Promotion\Models\Voucher;

interface VoucherRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get active vouchers.
     */
    public function getActive(): Collection;

    /**
     * Find voucher by code.
     */
    public function findByCode(string $code): ?Voucher;

    /**
     * Get paginated vouchers with filters.
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Check if user has used voucher.
     */
    public function hasUserUsed(int $voucherId, int $userId): bool;

    /**
     * Record voucher usage.
     */
    public function recordUsage(int $voucherId, int $userId, float $orderAmount, float $discountAmount): void;
}
