<?php

namespace Modules\User\Services;

use App\Models\User;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Modules\User\Repositories\Interfaces\UserRepositoryInterface;

class UserService extends BaseService
{
    /**
     * UserService constructor.
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        parent::__construct($userRepository);
    }

    /**
     * Get paginated users with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getFilteredUsers($filters, $perPage);
    }

    /**
     * Create a new user with role.
     *
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): Model
    {
        $role = $data['role'] ?? null;
        unset($data['role']);

        // Generate name from first_name and last_name
        if (! empty($data['first_name']) || ! empty($data['last_name'])) {
            $data['name'] = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
        }

        // Hash password if provided
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = $this->repository->create($data);

        if ($role) {
            $user->assignRole($role);
        }

        return $user;
    }

    /**
     * Update user with role.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateUser(int $id, array $data): bool
    {
        $role = $data['role'] ?? null;
        unset($data['role']);

        $updated = $this->repository->update($id, $data);

        if ($updated && $role) {
            $this->repository->syncRoles($id, [$role]);
        }

        return $updated;
    }

    /**
     * Update user status.
     */
    public function updateStatus(int $id, string $status): bool
    {
        return $this->repository->updateStatus($id, $status);
    }

    /**
     * Update user role.
     */
    public function updateRole(int $id, string $role): bool
    {
        return $this->repository->syncRoles($id, [$role]);
    }

    /**
     * Find user by ID with relations.
     */
    public function findWithRelations(int $id, array $relations = []): ?User
    {
        return $this->repository->find($id, ['*'], $relations);
    }

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->repository->findByEmail($email);
    }
}
