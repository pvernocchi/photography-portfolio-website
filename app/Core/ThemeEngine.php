<?php
declare(strict_types=1);

namespace App\Core;

class ThemeEngine
{
    public static function activeTheme(): string
    {
        $theme = \App\Models\Setting::get('active_theme', 'minimal-light');
        $theme = is_string($theme) ? trim($theme) : 'minimal-light';

        return self::exists($theme) ? $theme : 'minimal-light';
    }

    public static function exists(string $theme): bool
    {
        return is_dir(BASE_PATH . '/themes/' . $theme);
    }

    public static function themeMeta(string $theme): array
    {
        $path = BASE_PATH . '/themes/' . $theme . '/theme.json';
        if (!is_file($path)) {
            return [];
        }

        $meta = json_decode((string) file_get_contents($path), true);
        return is_array($meta) ? $meta : [];
    }

    public static function themes(): array
    {
        $themes = [];
        $base = BASE_PATH . '/themes';
        if (!is_dir($base)) {
            return $themes;
        }

        foreach (scandir($base) ?: [] as $dir) {
            if ($dir === '.' || $dir === '..' || !is_dir($base . '/' . $dir)) {
                continue;
            }
            $themes[$dir] = self::themeMeta($dir);
        }

        return $themes;
    }

    public static function resolveTemplate(string $view): string
    {
        $theme = self::activeTheme();
        $themePath = BASE_PATH . '/themes/' . $theme . '/templates/' . $view . '.php';
        if (is_file($themePath)) {
            return $themePath;
        }

        return BASE_PATH . '/app/Views/' . $view . '.php';
    }

    public static function cssPath(string $kind = 'style'): ?string
    {
        $theme = self::activeTheme();
        $path = BASE_PATH . '/themes/' . $theme . '/css/' . $kind . '.css';
        return is_file($path) ? $path : null;
    }
}
