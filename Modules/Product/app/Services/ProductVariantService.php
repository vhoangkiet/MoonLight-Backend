<?php

namespace Modules\Product\Services;

use App\Exceptions\DomainException;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Modules\Product\Enums\VariantStatus;
use Modules\Product\Models\ProductVariant;
use Modules\Product\Repositories\Interfaces\ProductVariantRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property ProductVariantRepositoryInterface $repository
 */
class ProductVariantService extends BaseService
{
    public function __construct(ProductVariantRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Get variants by product.
     */
    public function getByProduct(int $productId): Collection
    {
        return $this->repository->getByProduct($productId);
    }

    /**
     * Create variant for product.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws DomainException
     */
    public function createVariant(int $productId, array $data): Model
    {
        return DB::transaction(function () use ($productId, $data): Model {
            $data['product_id'] = $productId;
            $data['sku'] = $this->generateUniqueSku($data['sku'] ?? null, $data);

            // Check for duplicate combination
            if ($this->existsByCombination($productId, $data)) {
                throw new DomainException(
                    'A variant with this combination already exists',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            try {
                return $this->repository->create($data);
            } catch (UniqueConstraintViolationException $e) {
                throw new DomainException(
                    'SKU already exists',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        });
    }

    /**
     * Update variant.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateVariant(int $id, array $data): bool
    {
        $variant = $this->repository->find($id);

        if (! $variant) {
            return false;
        }

        // Regenerate SKU if not provided
        if (empty($data['sku'])) {
            unset($data['sku']);
        } elseif ($data['sku'] !== $variant->sku) {
            if ($this->repository->existsBySku($data['sku'], $id)) {
                throw new DomainException(
                    'SKU already exists',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Delete variant.
     */
    public function deleteVariant(int $id): bool
    {
        $variant = $this->repository->find($id);

        if (! $variant) {
            return false;
        }

        return $this->repository->delete($id);
    }

    /**
     * Update variant stock.
     *
     * @throws DomainException
     */
    public function updateStock(int $id, int $quantity): bool
    {
        if ($quantity < 0) {
            throw new DomainException(
                'Stock quantity cannot be negative',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return $this->repository->updateStock($id, $quantity);
    }

    /**
     * Update variant status.
     */
    public function updateStatus(int $id, VariantStatus $status): bool
    {
        return $this->repository->update($id, ['status' => $status]);
    }

    /**
     * Find variant by SKU.
     */
    public function findBySku(string $sku): ?ProductVariant
    {
        return $this->repository->findBySku($sku);
    }

    /**
     * Check if variant combination exists.
     *
     * @param  array<string, mixed>  $data
     */
    private function existsByCombination(int $productId, array $data): bool
    {
        $attributes = [
            'shape' => $data['shape'] ?? null,
            'length' => $data['length'] ?? null,
            'tonal_palette' => $data['tonal_palette'] ?? null,
            'size' => $data['size'] ?? null,
        ];

        return $this->repository->existsByCombination($productId, $attributes);
    }

    /**
     * Generate unique SKU.
     *
     * @param  array<string, mixed>  $data
     */
    private function generateUniqueSku(?string $sku, array $data): string
    {
        if ($sku) {
            return strtoupper($sku);
        }

        // Auto-generate SKU from attributes
        $parts = [
            substr($data['shape'] ?? 'NA', 0, 3),
            substr($data['length'] ?? 'NA', 0, 3),
            substr($data['tonal_palette'] ?? 'NA', 0, 3),
            $data['size'] ?? 'NA',
        ];

        $baseSku = strtoupper(implode('-', $parts));
        $sku = $baseSku;
        $counter = 1;
        $maxAttempts = 1000;

        while ($this->repository->existsBySku($sku) && $counter < $maxAttempts) {
            $sku = $baseSku.'-'.$counter;
            $counter++;
        }

        if ($counter >= $maxAttempts) {
            throw new DomainException(
                'Unable to generate unique SKU after '.$maxAttempts.' attempts',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $sku;
    }
}
