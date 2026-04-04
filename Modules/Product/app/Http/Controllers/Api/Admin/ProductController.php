<?php

namespace Modules\Product\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Product\Catalog\Enums\ProductStatus;
use Modules\Product\Catalog\Services\ProductService;
use Modules\Product\Http\Requests\Admin\IndexProductRequest;
use Modules\Product\Http\Requests\Admin\StoreProductRequest;
use Modules\Product\Http\Requests\Admin\UpdateProductRequest;
use Modules\Product\Http\Requests\Admin\UpdateProductStatusRequest;
use Modules\Product\Http\Resources\ProductDetailResource;
use Modules\Product\Http\Resources\ProductResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Admin - Product Management
 */
class ProductController extends BaseController
{
    public function __construct(protected ProductService $productService) {}

    /**
     * List products with filters and pagination.
     *
     * Query string is validated by {@see IndexProductRequest}.
     *
     * @response array{success: true, message: string, data: array{data: ProductResource[], links: array<string, string|null>, meta: array<string, mixed>}}
     */
    public function index(IndexProductRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $validated = $request->validated();

            $filters = [
                'search' => $validated['search'] ?? null,
                'status' => $validated['status'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'sort_by' => $validated['sort_by'] ?? 'created_at',
                'sort_order' => $validated['sort_order'] ?? 'desc',
            ];

            $products = $this->productService->getProducts($filters, $validated['per_page'] ?? 15);

            return $this->successResponse(
                ProductResource::collection($products),
                'Products retrieved successfully'
            );
        });
    }

    /**
     * Create a product.
     *
     * @response array{success: true, message: string, data: ProductDetailResource} 201
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $product = $this->productService->createProduct(
                $request->validated(),
                (int) $request->user()->id
            );

            return $this->successResponse(
                new ProductDetailResource($product),
                'Product created successfully',
                Response::HTTP_CREATED
            );
        });
    }

    /**
     * Get a single product with category, variants, discounts, and gallery media.
     *
     * @urlParam id integer required Product ID. Example: 1
     *
     * @response array{success: true, message: string, data: ProductDetailResource}
     */
    public function show(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            $product = $this->productService->find($id);

            if (! $product) {
                return $this->errorResponse('Product not found', Response::HTTP_NOT_FOUND);
            }

            $product->load(['category', 'variants', 'discounts', 'media']);

            return $this->successResponse(
                new ProductDetailResource($product),
                'Product retrieved successfully'
            );
        });
    }

    /**
     * Update a product.
     *
     * @urlParam id integer required Product ID. Example: 1
     *
     * @response array{success: true, message: string, data: ProductDetailResource}
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $product = $this->productService->find($id);

            if (! $product) {
                return $this->errorResponse('Product not found', Response::HTTP_NOT_FOUND);
            }

            $this->productService->updateProduct($id, $request->validated(), (int) $request->user()->id);

            $product = $this->productService->find($id);
            $product?->load(['category', 'variants', 'discounts', 'media']);

            return $this->successResponse(
                new ProductDetailResource($product),
                'Product updated successfully'
            );
        });
    }

    /**
     * Delete a product (soft delete).
     *
     * @urlParam id integer required Product ID. Example: 1
     *
     * @response array{success: true, message: string, data: null}
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            $deleted = $this->productService->deleteProduct($id);

            if (! $deleted) {
                return $this->errorResponse('Product not found', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(null, 'Product deleted successfully');
        });
    }

    /**
     * Update product status (active / inactive).
     *
     * @urlParam id integer required Product ID. Example: 1
     *
     * @response array{success: true, message: string, data: ProductDetailResource}
     */
    public function updateStatus(UpdateProductStatusRequest $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $status = ProductStatus::from($request->validated('status'));
            $updated = $this->productService->updateStatus($id, $status);

            if (! $updated) {
                return $this->errorResponse('Product not found', Response::HTTP_NOT_FOUND);
            }

            $product = $this->productService->find($id);
            $product?->load(['category', 'variants', 'discounts', 'media']);

            return $this->successResponse(
                new ProductDetailResource($product),
                'Product status updated successfully'
            );
        });
    }
}
