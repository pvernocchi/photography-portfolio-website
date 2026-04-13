<?php declare(strict_types=1); ?>
<header class="page-header"><h1><?= e($locale === 'en' ? $category['name_en'] : $category['name_es']) ?></h1></header>
<section class="gallery-grid protected-gallery" data-protected="1">
<?php foreach ($images as $index => $image): ?>
    <article class="gallery-card image-item" data-lightbox-index="<?= (int) $index ?>" data-display-src="/image/display/<?= (int) $image['id'] ?>" data-alt="<?= e((string) (($locale === 'en' ? $image['alt_en'] : $image['alt_es']) ?: 'Photo')) ?>">
        <canvas class="protected-canvas" width="400" height="260"></canvas>
        <img src="/image/thumb/<?= (int) $image['id'] ?>" alt="<?= e((string) (($locale === 'en' ? $image['alt_en'] : $image['alt_es']) ?: 'Photo')) ?>" draggable="false" class="source-image" loading="lazy">
    </article>
<?php endforeach; ?>
</section>
<?php include BASE_PATH . '/app/Views/frontend/partials/lightbox.php'; ?>
