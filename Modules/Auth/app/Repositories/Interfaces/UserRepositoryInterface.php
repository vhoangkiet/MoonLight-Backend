<?php

namespace Modules\Auth\Repositories\Interfaces;

use App\Models\User;
use App\Repositories\Interfaces\BaseRepositoryInterface;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a user by email.
     *
     * @return User|null
     */
    public function findByEmail(string $email);
}
