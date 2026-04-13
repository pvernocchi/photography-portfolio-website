<?php
declare(strict_types=1);

namespace App\Core;

class Language
{
    private const SUPPORTED = ['es', 'en'];
    private static array $translations = [];
    private static ?string $locale = null;

    public static function locale(): string
    {
        if (self::$locale !== null) {
            return self::$locale;
        }

        $sessionLocale = Session::get('locale');
        if (is_string($sessionLocale) && in_array($sessionLocale, self::SUPPORTED, true)) {
            self::$locale = $sessionLocale;
            return self::$locale;
        }

        $default = (string) app_config('app.default_language', 'es');
        try {
            $settingDefault = \App\Models\Setting::get('default_language', $default);
            if (is_string($settingDefault) && $settingDefault !== '') {
                $default = $settingDefault;
            }
        } catch (\Throwable) {
            // Settings table may not be available during initial bootstrap.
        }
        self::$locale = in_array($default, self::SUPPORTED, true) ? $default : 'es';
        Session::put('locale', self::$locale);

        return self::$locale;
    }

    public static function setLocale(string $locale): void
    {
        if (!in_array($locale, self::SUPPORTED, true)) {
            return;
        }

        self::$locale = $locale;
        Session::put('locale', $locale);
        setcookie('site_locale', $locale, [
            'expires' => time() + 31536000,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function fromCookie(): void
    {
        if (self::$locale !== null || Session::get('locale') !== null) {
            return;
        }

        $cookieLocale = $_COOKIE['site_locale'] ?? '';
        if (is_string($cookieLocale) && in_array($cookieLocale, self::SUPPORTED, true)) {
            self::setLocale($cookieLocale);
        }
    }

    public static function translate(string $key, ?string $locale = null): string
    {
        $activeLocale = $locale ?? self::locale();
        if (!isset(self::$translations[$activeLocale])) {
            $path = BASE_PATH . '/app/Languages/' . $activeLocale . '.php';
            self::$translations[$activeLocale] = is_file($path) ? (array) require $path : [];
        }

        return (string) (self::$translations[$activeLocale][$key] ?? $key);
    }

    public static function supported(): array
    {
        return self::SUPPORTED;
    }
}
