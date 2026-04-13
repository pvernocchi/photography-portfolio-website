<?php declare(strict_types=1); ?>
<section class="hero"><h1><?= e(__('home.welcome')) ?></h1><a class="btn-front" href="/gallery"><?= e(__('home.explore')) ?></a></section>
<section class="gallery-grid">
<?php foreach ($categories as $category): ?>
    <a class="gallery-card" href="/gallery/<?= e($category['slug']) ?>">
        <?php if (!empty($category['cover_image_ref'])): ?><img src="/image/thumb/<?= (int) $category['cover_image_ref'] ?>" alt="cover" draggable="false"><?php endif; ?>
        <h2><?= e($locale === 'en' ? $category['name_en'] : $category['name_es']) ?></h2>
    </a>
<?php endforeach; ?>
</section>
