<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\CSRF;

$appName = (string) app_config('app.name', 'Vernocchi Photography');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$username = '';
if (Auth::check()) {
    $user = Auth::user();
    $username = (string) ($user['username'] ?? '');
}
$initials = $username !== '' ? mb_strtoupper(mb_substr($username, 0, 2)) : 'AD';

function adminNavActive(string $prefix, string $current): string {
    return str_starts_with($current, $prefix) ? ' active' : '';
}
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- Top Navigation Bar -->
<header class="admin-topbar">
    <a class="topbar-brand" href="/admin/dashboard">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
            <circle cx="12" cy="13" r="3"/>
        </svg>
        <span><?= e($appName) ?></span>
    </a>

    <nav class="topbar-nav" aria-label="Admin navigation">
        <a href="/admin/dashboard" class="<?= adminNavActive('/admin/dashboard', $currentPath) ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
        <a href="/admin/categories" class="<?= adminNavActive('/admin/categories', $currentPath) ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            Galleries
        </a>
        <a href="/admin/images" class="<?= adminNavActive('/admin/images', $currentPath) ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            Images
        </a>
        <a href="/admin/settings" class="<?= adminNavActive('/admin/settings', $currentPath) ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Settings
        </a>
    </nav>

    <div class="topbar-right">
        <?php if ($username !== ''): ?>
        <div class="topbar-user">
            <div class="topbar-avatar" aria-hidden="true"><?= e($initials) ?></div>
            <span class="topbar-username"><?= e($username) ?></span>
        </div>
        <?php endif; ?>
        <?php if (Auth::check() || Auth::hasPartialSession()): ?>
        <a href="/admin/logout" class="topbar-signout">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign out
        </a>
        <?php endif; ?>
        <button class="topbar-hamburger" id="nav-hamburger" aria-label="Open navigation" aria-expanded="false" aria-controls="nav-drawer">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<div class="admin-topbar-accent" aria-hidden="true"></div>

<!-- Mobile Nav Drawer -->
<div class="admin-nav-drawer" id="nav-drawer" role="dialog" aria-label="Navigation" aria-modal="true">
    <div class="admin-nav-drawer-overlay" id="nav-drawer-overlay"></div>
    <nav class="admin-nav-drawer-panel">
        <button class="admin-nav-drawer-close" id="nav-drawer-close" aria-label="Close navigation">×</button>
        <div class="admin-nav-drawer-brand">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                <circle cx="12" cy="13" r="3"/>
            </svg>
            <?= e($appName) ?>
        </div>
        <a href="/admin/dashboard" class="<?= adminNavActive('/admin/dashboard', $currentPath) ?>">Dashboard</a>
        <a href="/admin/categories" class="<?= adminNavActive('/admin/categories', $currentPath) ?>">Galleries</a>
        <a href="/admin/images" class="<?= adminNavActive('/admin/images', $currentPath) ?>">Images</a>
        <a href="/admin/settings" class="<?= adminNavActive('/admin/settings', $currentPath) ?>">Settings</a>
        <div class="admin-nav-drawer-divider"></div>
        <?php if (Auth::check() || Auth::hasPartialSession()): ?>
            <a href="/admin/logout">Sign out</a>
        <?php endif; ?>
    </nav>
</div>

<div class="admin-shell">
    <main class="admin-main">
        <?= $content ?>
    </main>
</div>

<script src="/assets/js/admin.js" defer></script>
</body>
</html>
