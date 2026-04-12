<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Api\Auth\AuthController;
use Modules\Auth\Http\Controllers\Api\Auth\PasswordController;
use Modules\Auth\Http\Controllers\Api\Auth\VerificationController;

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

        // This GET route allows Laravel to generate reset link in Email, it will redirect to FE
        Route::get('password/reset/{token}', function ($token) {
            $frontendUrl = config('app.frontend_url', config('app.url'));

            return redirect()->to($frontendUrl.'/password/reset?token='.$token.'&email='.urlencode(request('email')));
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

        Route::post('profile', [AuthController::class, 'updateProfile'])
            ->name('auth.profile.update');

        // Email Verification (Authenticated)
        Route::post('email/resend', [VerificationController::class, 'resend'])
            ->name('verification.resend');
    });
});
