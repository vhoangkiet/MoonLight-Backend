<?php

namespace Modules\Product\Http\Controllers\Api\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Http\Requests\Admin\StoreProductRequest;
use Modules\Product\Http\Requests\Admin\UpdateProductRequest;
use Modules\Product\Http\Resources\ProductDetailResource;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Services\ProductService;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Admin - Product Management
 */
class ProductController extends BaseController
{
    public function __construct(protected ProductService $productService) {}

    /**
     * List products with filters.
     */
    public function index(): JsonResponse
    {
        return $this->execute(function (): JsonResponse {
            $validated = validator(request()->all(), [
                'search' => ['nullable', 'string', 'max:255'],
                'status' => ['nullable', 'string', 'in:active,inactive'],
                'category_id' => ['nullable', 'integer', 'exists:categories,id'],
                'sort_by' => ['nullable', 'string', 'in:name,created_at,id'],
                'sort_order' => ['nullable', 'string', 'in:asc,desc'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            ])->validate();

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
     * Store a new product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $product = $this->productService->createProduct($request->validated());

            return $this->successResponse(
                new ProductDetailResource($product),
                'Product created successfully',
                Response::HTTP_CREATED
            );
        });
    }

    /**
     * Show product details.
     */
    public function show(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            $product = $this->productService->find($id);

            if (! $product) {
                return $this->errorResponse('Product not found', Response::HTTP_NOT_FOUND);
            }

            $product->load(['category', 'variants', 'discounts']);

            return $this->successResponse(
                new ProductDetailResource($product),
                'Product retrieved successfully'
            );
        });
    }

    /**
     * Update product.
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $product = $this->productService->find($id);

            if (! $product) {
                return $this->errorResponse('Product not found', Response::HTTP_NOT_FOUND);
            }

            $this->productService->updateProduct($id, $request->validated());

            $product = $this->productService->find($id);
            $product?->load(['category', 'variants', 'discounts']);

            return $this->successResponse(
                new ProductDetailResource($product),
                'Product updated successfully'
            );
        });
    }

    /**
     * Delete product.
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
     * Update product status.
     */
    public function updateStatus(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            try {
                $status = ProductStatus::from(request('status'));
            } catch (\ValueError $e) {
                throw new DomainException(
                    'Invalid status value',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            $updated = $this->productService->updateStatus($id, $status);

            if (! $updated) {
                return $this->errorResponse('Product not found', Response::HTTP_NOT_FOUND);
            }

            $product = $this->productService->find($id);
            $product?->load(['category', 'variants']);

            return $this->successResponse(
                new ProductDetailResource($product),
                'Product status updated successfully'
            );
        });
    }
}
