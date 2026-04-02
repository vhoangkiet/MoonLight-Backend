<?php

namespace Modules\Product\Services;

use App\Exceptions\DomainException;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\Discount;
use Modules\Product\Repositories\Interfaces\DiscountRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property DiscountRepositoryInterface $repository
 */
class DiscountService extends BaseService
{
    public function __construct(DiscountRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Get all discounts.
     */
    public function getDiscounts(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Get active discounts.
     */
    public function getActiveDiscounts(): Collection
    {
        return $this->repository->getActive();
    }

    /**
     * Create discount.
     *
     * @param  array<string, mixed>  $data
     */
    public function createDiscount(array $data): Model
    {
        return $this->repository->create($data);
    }

    /**
     * Update discount.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateDiscount(int $id, array $data): bool
    {
        $discount = $this->repository->find($id);

        if (! $discount) {
            return false;
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Delete discount.
     */
    public function deleteDiscount(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Apply discount to product.
     */
    public function applyToProduct(int $discountId, int $productId, ?int $variantId = null): bool
    {
        $discount = $this->repository->find($discountId);

        if (! $discount) {
            throw new DomainException('Discount not found', Response::HTTP_NOT_FOUND);
        }

        return $this->repository->applyToProduct($discountId, $productId, $variantId);
    }

    /**
     * Remove discount from product.
     */
    public function removeFromProduct(int $discountId, int $productId, ?int $variantId = null): bool
    {
        $discount = $this->repository->find($discountId);

        if (! $discount) {
            throw new DomainException('Discount not found', Response::HTTP_NOT_FOUND);
        }

        return $this->repository->removeFromProduct($discountId, $productId, $variantId);
    }

    /**
     * Get discount by code.
     */
    public function getByCode(string $code): ?Discount
    {
        return $this->repository->findByCode($code);
    }
}
