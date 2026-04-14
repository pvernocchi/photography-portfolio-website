# vernocchi.es — Photography Portfolio

A bilingual (Spanish/English) photography portfolio web application built with **plain PHP 8.1** — zero Composer dependencies, zero frameworks. Designed for Namecheap shared hosting with MySQL.

## ✨ Features

### 📸 Image Management
- Upload JPG images up to 20MB through a secure admin interface
- Import JPG images uploaded via FTP to `public/uploads` (batched import from admin)
- Automatic thumbnail (400px) and display (1600px) image generation via GD library
- EXIF data stripping for privacy protection
- Drag-and-drop reordering of photos within categories
- Flat category system with name (ES/EN), slug, and cover image

### 📧 Contact & Mail
- Contact form with honeypot + Cloudflare Turnstile spam protection
- Configurable mail driver: PHP `mail()` or SMTP
- Full SMTP support with AUTH LOGIN, TLS/SSL encryption
- SMTP credentials encrypted at rest via AES-256-GCM
- Customizable sender name and email address

### 🔒 Aggressive Image Protection
- Images served through PHP — no direct file URLs ever exposed
- Canvas rendering prevents "Save Image As" browser option
- Right-click and drag disabled on gallery areas
- CSS overlay layer on top of all images
- Print stylesheet hides all images
- Keyboard shortcut blocking (Ctrl+S, Ctrl+Shift+I)
- Hotlink protection via referer validation
- `Cache-Control: no-store` and `X-Content-Type-Options: nosniff` headers
- Configurable text watermark (position, opacity, font size, on/off)

### 🔐 Admin Security
- Single admin account with TOTP-based MFA (Microsoft Authenticator compatible)
- Custom RFC 6238 TOTP implementation — no external libraries
- Forced MFA setup on first login
- "Remember me" with secure hashed tokens (still requires MFA)
- Session hardening: IP/user-agent binding, inactivity timeout, ID regeneration
- CSRF protection on all forms
- All database queries use PDO prepared statements
- Password change with 12-character minimum
- AES-256-GCM encryption for sensitive settings (e.g. SMTP passwords)

### 🎨 Theme System
Three selectable themes, switchable from the admin panel:

| Theme | Description |
|---|---|
| **Minimal Light** | Clean white, thin typography, lots of whitespace, borderless cards |
| **Dark Room** | Dark charcoal background, images pop, cinematic feel |
| **Editorial** | Magazine-inspired, structured grid with varied card sizes |

All themes support automatic dark/light mode via `prefers-color-scheme` CSS media queries.

### 🌐 Multilingual
- Public frontend: Spanish 🇪🇸 and English 🇬🇧 with language switcher
- Admin interface: English
- Categories and image metadata support both languages (`name_es`/`name_en`, `title_es`/`title_en`)
- Translation files: `app/Languages/es.php`, `app/Languages/en.php`

### ⚙️ Admin Settings (Tabbed Interface)
- **General**: Site title, descriptions (ES/EN), default language, contact email
- **Security**: Cloudflare Turnstile site/secret keys
- **Theme**: Visual theme selector with preview
- **About**: Rich text editor for About page content (ES/EN), profile photo upload
- **Watermark**: Enable/disable, text, position, opacity, font size
- **Analytics**: Google Analytics GA4 integration with GDPR cookie consent banner
- **SEO**: Meta titles/descriptions (ES/EN), Open Graph image, Twitter cards, XML sitemap
- **Contact**: Mail driver selection (PHP mail / SMTP), SMTP host, port, encryption, credentials, sender name/email

### 📄 Public Pages
- **Homepage**: Welcome hero + featured categories
- **Gallery**: Category grid → image grid within category
- **Lightbox**: Full-screen image viewer with ← → keyboard/swipe navigation, image counter
- **About**: Photographer bio page (editable from admin)
- **Contact**: Contact form with honeypot + Cloudflare Turnstile spam protection, sends via configurable mail driver (PHP `mail()` or SMTP)
- **Sitemap**: Dynamic XML sitemap at `/sitemap.xml`

---

## 📋 Requirements

- PHP 8.1+
- MySQL 8+
- Apache with `mod_rewrite` enabled
- GD extension (for image processing)
- OpenSSL extension (for AES-256-GCM encryption)
- PDO MySQL extension
- PHP `mail()` function or external SMTP server (for contact form)

---

## 🗂️ Project Structure

