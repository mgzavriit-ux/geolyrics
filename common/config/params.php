<?php

declare(strict_types=1);

$apiRequestJwtSecret = getenv('API_REQUEST_JWT_SECRET');
$userJwtSecret = getenv('USER_AUTH_JWT_SECRET');
$googleClientIds = getenv('GOOGLE_AUTH_CLIENT_IDS');
$yiiEnv = defined('YII_ENV') ? YII_ENV : 'prod';

if (($apiRequestJwtSecret === false || $apiRequestJwtSecret === '') && $yiiEnv !== 'prod') {
    $apiRequestJwtSecret = 'dev-geolyrics-request-jwt-secret';
}

if (($userJwtSecret === false || $userJwtSecret === '') && $yiiEnv !== 'prod') {
    $userJwtSecret = 'dev-geolyrics-user-jwt-secret';
}

return [
    'adminEmail' => getenv('ADMIN_EMAIL') ?: 'admin@geolyrics.ge',
    'apiRequestJwtAuth' => [
        'audience' => getenv('API_REQUEST_JWT_AUDIENCE') ?: 'geolyrics-api',
        'headerName' => getenv('API_REQUEST_JWT_HEADER') ?: 'X-GeoLyrics-Request-JWT',
        'issuer' => getenv('API_REQUEST_JWT_ISSUER') ?: 'geovue',
        'secret' => $apiRequestJwtSecret === false ? '' : $apiRequestJwtSecret,
    ],
    'userJwtAuth' => [
        'accessTokenTtl' => (int) (getenv('USER_AUTH_ACCESS_TOKEN_TTL') ?: 900),
        'audience' => getenv('USER_AUTH_JWT_AUDIENCE') ?: 'geolyrics-client',
        'issuer' => getenv('USER_AUTH_JWT_ISSUER') ?: 'geolyrics-api',
        'refreshTokenTtl' => (int) (getenv('USER_AUTH_REFRESH_TOKEN_TTL') ?: 2592000),
        'secret' => $userJwtSecret === false ? '' : $userJwtSecret,
    ],
    'googleAuth' => [
        'certsUrl' => getenv('GOOGLE_AUTH_CERTS_URL') ?: 'https://www.googleapis.com/oauth2/v1/certs',
        'clientIds' => $googleClientIds === false || $googleClientIds === ''
            ? []
            : array_values(array_filter(array_map('trim', explode(',', $googleClientIds)))),
    ],
    'apiHost' => getenv('API_HOST') ?: 'api.geolyrics.ge',
    'apiUrl' => getenv('API_URL') ?: 'http://api.geolyrics.ge',
    'backendHost' => getenv('BACKEND_HOST') ?: 'admin.geolyrics.ge',
    'backendUrl' => getenv('BACKEND_URL') ?: 'http://admin.geolyrics.ge',
    'supportEmail' => getenv('SUPPORT_EMAIL') ?: 'support@geolyrics.ge',
    'senderEmail' => getenv('SENDER_EMAIL') ?: 'noreply@geolyrics.ge',
    'senderName' => getenv('SENDER_NAME') ?: 'GeoLyrics',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 10,
];
