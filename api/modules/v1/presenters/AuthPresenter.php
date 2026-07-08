<?php

declare(strict_types=1);

namespace api\modules\v1\presenters;

use common\models\auth\AuthTokenPair;
use common\models\User;

final class AuthPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function presentSession(User $user, AuthTokenPair $tokenPair): array
    {
        return [
            'user' => $this->presentUser($user),
            'tokens' => $tokenPair->toArray(),
        ];
    }

    /**
     * @return array{id:int, name:string, email:string, role:string, roles:string[]}
     */
    public function presentUser(User $user): array
    {
        return [
            'id' => (int) $user->getId(),
            'name' => $user->getDisplayName(),
            'email' => (string) $user->email,
            'role' => (string) $user->role,
            'roles' => [
                (string) $user->role,
            ],
        ];
    }
}
