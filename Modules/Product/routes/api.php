<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use Modules\Product\Http\Controllers\Api\Customer\CategoryController as CustomerCategoryController;

/*
|--------------------------------------------------------------------------
| Admin Routes (role:admin,staff)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'role:admin|staff'])
    ->prefix('v1/admin')
    ->group(function () {

        // Category Management
        Route::get('categories', [AdminCategoryController::class, 'index'])
            ->name('admin.categories.index');
        Route::get('categories/tree', [AdminCategoryController::class, 'tree'])
            ->name('admin.categories.tree');
        Route::post('categories', [AdminCategoryController::class, 'store'])
            ->name('admin.categories.store');
        Route::put('categories/reorder', [AdminCategoryController::class, 'reorder'])
            ->name('admin.categories.reorder');
        Route::get('categories/{id}', [AdminCategoryController::class, 'show'])
            ->name('admin.categories.show');
        Route::put('categories/{id}', [AdminCategoryController::class, 'update'])
            ->name('admin.categories.update');
        Route::delete('categories/{id}', [AdminCategoryController::class, 'destroy'])
            ->name('admin.categories.destroy');
        Route::patch('categories/{id}/status', [AdminCategoryController::class, 'updateStatus'])
            ->name('admin.categories.update-status');
    });

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    Route::get('categories', [CustomerCategoryController::class, 'index'])
        ->name('categories.index');
    Route::get('categories/{slug}', [CustomerCategoryController::class, 'show'])
        ->name('categories.show');
});
