<?php

declare(strict_types=1);

namespace common\services\auth;

use common\models\auth\GoogleIdentity;
use RuntimeException;
use Yii;
use yii\caching\CacheInterface;
use yii\helpers\Json;
use yii\web\UnauthorizedHttpException;

final class GoogleIdentityVerifier
{
    private const string CERTS_CACHE_KEY = 'google.identity.certs';

    /**
     * @param string[] $clientIds
     */
    public function __construct(
        private readonly array $clientIds,
        private readonly string $certsUrl,
        private readonly int $leeway = 30,
        private readonly CacheInterface | null $cache = null,
    ) {
    }

    public function verify(string $idToken): GoogleIdentity
    {
        if ($this->clientIds === []) {
            throw new UnauthorizedHttpException('Google auth is not configured.');
        }

        [$header, $payload, $signingInput, $signature] = $this->decodeToken($idToken);

        if (($header['alg'] ?? null) !== 'RS256') {
            throw new UnauthorizedHttpException('Google token algorithm is invalid.');
        }

        $kid = $header['kid'] ?? null;

        if (is_string($kid) === false || $kid === '') {
            throw new UnauthorizedHttpException('Google token key is invalid.');
        }

        $cert = $this->findCertificate($kid);

        if (openssl_verify($signingInput, $signature, $cert, OPENSSL_ALGO_SHA256) !== 1) {
            throw new UnauthorizedHttpException('Google token signature is invalid.');
        }

        $this->validatePayload($payload);

        return $this->createIdentity($payload);
    }

    /**
     * @return array{0:array<string, mixed>, 1:array<string, mixed>, 2:string, 3:string}
     */
    private function decodeToken(string $idToken): array
    {
        $parts = explode('.', $idToken);

        if (count($parts) !== 3) {
            throw new UnauthorizedHttpException('Google token is invalid.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        return [
            $this->decodeJsonPart($encodedHeader),
            $this->decodeJsonPart($encodedPayload),
            $encodedHeader . '.' . $encodedPayload,
            $this->decodeBase64Url($encodedSignature),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonPart(string $value): array
    {
        $json = $this->decodeBase64Url($value);
        $data = Json::decode($json, true);

        if (is_array($data) === false) {
            throw new UnauthorizedHttpException('Google token payload is invalid.');
        }

        return $data;
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
            throw new UnauthorizedHttpException('Google token is invalid.');
        }

        return $decoded;
    }

    private function findCertificate(string $kid): string
    {
        $certs = $this->findCertificates();
        $cert = $certs[$kid] ?? null;

        if (is_string($cert) === false || $cert === '') {
            throw new UnauthorizedHttpException('Google token key is unknown.');
        }

        return $cert;
    }

    /**
     * @return array<string, string>
     */
    private function findCertificates(): array
    {
        $cache = $this->getCache();
        $cached = $cache->get(self::CERTS_CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
            ],
        ]);
        $json = @file_get_contents($this->certsUrl, false, $context);

        if ($json === false) {
            throw new RuntimeException('Failed to fetch Google certificates.');
        }

        $certs = Json::decode($json, true);

        if (is_array($certs) === false) {
            throw new RuntimeException('Google certificates response is invalid.');
        }

        $cache->set(self::CERTS_CACHE_KEY, $certs, 3600);

        return $certs;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validatePayload(array $payload): void
    {
        $timestamp = time();
        $issuer = $payload['iss'] ?? null;

        if ($issuer !== 'accounts.google.com' && $issuer !== 'https://accounts.google.com') {
            throw new UnauthorizedHttpException('Google token issuer is invalid.');
        }

        if (in_array($payload['aud'] ?? null, $this->clientIds, true) === false) {
            throw new UnauthorizedHttpException('Google token audience is invalid.');
        }

        $expiresAt = $this->getTimestampClaim($payload, 'exp');
        $issuedAt = $this->getTimestampClaim($payload, 'iat');

        if ($expiresAt === null || $issuedAt === null) {
            throw new UnauthorizedHttpException('Google token time claims are invalid.');
        }

        if ($issuedAt > $timestamp + $this->leeway || $expiresAt < $timestamp - $this->leeway) {
            throw new UnauthorizedHttpException('Google token has expired.');
        }

        if (($payload['email_verified'] ?? null) !== true && ($payload['email_verified'] ?? null) !== 'true') {
            throw new UnauthorizedHttpException('Google email is not verified.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createIdentity(array $payload): GoogleIdentity
    {
        $subject = $payload['sub'] ?? null;
        $email = $payload['email'] ?? null;

        if (is_string($subject) === false || $subject === '' || is_string($email) === false || $email === '') {
            throw new UnauthorizedHttpException('Google token identity is invalid.');
        }

        $name = $payload['name'] ?? null;

        if (is_string($name) === false || trim($name) === '') {
            $name = (string) strstr($email, '@', true);
        }

        return new GoogleIdentity($subject, mb_strtolower($email), trim($name));
    }

    /**
     * @param array<string, mixed> $payload
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

    private function getCache(): CacheInterface
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        return Yii::$app->cache;
    }
}
