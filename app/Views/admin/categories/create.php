<?php
declare(strict_types=1);
use App\Core\CSRF;
?>
<header class="page-header"><h1>Create Category</h1></header>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="/admin/categories/store" class="card form-stack" id="category-form">
    <?= CSRF::field() ?>
    <label>Name (ES)<input type="text" name="name_es" required></label>
    <label>Name (EN)<input type="text" name="name_en" id="category-name-en" required></label>
    <label>Slug<input type="text" name="slug" id="category-slug" required></label>
    <label class="checkbox-row"><input type="checkbox" name="is_visible" value="1" checked> Visible</label>
    <label class="checkbox-row"><input type="checkbox" name="is_private" value="1"> Private gallery</label>
    <label>Private gallery password
        <input type="password" name="private_password" minlength="8" autocomplete="new-password" placeholder="At least 8 characters including letters, numbers, and symbols (all required)">
    </label>
    <label class="checkbox-row"><input type="checkbox" name="allow_original_download" value="1"> Allow original download</label>
    <button class="btn btn-primary" type="submit">Save</button>
</form>
