<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function render(string $view, array $data = [], ?string $layout = 'admin'): void
    {
        extract($data, EXTR_SKIP);

        $viewPath = $layout === 'frontend'
            ? ThemeEngine::resolveTemplate($view)
            : BASE_PATH . '/app/Views/' . $view . '.php';
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

    /**
     * Send a JSON response and exit.
     *
     * @param array<string, mixed> $data
     */
    protected function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Send a JSON error response and exit.
     */
    protected function jsonError(string $message, int $status = 400): never
    {
        $this->json(['ok' => false, 'error' => $message], $status);
    }

    /**
     * Parse the raw request body as JSON and return the decoded array.
     * Returns an empty array if the body is missing or not valid JSON.
     *
     * @return array<string, mixed>
     */
    protected function jsonInput(): array
    {
        $raw  = (string) file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
