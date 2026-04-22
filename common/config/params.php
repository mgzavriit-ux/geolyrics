<?php

declare(strict_types=1);

return [
    'adminEmail' => getenv('ADMIN_EMAIL') ?: 'admin@geolyrics.ge',
    'apiHost' => getenv('API_HOST') ?: 'api.geolyrics.ge',
    'apiUrl' => getenv('API_URL') ?: 'https://api.geolyrics.ge',
    'backendHost' => getenv('BACKEND_HOST') ?: 'admin.geolyrics.ge',
    'backendUrl' => getenv('BACKEND_URL') ?: 'https://admin.geolyrics.ge',
    'supportEmail' => getenv('SUPPORT_EMAIL') ?: 'support@geolyrics.ge',
    'senderEmail' => getenv('SENDER_EMAIL') ?: 'noreply@geolyrics.ge',
    'senderName' => getenv('SENDER_NAME') ?: 'GeoLyrics',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 10,
];
