<?php

namespace Modules\Product\Http\Controllers\Api\Customer;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Product\Catalog\Enums\ProductStatus;
use Modules\Product\Catalog\Services\ProductService;
use Modules\Product\Http\Resources\ProductDetailResource;
use Modules\Product\Http\Resources\ProductResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Customer - Products
 */
class ProductController extends BaseController
{
    public function __construct(protected ProductService $productService) {}

    /**
     * List active products.
     */
    public function index(): JsonResponse
    {
        return $this->execute(function (): JsonResponse {
            $validated = validator(request()->all(), [
                'search' => ['nullable', 'string', 'max:255'],
                'category_id' => ['nullable', 'integer', 'exists:categories,id'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            ])->validate();

            $filters = [
                'status' => ProductStatus::ACTIVE->value,
                'search' => $validated['search'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
            ];

            $products = $this->productService->getProducts($filters, $validated['per_page'] ?? 15);

            return $this->successResponse(
                ProductResource::collection($products),
                'Products retrieved successfully'
            );
        });
    }

    /**
     * Show product by slug.
     */
    public function show(string $slug): JsonResponse
    {
        return $this->execute(function () use ($slug): JsonResponse {
            $product = $this->productService->getBySlug($slug, ProductStatus::ACTIVE->value);

            if (! $product) {
                return $this->errorResponse('Product not found', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new ProductDetailResource($product),
                'Product retrieved successfully'
            );
        });
    }

    /**
     * Calculate product price with discount and voucher.
     */
    public function calculatePrice(): JsonResponse
    {
        return $this->execute(function (): JsonResponse {
            $validated = validator(request()->all(), [
                'product_id' => ['required', 'integer'],
                'variant_id' => ['required', 'integer'],
                'voucher_code' => ['nullable', 'string'],
            ])->validate();

            $productId = $validated['product_id'];
            $variantId = $validated['variant_id'];
            $voucherCode = $validated['voucher_code'] ?? null;

            $result = $this->productService->calculatePrice(
                $productId,
                $variantId,
                ['voucher_code' => $voucherCode]
            );

            return $this->successResponse($result, 'Price calculated successfully');
        });
    }
}
