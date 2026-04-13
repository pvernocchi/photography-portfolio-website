<?php declare(strict_types=1);
$siteTitle = (string) \App\Models\Setting::get('site_title', 'Vernocchi Photography');
$navCategories = \App\Models\Category::visible();
?>
<nav class="front-nav">
    <a href="/" class="brand"><?= e($siteTitle) ?></a>
    <button class="menu-toggle" type="button" aria-label="menu">☰</button>
    <div class="menu-links">
        <div class="nav-dropdown">
            <a href="/gallery" class="nav-dropdown-toggle"><?= e(__('nav.gallery')) ?></a>
            <?php if (!empty($navCategories)): ?>
            <div class="nav-dropdown-menu">
                <?php foreach ($navCategories as $cat): ?>
                    <a href="/gallery/<?= e($cat['slug']) ?>"><?= e(\App\Core\Language::locale() === 'en' && ($cat['name_en'] ?? '') !== '' ? $cat['name_en'] : $cat['name_es']) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <a href="/about"><?= e(__('nav.about')) ?></a>
        <a href="/lang/es">ES 🇪🇸</a>
        <a href="/lang/en">EN 🇬🇧</a>
        <button class="theme-toggle" type="button" aria-label="Toggle dark mode" title="Toggle dark mode">🌙</button>
    </div>
</nav>
