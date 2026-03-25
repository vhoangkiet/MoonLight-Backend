<?php

namespace Modules\Auth\Services;

use App\Models\User;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Auth\Repositories\Interfaces\UserRepositoryInterface;

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