```
vernocchi.es/
├── .github/
│   └── workflows/
│       └── deploy.yml              # GitHub Actions FTP auto-deploy
├── app/
│   ├── bootstrap.php               # Autoloader, config, session, error handling
│   ├── Core/
│   │   ├── Auth.php                # Authentication helper
│   │   ├── Controller.php          # Base controller class
│   │   ├── CSRF.php                # CSRF token management
│   │   ├── Database.php            # PDO MySQL singleton wrapper
│   │   ├── Encryption.php          # AES-256-GCM encryption/decryption
│   │   ├── ImageProcessor.php      # GD-based image processing + watermark
│   │   ├── Language.php            # Multilingual system
│   │   ├── Mailer.php              # Mail driver (PHP mail + SMTP)
│   │   ├── Router.php              # Custom router with middleware
│   │   ├── Session.php             # Session management + remember-me
│   │   ├── ThemeEngine.php         # Theme loading and resolution
│   │   └── TOTP.php                # Custom RFC 6238 TOTP (MS Authenticator)
│   ├── Controllers/
│   │   ├── AdminController.php     # Admin dashboard (stats)
│   │   ├── AuthController.php      # Login, logout, MFA, password change
│   │   ├── CategoryController.php  # Category CRUD + reorder
│   │   ├── FrontendController.php  # Public pages (home, gallery, about, contact)
│   │   ├── HomeController.php      # Legacy home placeholder
│   │   ├── ImageController.php     # Image upload, edit, delete, reorder
│   │   ├── ImageServeController.php# Secure image serving (hotlink protection)
│   │   ├── SettingsController.php  # Admin settings (all tabs)
│   │   └── SitemapController.php   # XML sitemap generation
│   ├── Languages/
│   │   ├── en.php                  # English translations
│   │   └── es.php                  # Spanish translations
│   ├── Models/
│   │   ├── Category.php            # Category model
│   │   ├── Image.php               # Image model
│   │   ├── RememberToken.php       # Remember-me token model
│   │   ├── Setting.php             # Settings model (cached)
│   │   └── User.php                # Admin user model
│   └── Views/
│       ├── admin/
│       │   ├── categories/         # Category list, create, edit views
│       │   ├── dashboard.php       # Dashboard with stats
│       │   ├── images/             # Image list, upload, edit views
│       │   └── settings/
│       │       ├── index.php       # Tabbed settings interface
│       │       └── password.php    # Password change form
│       ├── auth/
│       │   ├── login.php           # Login form
│       │   ├── mfa_setup.php       # MFA QR code setup
│       │   └── mfa_verify.php      # MFA code verification
│       ├── frontend/
│       │   ├── about.php           # About page
│       │   ├── contact.php         # Contact form
│       │   ├── gallery/            # Gallery index + category views
│       │   ├── home.php            # Homepage
│       │   ├── layouts/            # Frontend layout template
│       │   └── partials/           # Nav, footer, lightbox, image protection, cookie banner
│       ├── home/                   # Legacy home view
│       └── layouts/
│           ├── admin.php           # Admin layout
│           └── frontend.php        # Frontend layout wrapper
├── config/
│   ├── config.php                  # 🔒 Local config (gitignored)
│   └── config.example.php         # Config template
├── database/
│   ├── schema.sql                  # Full database schema (all tables + seed data)
│   ├── migration_phase2.sql        # Categories + images tables
│   ├── migration_phase4.sql        # Settings table + default values
│   ├── migration_smtp.sql          # SMTP/contact mail settings
│   └── seed_admin.php              # Create default admin user
├── public/                         # ← Apache document root
│   ├── .htaccess                   # URL rewriting to index.php
│   ├── index.php                   # Front controller (all routes)
│   ├── assets/
│   │   ├── css/
│   │   │   ├── admin.css           # Admin panel styles
│   │   │   └── frontend.css        # Frontend styles
│   │   ├── img/
│   │   │   └── flags/
│   │   │       ├── es.svg          # Spanish flag (language switcher)
│   │   │       └── gb.svg          # British flag (language switcher)
│   │   └── js/
│   │       ├── admin.js            # Admin panel functionality
│   │       ├── contact-form.js     # Contact form + Turnstile validation
│   │       ├── cookie-consent.js   # GA4 cookie consent banner
│   │       ├── image-loading.js    # Lazy loading for images
│   │       ├── lightbox.js         # Lightbox viewer
│   │       ├── mobile-menu.js      # Mobile navigation menu
│   │       └── theme-toggle.js     # Dark/light mode toggle
│   └── uploads/                    # (placeholder directory)
├── storage/                        # ⚠️ OUTSIDE public root
│   ├── originals/{category_id}/    # Full-size originals (never served)
│   ├── thumbnails/{category_id}/   # 400px thumbnails
│   └── display/{category_id}/      # 1600px display versions
├── themes/
│   ├── minimal-light/
│   │   ├── theme.json
│   │   └── css/
│   │       ├── style.css
│   │       └── dark.css
│   ├── dark-room/
│   │   ├── theme.json
│   │   └── css/
│   │       ├── style.css
│   │       └── dark.css
│   └── editorial/
│       ├── theme.json
│       └── css/
│           ├── style.css
│           └── dark.css
├── .gitignore
└── README.md
```

