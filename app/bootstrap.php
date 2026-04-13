<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Language;
use App\Core\Session;

const BASE_PATH = __DIR__ . '/..';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});

$localConfigPath = BASE_PATH . '/config/config.php';
$exampleConfigPath = BASE_PATH . '/config/config.example.php';

if (is_file($localConfigPath)) {
    $GLOBALS['config'] = require $localConfigPath;
} elseif (is_file($exampleConfigPath)) {
    $GLOBALS['config'] = require $exampleConfigPath;
} else {
    throw new RuntimeException('Configuration file is missing.');
}

function app_config(?string $key = null, mixed $default = null): mixed
{
    $config = $GLOBALS['config'] ?? [];

    if ($key === null || $key === '') {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function __(string $key, ?string $locale = null): string
{
    return Language::translate($key, $locale);
}

$debug = (bool) app_config('app.debug', false);
ini_set('display_errors', $debug ? '1' : '0');
error_reporting(E_ALL);

set_exception_handler(static function (Throwable $throwable) use ($debug): void {
    http_response_code(500);

    if ($debug) {
        echo '<pre>' . e((string) $throwable) . '</pre>';
        return;
    }

    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    error_log(
        sprintf("[%s] %s in %s:%d\n%s\n", date('c'), $throwable->getMessage(), $throwable->getFile(), $throwable->getLine(), $throwable->getTraceAsString()),
        3,
        $logDir . '/app.log'
    );

    echo 'An unexpected error occurred. Please try again later.';
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    $convertToException = [
        E_ERROR,
        E_WARNING,
        E_PARSE,
        E_CORE_ERROR,
        E_CORE_WARNING,
        E_COMPILE_ERROR,
        E_COMPILE_WARNING,
        E_USER_ERROR,
        E_USER_WARNING,
        E_RECOVERABLE_ERROR,
    ];

    if (in_array($severity, $convertToException, true)) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    return false;
});

Session::start();
Language::fromCookie();
Auth::checkRememberToken();
