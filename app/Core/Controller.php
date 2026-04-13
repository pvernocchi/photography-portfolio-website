<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function render(string $view, array $data = [], ?string $layout = 'admin'): void
    {
        extract($data, EXTR_SKIP);

        $viewPath = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!is_file($viewPath)) {
            http_response_code(500);
            echo 'View not found.';
            return;
        }

        ob_start();
        include $viewPath;
        $content = (string) ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutPath = BASE_PATH . '/app/Views/layouts/' . $layout . '.php';
        if (!is_file($layoutPath)) {
            echo $content;
            return;
        }

        include $layoutPath;
    }

    protected function redirect(string $path): never
    {
        $url = rtrim((string) app_config('app.url', ''), '/');
        header('Location: ' . $url . $path);
        exit;
    }
}
