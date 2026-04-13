<?php
declare(strict_types=1);

namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        session_name('vernocchi_session');
        session_start();

        $lifetime = (int) app_config('session.lifetime', 1800);
        $now = time();

        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = $now;
        } elseif (($now - (int) $_SESSION['last_activity']) > $lifetime) {
            self::destroy();
            session_start();
            $_SESSION['last_activity'] = $now;
        } else {
            $_SESSION['last_activity'] = $now;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (!isset($_SESSION['ip_address'])) {
            $_SESSION['ip_address'] = $ip;
        }
        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = $ua;
        }

        if (($_SESSION['ip_address'] ?? '') !== $ip || ($_SESSION['user_agent'] ?? '') !== $ua) {
            self::destroy();
            session_start();
            $_SESSION['ip_address'] = $ip;
            $_SESSION['user_agent'] = $ua;
            $_SESSION['last_activity'] = $now;
        }
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
    }

    public static function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);

        return $message;
    }
}
