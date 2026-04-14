<?php
declare(strict_types=1);
use App\Core\CSRF;
?>
<header class="page-header row-between">
    <h1>
        <?php if (($activeFilter ?? 'all') === 'unassigned'): ?>
            Images Without Category
        <?php elseif (($activeFilter ?? 'all') === 'duplicated'): ?>
            Duplicated Images
        <?php else: ?>
            Image Library
        <?php endif; ?>
    </h1>
    <div class="row-between" style="gap:0.5rem">
        <button type="button" id="bulk-select-all" class="btn-link">Select all</button>
        <button type="button" id="bulk-select-none" class="btn-link" style="display:none">Deselect all</button>
        <a class="btn btn-primary" href="/admin/images/upload">Upload images</a>
    </div>
</header>
<?php if (!empty($success)): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<nav class="tabs">
    <a class="tab-btn <?= ($activeFilter ?? 'all') === 'all' ? 'active' : '' ?>" href="/admin/images">All images</a>
    <a class="tab-btn <?= ($activeFilter ?? 'all') === 'unassigned' ? 'active' : '' ?>" href="/admin/images/unassigned">Without category assigned</a>
    <a class="tab-btn <?= ($activeFilter ?? 'all') === 'duplicated' ? 'active' : '' ?>" href="/admin/images/duplicated">Duplicated images</a>
    <?php foreach ($categories as $cat): ?>
        <?php if ((int) ($cat['images_count'] ?? 0) < 1) { continue; } ?>
        <a class="tab-btn" href="/admin/categories/<?= (int) $cat['id'] ?>/images"><?= e($cat['name_en']) ?></a>
    <?php endforeach; ?>
</nav>
<?php if (empty($images)): ?>
<div class="card"><p>No images for this view. <a href="/admin/images/upload">Upload your first photos</a></p></div>
<?php else: ?>
<div class="image-grid" id="bulk-grid">
    <?php foreach ($images as $image): ?>
    <article class="card image-card" data-id="<?= (int) $image['id'] ?>">
        <label class="image-card-select">
            <input type="checkbox" class="image-card-checkbox" value="<?= (int) $image['id'] ?>" aria-label="Select image">
        </label>
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

<div class="bulk-bar" id="bulk-bar" hidden>
    <span class="bulk-bar-count" id="bulk-count"></span>
    <form method="post" action="/admin/images/bulk-action" id="bulk-form">
        <?= CSRF::field() ?>
        <input type="hidden" name="return_to" value="<?= e((string) ($returnTo ?? '/admin/images')) ?>">
        <div class="bulk-bar-actions">
            <?php if (!empty($categories)): ?>
            <select name="assign_category_id" class="bulk-bar-select" aria-label="Select gallery to assign images to">
                <option value="">Assign to gallery</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>"><?= e($cat['name_en']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="category_id" class="bulk-bar-select" aria-label="Select gallery to remove images from">
                <option value="">Select gallery</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>"><?= e($cat['name_en']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="action" value="assign_to_category" class="btn bulk-bar-btn bulk-bar-btn-outline">Assign to gallery</button>
            <button type="submit" name="action" value="remove_from_category" class="btn bulk-bar-btn bulk-bar-btn-outline">Remove from gallery</button>
            <?php endif; ?>
            <button type="submit" name="action" value="delete" class="btn btn-danger bulk-bar-btn">Remove and delete from storage</button>
        </div>
        <div id="bulk-ids"></div>
    </form>
</div>
<script>
window.AdminBulkSelect?.init('#bulk-grid', '#bulk-form', '#bulk-bar', '#bulk-count', '#bulk-ids', '#bulk-select-all', '#bulk-select-none');
</script>
<?php endif; ?>
