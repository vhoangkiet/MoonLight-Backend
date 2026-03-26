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
     * Register a new account.
     *
     * Create a new user and assign default 'customer' role.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $user = $this->authService->register($request->validated());

            return $this->successResponse(
                new AuthResource($user),
                'Registration successful.',
                Response::HTTP_CREATED
            );
        });
    }

    /**
     * Login and receive Token.
     *
     * Authenticate via Email and Password. Return access_token and refresh_token.
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
     * Refresh Access Token.
     *
     * Use refresh_token to get a new pair of tokens.
     */
    public function refreshToken(RefreshTokenRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $tokens = $this->authService->refreshToken($request->validated('refresh_token'));

            return $this->successResponse($tokens, 'Token refreshed successfully.');
        });
    }

    /**
     * Logout.
     *
     * Revoke the current token of the user.
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
     * View personal Profile.
     *
     * Return detailed information of the logged-in user with Roles.
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
     * Update Profile.
     *
     * Allow updating name and uploading avatar image.
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
