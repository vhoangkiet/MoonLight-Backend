<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Services\Admin\UserRoleService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserRoleController extends ApiController
{
    public function __construct(
        protected readonly UserRoleService $userRoleService,
    ) {}

    public function update(UpdateUserRoleRequest $request, string $id): JsonResponse
    {
        return $this->execute(function () use ($request, $id): JsonResponse {
            $data = $this->userRoleService->updateRole(
                $id,
                $request->string('role')->toString(),
            );

            return ApiResponse::ok($data);
        });
    }
}
