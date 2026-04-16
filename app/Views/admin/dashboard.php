<?php
declare(strict_types=1);
$stats = $stats ?? [];
$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$dateStr = $now->format('l, F j, Y');
?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="dashboard-banner">
    <p class="dashboard-banner-label">Admin Dashboard</p>
    <h1>Welcome back, <?= e($user['username'] ?? 'admin') ?></h1>
    <p class="dashboard-banner-date"><?= e($dateStr) ?></p>
</div>

<section class="cards-grid">

    <article class="stat-card">
        <svg class="stat-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        <div class="stat-card-value"><?= (int) ($stats['categories'] ?? 0) ?></div>
        <div class="stat-card-label">Galleries</div>
    </article>

    <article class="stat-card">
        <svg class="stat-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        <div class="stat-card-value"><?= (int) ($stats['images'] ?? 0) ?></div>
        <div class="stat-card-label">Images</div>
    </article>

    <article class="stat-card">
        <svg class="stat-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
        <div class="stat-card-value"><?= number_format(((int) ($stats['storage'] ?? 0)) / 1024 / 1024, 1) ?> <small style="font-size:1rem;font-weight:400;color:var(--muted)">MB</small></div>
        <div class="stat-card-label">Storage used</div>
    </article>

    <article class="stat-card">
        <svg class="stat-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 12l2.5 2.5L16 9"/></svg>
        <div class="stat-card-value" style="font-size:1.1rem;line-height:1.4"><?= e((string) ($stats['theme'] ?? 'minimal-light')) ?></div>
        <div class="stat-card-label">Active theme</div>
    </article>

    <article class="stat-card">
        <svg class="stat-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        <div class="stat-card-value" style="font-size:1.1rem;line-height:1.4;color:<?= ($stats['mfa'] ?? 'Disabled') === 'Disabled' ? 'var(--muted)' : 'var(--success)' ?>"><?= e((string) ($stats['mfa'] ?? 'Disabled')) ?></div>
        <div class="stat-card-label">MFA status</div>
    </article>

</section>
