<?php
declare(strict_types=1);
$stats = $stats ?? [];
?>
<header class="page-header">
    <h1>Dashboard</h1>
    <p>Welcome back, <?= e($user['username'] ?? 'admin') ?></p>
</header>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<section class="cards-grid">
    <article class="stat-card">
        <div class="stat-icon stat-icon-blue">📁</div>
        <h2>Categories</h2>
        <p><?= (int) ($stats['categories'] ?? 0) ?></p>
    </article>
    <article class="stat-card">
        <div class="stat-icon stat-icon-green">🖼️</div>
        <h2>Images</h2>
        <p><?= (int) ($stats['images'] ?? 0) ?></p>
    </article>
    <article class="stat-card">
        <div class="stat-icon stat-icon-purple">💾</div>
        <h2>Storage</h2>
        <p><?= number_format(((int) ($stats['storage'] ?? 0)) / 1024 / 1024, 2) ?> MB</p>
    </article>
    <article class="stat-card">
        <div class="stat-icon stat-icon-amber">🎨</div>
        <h2>Theme</h2>
        <p><?= e((string) ($stats['theme'] ?? 'minimal-light')) ?></p>
    </article>
    <article class="stat-card">
        <div class="stat-icon stat-icon-rose">🔐</div>
        <h2>MFA Status</h2>
        <p><?= e((string) ($stats['mfa'] ?? 'Disabled')) ?></p>
    </article>
</section>
