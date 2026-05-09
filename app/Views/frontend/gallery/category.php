<?php
declare(strict_types=1);
use App\Core\CSRF;
?>
<header class="page-header"><h1><?= e($locale === 'en' ? $category['name_en'] : $category['name_es']) ?></h1></header>
<?php if (!empty($isPrivateLocked)): ?>
    <form method="post" action="/private-gallery/<?= e($category['slug']) ?>/unlock" class="contact-form private-gallery-form">
        <?= CSRF::field() ?>
        <?php if (!empty($privateError)): ?>
            <div class="contact-status error"><?= e($privateError) ?></div>
        <?php endif; ?>
        <div class="form-group">
            <label class="form-label" for="private-password-input"><?= e(__('gallery.private_password_label')) ?></label>
            <input class="form-input" id="private-password-input" type="password" name="private_password" minlength="8" required autocomplete="current-password">
        </div>
        <div class="form-actions">
            <button class="btn-front" type="submit"><?= e(__('gallery.unlock')) ?></button>
        </div>
    </form>
<?php else: ?>
    <section class="gallery-grid protected-gallery" data-protected="1">
    <?php foreach ($images as $index => $image): ?>
        <article class="gallery-card image-item" data-lightbox-index="<?= (int) $index ?>" data-display-src="/image/display/<?= (int) $image['id'] ?>" data-alt="<?= e((string) (($locale === 'en' ? $image['alt_en'] : $image['alt_es']) ?: 'Photo')) ?>">
            <canvas class="protected-canvas" width="400" height="260"></canvas>
            <img src="/image/thumb/<?= (int) $image['id'] ?>" alt="<?= e((string) (($locale === 'en' ? $image['alt_en'] : $image['alt_es']) ?: 'Photo')) ?>" draggable="false" class="gallery-source-image" <?= $index < 4 ? 'fetchpriority="high"' : '' ?>>
        </article>
    <?php endforeach; ?>
    </section>
    <?php if (!empty($allowOriginalDownload) && !empty($images)): ?>
        <section class="private-downloads">
            <h2><?= e(__('gallery.download_original')) ?></h2>
            <ul>
                <?php foreach ($images as $image): ?>
                    <li>
                        <a href="/private-gallery/<?= e($category['slug']) ?>/download/<?= (int) $image['id'] ?>">
                            <?= e((string) (($locale === 'en' ? $image['title_en'] : $image['title_es']) ?: $image['original_filename'])) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
    <?php include BASE_PATH . '/app/Views/frontend/partials/lightbox.php'; ?>
<?php endif; ?>
