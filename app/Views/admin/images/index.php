<?php
declare(strict_types=1);
use App\Core\CSRF;
?>
<header class="page-header row-between">
    <h1>Images — <?= e($category['name_en']) ?></h1>
    <a class="btn btn-primary" href="/admin/images/upload">Upload images</a>
</header>
<?php if (!empty($success)): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="image-grid" id="image-sortable">
    <?php foreach ($images as $image): ?>
    <article class="card image-card sortable-item" draggable="true" data-id="<?= (int) $image['id'] ?>">
        <img src="/image/thumb/<?= (int) $image['id'] ?>" alt="thumb">
        <div class="actions">
            <button type="button" class="btn-link" data-cover="<?= (int) $image['id'] ?>" data-category="<?= (int) $category['id'] ?>">Set cover</button>
            <a href="/admin/images/<?= (int) $image['id'] ?>/edit">Edit</a>
            <form method="post" action="/admin/images/<?= (int) $image['id'] ?>/delete" onsubmit="return confirm('Delete image from all galleries?')">
                <?= CSRF::field() ?>
                <button type="submit" class="link-danger">Delete</button>
            </form>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<script>
window.AdminReorder?.bind('#image-sortable', '/admin/categories/<?= (int) $category['id'] ?>/images/reorder');
document.querySelectorAll('[data-cover]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const payload = new URLSearchParams();
        payload.set('csrf_token', window.VernocchiAdmin?.csrfToken || '');
        payload.set('image_id', btn.dataset.cover);
        await fetch('/admin/categories/<?= (int) $category['id'] ?>/images/set-cover', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: payload.toString(),
        });
        window.location.reload();
    });
});
</script>
