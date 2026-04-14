<?php
declare(strict_types=1);
use App\Core\CSRF;
?>
<header class="page-header row-between">
    <h1>Image Library</h1>
    <a class="btn btn-primary" href="/admin/images/upload">Upload images</a>
</header>
<?php if (!empty($success)): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if (empty($images)): ?>
<div class="card"><p>No images yet. <a href="/admin/images/upload">Upload your first photos</a></p></div>
<?php else: ?>
<div class="image-grid">
    <?php foreach ($images as $image): ?>
    <article class="card image-card">
        <img src="/image/thumb/<?= (int) $image['id'] ?>" alt="<?= e($image['original_filename']) ?>">
        <p class="muted image-card-name"><?= e($image['original_filename']) ?></p>
        <div class="actions">
            <a href="/admin/images/<?= (int) $image['id'] ?>/edit">Edit</a>
            <form method="post" action="/admin/images/<?= (int) $image['id'] ?>/delete" onsubmit="return confirm('Delete this image from all galleries?')">
                <?= CSRF::field() ?>
                <button type="submit" class="link-danger">Delete</button>
            </form>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>
