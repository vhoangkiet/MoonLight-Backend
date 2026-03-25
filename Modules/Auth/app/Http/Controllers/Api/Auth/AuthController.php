<?php

namespace Modules\Auth\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use Modules\Auth\Http\Requests\Auth\LoginRequest;
use Modules\Auth\Http\Requests\Auth\RefreshTokenRequest;
use Modules\Auth\Http\Requests\Auth\RegisterRequest;
use Modules\Auth\Http\Requests\Auth\UpdateProfileRequest;
use Modules\Auth\Http\Resources\AuthResource;
use Modules\Auth\Services\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends BaseController
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $user = $this->authService->register($request->validated());

            return $this->successResponse(
                new AuthResource($user),
                'Registration successful. Please check your email to verify your account.',
                Response::HTTP_CREATED
            );
        });
    }

    /**
     * Login and return access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $tokens = $this->authService->login(
                $request->validated(),
                $request->ip()
            );

            return $this->successResponse($tokens, 'Login successful.');
        });
    }

    /**
     * Refresh access token.
     */
    public function refreshToken(RefreshTokenRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $tokens = $this->authService->refreshToken($request->validated('refresh_token'));

            return $this->successResponse($tokens, 'Token refreshed successfully.');
        });
    }

    /**
     * Logout the current user.
     */
    public function logout(): JsonResponse
    {
        return $this->execute(function () {
            /** @var User $user */
            $user = auth()->user();
            $this->authService->logout($user);

            return $this->successResponse(null, 'Logged out successfully.');
        });
    }

    /**
     * Get the authenticated user's profile.
     */
    public function profile(): JsonResponse
    {
        return $this->execute(function () {
            /** @var User $user */
            $user = auth()->user();
            $user->load('roles', 'media');

            return $this->successResponse(new AuthResource($user));
        });
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            /** @var User $user */
            $user = auth()->user();

            $updatedUser = $this->authService->updateProfile(
                $user,
                $request->validated(),
                $request->file('avatar')
            );

            return $this->successResponse(
                new AuthResource($updatedUser->load('roles', 'media')),
                'Profile updated successfully.'
            );
        });
    }
}
