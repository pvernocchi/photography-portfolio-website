<?php
declare(strict_types=1);

use App\Core\CSRF;

$s = static fn (string $key, string $default = ''): string => (string) ($settings[$key]['setting_value'] ?? $default);
?>
<header class="page-header">
    <h1>Settings</h1>
    <p>Manage your site configuration</p>
</header>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="tabs">
    <button class="tab-btn active" data-tab="general">
        <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        General
    </button>
    <button class="tab-btn" data-tab="appearance">
        <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r="2.5"/><path d="M17.5 10.5a2.5 2.5 0 1 1 0 5"/><circle cx="8.5" cy="8.5" r="2.5"/><circle cx="6.5" cy="14.5" r="2.5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.9 0 1.5-.7 1.5-1.5 0-.4-.1-.7-.4-1-.2-.3-.4-.7-.4-1 0-.8.7-1.5 1.5-1.5H16c3.3 0 6-2.7 6-6 0-5.5-4.5-9-10-9z"/></svg>
        Appearance
    </button>
    <button class="tab-btn" data-tab="content">
        <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Content
    </button>
    <button class="tab-btn" data-tab="contact-form">
        <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        Contact Form
    </button>
    <button class="tab-btn" data-tab="seo-analytics">
        <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        SEO &amp; Analytics
    </button>
    <button class="tab-btn" data-tab="social">
        <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/><circle cx="18" cy="3" r="3"/></svg>
        Social Links
    </button>
</div>

<!-- ─── General ──────────────────────────────────────── -->
<section class="tab-panel active" data-panel="general">
    <form class="card form-stack" method="post" action="/admin/settings/general">
        <?= CSRF::field() ?>
        <p class="form-section-title">Site Information</p>
        <label>Site title
            <input name="site_title" value="<?= e($s('site_title')) ?>">
        </label>
        <label>Default language
            <select name="default_language">
                <option value="es" <?= $s('default_language', 'es') === 'es' ? 'selected' : '' ?>>es</option>
                <option value="en" <?= $s('default_language') === 'en' ? 'selected' : '' ?>>en</option>
            </select>
        </label>
        <p class="form-section-title">Site Descriptions</p>
        <label>Description (ES)
            <textarea name="site_description_es"><?= e($s('site_description_es')) ?></textarea>
        </label>
        <label>Description (EN)
            <textarea name="site_description_en"><?= e($s('site_description_en')) ?></textarea>
        </label>
        <button class="btn btn-primary">Save General Settings</button>
    </form>
</section>

<!-- ─── Appearance ───────────────────────────────────── -->
<section class="tab-panel" data-panel="appearance">
    <form class="card form-stack" method="post" action="/admin/settings/theme">
        <?= CSRF::field() ?>
        <p class="form-section-title">Theme</p>
        <div class="theme-grid">
            <?php foreach ($themes as $slug => $theme): ?>
                <label class="theme-card">
                    <input type="radio" name="active_theme" value="<?= e($slug) ?>" <?= $s('active_theme', 'minimal-light') === $slug ? 'checked' : '' ?>>
                    <strong><?= e((string) ($theme['name'] ?? $slug)) ?></strong>
                    <span class="muted"><?= e((string) ($theme['description'] ?? '')) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-primary">Save Theme</button>
    </form>

    <form class="card form-stack mt-24" method="post" action="/admin/settings/watermark">
        <?= CSRF::field() ?>
        <p class="form-section-title">Watermark</p>
        <label class="checkbox-row">
            <input type="checkbox" name="watermark_enabled" value="1" <?= $s('watermark_enabled') === '1' ? 'checked' : '' ?>>
            Enable watermark on images
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
        <button class="btn btn-primary">Save Watermark</button>
    </form>
</section>

<!-- ─── Content ──────────────────────────────────────── -->
<section class="tab-panel" data-panel="content">
    <form class="card form-stack" method="post" action="/admin/settings/about">
        <?= CSRF::field() ?>
        <p class="form-section-title">About Page</p>
        <label>About content (ES)
            <textarea name="about_content_es" rows="6"><?= e($s('about_content_es')) ?></textarea>
        </label>
        <label>About content (EN)
            <textarea name="about_content_en" rows="6"><?= e($s('about_content_en')) ?></textarea>
        </label>
        <label>About photo path
            <input name="about_photo" value="<?= e($s('about_photo')) ?>">
            <small class="form-help">Relative path to the about page photo</small>
        </label>
        <button class="btn btn-primary">Save Content</button>
    </form>
</section>

<!-- ─── Contact Form ─────────────────────────────────── -->
<section class="tab-panel" data-panel="contact-form">
    <form class="card form-stack" method="post" action="/admin/settings/contact" id="contact-form">
        <?= CSRF::field() ?>
        <p class="form-section-title">Contact Email</p>
        <label>Recipient email
            <input name="contact_email" type="email" value="<?= e($s('contact_email')) ?>">
            <small class="form-help">Where contact form submissions are sent</small>
        </label>

        <p class="form-section-title">Captcha (Cloudflare Turnstile)</p>
        <label>Turnstile site key
            <input name="turnstile_site_key" value="<?= e($s('turnstile_site_key')) ?>">
        </label>
        <label>Turnstile secret key
            <input name="turnstile_secret_key" type="password" value="<?= e($s('turnstile_secret_key')) ?>" autocomplete="off">
            <small class="form-help">Used to protect the contact form from spam</small>
        </label>

        <p class="form-section-title">Mail Delivery</p>
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
                    <small class="form-help">Leave blank to keep existing password.</small>
                <?php endif; ?>
            </label>
            <label>From name
                <input name="smtp_from_name" value="<?= e($s('smtp_from_name')) ?>">
            </label>
            <label>From email
                <input name="smtp_from_email" value="<?= e($s('smtp_from_email')) ?>">
            </label>
        </div>
        <button class="btn btn-primary">Save Contact Form Settings</button>
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

<!-- ─── SEO & Analytics ──────────────────────────────── -->
<section class="tab-panel" data-panel="seo-analytics">
    <form class="card form-stack" method="post" action="/admin/settings/seo">
        <?= CSRF::field() ?>
        <p class="form-section-title">Search Engine Optimization</p>
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
            <small class="form-help">Used for social media link previews</small>
        </label>
        <button class="btn btn-primary">Save SEO Settings</button>
    </form>

    <form class="card form-stack mt-24" method="post" action="/admin/settings/analytics">
        <?= CSRF::field() ?>
        <p class="form-section-title">Analytics</p>
        <label>Google Analytics ID
            <input name="google_analytics_id" value="<?= e($s('google_analytics_id')) ?>">
            <small class="form-help">e.g. G-XXXXXXXXXX or UA-XXXXXXXX-X</small>
        </label>
        <button class="btn btn-primary">Save Analytics Settings</button>
    </form>
</section>

<!-- ─── Social Links ─────────────────────────────────── -->
<section class="tab-panel" data-panel="social">
    <form class="card form-stack" method="post" action="/admin/settings/social">
        <?= CSRF::field() ?>
        <p class="form-section-title">Social Media Profiles</p>
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
        <button class="btn btn-primary">Save Social Links</button>
    </form>
</section>
