<?php

namespace Modules\User\Http\Controllers\Api\Customer;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\User\Http\Requests\Customer\UpdateProfileRequest;
use Modules\User\Http\Resources\UserResource;

/**
 * @tags Customer - Profile
 */
class ProfileController extends BaseController
{
    /**
     * Get current user profile.
     *
     * @response array{data: UserResource, message: string}
     */
    public function show(Request $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $user = $request->user()->load(['addresses', 'roles']);

            return $this->successResponse(new UserResource($user), 'Profile retrieved successfully');
        });
    }

    /**
     * Update current user profile.
     *
     * @bodyParam first_name string User first name. Example: "John"
     * @bodyParam last_name string User last name. Example: "Doe"
     * @bodyParam email string User email. Example: "john@example.com"
     * @bodyParam avatar file Avatar image (jpeg, png, webp). Max 2MB
     *
     * @response array{data: UserResource, message: string}
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $user = $request->user();
            $data = $request->validated();

            // Update name from first_name + last_name
            if (isset($data['first_name']) || isset($data['last_name'])) {
                $data['name'] = trim(($data['first_name'] ?? $user->first_name).' '.($data['last_name'] ?? $user->last_name));
            }

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $user->addMedia($request->file('avatar'))->toMediaCollection('avatar');
            }

            // Remove avatar from data array (not stored in DB column)
            unset($data['avatar']);

            $user->update($data);

            return $this->successResponse(new UserResource($user->fresh()), 'Profile updated successfully');
        });
    }

    /**
     * Change password.
     *
     * @bodyParam current_password string required Current password. Example: "oldpassword"
     * @bodyParam password string required New password. Example: "newpassword123"
     * @bodyParam password_confirmation string required Confirm new password. Example: "newpassword123"
     *
     * @response array{data: null, message: string}
     */
    public function changePassword(Request $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $request->user()->update([
                'password' => Hash::make($request->input('password')),
            ]);

            return $this->successResponse(null, 'Password changed successfully');
        });
    }
}
