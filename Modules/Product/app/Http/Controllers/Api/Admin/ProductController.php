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
