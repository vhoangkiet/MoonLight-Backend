<?php

namespace Modules\Product\Services;

use App\Exceptions\DomainException;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Models\Product;
use Modules\Product\Repositories\Interfaces\ProductRepositoryInterface;
use Modules\Product\Repositories\Interfaces\ProductVariantRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property ProductRepositoryInterface $repository
 */
class ProductService extends BaseService
{
    public function __construct(
        ProductRepositoryInterface $repository,
        private ProductVariantRepositoryInterface $variantRepository,
        private PricingService $pricingService
    ) {
        parent::__construct($repository);
    }

    /**
     * Get products with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getFiltered($filters, $perPage);
    }

    /**
     * Get product by slug.
     */
    public function getBySlug(string $slug, ?string $status = null): ?Product
    {
        $product = $this->repository->findBySlug($slug, $status);

        if ($product) {
            $product->load(['category', 'variants', 'discounts']);
        }

        return $product;
    }

    /**
     * Create product with slug generation.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws DomainException
     */
    public function createProduct(array $data): Model
    {
        $data['slug'] = $this->generateUniqueSlug($data['name']);

        return $this->repository->create($data);
    }

    /**
     * Update product.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProduct(int $id, array $data): bool
    {
        $product = $this->repository->find($id);

        if (! $product) {
            return false;
        }

        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $id);
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Delete product.
     */
    public function deleteProduct(int $id): bool
    {
        $product = $this->repository->find($id);

        if (! $product) {
            return false;
        }

        // Check if product has variants
        if ($product->allVariants()->count() > 0) {
            throw new DomainException(
                'Cannot delete product with variants. Delete all variants first.',
                Response::HTTP_CONFLICT
            );
        }

        return $this->repository->delete($id);
    }

    /**
     * Update product status.
     */
    public function updateStatus(int $id, ProductStatus $status): bool
    {
        return $this->repository->update($id, ['status' => $status]);
    }

    /**
     * Calculate product price with discounts and vouchers.
     *
     * @param  array<string, mixed>  $options
     */
    public function calculatePrice(int $productId, int $variantId, array $options = []): array
    {
        return $this->pricingService->calculate($productId, $variantId, $options);
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
