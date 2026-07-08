<?php

declare(strict_types=1);

namespace api\components;

use Yii;
use yii\base\ActionFilter;
use yii\web\Request;
use yii\web\UnauthorizedHttpException;

final class RequestJwtAuth extends ActionFilter
{
    public string $audience = 'geolyrics-api';
    public string $headerName = 'X-GeoLyrics-Request-JWT';
    public string $issuer = 'geovue';
    public int $leeway = 30;
    public int $maxTtl = 120;
    public string $secret = '';

    public function beforeAction($action): bool
    {
        $this->validateRequest(Yii::$app->request);

        return parent::beforeAction($action);
    }

    private function validateRequest(Request $request): void
    {
        if ($this->secret === '') {
            throw new UnauthorizedHttpException('API request signature is not configured.');
        }

        $token = trim((string) $request->headers->get($this->headerName, ''));

        if ($token === '') {
            throw new UnauthorizedHttpException('API request signature is required.');
        }

        $payload = $this->decodeToken($token);

        $this->validatePayload($payload, $request);
    }

    /**
     * @return array<string, int|string>
     */
    private function decodeToken(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new UnauthorizedHttpException('API request signature is invalid.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = $this->decodeJsonPart($encodedHeader);
        $payload = $this->decodeJsonPart($encodedPayload);

        if (($header['alg'] ?? null) !== 'HS256') {
            throw new UnauthorizedHttpException('API request signature algorithm is invalid.');
        }

        $signature = $this->decodeBase64Url($encodedSignature);
        $expectedSignature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->secret, true);

        if (hash_equals($expectedSignature, $signature) === false) {
            throw new UnauthorizedHttpException('API request signature is invalid.');
        }

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
            throw new UnauthorizedHttpException('API request signature payload is invalid.');
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
            throw new UnauthorizedHttpException('API request signature is invalid.');
        }

        return $decoded;
    }

    /**
     * @param array<string, int|string> $payload
     */
    private function validatePayload(array $payload, Request $request): void
    {
        $timestamp = time();

        if (($payload['iss'] ?? null) !== $this->issuer || ($payload['aud'] ?? null) !== $this->audience) {
            throw new UnauthorizedHttpException('API request signature issuer is invalid.');
        }

        $issuedAt = $this->getTimestampClaim($payload, 'iat');
        $expiresAt = $this->getTimestampClaim($payload, 'exp');

        if ($issuedAt === null || $expiresAt === null) {
            throw new UnauthorizedHttpException('API request signature time claims are invalid.');
        }

        if ($issuedAt > $timestamp + $this->leeway || $expiresAt < $timestamp - $this->leeway) {
            throw new UnauthorizedHttpException('API request signature has expired.');
        }

        if ($expiresAt - $issuedAt > $this->maxTtl) {
            throw new UnauthorizedHttpException('API request signature lifetime is invalid.');
        }

        if (($payload['method'] ?? null) !== strtoupper($request->method)) {
            throw new UnauthorizedHttpException('API request signature method is invalid.');
        }

        if (($payload['uri'] ?? null) !== $request->getUrl()) {
            throw new UnauthorizedHttpException('API request signature URI is invalid.');
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
