<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\CSRF;

$appName = (string) app_config('app.name', 'Vernocchi Photography');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? $appName) ?> · <?= e($appName) ?></title>
    <meta name="csrf-token" content="<?= e(CSRF::token()) ?>">
    <link rel="icon" type="image/svg+xml" href="https://www.vernocchi.es/Shutter-Icon-2.svg">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="brand"><?= e($appName) ?></div>
        <nav>
            <a href="/admin/dashboard">Dashboard</a>
            <a href="/admin/categories">Categories</a>
            <a href="/admin/images">Images</a>
            <a href="/admin/settings">Settings</a>
            <a href="/admin/settings/password">Password</a>
            <a href="/admin/mfa/webauthn/setup">Security Keys</a>
            <?php if (Auth::check() || Auth::hasPartialSession()): ?>
                <a href="/admin/logout">Logout</a>
            <?php endif; ?>
        </nav>
    </aside>
    <main class="admin-main">
        <?= $content ?>
    </main>
</div>
<script src="/assets/js/admin.js" defer></script>
</body>
</html>
