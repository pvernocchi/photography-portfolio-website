<?php declare(strict_types=1); ?>
<?php if (($gaId ?? '') !== ''): ?>
<div id="cookie-banner" class="cookie-banner" hidden
     role="region"
     aria-label="<?= e(__('cookie.banner_label')) ?>"
     data-ga-id="<?= e($gaId) ?>">
    <p><?= e(__('cookie.message')) ?></p>
    <div class="cookie-actions">
        <button id="cookie-accept" class="cookie-btn-accept"><?= e(__('cookie.accept')) ?></button>
        <button id="cookie-reject" class="cookie-btn-reject"><?= e(__('cookie.reject')) ?></button>
    </div>
</div>
<?php endif; ?>
