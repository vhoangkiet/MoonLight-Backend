<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a user by email.
     *
     * @return User|null
     */
    public function findByEmail(string $email);
}
