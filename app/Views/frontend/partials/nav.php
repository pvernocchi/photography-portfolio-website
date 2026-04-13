<?php declare(strict_types=1); $siteTitle = (string) \App\Models\Setting::get('site_title', 'Vernocchi Photography'); ?>
<nav class="front-nav">
    <a href="/" class="brand"><?= e($siteTitle) ?></a>
    <button class="menu-toggle" type="button" aria-label="menu">☰</button>
    <div class="menu-links">
        <a href="/gallery"><?= e(__('nav.gallery')) ?></a>
        <a href="/contact"><?= e(__('nav.contact')) ?></a>
        <a href="/about"><?= e(__('nav.about')) ?></a>
        <a href="/lang/es">ES 🇪🇸</a>
        <a href="/lang/en">EN 🇬🇧</a>
    </div>
</nav>
