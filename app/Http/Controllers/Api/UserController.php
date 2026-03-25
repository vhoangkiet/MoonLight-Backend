<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends BaseController
{
    /**
     * UserController constructor.
     */
    public function __construct(protected UserService $userService) {}

    /**
     * Get list of users.
     */
    public function index(): JsonResponse
    {
        $users = $this->userService->getAllUsers();

        return $this->successResponse(UserResource::collection($users), 'Users retrieved successfully');
    }

    /**
     * Create a new user.
     */
    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return $this->successResponse(new UserResource($user), 'User created successfully', 201);
    }

    /**
     * Get user detail.
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->find($id);

        if (! $user) {
            return $this->errorResponse('User not found', 404);
        }

        return $this->successResponse(new UserResource($user), 'User retrieved successfully');
    }

    /**
     * Update user.
     */
    public function update(UserRequest $request, int $id): JsonResponse
    {
        $updated = $this->userService->update($id, $request->validated());

        if (! $updated) {
            return $this->errorResponse('User update failed or user not found', 400);
        }

        $user = $this->userService->find($id);

        return $this->successResponse(new UserResource($user), 'User updated successfully');
    }

    /**
     * Delete user.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->userService->delete($id);

        if (! $deleted) {
            return $this->errorResponse('User deletion failed or user not found', 400);
        }

        return $this->successResponse(null, 'User deleted successfully');
    }
}
