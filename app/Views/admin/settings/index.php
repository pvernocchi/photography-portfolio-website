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
    <button class="tab-btn" data-tab="security">Security</button>
    <button class="tab-btn" data-tab="theme">Theme</button>
    <button class="tab-btn" data-tab="about">About</button>
    <button class="tab-btn" data-tab="watermark">Watermark</button>
    <button class="tab-btn" data-tab="analytics">Analytics</button>
    <button class="tab-btn" data-tab="seo">SEO</button>
    <button class="tab-btn" data-tab="social">Social</button>
    <button class="tab-btn" data-tab="contact">Contact</button>
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
        <label>Description (ES)
            <textarea name="site_description_es"><?= e($s('site_description_es')) ?></textarea>
        </label>
        <label>Description (EN)
            <textarea name="site_description_en"><?= e($s('site_description_en')) ?></textarea>
        </label>
        <button class="btn btn-primary">Save</button>
    </form>
</section>

<section class="tab-panel" data-panel="security">
    <form class="card form-stack" method="post" action="/admin/settings/security">
        <?= CSRF::field() ?>
        <label>Turnstile site key
            <input name="turnstile_site_key" value="<?= e($s('turnstile_site_key')) ?>">
        </label>
        <label>Turnstile secret key
            <input name="turnstile_secret_key" type="password" value="<?= e($s('turnstile_secret_key')) ?>" autocomplete="off">
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

<section class="tab-panel" data-panel="social">
    <form class="card form-stack" method="post" action="/admin/settings/social">
        <?= CSRF::field() ?>
        <label>Instagram URL
            <input name="social_instagram" type="url" value="<?= e($s('social_instagram')) ?>" placeholder="https://instagram.com/yourprofile">
        </label>
        <label>Facebook URL
            <input name="social_facebook" type="url" value="<?= e($s('social_facebook')) ?>" placeholder="https://facebook.com/yourpage">
        </label>
        <label>Twitter / X URL
            <input name="social_twitter" type="url" value="<?= e($s('social_twitter')) ?>" placeholder="https://x.com/yourhandle">
        </label>
        <label>LinkedIn URL
            <input name="social_linkedin" type="url" value="<?= e($s('social_linkedin')) ?>" placeholder="https://linkedin.com/in/yourprofile">
        </label>
        <label>YouTube URL
            <input name="social_youtube" type="url" value="<?= e($s('social_youtube')) ?>" placeholder="https://youtube.com/@yourchannel">
        </label>
        <label>GitHub URL
            <input name="social_github" type="url" value="<?= e($s('social_github')) ?>" placeholder="https://github.com/yourprofile">
        </label>
        <button class="btn btn-primary">Save</button>
    </form>
</section>

<section class="tab-panel" data-panel="contact">
    <form class="card form-stack" method="post" action="/admin/settings/contact" id="contact-form">
        <?= CSRF::field() ?>
        <label>Mail driver
            <select name="mail_driver" id="mail-driver-select">
                <option value="mail" <?= $s('mail_driver', 'mail') === 'mail' ? 'selected' : '' ?>>PHP mail()</option>
                <option value="smtp" <?= $s('mail_driver', 'mail') === 'smtp' ? 'selected' : '' ?>>SMTP</option>
            </select>
        </label>
        <div id="smtp-fields">
            <label>SMTP Host
                <input name="smtp_host" value="<?= e($s('smtp_host')) ?>">
            </label>
            <label>SMTP Port
                <input name="smtp_port" type="number" min="1" value="<?= e($s('smtp_port', '587')) ?>">
            </label>
            <label>Encryption
                <select name="smtp_encryption">
                    <option value="none" <?= $s('smtp_encryption', 'tls') === 'none' ? 'selected' : '' ?>>none</option>
                    <option value="ssl" <?= $s('smtp_encryption') === 'ssl' ? 'selected' : '' ?>>ssl</option>
                    <option value="tls" <?= $s('smtp_encryption', 'tls') === 'tls' ? 'selected' : '' ?>>tls</option>
                </select>
            </label>
            <label class="checkbox-row">
                <input type="checkbox" name="smtp_logging_enabled" value="1" <?= $s('smtp_logging_enabled', '1') === '1' ? 'checked' : '' ?>>
                Enable SMTP debug logging
            </label>
            <label>Username
                <input name="smtp_username" value="<?= e($s('smtp_username')) ?>">
            </label>
            <label>Password
                <input name="smtp_password" type="password" autocomplete="off">
                <?php if ($s('smtp_password') !== ''): ?>
                    <small class="muted">Leave blank to keep existing password.</small>
                <?php endif; ?>
            </label>
            <label>From name
                <input name="smtp_from_name" value="<?= e($s('smtp_from_name')) ?>">
            </label>
            <label>From email
                <input name="smtp_from_email" value="<?= e($s('smtp_from_email')) ?>">
            </label>
        </div>
        <button class="btn btn-primary">Save</button>
    </form>
    <script>
        (function () {
            const select = document.getElementById('mail-driver-select');
            const smtpFields = document.getElementById('smtp-fields');
            function toggle() {
                smtpFields.style.display = select.value === 'smtp' ? '' : 'none';
            }
            toggle();
            select.addEventListener('change', toggle);
        }());
    </script>
</section>
