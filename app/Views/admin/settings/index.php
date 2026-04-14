<?php
declare(strict_types=1);

use App\Core\CSRF;

$s = static fn (string $key, string $default = ''): string => (string) ($settings[$key]['setting_value'] ?? $default);
?>
<header class="page-header"><h1>Settings</h1></header>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="tabs">
    <button class="tab-btn active" data-tab="general">General</button>
    <button class="tab-btn" data-tab="theme">Theme</button>
    <button class="tab-btn" data-tab="about">About</button>
    <button class="tab-btn" data-tab="watermark">Watermark</button>
    <button class="tab-btn" data-tab="analytics">Analytics</button>
    <button class="tab-btn" data-tab="seo">SEO</button>
</div>

<section class="tab-panel active" data-panel="general">
    <form class="card form-stack" method="post" action="/admin/settings/general">
        <?= CSRF::field() ?>
        <label>Site title
            <input name="site_title" value="<?= e($s('site_title')) ?>">
        </label>
        <label>Default language
            <select name="default_language">
                <option value="es" <?= $s('default_language', 'es') === 'es' ? 'selected' : '' ?>>es</option>
                <option value="en" <?= $s('default_language') === 'en' ? 'selected' : '' ?>>en</option>
            </select>
        </label>
        <label>Contact email
            <input name="contact_email" value="<?= e($s('contact_email')) ?>">
        </label>
        <label>Turnstile site key
            <input name="turnstile_site_key" value="<?= e($s('turnstile_site_key')) ?>">
        </label>
        <label>Turnstile secret key
            <input name="turnstile_secret_key" type="password" value="<?= e($s('turnstile_secret_key')) ?>" autocomplete="off">
        </label>
        <label>Description (ES)
            <textarea name="site_description_es"><?= e($s('site_description_es')) ?></textarea>
        </label>
        <label>Description (EN)
            <textarea name="site_description_en"><?= e($s('site_description_en')) ?></textarea>
        </label>
        <button class="btn btn-primary">Save</button>
    </form>
</section>

<section class="tab-panel" data-panel="theme">
    <form class="card form-stack" method="post" action="/admin/settings/theme">
        <?= CSRF::field() ?>
        <div class="theme-grid">
            <?php foreach ($themes as $slug => $theme): ?>
                <label class="theme-card">
                    <input type="radio" name="active_theme" value="<?= e($slug) ?>" <?= $s('active_theme', 'minimal-light') === $slug ? 'checked' : '' ?>>
                    <strong><?= e((string) ($theme['name'] ?? $slug)) ?></strong>
                    <span class="muted"><?= e((string) ($theme['description'] ?? '')) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-primary">Save</button>
    </form>
</section>

<section class="tab-panel" data-panel="about">
    <form class="card form-stack" method="post" action="/admin/settings/about">
        <?= CSRF::field() ?>
        <label>About content (ES)
            <textarea name="about_content_es" rows="6"><?= e($s('about_content_es')) ?></textarea>
        </label>
        <label>About content (EN)
            <textarea name="about_content_en" rows="6"><?= e($s('about_content_en')) ?></textarea>
        </label>
        <label>About photo path
            <input name="about_photo" value="<?= e($s('about_photo')) ?>">
        </label>
        <button class="btn btn-primary">Save</button>
    </form>
</section>

<section class="tab-panel" data-panel="watermark">
    <form class="card form-stack" method="post" action="/admin/settings/watermark">
        <?= CSRF::field() ?>
        <label class="checkbox-row">
            <input type="checkbox" name="watermark_enabled" value="1" <?= $s('watermark_enabled') === '1' ? 'checked' : '' ?>>
            Enabled
        </label>
        <label>Text
            <input name="watermark_text" value="<?= e($s('watermark_text', 'vernocchi.es')) ?>">
        </label>
        <label>Position
            <select name="watermark_position">
                <?php foreach (['bottom-right', 'bottom-left', 'center', 'tiled'] as $position): ?>
                    <option value="<?= e($position) ?>" <?= $s('watermark_position', 'bottom-right') === $position ? 'selected' : '' ?>>
                        <?= e($position) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Opacity
            <input name="watermark_opacity" type="number" min="10" max="100" value="<?= e($s('watermark_opacity', '30')) ?>">
        </label>
        <label>Font size
            <input name="watermark_font_size" type="number" min="10" max="48" value="<?= e($s('watermark_font_size', '16')) ?>">
        </label>
        <button class="btn btn-primary">Save</button>
    </form>
</section>

<section class="tab-panel" data-panel="analytics">
    <form class="card form-stack" method="post" action="/admin/settings/analytics">
        <?= CSRF::field() ?>
        <label>Google Analytics ID
            <input name="google_analytics_id" value="<?= e($s('google_analytics_id')) ?>">
        </label>
        <button class="btn btn-primary">Save</button>
    </form>
</section>

<section class="tab-panel" data-panel="seo">
    <form class="card form-stack" method="post" action="/admin/settings/seo">
        <?= CSRF::field() ?>
        <label>Meta title (ES)
            <input name="meta_title_es" value="<?= e($s('meta_title_es')) ?>">
        </label>
        <label>Meta title (EN)
            <input name="meta_title_en" value="<?= e($s('meta_title_en')) ?>">
        </label>
        <label>Meta description (ES)
            <textarea name="meta_description_es"><?= e($s('meta_description_es')) ?></textarea>
        </label>
        <label>Meta description (EN)
            <textarea name="meta_description_en"><?= e($s('meta_description_en')) ?></textarea>
        </label>
        <label>OG image path
            <input name="og_image" value="<?= e($s('og_image')) ?>">
        </label>
        <button class="btn btn-primary">Save</button>
    </form>
</section>
