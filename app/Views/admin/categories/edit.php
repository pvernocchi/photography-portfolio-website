<?php
declare(strict_types=1);
use App\Core\CSRF;
?>
<header class="page-header"><h1>Edit Category</h1></header>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="/admin/categories/<?= (int) $category['id'] ?>/update" class="card form-stack" id="category-form">
    <?= CSRF::field() ?>
    <label>Name (ES)<input type="text" name="name_es" value="<?= e($category['name_es']) ?>" required></label>
    <label>Name (EN)<input type="text" name="name_en" id="category-name-en" value="<?= e($category['name_en']) ?>" required></label>
    <label>Slug<input type="text" name="slug" id="category-slug" value="<?= e($category['slug']) ?>" required></label>
    <label class="checkbox-row"><input type="checkbox" name="is_visible" value="1" <?= !empty($category['is_visible']) ? 'checked' : '' ?>> Visible</label>
    <label>Cover image
        <select name="cover_image_id">
            <option value="0">No cover</option>
            <?php foreach ($images as $image): ?>
                <option value="<?= (int) $image['id'] ?>" <?= (int) $category['cover_image_id'] === (int) $image['id'] ? 'selected' : '' ?>>
                    <?= e($image['original_filename']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="btn btn-primary" type="submit">Save</button>
</form>
