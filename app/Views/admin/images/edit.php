<?php
declare(strict_types=1);
use App\Core\CSRF;
?>
<header class="page-header"><h1>Edit Image</h1></header>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="/admin/images/<?= (int) $image['id'] ?>/update" class="card form-stack">
    <?= CSRF::field() ?>
    <label>Title (ES)<input type="text" name="title_es" value="<?= e((string) ($image['title_es'] ?? '')) ?>"></label>
    <label>Title (EN)<input type="text" name="title_en" value="<?= e((string) ($image['title_en'] ?? '')) ?>"></label>
    <label>Alt text (ES)<input type="text" name="alt_es" value="<?= e((string) ($image['alt_es'] ?? '')) ?>"></label>
    <label>Alt text (EN)<input type="text" name="alt_en" value="<?= e((string) ($image['alt_en'] ?? '')) ?>"></label>
    <fieldset>
        <legend>Galleries</legend>
        <div class="gallery-checkboxes">
            <?php foreach ($categories as $cat): ?>
            <label class="checkbox-row">
                <input type="checkbox" name="categories[]" value="<?= (int) $cat['id'] ?>"
                    <?= in_array((int) $cat['id'], $assignedCategories ?? [], true) ? 'checked' : '' ?>>
                <?= e($cat['name_en']) ?>
            </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <button class="btn btn-primary" type="submit">Save</button>
</form>
