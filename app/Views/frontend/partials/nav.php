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
        <a href="/contact"><?= e(__('nav.contact')) ?></a>
        <a href="/lang/es" class="lang-link">
            <img src="/assets/img/flags/es.svg" class="flag-icon" aria-hidden="true" width="18" height="12">
            ES
        </a>
        <a href="/lang/en" class="lang-link">
            <img src="/assets/img/flags/gb.svg" class="flag-icon" aria-hidden="true" width="18" height="12">
            EN
        </a>
        <button class="theme-toggle" type="button" id="theme-toggle" aria-label="Switch to dark mode">🌙</button>
    </div>
</nav>
