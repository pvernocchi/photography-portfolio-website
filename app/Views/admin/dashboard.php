<?php
declare(strict_types=1);
?>
<header class="page-header">
    <h1>Welcome, <?= e($user['username'] ?? 'admin') ?></h1>
</header>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<section class="cards-grid">
    <article class="card">
        <h2>Categories</h2>
        <p>Phase 2 placeholder for category management.</p>
    </article>
    <article class="card">
        <h2>Images</h2>
        <p>Phase 2 placeholder for image uploads and ordering.</p>
    </article>
    <article class="card">
        <h2>Settings</h2>
        <p>Phase 4 placeholder for site and theme settings.</p>
    </article>
</section>

<section class="card mt-24">
    <h2>Stats (Placeholder)</h2>
    <ul>
        <li>Total categories: —</li>
        <li>Total images: —</li>
        <li>Last login: —</li>
    </ul>
</section>
