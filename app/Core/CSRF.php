<?php
declare(strict_types=1);

namespace App\Core;

class CSRF
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(self::token()) . '">';
    }

    public static function validate(?string $token): bool
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if ($token === null || $sessionToken === '' || !hash_equals((string) $sessionToken, $token)) {
            return false;
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return true;
    }
}
