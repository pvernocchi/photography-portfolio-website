<?php declare(strict_types=1);
$siteTitle = (string) \App\Models\Setting::get('site_title', 'Vernocchi Photography');
?>
<footer class="front-footer">
    <div class="footer-content">
        <p class="footer-copyright">
            © <?= date('Y') ?> <?= e($siteTitle) ?>. <?= e(__('footer.rights')) ?>
        </p>
        <div class="footer-links">
            <a href="/about"><?= e(__('nav.about')) ?></a>
            <span class="footer-divider">·</span>
            <a href="/gallery"><?= e(__('nav.gallery')) ?></a>
        </div>
    </div>
</footer>
