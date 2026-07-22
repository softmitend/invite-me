<?php

$storagePath = '/tmp/inviteme-storage';

foreach ([
    $storagePath,
    $storagePath.'/app',
    $storagePath.'/app/public',
    $storagePath.'/framework',
    $storagePath.'/framework/cache',
    $storagePath.'/framework/cache/data',
    $storagePath.'/framework/sessions',
    $storagePath.'/framework/testing',
    $storagePath.'/framework/views',
    $storagePath.'/logs',
] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}

foreach ([
    'LARAVEL_STORAGE_PATH' => $storagePath,
    'VIEW_COMPILED_PATH' => $storagePath.'/framework/views',
    'APP_CONFIG_CACHE' => $storagePath.'/framework/cache/config.php',
    'APP_EVENTS_CACHE' => $storagePath.'/framework/cache/events.php',
    'APP_PACKAGES_CACHE' => $storagePath.'/framework/cache/packages.php',
    'APP_ROUTES_CACHE' => $storagePath.'/framework/cache/routes.php',
    'APP_SERVICES_CACHE' => $storagePath.'/framework/cache/services.php',
    'LOG_CHANNEL' => 'stderr',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'cookie',
    'QUEUE_CONNECTION' => 'sync',
] as $key => $value) {
    if (getenv($key) === false) {
        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
