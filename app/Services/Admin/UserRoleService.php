<?php

namespace App\Services\Admin;

use App\Exceptions\DomainException;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class UserRoleService
{
    /**
     * @return array{id: int|string, email: string, role: string}
     */
    public function updateRole(string $userId, string $role): array
    {
        $user = User::query()->find($userId);

        if ($user === null) {
            throw new DomainException(
                status: Response::HTTP_NOT_FOUND,
                translationKey: 'api.not_found',
                code: 'NOT_FOUND',
            );
        }

        $user->syncRoles([$role]);

        return [
            'id' => $user->getKey(),
            'email' => $user->email,
            'role' => $role,
        ];
    }
}
