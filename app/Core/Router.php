<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private string $groupPrefix = '';
    private array $groupMiddleware = [];

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function group(string $prefix, array $middleware, callable $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix .= '/' . trim($prefix, '/');
        $this->groupPrefix = rtrim($this->groupPrefix, '/');
        $this->groupMiddleware = array_merge($this->groupMiddleware, $middleware);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        $methodRoutes = $this->routes[$method] ?? [];

        foreach ($methodRoutes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            array_shift($matches);
            $params = [];
            foreach ($route['params'] as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }

            if (!$this->runMiddleware($route['middleware'])) {
                return;
            }

            call_user_func_array($route['handler'], $params);
            return;
        }

        $this->notFound();
    }

    private function add(string $method, string $path, callable|array $handler, array $middleware): void
    {
        $fullPath = ($this->groupPrefix ?: '') . '/' . ltrim($path, '/');
        $fullPath = '/' . trim($fullPath, '/');
        if ($fullPath === '//') {
            $fullPath = '/';
        }

        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $fullPath, $paramMatches);
        $paramNames = $paramMatches[1] ?? [];

        $regex = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $fullPath);
        $regex = '#^' . str_replace('/', '\/', $regex) . '$#';

        $this->routes[$method][] = [
            'handler' => is_array($handler) ? static fn (...$args) => (new $handler[0]())->{$handler[1]}(...$args) : $handler,
            'regex' => $regex,
            'params' => $paramNames,
            'middleware' => array_values(array_unique(array_merge($this->groupMiddleware, $middleware))),
        ];
    }

    private function runMiddleware(array $middleware): bool
    {
        foreach ($middleware as $item) {
            if ($item === 'guest' && (Auth::check() || Auth::hasPartialSession())) {
                header('Location: ' . rtrim((string) app_config('app.url', ''), '/') . '/admin/dashboard');
                return false;
            }

            if ($item === 'auth' && !Auth::check() && !Auth::hasPartialSession()) {
                header('Location: ' . rtrim((string) app_config('app.url', ''), '/') . '/admin/login');
                return false;
            }

            if ($item === 'mfa' && !Auth::isMfaVerified()) {
                Auth::redirectForPendingMfa();
            }
        }

        return true;
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo '<h1>404</h1><p>Page not found.</p>';
    }
}
