<?php
declare(strict_types=1);
use App\Core\CSRF;
?>
<header class="page-header"><h1>Upload Images — <?= e($category['name_en']) ?></h1></header>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="/admin/categories/<?= (int) $category['id'] ?>/images/upload" enctype="multipart/form-data" class="card form-stack" id="upload-form">
    <?= CSRF::field() ?>
    <label class="upload-zone" for="images-input">Drop JPG files here or click to pick (max 20MB each)</label>
    <input id="images-input" type="file" name="images[]" accept="image/jpeg" multiple required>
    <button class="btn btn-primary" type="submit">Upload</button>
</form>
