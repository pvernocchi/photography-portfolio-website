<?php
declare(strict_types=1);
$stats = $stats ?? [];
?>
<header class="page-header">
    <h1>Welcome, <?= e($user['username'] ?? 'admin') ?></h1>
</header>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<section class="cards-grid">
    <article class="card"><h2>Total categories</h2><p><?= (int) ($stats['categories'] ?? 0) ?></p></article>
    <article class="card"><h2>Total images</h2><p><?= (int) ($stats['images'] ?? 0) ?></p></article>
    <article class="card"><h2>Total storage</h2><p><?= number_format(((int) ($stats['storage'] ?? 0)) / 1024 / 1024, 2) ?> MB</p></article>
    <article class="card"><h2>Current theme</h2><p><?= e((string) ($stats['theme'] ?? 'minimal-light')) ?></p></article>
    <article class="card"><h2>MFA status</h2><p><?= e((string) ($stats['mfa'] ?? 'Disabled')) ?></p></article>
</section>
