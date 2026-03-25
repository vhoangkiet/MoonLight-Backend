<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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
     * Get all users.
     */
    public function getAllUsers(): Collection
    {
        return $this->all();
    }

    /**
     * Custom business logic for finding user by email.
     *
     * @return User|null
     */
    public function findByEmail(string $email)
    {
        return $this->repository->findByEmail($email);
    }
}
