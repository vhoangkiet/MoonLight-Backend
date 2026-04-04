<?php

namespace Modules\Product\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Modules\Product\Catalog\Enums\VariantStatus;
use Modules\Product\Catalog\Services\ProductVariantService;
use Modules\Product\Http\Requests\Admin\IndexProductVariantsRequest;
use Modules\Product\Http\Requests\Admin\StoreProductVariantRequest;
use Modules\Product\Http\Requests\Admin\UpdateProductVariantRequest;
use Modules\Product\Http\Requests\Admin\UpdateProductVariantStatusRequest;
use Modules\Product\Http\Requests\Admin\UpdateProductVariantStockRequest;
use Modules\Product\Http\Resources\ProductVariantResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Admin - Product Variant Management
 */
class ProductVariantController extends BaseController
{
    public function __construct(protected ProductVariantService $variantService) {}

    /**
     * List variants for product.
     */
    public function index(IndexProductVariantsRequest $request, int $productId): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $variants = $this->variantService->getByProduct((int) $request->validated('product_id'));

            return $this->successResponse(
                ProductVariantResource::collection($variants),
                'Product variants retrieved successfully'
            );
        });
    }

    /**
     * Store new variant.
     */
    public function store(StoreProductVariantRequest $request, int $productId): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $variant = $this->variantService->createVariant(
                (int) $request->validated('product_id'),
                Arr::except($request->validated(), ['product_id'])
            );

            return $this->successResponse(
                new ProductVariantResource($variant),
                'Product variant created successfully',
                Response::HTTP_CREATED
            );
        });
    }

    /**
     * Show variant.
     */
    public function show(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            $variant = $this->variantService->find($id);

            if (! $variant) {
                return $this->errorResponse('Variant not found', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new ProductVariantResource($variant),
                'Product variant retrieved successfully'
            );
        });
    }

    /**
     * Update variant.
     */
    public function update(UpdateProductVariantRequest $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $updated = $this->variantService->updateVariant($id, $request->validated());

            if (! $updated) {
                return $this->errorResponse('Variant not found', Response::HTTP_NOT_FOUND);
            }

            $variant = $this->variantService->find($id);

            return $this->successResponse(
                new ProductVariantResource($variant),
                'Product variant updated successfully'
            );
        });
    }

    /**
     * Delete variant.
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            $deleted = $this->variantService->deleteVariant($id);

            if (! $deleted) {
                return $this->errorResponse('Variant not found', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(null, 'Product variant deleted successfully');
        });
    }

    /**
     * Update variant stock.
     */
    public function updateStock(UpdateProductVariantStockRequest $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $updated = $this->variantService->updateStock($id, (int) $request->validated('stock'));

            if (! $updated) {
                return $this->errorResponse('Variant not found', Response::HTTP_NOT_FOUND);
            }

            $variant = $this->variantService->find($id);

            return $this->successResponse(
                new ProductVariantResource($variant),
                'Stock updated successfully'
            );
        });
    }

    /**
     * Update variant status.
     */
    public function updateStatus(UpdateProductVariantStatusRequest $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $status = VariantStatus::from($request->validated('status'));
            $updated = $this->variantService->updateStatus($id, $status);

            if (! $updated) {
                return $this->errorResponse('Variant not found', Response::HTTP_NOT_FOUND);
            }

            $variant = $this->variantService->find($id);

            return $this->successResponse(
                new ProductVariantResource($variant),
                'Variant status updated successfully'
            );
        });
    }
}
