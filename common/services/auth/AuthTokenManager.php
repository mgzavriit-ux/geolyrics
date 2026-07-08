<?php

declare(strict_types=1);

namespace common\services\auth;

use common\models\auth\AuthTokenPair;
use common\models\User;
use common\models\UserRefreshToken;
use RuntimeException;
use Yii;
use yii\base\Security;
use yii\db\Connection;
use yii\web\Request;
use yii\web\UnauthorizedHttpException;

final class AuthTokenManager
{
    private const int RANDOM_TOKEN_BYTES = 32;

    public function __construct(
        private readonly JwtTokenService $jwtTokenService,
        private readonly int $accessTokenTtl,
        private readonly int $refreshTokenTtl,
        private readonly Security | null $security = null,
        private readonly Connection | null $db = null,
        private readonly Request | null $request = null,
    ) {
    }

    public function createTokenPair(User $user): AuthTokenPair
    {
        $accessTokenExpiresAt = time() + $this->accessTokenTtl;
        $accessToken = $this->jwtTokenService->createAccessToken($user, $accessTokenExpiresAt);
        $refreshToken = $this->createRefreshToken($user);

        return new AuthTokenPair(
            $accessToken,
            $accessTokenExpiresAt,
            $refreshToken,
            time() + $this->refreshTokenTtl,
        );
    }

    public function refreshTokenPair(string $refreshToken): AuthTokenPair
    {
        $tokenModel = $this->findValidRefreshToken($refreshToken);

        if ($tokenModel === null) {
            throw new UnauthorizedHttpException('Refresh token is invalid.');
        }

        return $this->getDb()->transaction(function () use ($tokenModel): AuthTokenPair {
            $user = $tokenModel->user;

            if ($user->isActive() === false) {
                throw new UnauthorizedHttpException('User is inactive.');
            }

            $tokenModel->revoke();

            if ($tokenModel->save(false, ['revoked_at', 'updated_at']) === false) {
                throw new RuntimeException('Failed to revoke refresh token.');
            }

            return $this->createTokenPair($user);
        });
    }

    public function revokeRefreshToken(string $refreshToken): bool
    {
        $tokenModel = $this->findValidRefreshToken($refreshToken);

        if ($tokenModel === null) {
            return false;
        }

        $tokenModel->revoke();

        return $tokenModel->save(false, ['revoked_at', 'updated_at']);
    }

    private function createRefreshToken(User $user): string
    {
        $plainToken = $this->generatePlainToken();
        $tokenModel = new UserRefreshToken();
        $tokenModel->user_id = (int) $user->getId();
        $tokenModel->token_hash = $this->createTokenHash($plainToken);
        $tokenModel->user_agent = $this->findUserAgent();
        $tokenModel->ip_address = $this->findUserIp();
        $tokenModel->expires_at = time() + $this->refreshTokenTtl;

        if ($tokenModel->save() === false) {
            throw new RuntimeException('Failed to save refresh token.');
        }

        return $plainToken;
    }

    private function findValidRefreshToken(string $plainToken): UserRefreshToken | null
    {
        $tokenModel = UserRefreshToken::find()
            ->andWhere(['token_hash' => $this->createTokenHash($plainToken)])
            ->with('user')
            ->one();

        if ($tokenModel instanceof UserRefreshToken === false) {
            return null;
        }

        if ($tokenModel->isRevoked() || $tokenModel->isExpired()) {
            return null;
        }

        return $tokenModel;
    }

    private function createTokenHash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    private function generatePlainToken(): string
    {
        return rtrim(strtr(base64_encode($this->getSecurity()->generateRandomKey(self::RANDOM_TOKEN_BYTES)), '+/', '-_'), '=');
    }

    private function findUserAgent(): string | null
    {
        $userAgent = $this->getRequest()->userAgent;

        if ($userAgent === null) {
            return null;
        }

        return mb_substr($userAgent, 0, 512);
    }

    private function findUserIp(): string | null
    {
        $userIp = $this->getRequest()->userIP;

        if ($userIp === null) {
            return null;
        }

        return mb_substr($userIp, 0, 64);
    }

    private function getSecurity(): Security
    {
        if ($this->security !== null) {
            return $this->security;
        }

        return Yii::$app->security;
    }

    private function getDb(): Connection
    {
        if ($this->db !== null) {
            return $this->db;
        }

        return UserRefreshToken::getDb();
    }

    private function getRequest(): Request
    {
        if ($this->request !== null) {
            return $this->request;
        }

        /** @var Request $request */
        $request = Yii::$app->request;

        return $request;
    }
}
