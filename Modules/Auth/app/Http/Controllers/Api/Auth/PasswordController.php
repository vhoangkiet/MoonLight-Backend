<?php

namespace Modules\Auth\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use Modules\Auth\Http\Requests\Auth\ChangePasswordRequest;
use Modules\Auth\Http\Requests\Auth\ForgotPasswordRequest;
use Modules\Auth\Http\Requests\Auth\ResetPasswordRequest;
use Modules\Auth\Services\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;

class PasswordController extends BaseController
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Change the authenticated user's password.
     */
    public function change(ChangePasswordRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            /** @var User $user */
            $user = auth()->user();

            $this->authService->changePassword(
                $user,
                $request->validated('current_password'),
                $request->validated('password')
            );

            return $this->successResponse(null, 'Password changed successfully.');
        });
    }

    /**
     * Send a password reset link to the given email.
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $this->authService->forgotPassword($request->validated('email'));

            return $this->successResponse(null, 'Password reset link sent to your email.');
        });
    }

    /**
     * Reset the password using a token.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $this->authService->resetPassword($request->validated());

            return $this->successResponse(null, 'Password has been reset successfully.');
        });
    }
}
