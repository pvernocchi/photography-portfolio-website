<?php declare(strict_types=1); ?>
<header class="page-header"><h1><?= e(__('gallery.title')) ?></h1></header>
<section class="gallery-grid">
<?php foreach ($categories as $i => $category): ?>
    <a class="gallery-card" href="/gallery/<?= e($category['slug']) ?>">
        <?php if (!empty($category['cover_image_ref'])): ?><img src="/image/thumb/<?= (int) $category['cover_image_ref'] ?>" alt="cover" draggable="false" <?= $i === 0 ? 'fetchpriority="high"' : '' ?>><?php endif; ?>
        <h2><?= e($locale === 'en' ? $category['name_en'] : $category['name_es']) ?></h2>
    </a>
<?php endforeach; ?>
<?php if (empty($categories)): ?><p><?= e(__('gallery.empty')) ?></p><?php endif; ?>
</section>
