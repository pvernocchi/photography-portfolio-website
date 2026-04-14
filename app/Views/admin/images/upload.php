<?php
declare(strict_types=1);
use App\Core\CSRF;
?>
<header class="page-header"><h1>Upload Images</h1></header>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="/admin/images/upload" enctype="multipart/form-data" class="card form-stack" id="upload-form">
    <?= CSRF::field() ?>
    <div class="upload-zone" id="upload-zone">
        <p class="upload-zone-text">Drop JPG files here or click to pick (max 20 MB each)</p>
        <input id="images-input" type="file" name="images[]" accept="image/jpeg" multiple required>
        <div id="upload-preview" class="upload-preview"></div>
    </div>
    <fieldset>
        <legend>Assign to galleries (optional — you can also assign after upload)</legend>
        <div class="gallery-checkboxes">
            <?php foreach ($categories as $cat): ?>
            <label class="checkbox-row">
                <input type="checkbox" name="categories[]" value="<?= (int) $cat['id'] ?>">
                <?= e($cat['name_en']) ?>
            </label>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
            <p class="muted">No galleries yet. <a href="/admin/categories/create">Create one</a></p>
            <?php endif; ?>
        </div>
    </fieldset>
    <button class="btn btn-primary" type="submit">Upload</button>
</form>
<script>
(function() {
    const zone = document.getElementById('upload-zone');
    const input = document.getElementById('images-input');
    const preview = document.getElementById('upload-preview');

    zone.addEventListener('click', (e) => {
        if (e.target === zone || e.target.classList.contains('upload-zone-text')) input.click();
    });

    zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        input.files = e.dataTransfer.files;
        showPreviews();
    });

    input.addEventListener('change', showPreviews);

    function showPreviews() {
        preview.innerHTML = '';
        const files = input.files;
        if (!files || files.length === 0) return;
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (!file.type.startsWith('image/')) continue;
            const div = document.createElement('div');
            div.className = 'upload-thumb';
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.onload = () => URL.revokeObjectURL(img.src);
            const span = document.createElement('span');
            span.textContent = file.name;
            div.appendChild(img);
            div.appendChild(span);
            preview.appendChild(div);
        }
    }
})();
</script>
