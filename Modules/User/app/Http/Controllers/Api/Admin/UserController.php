<?php

namespace Modules\User\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Http\Requests\Admin\StoreUserRequest;
use Modules\User\Http\Requests\Admin\UpdateUserRequest;
use Modules\User\Http\Resources\UserDetailResource;
use Modules\User\Http\Resources\UserResource;
use Modules\User\Services\UserService;

/**
 * @tags Admin - User Management
 */
class UserController extends BaseController
{
    /**
     * UserController constructor.
     */
    public function __construct(protected UserService $userService) {}

    /**
     * List users with search and filter.
     *
     * @response array{data: UserResource[], meta: array{current_page: int, last_page: int, per_page: int, total: int}}
     *
     * @queryParam search string Search by name, email. Example: "john"
     * @queryParam status string Filter by status (active, inactive, blocked). Example: "active"
     * @queryParam role string Filter by role (admin, staff, customer). Example: "customer"
     * @queryParam sort_by string Sort field. Example: "created_at"
     * @queryParam sort_order string Sort order (asc, desc). Example: "desc"
     * @queryParam per_page int Items per page. Example: 15
     */
    public function index(Request $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $filters = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'role' => $request->input('role'),
                'sort_by' => $request->input('sort_by', 'created_at'),
                'sort_order' => $request->input('sort_order', 'desc'),
            ];

            $users = $this->userService->getFilteredUsers($filters, $request->input('per_page', 15));

            return $this->successResponse(UserResource::collection($users), 'Users retrieved successfully');
        });
    }

    /**
     * Store a new user.
     *
     * @bodyParam first_name string required User first name. Example: "John"
     * @bodyParam last_name string required User last name. Example: "Doe"
     * @bodyParam email string required User email. Example: "john@example.com"
     * @bodyParam password string User password. Example: "password123"
     * @bodyParam status string User status (active, inactive, blocked). Example: "active"
     * @bodyParam role string User role (admin, staff, customer). Example: "customer"
     *
     * @response array{data: UserResource, message: string} 201
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $data = $request->validated();

            $user = $this->userService->createUser($data);

            return $this->successResponse(new UserResource($user), 'User created successfully', 201);
        });
    }

    /**
     * Show user details.
     *
     * @urlParam id integer required User ID. Example: 1
     *
     * @response array{data: UserDetailResource, message: string}
     */
    public function show(int $id): JsonResponse
    {
        return $this->execute(function () use ($id): JsonResponse {
            $user = $this->userService->findWithRelations($id, ['addresses', 'roles']);

            if (! $user) {
                return $this->errorResponse('User not found', 404);
            }

            return $this->successResponse(new UserDetailResource($user), 'User retrieved successfully');
        });
    }

    /**
     * Update user.
     *
     * @urlParam id integer required User ID. Example: 1
     *
     * @bodyParam first_name string User first name. Example: "John"
     * @bodyParam last_name string User last name. Example: "Doe"
     * @bodyParam email string User email. Example: "john@example.com"
     * @bodyParam status string User status. Example: "active"
     * @bodyParam role string User role. Example: "customer"
     *
     * @response array{data: UserResource, message: string}
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $updated = $this->userService->updateUser($id, $request->validated());

            if (! $updated) {
                return $this->errorResponse('User not found or update failed', 404);
            }

            $user = $this->userService->findWithRelations($id, ['addresses', 'roles']);

            return $this->successResponse(new UserDetailResource($user), 'User updated successfully');
        });
    }

    /**
     * Delete user.
     *
     * @urlParam id integer required User ID. Example: 1
     *
     * @response array{data: null, message: string}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            // Prevent self-deletion
            if ($id === $request->user()->id) {
                return $this->errorResponse('Cannot delete your own account', 403);
            }

            $deleted = $this->userService->delete($id);

            if (! $deleted) {
                return $this->errorResponse('User not found or deletion failed', 404);
            }

            return $this->successResponse(null, 'User deleted successfully');
        });
    }

    /**
     * Update user status.
     *
     * @urlParam id integer required User ID. Example: 1
     *
     * @bodyParam status string required Status (active, inactive, blocked). Example: "active"
     *
     * @response array{data: UserResource, message: string}
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $request->validate(['status' => 'required|in:active,inactive,blocked']);

            // Prevent self-status change
            if ($id === $request->user()->id) {
                return $this->errorResponse('Cannot change your own status', 403);
            }

            $updated = $this->userService->updateStatus($id, $request->input('status'));

            if (! $updated) {
                return $this->errorResponse('User not found', 404);
            }

            $user = $this->userService->findWithRelations($id, ['addresses', 'roles']);

            return $this->successResponse(new UserDetailResource($user), 'User status updated successfully');
        });
    }

    /**
     * Update user role.
     *
     * @urlParam id integer required User ID. Example: 1
     *
     * @bodyParam role string required Role (admin, staff, customer). Example: "staff"
     *
     * @response array{data: UserResource, message: string}
     */
    public function updateRole(Request $request, int $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $request->validate(['role' => 'required|string|exists:roles,name']);

            // Prevent self-demotion from admin
            if ($id === $request->user()->id) {
                return $this->errorResponse('Cannot change your own role', 403);
            }

            $updated = $this->userService->updateRole($id, $request->input('role'));

            if (! $updated) {
                return $this->errorResponse('User not found', 404);
            }

            $user = $this->userService->findWithRelations($id, ['addresses', 'roles']);

            return $this->successResponse(new UserDetailResource($user), 'User role updated successfully');
        });
    }
}
