<?php

namespace Modules\User\Repositories\Interfaces;

use App\Models\User;
use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get paginated users with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Update user status.
     */
    public function updateStatus(int $id, string $status): bool;

    /**
     * Sync user roles.
     */
    public function syncRoles(int $id, array $roles): bool;
}
