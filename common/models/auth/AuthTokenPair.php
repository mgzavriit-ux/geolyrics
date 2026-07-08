<?php

declare(strict_types=1);

namespace common\models\auth;

final class AuthTokenPair
{
    public function __construct(
        private readonly string $accessToken,
        private readonly int $accessTokenExpiresAt,
        private readonly string $refreshToken,
        private readonly int $refreshTokenExpiresAt,
    ) {
    }

    /**
     * @return array{tokenType:string, accessToken:string, accessTokenExpiresAt:int, refreshToken:string, refreshTokenExpiresAt:int}
     */
    public function toArray(): array
    {
        return [
            'tokenType' => 'Bearer',
            'accessToken' => $this->accessToken,
            'accessTokenExpiresAt' => $this->accessTokenExpiresAt,
            'refreshToken' => $this->refreshToken,
            'refreshTokenExpiresAt' => $this->refreshTokenExpiresAt,
        ];
    }
}
