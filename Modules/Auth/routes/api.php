<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Api\Auth\AuthController;
use Modules\Auth\Http\Controllers\Api\Auth\PasswordController;
use Modules\Auth\Http\Controllers\Api\Auth\VerificationController;
use Modules\Auth\Http\Controllers\Api\UserController;

Route::prefix('v1')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Auth Routes (Public)
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:5,1')
            ->name('auth.register');

        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('auth.login');

        Route::post('refresh', [AuthController::class, 'refreshToken'])
            ->name('auth.refresh');

        // Password (Public)
        Route::post('password/forgot', [PasswordController::class, 'forgot'])
            ->middleware('throttle:3,1')
            ->name('auth.password.forgot');

        // Route GET này để Laravel tạo link reset trong Email, nó sẽ redirect về FE
        Route::get('password/reset/{token}', function ($token) {
            $frontendUrl = env('FRONTEND_URL', config('app.url'));

            return redirect()->to($frontendUrl.'/password/reset?token='.$token.'&email='.request('email'));
        })->name('password.reset');

        Route::post('password/reset', [PasswordController::class, 'reset'])
            ->middleware('throttle:5,1')
            ->name('auth.password.reset');

        // Email Verification (Public)
        Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
            ->middleware('signed')
            ->name('verification.verify');
    });

    /*
    |--------------------------------------------------------------------------
    | Auth Routes (Authenticated)
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])
            ->name('auth.logout');

        Route::get('profile', [AuthController::class, 'profile'])
            ->name('auth.profile');

        Route::put('profile', [AuthController::class, 'updateProfile'])
            ->name('auth.profile.update');

        // Password (Authenticated)
        Route::put('password/change', [PasswordController::class, 'change'])
            ->name('auth.password.change');

        // Email Verification (Authenticated)
        Route::post('email/resend', [VerificationController::class, 'resend'])
            ->name('verification.resend');

        // User Management
        Route::apiResource('users', UserController::class);
    });
});
