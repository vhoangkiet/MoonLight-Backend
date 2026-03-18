<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->as('api.v1.')
    ->group(function (): void {
        Route::prefix('auth')->as('auth.')->group(function (): void {
            Route::post('register', [AuthController::class, 'register'])
                ->middleware('throttle:5,1')
                ->name('register');
            Route::post('otp/verify', [AuthController::class, 'verifyOtp'])
                ->middleware('throttle:10,1')
                ->name('otp.verify');
            Route::post('otp/resend', [AuthController::class, 'resendOtp'])
                ->middleware('throttle:3,1')
                ->name('otp.resend');

            Route::post('login', [AuthController::class, 'login'])
                ->middleware('throttle:6,1')
                ->name('login');
            Route::post('refresh', [AuthController::class, 'refreshToken'])
                ->middleware('throttle:10,1')
                ->name('refresh');

            Route::middleware('auth:api')->group(function (): void {
                Route::get('sessions', [AuthController::class, 'listTokens'])->name('sessions.index');
                Route::post('logout', [AuthController::class, 'revokeCurrentToken'])->name('logout');
                Route::post('logout-all', [AuthController::class, 'revokeAllTokens'])->name('logout_all');

                Route::post('password/change', [AuthController::class, 'changePassword'])->name('password.change');
            });

            Route::post('password/forgot', [AuthController::class, 'forgotPassword'])->name('password.forgot');
            Route::post('password/reset', [AuthController::class, 'resetPassword'])->name('password.reset');
        });
    });
