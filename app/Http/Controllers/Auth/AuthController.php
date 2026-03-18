<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Services\Auth\AuthService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(
        protected readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $user = $this->authService->register($request->validated());

            return ApiResponse::created([
                'email' => $user->email,
            ]);
        });
    }

    public function resendOtp(RequestOtpRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $this->authService->requestRegisterOtp(
                $request->string('email')->toString(),
            );

            return ApiResponse::ok([
                'email' => $request->string('email')->toString(),
            ]);
        });
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $this->authService->verifyRegisterOtp(
                $request->string('email')->toString(),
                $request->string('otp')->toString(),
            );

            return ApiResponse::ok([
                'email' => $request->string('email')->toString(),
            ]);
        });
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $result = $this->authService->login(
                $request->string('email')->toString(),
                $request->string('password')->toString(),
            );

            return ApiResponse::ok(new LoginResource($result));
        });
    }

    public function listTokens(Request $request): JsonResponse
    {
        return $this->execute(function (): JsonResponse {
            $sessions = $this->authService->listSessions();

            return ApiResponse::ok([
                'sessions' => $sessions,
            ]);
        });
    }

    public function revokeCurrentToken(Request $request): JsonResponse
    {
        return $this->execute(function (): JsonResponse {
            $this->authService->logoutCurrentToken();

            return ApiResponse::noContent();
        });
    }

    public function revokeAllTokens(Request $request): JsonResponse
    {
        return $this->execute(function (): JsonResponse {
            $this->authService->logoutAllTokens();

            return ApiResponse::noContent();
        });
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $this->authService->changePassword(
                $request->string('current_password')->toString(),
                $request->string('password')->toString(),
            );

            return ApiResponse::noContent();
        });
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $this->authService->forgotPassword(
                $request->string('email')->toString(),
            );

            return ApiResponse::ok([
                'email' => $request->string('email')->toString(),
            ]);
        });
    }

    public function refreshToken(RefreshTokenRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $result = $this->authService->refreshToken(
                $request->string('refresh_token')->toString(),
            );

            return ApiResponse::ok(new LoginResource($result));
        });
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $this->authService->resetPassword($request->validated());

            return ApiResponse::noContent();
        });
    }
}
