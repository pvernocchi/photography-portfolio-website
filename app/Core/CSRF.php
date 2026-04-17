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

    /**
     * Validate the CSRF token without rotating it.
     * Use for AJAX/JSON endpoints where the page is not reloaded between requests
     * (e.g. the two-step WebAuthn challenge/response flow).
     */
    public static function check(?string $token): bool
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if ($token === null || $sessionToken === '' || !hash_equals((string) $sessionToken, $token)) {
            return false;
        }

        return true;
    }
}
