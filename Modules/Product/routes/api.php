<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use Modules\Product\Http\Controllers\Api\Admin\DiscountController as AdminDiscountController;
use Modules\Product\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use Modules\Product\Http\Controllers\Api\Admin\ProductMediaUploadController as AdminProductMediaUploadController;
use Modules\Product\Http\Controllers\Api\Admin\ProductVariantController as AdminProductVariantController;
use Modules\Product\Http\Controllers\Api\Admin\VoucherController as AdminVoucherController;
use Modules\Product\Http\Controllers\Api\Customer\CategoryController as CustomerCategoryController;
use Modules\Product\Http\Controllers\Api\Customer\ProductController as CustomerProductController;

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

        // Product media (staging upload)
        Route::post('product-media/upload', [AdminProductMediaUploadController::class, 'store'])
            ->name('admin.product-media.upload');

        // Product Management
        Route::get('products', [AdminProductController::class, 'index'])
            ->name('admin.products.index');
        Route::post('products', [AdminProductController::class, 'store'])
            ->name('admin.products.store');
        Route::get('products/{id}', [AdminProductController::class, 'show'])
            ->name('admin.products.show');
        Route::put('products/{id}', [AdminProductController::class, 'update'])
            ->name('admin.products.update');
        Route::delete('products/{id}', [AdminProductController::class, 'destroy'])
            ->name('admin.products.destroy');
        Route::patch('products/{id}/status', [AdminProductController::class, 'updateStatus'])
            ->name('admin.products.update-status');

        // Product Variant Management
        Route::get('products/{productId}/variants', [AdminProductVariantController::class, 'index'])
            ->name('admin.products.variants.index');
        Route::post('products/{productId}/variants', [AdminProductVariantController::class, 'store'])
            ->name('admin.products.variants.store');
        Route::get('variants/{id}', [AdminProductVariantController::class, 'show'])
            ->name('admin.variants.show');
        Route::put('variants/{id}', [AdminProductVariantController::class, 'update'])
            ->name('admin.variants.update');
        Route::delete('variants/{id}', [AdminProductVariantController::class, 'destroy'])
            ->name('admin.variants.destroy');
        Route::patch('variants/{id}/stock', [AdminProductVariantController::class, 'updateStock'])
            ->name('admin.variants.update-stock');
        Route::patch('variants/{id}/status', [AdminProductVariantController::class, 'updateStatus'])
            ->name('admin.variants.update-status');

        // Discount Management
        Route::get('discounts', [AdminDiscountController::class, 'index'])
            ->name('admin.discounts.index');
        Route::post('discounts', [AdminDiscountController::class, 'store'])
            ->name('admin.discounts.store');
        Route::get('discounts/{id}', [AdminDiscountController::class, 'show'])
            ->name('admin.discounts.show');
        Route::put('discounts/{id}', [AdminDiscountController::class, 'update'])
            ->name('admin.discounts.update');
        Route::delete('discounts/{id}', [AdminDiscountController::class, 'destroy'])
            ->name('admin.discounts.destroy');
        Route::post('discounts/{id}/apply', [AdminDiscountController::class, 'applyToProduct'])
            ->name('admin.discounts.apply');
        Route::post('discounts/{id}/remove', [AdminDiscountController::class, 'removeFromProduct'])
            ->name('admin.discounts.remove');

        // Voucher Management
        Route::get('vouchers', [AdminVoucherController::class, 'index'])
            ->name('admin.vouchers.index');
        Route::post('vouchers', [AdminVoucherController::class, 'store'])
            ->name('admin.vouchers.store');
        Route::get('vouchers/{id}', [AdminVoucherController::class, 'show'])
            ->name('admin.vouchers.show');
        Route::put('vouchers/{id}', [AdminVoucherController::class, 'update'])
            ->name('admin.vouchers.update');
        Route::delete('vouchers/{id}', [AdminVoucherController::class, 'destroy'])
            ->name('admin.vouchers.destroy');
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

    // Product Routes
    Route::get('products', [CustomerProductController::class, 'index'])
        ->name('products.index');
    Route::get('products/{slug}', [CustomerProductController::class, 'show'])
        ->name('products.show');
    Route::post('products/calculate-price', [CustomerProductController::class, 'calculatePrice'])
        ->name('products.calculate-price');
});
