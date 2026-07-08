<?php

declare(strict_types=1);

namespace common\services\auth;

use common\models\User;
use yii\web\UnauthorizedHttpException;

final class JwtTokenService
{
    public function __construct(
        private readonly string $secret,
        private readonly string $issuer,
        private readonly string $audience,
        private readonly int $leeway = 30,
    ) {
    }

    public function createAccessToken(User $user, int $expiresAt): string
    {
        $timestamp = time();

        return $this->encode([
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => (string) $user->getId(),
            'role' => (string) $user->role,
            'typ' => 'access',
            'iat' => $timestamp,
            'exp' => $expiresAt,
        ]);
    }

    public function findUserIdByAccessToken(string $token): int | null
    {
        try {
            $payload = $this->decode($token);
        } catch (UnauthorizedHttpException) {
            return null;
        }

        if (($payload['typ'] ?? null) !== 'access') {
            return null;
        }

        $subject = $payload['sub'] ?? null;

        if (is_string($subject) && ctype_digit($subject)) {
            return (int) $subject;
        }

        if (is_int($subject)) {
            return $subject;
        }

        return null;
    }

    /**
     * @param array<string, int|string> $payload
     */
    private function encode(array $payload): string
    {
        if ($this->secret === '') {
            throw new UnauthorizedHttpException('User token secret is not configured.');
        }

        $encodedHeader = $this->encodeBase64Url(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));
        $encodedPayload = $this->encodeBase64Url(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->secret, true);

        return $encodedHeader . '.' . $encodedPayload . '.' . $this->encodeBase64Url($signature);
    }

    /**
     * @return array<string, int|string>
     */
    private function decode(string $token): array
    {
        if ($this->secret === '') {
            throw new UnauthorizedHttpException('User token secret is not configured.');
        }

        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new UnauthorizedHttpException('User token is invalid.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = $this->decodeJsonPart($encodedHeader);
        $payload = $this->decodeJsonPart($encodedPayload);

        if (($header['alg'] ?? null) !== 'HS256') {
            throw new UnauthorizedHttpException('User token algorithm is invalid.');
        }

        $signature = $this->decodeBase64Url($encodedSignature);
        $expectedSignature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->secret, true);

        if (hash_equals($expectedSignature, $signature) === false) {
            throw new UnauthorizedHttpException('User token signature is invalid.');
        }

        $this->validatePayload($payload);

        return $payload;
    }

    /**
     * @return array<string, int|string>
     */
    private function decodeJsonPart(string $value): array
    {
        $json = $this->decodeBase64Url($value);
        $data = json_decode($json, true);

        if (is_array($data) === false) {
            throw new UnauthorizedHttpException('User token payload is invalid.');
        }

        return $data;
    }

    private function encodeBase64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function decodeBase64Url(string $value): string
    {
        $base64 = strtr($value, '-_', '+/');
        $paddingLength = (4 - strlen($base64) % 4) % 4;

        if ($paddingLength > 0) {
            $base64 .= str_repeat('=', $paddingLength);
        }

        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            throw new UnauthorizedHttpException('User token is invalid.');
        }

        return $decoded;
    }

    /**
     * @param array<string, int|string> $payload
     */
    private function validatePayload(array $payload): void
    {
        $timestamp = time();

        if (($payload['iss'] ?? null) !== $this->issuer || ($payload['aud'] ?? null) !== $this->audience) {
            throw new UnauthorizedHttpException('User token issuer is invalid.');
        }

        $issuedAt = $this->getTimestampClaim($payload, 'iat');
        $expiresAt = $this->getTimestampClaim($payload, 'exp');

        if ($issuedAt === null || $expiresAt === null) {
            throw new UnauthorizedHttpException('User token time claims are invalid.');
        }

        if ($issuedAt > $timestamp + $this->leeway || $expiresAt < $timestamp - $this->leeway) {
            throw new UnauthorizedHttpException('User token has expired.');
        }
    }

    /**
     * @param array<string, int|string> $payload
     */
    private function getTimestampClaim(array $payload, string $name): int | null
    {
        $value = $payload[$name] ?? null;

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        return null;
    }
}
