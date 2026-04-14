<?php
declare(strict_types=1);
use App\Core\CSRF;
?>
<header class="page-header"><h1>Assign Galleries</h1></header>
<?php if (!empty($success)): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if (empty($images)): ?>
<div class="card"><p>No recently uploaded images. <a href="/admin/images/upload">Upload images</a></p></div>
<?php else: ?>
<form method="post" action="/admin/images/assign" class="form-stack">
    <?= CSRF::field() ?>
    <div class="assign-grid">
        <?php foreach ($images as $image): ?>
        <div class="card assign-card">
            <img src="/image/thumb/<?= (int) $image['id'] ?>" alt="<?= e($image['original_filename']) ?>">
            <p class="muted"><?= e($image['original_filename']) ?></p>
            <div class="gallery-checkboxes">
                <?php foreach ($categories as $cat): ?>
                <label class="checkbox-row">
                    <input type="checkbox" name="assignments[<?= (int) $image['id'] ?>][]" value="<?= (int) $cat['id'] ?>"
                        <?= in_array((int) $cat['id'], $image['assigned_categories'] ?? [], true) ? 'checked' : '' ?>>
                    <?= e($cat['name_en']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-primary" type="submit">Save assignments</button>
</form>
<?php endif; ?>
