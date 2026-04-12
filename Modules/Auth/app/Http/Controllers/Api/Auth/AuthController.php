<?php

namespace Modules\Auth\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use Modules\Auth\Http\Requests\Auth\LoginRequest;
use Modules\Auth\Http\Requests\Auth\RefreshTokenRequest;
use Modules\Auth\Http\Requests\Auth\RegisterRequest;
use Modules\Auth\Http\Requests\Auth\RemoveProfileAvatarRequest;
use Modules\Auth\Http\Requests\Auth\UpdateProfileRequest;
use Modules\Auth\Http\Requests\Auth\UploadProfileAvatarRequest;
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

            $cookie = Cookie::make(
                'auth_token',
                $tokens['refresh_token'],
                60 * 24 * 30, // 30 days
                '/',
                null,
                true,  // Secure - bắt buộc true khi sameSite='none'
                true,  // HttpOnly
                false,
                'none' // Cho phép cross-domain
            );

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data' => $tokens,
            ], Response::HTTP_OK)->cookie($cookie);
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
            $cookie = Cookie::make(
                'auth_token',
                $tokens['refresh_token'],
                60 * 24 * 30, // 30 days
                '/',
                null,
                true,  // Secure - bắt buộc true khi sameSite='none'
                true,  // HttpOnly
                false,
                'none' // Cho phép cross-domain
            );

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data' => $tokens,
            ], Response::HTTP_OK)->cookie($cookie);
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

            return response()->json([
                'success' => true,
                'message' => 'Logged out',
            ])->withCookie(cookie()->forget('auth_token'));
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
     * Update first and last name. Use profile/avatar routes for the avatar.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            /** @var User $user */
            $user = auth()->user();

            $updatedUser = $this->authService->updateProfile(
                $user,
                $request->validated(),
            );

            return $this->successResponse(
                new AuthResource($updatedUser->load('roles', 'media')),
                'Profile updated successfully.'
            );
        });
    }

    /**
     * Upload or replace the authenticated user's avatar.
     */
    public function uploadProfileAvatar(UploadProfileAvatarRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            /** @var User $user */
            $user = auth()->user();

            $updatedUser = $this->authService->uploadProfileAvatar(
                $user,
                $request->file('avatar')
            );

            return $this->successResponse(
                new AuthResource($updatedUser->load('roles', 'media')),
                'Avatar updated successfully.'
            );
        });
    }

    /**
     * Remove the authenticated user's avatar.
     */
    public function removeProfileAvatar(RemoveProfileAvatarRequest $request): JsonResponse
    {
        return $this->execute(function (): JsonResponse {
            /** @var User $user */
            $user = auth()->user();

            $updatedUser = $this->authService->removeProfileAvatar($user);

            return $this->successResponse(
                new AuthResource($updatedUser->load('roles', 'media')),
                'Avatar removed successfully.'
            );
        });
    }
}
