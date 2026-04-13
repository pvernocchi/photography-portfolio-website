<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\RememberToken;
use App\Models\User;

class Auth
{
    private const REMEMBER_COOKIE = 'remember_token';

    public static function check(): bool
    {
        return (bool) Session::get('authenticated', false) && Session::get('user_id') !== null;
    }

    public static function isMfaVerified(): bool
    {
        return self::check() && (bool) Session::get('mfa_verified', false);
    }

    public static function pendingMfaUserId(): ?int
    {
        $userId = Session::get('pending_mfa_user_id');
        return $userId !== null ? (int) $userId : null;
    }

    public static function currentUserId(): ?int
    {
        if (self::check()) {
            return (int) Session::get('user_id');
        }

        return self::pendingMfaUserId();
    }

    public static function hasPartialSession(): bool
    {
        return self::pendingMfaUserId() !== null;
    }

    public static function completeLogin(int $userId, bool $remember): void
    {
        Session::regenerate();
        Session::put('user_id', $userId);
        Session::put('authenticated', true);
        Session::put('mfa_verified', true);
        Session::forget('pending_mfa_user_id');

        if ($remember) {
            RememberToken::issue($userId, (int) app_config('session.remember_days', 30));
        }
    }

    public static function checkRememberToken(): void
    {
        if (self::check() || self::hasPartialSession()) {
            return;
        }

        $token = $_COOKIE[self::REMEMBER_COOKIE] ?? null;
        if ($token === null || $token === '') {
            return;
        }

        $userId = RememberToken::validateToken($token);
        if ($userId === null) {
            return;
        }

        Session::put('pending_mfa_user_id', $userId);
        Session::put('pending_remember', true);
    }

    public static function redirectForPendingMfa(): never
    {
        $userId = self::pendingMfaUserId();
        if ($userId === null) {
            header('Location: ' . rtrim((string) app_config('app.url', ''), '/') . '/admin/login');
            exit;
        }

        $user = User::findById($userId);
        $path = (!empty($user['mfa_enabled'])) ? '/admin/mfa/verify' : '/admin/mfa/setup';

        header('Location: ' . rtrim((string) app_config('app.url', ''), '/') . $path);
        exit;
    }

    public static function logout(): void
    {
        $token = $_COOKIE[self::REMEMBER_COOKIE] ?? null;
        if ($token) {
            RememberToken::revokePlainToken($token);
            setcookie(self::REMEMBER_COOKIE, '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }

        Session::destroy();
    }
}
