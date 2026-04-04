<?php

namespace Modules\Product\Catalog\Services;

use App\Exceptions\DomainException;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\Product\Catalog\Enums\ProductStatus;
use Modules\Product\Catalog\Models\Product;
use Modules\Product\Catalog\Repositories\Interfaces\ProductRepositoryInterface;
use Modules\Product\Catalog\Repositories\Interfaces\ProductVariantRepositoryInterface;
use Modules\Product\Media\Services\ProductMediaService;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property ProductRepositoryInterface $repository
 */
class ProductService extends BaseService
{
    public function __construct(
        ProductRepositoryInterface $repository,
        private ProductVariantRepositoryInterface $variantRepository,
        private PricingService $pricingService,
        private ProductMediaService $productMediaService
    ) {
        parent::__construct($repository);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getFiltered($filters, $perPage);
    }

    public function getBySlug(string $slug, ?string $status = null): ?Product
    {
        $product = $this->repository->findBySlug($slug, $status);

        if ($product) {
            $product->load(['category', 'variants', 'discounts', 'media']);
        }

        return $product;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws DomainException
     */
    public function createProduct(array $data, int $userId): Model
    {
        $mediaUuids = $data['media_uuids'] ?? null;
        unset($data['media_uuids']);

        $data['slug'] = $this->generateUniqueSlug($data['name']);

        $product = $this->repository->create($data);

        if (is_array($mediaUuids) && $mediaUuids !== []) {
            $this->productMediaService->syncGallery($product, $mediaUuids, $userId);
        }

        return $product->fresh(['category', 'variants', 'discounts', 'media']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProduct(int $id, array $data, int $userId): bool
    {
        $product = $this->repository->find($id);

        if (! $product instanceof Product) {
            return false;
        }

        $shouldSyncMedia = array_key_exists('media_uuids', $data);
        $mediaUuids = $data['media_uuids'] ?? [];
        if ($shouldSyncMedia) {
            unset($data['media_uuids']);
        }

        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $id);
        }

        $updated = $this->repository->update($id, $data);

        if ($updated && $shouldSyncMedia) {
            $product = $this->repository->find($id);
            if ($product instanceof Product) {
                $this->productMediaService->syncGallery($product, is_array($mediaUuids) ? $mediaUuids : [], $userId);
            }
        }

        return $updated;
    }

    public function deleteProduct(int $id): bool
    {
        $product = $this->repository->find($id);

        if (! $product) {
            return false;
        }

        if ($product->allVariants()->count() > 0) {
            throw new DomainException(
                'Cannot delete product with variants. Delete all variants first.',
                Response::HTTP_CONFLICT
            );
        }

        return $this->repository->delete($id);
    }

    public function updateStatus(int $id, ProductStatus $status): bool
    {
        return $this->repository->update($id, ['status' => $status]);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function calculatePrice(int $productId, int $variantId, array $options = []): array
    {
        return $this->pricingService->calculate($productId, $variantId, $options);
    }

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
