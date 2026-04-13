<?php declare(strict_types=1); ?>
<section class="card-front">
    <h1><?= e(__('about.title')) ?></h1>
    <?php
    $safeAboutContent = strip_tags((string) $aboutContent, '<p><br><strong><em><ul><ol><li><a><h2><h3><blockquote>');
    echo $safeAboutContent;
    ?>
</section>
