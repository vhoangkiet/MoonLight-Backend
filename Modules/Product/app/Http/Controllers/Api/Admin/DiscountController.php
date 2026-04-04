<?php

namespace Modules\Product\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Product\Http\Requests\Admin\ManageDiscountProductRequest;
use Modules\Product\Http\Requests\Admin\StoreDiscountRequest;
use Modules\Product\Http\Requests\Admin\UpdateDiscountRequest;
use Modules\Product\Http\Resources\DiscountResource;
use Modules\Product\Promotion\Services\DiscountService;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Admin - Discount Management
 */
class DiscountController extends BaseController
{
    public function __construct(protected DiscountService $discountService) {}

    /**
     * List all discounts.
     */
    public function index(): JsonResponse
    {
        return $this->execute(function (): JsonResponse {
            $discounts = $this->discountService->getDiscounts();

            return $this->successResponse(
                DiscountResource::collection($discounts),
                'Discounts retrieved successfully'
            );
        });
    }

    /**
     * Store new discount.
     */
    public function store(StoreDiscountRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $discount = $this->discountService->createDiscount($request->validated());

            return $this->successResponse(
                new DiscountResource($discount),
                'Discount created successfully',
                Response::HTTP_CREATED
            );
        });
    }

    /**
     * Show discount.
     */
    public function show(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            $discount = $this->discountService->find($id);

            if (! $discount) {
                return $this->errorResponse('Discount not found', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new DiscountResource($discount),
                'Discount retrieved successfully'
            );
        });
    }

    /**
     * Update discount.
     */
    public function update(UpdateDiscountRequest $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $updated = $this->discountService->updateDiscount($id, $request->validated());

            if (! $updated) {
                return $this->errorResponse('Discount not found', Response::HTTP_NOT_FOUND);
            }

            $discount = $this->discountService->find($id);

            return $this->successResponse(
                new DiscountResource($discount),
                'Discount updated successfully'
            );
        });
    }

    /**
     * Delete discount.
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            $deleted = $this->discountService->deleteDiscount($id);

            if (! $deleted) {
                return $this->errorResponse('Discount not found', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(null, 'Discount deleted successfully');
        });
    }

    /**
     * Apply discount to product.
     */
    public function applyToProduct(ManageDiscountProductRequest $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $validated = $request->validated();

            $this->discountService->applyToProduct($id, $validated['product_id'], $validated['variant_id'] ?? null);

            return $this->successResponse(null, 'Discount applied to product successfully');
        });
    }

    /**
     * Remove discount from product.
     */
    public function removeFromProduct(ManageDiscountProductRequest $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $validated = $request->validated();

            $this->discountService->removeFromProduct($id, $validated['product_id'], $validated['variant_id'] ?? null);

            return $this->successResponse(null, 'Discount removed from product successfully');
        });
    }
}
