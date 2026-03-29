<?php

namespace Modules\Product\Http\Controllers\Api\Customer;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\CategoryStatus;
use Modules\Product\Http\Resources\CategoryDetailResource;
use Modules\Product\Http\Resources\CategoryTreeResource;
use Modules\Product\Services\CategoryService;

/**
 * @tags Customer - Categories
 */
class CategoryController extends BaseController
{
    public function __construct(protected CategoryService $categoryService) {}

    /**
     * List active categories as tree.
     */
    public function index(): JsonResponse
    {
        return $this->execute(function (): JsonResponse {
            $tree = $this->categoryService->getCategoryTree(['status' => CategoryStatus::ACTIVE]);

            return $this->successResponse(CategoryTreeResource::collection($tree), 'Categories retrieved successfully');
        });
    }

    /**
     * Show category by slug.
     */
    public function show(string $slug): JsonResponse
    {
        return $this->execute(function () use ($slug): JsonResponse {
            $category = $this->categoryService->findBySlug($slug, CategoryStatus::ACTIVE->value);

            if (! $category) {
                return $this->errorResponse('Category not found', 404);
            }

            $category->load(['children' => function ($query) {
                $query->active()->ordered();
            }, 'parent']);

            return $this->successResponse(new CategoryDetailResource($category), 'Category retrieved successfully');
        });
    }
}
