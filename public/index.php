<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\FrontendController;
use App\Controllers\ImageController;
use App\Controllers\ImageServeController;
use App\Controllers\SettingsController;
use App\Controllers\SitemapController;
use App\Core\Router;

$router = new Router();

$router->get('/', [FrontendController::class, 'home']);
$router->get('/gallery', [FrontendController::class, 'gallery']);
$router->get('/gallery/{slug}', [FrontendController::class, 'category']);
$router->get('/about', [FrontendController::class, 'about']);
$router->get('/contact', [FrontendController::class, 'contact']);
$router->post('/contact/send', [FrontendController::class, 'sendContact']);
$router->get('/lang/{locale}', [FrontendController::class, 'switchLanguage']);
$router->get('/sitemap.xml', [SitemapController::class, 'index']);
$router->get('/theme/style.css', static function (): void {
    (new FrontendController())->themeCss('style');
});
$router->get('/theme/dark.css', static function (): void {
    (new FrontendController())->themeCss('dark');
});

$router->get('/image/thumb/{id}', [ImageServeController::class, 'thumbnail']);
$router->get('/image/display/{id}', [ImageServeController::class, 'display']);

$router->group('/admin', [], static function (Router $router): void {
    $router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
    $router->post('/login', [AuthController::class, 'login'], ['guest']);

    $router->get('/mfa/setup', [AuthController::class, 'showMfaSetup'], ['auth']);
    $router->post('/mfa/setup', [AuthController::class, 'setupMfa'], ['auth']);

    $router->get('/mfa/verify', [AuthController::class, 'showMfaVerify'], ['auth']);
    $router->post('/mfa/verify', [AuthController::class, 'verifyMfa'], ['auth']);

    $router->get('/logout', [AuthController::class, 'logout'], ['auth']);

    $router->group('', ['auth', 'mfa'], static function (Router $router): void {
        $router->get('/dashboard', [AdminController::class, 'index']);

        $router->get('/categories', [CategoryController::class, 'index']);
        $router->get('/categories/create', [CategoryController::class, 'create']);
        $router->post('/categories/store', [CategoryController::class, 'store']);
        $router->get('/categories/{id}/edit', [CategoryController::class, 'edit']);
        $router->post('/categories/{id}/update', [CategoryController::class, 'update']);
        $router->post('/categories/{id}/delete', [CategoryController::class, 'delete']);
        $router->post('/categories/reorder', [CategoryController::class, 'reorder']);

        $router->get('/images', [ImageController::class, 'library']);
        $router->get('/images/upload', [ImageController::class, 'showUpload']);
        $router->post('/images/upload', [ImageController::class, 'upload']);
        $router->get('/images/assign', [ImageController::class, 'showAssign']);
        $router->post('/images/assign', [ImageController::class, 'saveAssign']);
        $router->get('/categories/{id}/images', [ImageController::class, 'index']);
        $router->post('/categories/{id}/images/reorder', [ImageController::class, 'reorder']);
        $router->post('/categories/{id}/images/set-cover', [ImageController::class, 'setCover']);
        $router->get('/images/{id}/edit', [ImageController::class, 'edit']);
        $router->post('/images/{id}/update', [ImageController::class, 'update']);
        $router->post('/images/{id}/delete', [ImageController::class, 'delete']);
        $router->post('/images/bulk-action', [ImageController::class, 'bulkAction']);

        $router->get('/settings', [SettingsController::class, 'index']);
        $router->post('/settings/general', [SettingsController::class, 'updateGeneral']);
        $router->post('/settings/security', [SettingsController::class, 'updateSecurity']);
        $router->post('/settings/theme', [SettingsController::class, 'updateTheme']);
        $router->post('/settings/about', [SettingsController::class, 'updateAbout']);
        $router->post('/settings/watermark', [SettingsController::class, 'updateWatermark']);
        $router->post('/settings/analytics', [SettingsController::class, 'updateAnalytics']);
        $router->post('/settings/seo', [SettingsController::class, 'updateSeo']);
        $router->post('/settings/contact', [SettingsController::class, 'updateContact']);

        $router->get('/settings/password', [AuthController::class, 'showChangePassword']);
        $router->post('/settings/password', [AuthController::class, 'changePassword']);
    });
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
