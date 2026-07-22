<?php

require __DIR__.'/vercel_bootstrap.php';

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

$result = [
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
    'laravel_boot' => false,
    'encrypter_ok' => false,
    'db_ok' => false,
    'home_queries_ok' => false,
    'vite_manifest_exists' => is_file(__DIR__.'/../public/build/manifest.json'),
    'home_render_ok' => false,
    'home_render_status' => null,
];

try {
    if (is_file($autoload)) {
        require_once $autoload;
        $app = require __DIR__.'/../bootstrap/app.php';

        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        $result['laravel_boot'] = true;

        try {
            $app->make('encrypter');
            $result['encrypter_ok'] = true;
        } catch (Throwable $exception) {
            $result['encrypter_error'] = $exception::class.': '.$exception->getMessage();
        }

        try {
            $app->make('db')->connection()->select('select 1 as ok');
            $result['db_ok'] = true;
        } catch (Throwable $exception) {
            $result['db_error'] = $exception::class.': '.$exception->getMessage();
        }

        try {
            App\Models\Category::where('is_active', true)->count();
            App\Models\Catalog::where('is_active', true)->count();
            App\Models\Review::where('is_visible', true)->count();
            $result['home_queries_ok'] = true;
        } catch (Throwable $exception) {
            $result['home_queries_error'] = $exception::class.': '.$exception->getMessage();
        }

        try {
            $request = Illuminate\Http\Request::create('/', 'GET');
            $response = $app->handle($request);
            $result['home_render_status'] = $response->getStatusCode();
            $result['home_render_ok'] = $response->getStatusCode() < 500;
        } catch (Throwable $exception) {
            $result['home_render_error'] = $exception::class.': '.$exception->getMessage();
        }
    }
} catch (Throwable $exception) {
    $result['laravel_error'] = $exception::class.': '.$exception->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT);
