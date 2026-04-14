<?php
declare(strict_types=1);
use App\Core\CSRF;
?>
<header class="page-header row-between">
    <h1>Images — <?= e($category['name_en']) ?></h1>
    <div class="row-between" style="gap:0.5rem">
        <button type="button" id="bulk-select-all" class="btn-link">Select all</button>
        <button type="button" id="bulk-select-none" class="btn-link" style="display:none">Deselect all</button>
        <a class="btn btn-primary" href="/admin/images/upload">Upload images</a>
    </div>
</header>
<?php if (!empty($success)): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="image-grid" id="image-sortable" data-bulk-grid="true">
    <?php foreach ($images as $image): ?>
    <article class="card image-card sortable-item" draggable="true" data-id="<?= (int) $image['id'] ?>">
        <label class="image-card-select">
            <input type="checkbox" class="image-card-checkbox" value="<?= (int) $image['id'] ?>" aria-label="Select image">
        </label>
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

<div class="bulk-bar" id="bulk-bar" hidden>
    <span class="bulk-bar-count" id="bulk-count"></span>
    <form method="post" action="/admin/images/bulk-action" id="bulk-form">
        <?= CSRF::field() ?>
        <input type="hidden" name="return_to" value="/admin/categories/<?= (int) $category['id'] ?>/images">
        <input type="hidden" name="category_id" value="<?= (int) $category['id'] ?>">
        <div id="bulk-ids"></div>
        <div class="bulk-bar-actions">
            <button type="submit" name="action" value="remove_from_category" class="btn bulk-bar-btn bulk-bar-btn-outline">Remove from gallery</button>
            <button type="submit" name="action" value="delete" class="btn btn-danger bulk-bar-btn">Remove and delete from storage</button>
        </div>
    </form>
</div>

<script>
window.AdminReorder?.bind('#image-sortable', '/admin/categories/<?= (int) $category['id'] ?>/images/reorder');
window.AdminBulkSelect?.init('#image-sortable', '#bulk-form', '#bulk-bar', '#bulk-count', '#bulk-ids', '#bulk-select-all', '#bulk-select-none');
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
