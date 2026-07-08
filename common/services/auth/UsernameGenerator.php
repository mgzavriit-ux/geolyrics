<?php

declare(strict_types=1);

namespace common\services\auth;

use common\models\User;
use yii\helpers\Inflector;

final class UsernameGenerator
{
    public function generateByEmail(string $email): string
    {
        $localPart = (string) strstr($email, '@', true);
        $baseUsername = trim(Inflector::slug($localPart, '-'), '-');

        if ($baseUsername === '') {
            $baseUsername = 'user';
        }

        return $this->createUniqueUsername($baseUsername);
    }

    private function createUniqueUsername(string $baseUsername): string
    {
        $candidate = $baseUsername;
        $suffix = 2;

        while ($this->hasUsername($candidate)) {
            $candidate = $baseUsername . '-' . $suffix;
            ++$suffix;
        }

        return $candidate;
    }

    private function hasUsername(string $username): bool
    {
        return User::find()->andWhere(['username' => $username])->exists();
    }
}
