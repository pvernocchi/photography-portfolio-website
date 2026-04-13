<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Core\Router;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);

$router->group('/admin', [], static function (Router $router): void {
    $router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
    $router->post('/login', [AuthController::class, 'login'], ['guest']);

    $router->get('/mfa/setup', [AuthController::class, 'showMfaSetup'], ['auth']);
    $router->post('/mfa/setup', [AuthController::class, 'setupMfa'], ['auth']);

    $router->get('/mfa/verify', [AuthController::class, 'showMfaVerify'], ['auth']);
    $router->post('/mfa/verify', [AuthController::class, 'verifyMfa'], ['auth']);

    $router->get('/logout', [AuthController::class, 'logout'], ['auth']);
    $router->get('/dashboard', [AdminController::class, 'index'], ['auth', 'mfa']);
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
