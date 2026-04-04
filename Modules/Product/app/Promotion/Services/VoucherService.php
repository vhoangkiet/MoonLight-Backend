<?php

namespace Modules\Product\Promotion\Services;

use App\Exceptions\DomainException;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Product\Promotion\Models\Voucher;
use Modules\Product\Promotion\Repositories\Interfaces\VoucherRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property VoucherRepositoryInterface $repository
 */
class VoucherService extends BaseService
{
    public function __construct(VoucherRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Get vouchers with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getVouchers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getFiltered($filters, $perPage);
    }

    /**
     * Get active vouchers.
     */
    public function getActiveVouchers(): Collection
    {
        return $this->repository->getActive();
    }

    /**
     * Create voucher.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws DomainException
     */
    public function createVoucher(array $data): Model
    {
        // Check if code already exists
        if ($this->repository->findByCode($data['code'])) {
            throw new DomainException(
                'Voucher code already exists',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return $this->repository->create($data);
    }

    /**
     * Update voucher.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateVoucher(int $id, array $data): bool
    {
        $voucher = $this->repository->find($id);

        if (! $voucher) {
            return false;
        }

        // Check code uniqueness if changed
        if (isset($data['code']) && $data['code'] !== $voucher->code) {
            if ($this->repository->findByCode($data['code'])) {
                throw new DomainException(
                    'Voucher code already exists',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Delete voucher.
     *
     * @throws DomainException
     */
    public function deleteVoucher(int $id): bool
    {
        $voucher = $this->repository->find($id);

        if (! $voucher) {
            return false;
        }

        // Check if voucher has usage history
        if ($voucher->uses()->count() > 0) {
            throw new DomainException(
                'Cannot delete voucher with usage history',
                Response::HTTP_CONFLICT
            );
        }

        return $this->repository->delete($id);
    }

    /**
     * Validate and apply voucher.
     *
     * @throws DomainException
     */
    public function validateVoucher(string $code, float $orderAmount, int $userId): array
    {
        $voucher = $this->repository->findByCode($code);

        if (! $voucher) {
            throw new DomainException('Invalid voucher code', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $voucher->isValid()) {
            throw new DomainException('Voucher is expired or inactive', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $voucher->canApply($orderAmount)) {
            throw new DomainException(
                "Minimum order amount of {$voucher->min_order_amount} required",
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Check if user already used
        if ($this->repository->hasUserUsed($voucher->id, $userId)) {
            throw new DomainException('You have already used this voucher', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $discountAmount = $voucher->calculateDiscount($orderAmount);

        return [
            'voucher' => $voucher,
            'discount_amount' => $discountAmount,
        ];
    }

    /**
     * Record voucher usage.
     */
    public function recordUsage(int $voucherId, int $userId, float $orderAmount, float $discountAmount): void
    {
        $this->repository->recordUsage($voucherId, $userId, $orderAmount, $discountAmount);
    }

    /**
     * Get voucher by code.
     */
    public function getByCode(string $code): ?Voucher
    {
        return $this->repository->findByCode($code);
    }
}