---

## 🚀 Installation

### 1. Upload Files

Upload the entire project to your hosting via FTP (or use GitHub Actions — see below).

### 2. Configure Database

Copy the example config and edit with your credentials:

```bash
cp config/config.example.php config/config.php
```

Edit `config/config.php`:

```php
return [
    'app' => [
        'name' => 'Vernocchi Photography',
        'url' => 'https://vernocchi.es',
        'debug' => false,
        'default_language' => 'es',
        'key' => '', // Set a random 32-character string for encryption
    ],
    'database' => [
        'host' => 'localhost',
        'name' => 'your_database_name',
        'user' => 'your_database_user',
        'pass' => 'your_database_password',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name' => 'vernocchi_session',
        'lifetime' => 1800,
        'remember_days' => 30,
    ],
    'totp' => [
        'issuer' => 'Vernocchi Photography',
        'digits' => 6,
        'period' => 30,
        'algorithm' => 'sha1',
    ],
    'turnstile' => [
        'site_key' => '',
        'secret_key' => '',
    ],
];
```

### 3. Import Database

Import the full schema via phpMyAdmin or MySQL CLI:

```bash
mysql -u your_user -p your_database < database/schema.sql
```

This creates all tables (`users`, `remember_tokens`, `sessions`, `categories`, `images`, `settings`) and seeds default settings.

> **Existing installations:** If upgrading, run `database/migration_smtp.sql` to add SMTP/contact mail settings.

### 4. Seed Admin Account

```bash
php database/seed_admin.php
```

This creates the default admin user. If an admin already exists, it will be skipped (safe to re-run).

### 5. Set Document Root

Point Apache's document root to the `public/` directory. On Namecheap shared hosting, this is typically configured in cPanel.

### 6. Set Directory Permissions

Ensure storage directories are writable:

```bash
chmod -R 755 storage/
chmod -R 755 public/uploads/
```

For large batches that may time out in browser uploads, upload JPG files by FTP to `public/uploads` and use **Admin → Images → Upload → Import from FTP folder**. Imports are processed in batches of 100 files per run.

### 7. First Login

1. Navigate to `https://yourdomain.com/admin/login`
2. Log in with default credentials:
   - **Username**: `admin`
   - **Password**: `changeme`
3. **⚠️ You will be prompted to set up MFA** — scan the QR code with Microsoft Authenticator
4. After MFA setup, go to **Settings → Password** and change the default password immediately

---

## 🔄 Deployment

### Manual FTP

1. Connect to your Namecheap FTP server
2. Upload all project files maintaining the directory structure
3. Ensure `config/config.php` exists with correct credentials
4. Import `database/schema.sql` if first deployment
5. Verify `storage/` directories are writable

### GitHub Actions Auto-Deploy

The repository includes a workflow (`.github/workflows/deploy.yml`) that automatically deploys to Namecheap via FTP on every push to `main`.

**Setup:**

1. Go to your GitHub repository → **Settings** → **Secrets and variables** → **Actions**
2. Add these repository secrets:

| Secret | Description | Example |
|---|---|---|
| `FTP_HOST` | Namecheap FTP hostname | `ftp.yourdomain.com` |
| `FTP_USER` | FTP username | `user@yourdomain.com` |
| `FTP_PASS` | FTP password | `your_ftp_password` |
| `FTP_REMOTE_DIR` | Remote directory path | `/public_html/` |

3. Push to `main` — the workflow triggers automatically
4. You can also trigger it manually from the Actions tab (`workflow_dispatch`)

**Excluded from deploy:** `.git*`, `.github/`, `README.md`, `database/`, `.gitignore`

---

## 🗄️ Database Tables

| Table | Purpose |
|---|---|
| `users` | Admin account (username, email, password hash, TOTP secret, MFA status) |
| `remember_tokens` | Secure "remember me" tokens (hashed, with expiry) |
| `sessions` | Optional DB-backed sessions |
| `categories` | Photography categories (bilingual names, slug, cover image, sort order, visibility) |
| `images` | Photo metadata (filename, bilingual titles/alt text, dimensions, file size, sort order) |
| `settings` | Key-value settings store (site title, theme, watermark config, analytics, SEO, mail driver, SMTP credentials, etc.) |

---

## 🛣️ Routes

### Public Routes

