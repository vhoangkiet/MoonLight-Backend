<?php

use App\Models\Address;
use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\Api\Admin\AddressController as AdminAddressController;
use Modules\User\Http\Controllers\Api\Admin\UserController as AdminUserController;
use Modules\User\Http\Controllers\Api\Customer\AddressController as CustomerAddressController;
use Modules\User\Http\Controllers\Api\Customer\ProfileController;

// Explicit binding with eager loading to prevent N+1 queries
Route::bind('address', function ($value) {
    return Address::with('user')->findOrFail($value);
});

/*
|--------------------------------------------------------------------------
| Admin Routes (role:admin,staff)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'role:admin|staff'])
    ->prefix('v1/admin')
    ->group(function () {

        // User Management
        Route::get('users', [AdminUserController::class, 'index'])
            ->name('admin.users.index');
        Route::post('users', [AdminUserController::class, 'store'])
            ->name('admin.users.store');
        Route::get('users/{id}', [AdminUserController::class, 'show'])
            ->name('admin.users.show');
        Route::put('users/{id}', [AdminUserController::class, 'update'])
            ->name('admin.users.update');
        Route::delete('users/{id}', [AdminUserController::class, 'destroy'])
            ->name('admin.users.destroy');
        Route::patch('users/{id}/status', [AdminUserController::class, 'updateStatus'])
            ->name('admin.users.update-status');
        Route::patch('users/{id}/role', [AdminUserController::class, 'updateRole'])
            ->name('admin.users.update-role');

        // Address Management (Admin can manage any user's addresses)
        Route::get('users/{userId}/addresses', [AdminAddressController::class, 'index'])
            ->name('admin.users.addresses.index');
        Route::post('users/{userId}/addresses', [AdminAddressController::class, 'store'])
            ->name('admin.users.addresses.store');
        Route::get('users/{userId}/addresses/{address}', [AdminAddressController::class, 'show'])
            ->name('admin.users.addresses.show');
        Route::put('users/{userId}/addresses/{address}', [AdminAddressController::class, 'update'])
            ->name('admin.users.addresses.update');
        Route::delete('users/{userId}/addresses/{address}', [AdminAddressController::class, 'destroy'])
            ->name('admin.users.addresses.destroy');
        Route::patch('users/{userId}/addresses/{address}/default', [AdminAddressController::class, 'setDefault'])
            ->name('admin.users.addresses.set-default');
    });

/*
|--------------------------------------------------------------------------
| Customer Routes (Authenticated)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api'])
    ->prefix('v1')
    ->group(function () {

        // Profile Management
        Route::get('profile', [ProfileController::class, 'show'])
            ->name('profile.show');
        Route::post('profile', [ProfileController::class, 'update'])
            ->name('profile.update');
        Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar'])
            ->name('profile.avatar.store');
        Route::delete('profile/avatar', [ProfileController::class, 'removeAvatar'])
            ->name('profile.avatar.destroy');
        Route::put('profile/password', [ProfileController::class, 'changePassword'])
            ->name('profile.change-password');

        // Address Management (Customer manages own addresses only)
        Route::get('profile/addresses', [CustomerAddressController::class, 'index'])
            ->name('profile.addresses.index');
        Route::post('profile/addresses', [CustomerAddressController::class, 'store'])
            ->name('profile.addresses.store');
        Route::get('profile/addresses/{address}', [CustomerAddressController::class, 'show'])
            ->name('profile.addresses.show');
        Route::put('profile/addresses/{address}', [CustomerAddressController::class, 'update'])
            ->name('profile.addresses.update');
        Route::delete('profile/addresses/{address}', [CustomerAddressController::class, 'destroy'])
            ->name('profile.addresses.destroy');
        Route::patch('profile/addresses/{address}/default', [CustomerAddressController::class, 'setDefault'])
            ->name('profile.addresses.set-default');
    });
