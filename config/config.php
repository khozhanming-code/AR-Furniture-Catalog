<?php

declare(strict_types=1);

/**
 * Central application configuration.
 *
 * Reads settings from the .env file in the project root.
 * No Composer or external package is required.
 */

$envFile = dirname(__DIR__) . '/.env';

$env = is_file($envFile)
    ? parse_ini_file($envFile, false, INI_SCANNER_RAW)
    : [];

if (!is_array($env)) {
    $env = [];
}

/**
 * Server environment variables take priority over the .env file.
 */
$envValue = static function (string $key, string $default = '') use ($env): string {
    $serverValue = getenv($key);

    if ($serverValue !== false) {
        return (string) $serverValue;
    }

    return isset($env[$key])
        ? (string) $env[$key]
        : $default;
};

return [
    'app' => [
        'name' => $envValue('APP_NAME', 'AR Furniture Catalog'),

        // Leave blank to detect the website URL automatically.
        'base_url' => $envValue('APP_BASE_URL'),

        'timezone' => $envValue('APP_TIMEZONE', 'Asia/Kuala_Lumpur'),
        'default_currency' => 'RM',
    ],

    'db' => [
        'host' => $envValue('DB_HOST', 'localhost'),
        'name' => $envValue('DB_NAME', 'ar_furniture'),
        'user' => $envValue('DB_USER', 'root'),
        'pass' => $envValue('DB_PASS'),
        'charset' => 'utf8mb4',
    ],

    'uploads' => [
        'models_dir' => 'models',
        'thumbs_dir' => 'uploads/thumbs',

        'max_model_mb' => max(1, (int) $envValue('MAX_MODEL_MB', '100')),
        'max_image_mb' => max(1, (int) $envValue('MAX_IMAGE_MB', '5')),

        'allowed_model_ext' => ['glb', 'gltf', 'usdz'],
        'allowed_image_ext' => ['jpg', 'jpeg', 'png', 'webp'],
    ],
];