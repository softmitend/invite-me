<?php

header('content-type: application/json');

$autoload = __DIR__.'/../vendor/autoload.php';
$lock = __DIR__.'/../composer.lock';
$packages = is_file($lock) ? json_decode((string) file_get_contents($lock), true) : [];
$symfonyVersion = null;

foreach (($packages['packages'] ?? []) as $package) {
    if (($package['name'] ?? null) === 'symfony/http-foundation') {
        $symfonyVersion = $package['version'] ?? null;
        break;
    }
}

echo json_encode([
    'ok' => true,
    'php_version' => PHP_VERSION,
    'sapi' => PHP_SAPI,
    'autoload_exists' => is_file($autoload),
    'symfony_http_foundation' => $symfonyVersion,
    'app_key_present' => (bool) getenv('APP_KEY'),
    'app_env' => getenv('APP_ENV') ?: null,
    'app_debug' => getenv('APP_DEBUG') ?: null,
    'db_connection' => getenv('DB_CONNECTION') ?: null,
    'db_url_present' => (bool) getenv('DB_URL'),
    'db_host_present' => (bool) getenv('DB_HOST'),
], JSON_PRETTY_PRINT);
