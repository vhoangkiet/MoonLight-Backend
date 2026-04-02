<?php

namespace Modules\Product\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Enums\CategoryStatus;
use Modules\Product\Http\Requests\Admin\IndexCategoryRequest;
use Modules\Product\Http\Requests\Admin\ReorderCategoriesRequest;
use Modules\Product\Http\Requests\Admin\StoreCategoryRequest;
use Modules\Product\Http\Requests\Admin\UpdateCategoryRequest;
use Modules\Product\Http\Requests\Admin\UpdateCategoryStatusRequest;
use Modules\Product\Http\Resources\CategoryDetailResource;
use Modules\Product\Http\Resources\CategoryResource;
use Modules\Product\Http\Resources\CategoryTreeResource;
use Modules\Product\Services\CategoryService;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Admin - Category Management
 */
class CategoryController extends BaseController
{
    public function __construct(protected CategoryService $categoryService) {}

    /**
     * List categories with filters.
     *
     * @queryParam search string Search by name or slug. Example: "electronics"
     * @queryParam status string Filter by status (active, inactive). Example: "active"
     * @queryParam parent_id integer Filter by parent category. Example: 1
     * @queryParam sort_by string Sort field. Example: "position"
     * @queryParam sort_order string Sort order (asc, desc). Example: "asc"
     * @queryParam per_page int Items per page. Example: 15
     */
    public function index(IndexCategoryRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $filters = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'parent_id' => $request->input('parent_id'),
                'sort_by' => $request->input('sort_by', 'position'),
                'sort_order' => $request->input('sort_order', 'asc'),
            ];

            $categories = $this->categoryService->getFilteredCategories($filters, $request->input('per_page', 15));

            return $this->successResponse(
                CategoryResource::collection($categories),
                'Categories retrieved successfully'
            );
        });
    }

    /**
     * Get category tree structure.
     */
    public function tree(Request $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $validated = validator($request->all(), [
                'status' => ['nullable', 'string', 'in:active,inactive'],
            ])->validate();

            $filters = [];
            if (isset($validated['status'])) {
                $filters['status'] = $validated['status'];
            }

            $tree = $this->categoryService->getCategoryTree($filters);

            return $this->successResponse(CategoryTreeResource::collection($tree), 'Category tree retrieved successfully');
        });
    }

    /**
     * Store a new category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $category = $this->categoryService->createCategory($request->validated());

            return $this->successResponse(new CategoryDetailResource($category), 'Category created successfully', Response::HTTP_CREATED);
        });
    }

    /**
     * Show category details.
     */
    public function show(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            $category = $this->categoryService->findWithRelations($id, ['parent', 'children', 'products']);

            if (! $category) {
                return $this->errorResponse('Category not found', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(new CategoryDetailResource($category), 'Category retrieved successfully');
        });
    }

    /**
     * Update category.
     */
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $category = $this->categoryService->findWithRelations($id, ['parent', 'children', 'products']);

            if (! $category) {
                return $this->errorResponse('Category not found', Response::HTTP_NOT_FOUND);
            }

            $this->categoryService->updateCategory($id, $request->validated());

            $category->load(['children' => function ($query) {
                $query->ordered();
            }, 'parent', 'products']);

            return $this->successResponse(new CategoryDetailResource($category), 'Category updated successfully');
        });
    }

    /**
     * Delete category.
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            $deleted = $this->categoryService->deleteCategory($id);

            if (! $deleted) {
                return $this->errorResponse('Category not found', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(null, 'Category deleted successfully');
        });
    }

    /**
     * Update category status.
     */
    public function updateStatus(UpdateCategoryStatusRequest $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $status = CategoryStatus::from($request->input('status'));

            $updated = $this->categoryService->updateStatus($id, $status);

            if (! $updated) {
                return $this->errorResponse('Category not found', Response::HTTP_NOT_FOUND);
            }

            $category = $this->categoryService->findWithRelations($id, ['parent', 'children']);

            if (! $category) {
                return $this->errorResponse('Category not found', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(new CategoryDetailResource($category), 'Category status updated successfully');
        });
    }

    /**
     * Reorder categories.
     */
    public function reorder(ReorderCategoriesRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $this->categoryService->reorderCategories($request->input('orders'));

            return $this->successResponse(null, 'Categories reordered successfully');
        });
    }
}
