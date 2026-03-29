<?php

namespace Modules\Product\Http\Controllers\Api\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\VariantStatus;
use Modules\Product\Http\Requests\Admin\StoreProductVariantRequest;
use Modules\Product\Http\Requests\Admin\UpdateProductVariantRequest;
use Modules\Product\Http\Resources\ProductVariantResource;
use Modules\Product\Services\ProductVariantService;
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
    public function index(int $productId): JsonResponse
    {
        return $this->execute(function () use ($productId): JsonResponse {
            // Validate product exists
            $validated = validator(['product_id' => $productId], [
                'product_id' => ['required', 'integer', 'exists:products,id'],
            ])->validate();

            $variants = $this->variantService->getByProduct($validated['product_id']);

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
        return $this->execute(function () use ($request, $productId): JsonResponse {
            // Validate product exists
            $validated = validator(['product_id' => $productId], [
                'product_id' => ['required', 'integer', 'exists:products,id'],
            ])->validate();

            $variant = $this->variantService->createVariant($validated['product_id'], $request->validated());

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
    public function updateStock(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            $validated = validator(request()->all(), [
                'stock' => ['required', 'integer', 'min:0'],
            ])->validate();

            $updated = $this->variantService->updateStock($id, $validated['stock']);

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
    public function updateStatus(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            try {
                $status = VariantStatus::from(request('status'));
            } catch (\ValueError $e) {
                throw new DomainException(
                    'Invalid status value',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
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
