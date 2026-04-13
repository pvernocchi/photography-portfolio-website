<?php
declare(strict_types=1);
use App\Core\CSRF;
?>
<header class="page-header row-between">
    <h1>Categories</h1>
    <a class="btn btn-primary" href="/admin/categories/create">Create category</a>
</header>
<?php if (!empty($success)): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="card">
    <ul class="sortable-list" id="category-sortable">
        <?php foreach ($categories as $category): ?>
            <li class="sortable-item" draggable="true" data-id="<?= (int) $category['id'] ?>">
                <strong><?= e($category['name_en']) ?></strong>
                <span class="muted">/ <?= e($category['slug']) ?> · <?= (int) $category['images_count'] ?> images</span>
                <div class="actions">
                    <a href="/admin/categories/<?= (int) $category['id'] ?>/images">Images</a>
                    <a href="/admin/categories/<?= (int) $category['id'] ?>/edit">Edit</a>
                    <form method="post" action="/admin/categories/<?= (int) $category['id'] ?>/delete" onsubmit="return confirm('Delete category?')">
                        <?= CSRF::field() ?>
                        <button type="submit" class="link-danger">Delete</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<script>
window.AdminReorder?.bind('#category-sortable', '/admin/categories/reorder');
</script>
