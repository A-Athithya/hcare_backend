<?php
return [
    'app_name' => 'Hcare API',
    'base_url' => getenv('BASE_URL'),
    'security' => [
        'aes_key' => getenv('AES_KEY') ?: ($_ENV['AES_KEY'] ?? ($_SERVER['AES_KEY'] ?? '')),
        'jwt_secret' => getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? ($_SERVER['JWT_SECRET'] ?? '')),
        'jwt_expiry' => getenv('JWT_EXPIRY') ?: ($_ENV['JWT_EXPIRY'] ?? ($_SERVER['JWT_EXPIRY'] ?? 900)),
        'refresh_token_expire' => getenv('REFRESH_TOKEN_EXPIRY') ?: ($_ENV['REFRESH_TOKEN_EXPIRY'] ?? ($_SERVER['REFRESH_TOKEN_EXPIRY'] ?? 604800))
    ]
];