| Method | Route | Description |
|---|---|---|
| GET | `/` | Homepage |
| GET | `/gallery` | All categories |
| GET | `/gallery/{slug}` | Images in a category |
| GET | `/about` | About the photographer |
| GET | `/contact` | Contact form |
| POST | `/contact/send` | Send contact message |
| GET | `/lang/{locale}` | Switch language (es/en) |
| GET | `/sitemap.xml` | XML sitemap |
| GET | `/image/thumb/{id}` | Serve thumbnail (protected) |
| GET | `/image/display/{id}` | Serve display image (protected) |
| GET | `/theme/style.css` | Active theme stylesheet |
| GET | `/theme/dark.css` | Active theme dark mode stylesheet |

### Admin Routes (require auth + MFA)

| Method | Route | Description |
|---|---|---|
| GET/POST | `/admin/login` | Admin login |
| GET/POST | `/admin/mfa/setup` | MFA setup (first login) |
| GET/POST | `/admin/mfa/verify` | MFA code verification |
| GET | `/admin/logout` | Logout |
| GET | `/admin/dashboard` | Dashboard with stats |
| GET | `/admin/categories` | List categories |
| GET/POST | `/admin/categories/create` `/admin/categories/store` | Create category |
| GET/POST | `/admin/categories/{id}/edit` `/admin/categories/{id}/update` | Edit category |
| POST | `/admin/categories/{id}/delete` | Delete category |
| POST | `/admin/categories/reorder` | Reorder categories (AJAX) |
| GET | `/admin/categories/{id}/images` | List images in category |
| GET/POST | `/admin/categories/{id}/images/upload` | Upload images |
| POST | `/admin/categories/{id}/images/reorder` | Reorder images (AJAX) |
| POST | `/admin/categories/{id}/images/set-cover` | Set category cover (AJAX) |
| POST | `/admin/images/import-ftp` | Import JPG files from `public/uploads` (batch) |
| GET/POST | `/admin/images/{id}/edit` `/admin/images/{id}/update` | Edit image metadata |
| POST | `/admin/images/{id}/delete` | Delete image |
| GET | `/admin/settings` | Settings (tabbed) |
| POST | `/admin/settings/general` | Update general settings |
| POST | `/admin/settings/security` | Update security settings (Turnstile) |
| POST | `/admin/settings/theme` | Update theme settings |
| POST | `/admin/settings/about` | Update about page settings |
| POST | `/admin/settings/watermark` | Update watermark settings |
| POST | `/admin/settings/analytics` | Update analytics settings |
| POST | `/admin/settings/seo` | Update SEO settings |
| POST | `/admin/settings/contact` | Update contact/mail settings |
| GET/POST | `/admin/settings/password` | Change password |

---

## 📦 Storage Structure

Images are stored **outside the public directory** and served through PHP for maximum protection:

```
storage/
├── originals/{category_id}/     # Full-size originals (never served to visitors)
├── thumbnails/{category_id}/    # 400px wide (gallery grid)
└── display/{category_id}/       # 1600px max wide (lightbox view)
```

---

## 🏗️ Architecture Notes

- **Zero external dependencies** — no Composer, no npm, no frameworks
- **Custom PSR-4 autoloader** via `spl_autoload_register()` in `app/bootstrap.php`
- **Front controller pattern** — all requests route through `public/index.php`
- **MVC structure** — Controllers, Models, Views cleanly separated
- **`declare(strict_types=1)`** on all PHP files
- **All vanilla JavaScript** — no jQuery, no frontend frameworks
- **Theme CSS** is served dynamically based on the active theme setting
- **Image processing** uses PHP's built-in GD library
- **AES-256-GCM encryption** for sensitive settings via `app/Core/Encryption.php`
- **Dual mail driver** — contact form sends via PHP `mail()` or SMTP (configurable in admin)

---

## ✅ First-Time Setup Checklist

- [ ] Upload files via FTP or configure GitHub Actions
- [ ] Create MySQL database via Namecheap cPanel
- [ ] Import `database/schema.sql`
- [ ] Configure `config/config.php` with database credentials
- [ ] Set a random 32-character `app.key` in `config/config.php` (used for encryption)
- [ ] Set Apache document root to `public/`
- [ ] Verify `storage/` directories are writable (`chmod 755`)
- [ ] Run `php database/seed_admin.php`
- [ ] Log in at `/admin/login` (admin / changeme)
- [ ] Set up MFA with Microsoft Authenticator
- [ ] Change default password in Settings → Password
- [ ] Configure site settings (title, theme, contact email, analytics)
- [ ] Configure mail settings (PHP mail or SMTP) in Settings → Contact
- [ ] Create your first photography category
- [ ] Upload your first photos
- [ ] Configure watermark settings (optional)
- [ ] Set up SEO meta tags and Open Graph image
- [ ] Add Google Analytics tracking ID (optional)
- [ ] Edit About page content

---

## 📄 License

All rights reserved. This is a private photography portfolio.
