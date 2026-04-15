# Photography Portfolio Website

A bilingual (Spanish/English) photography portfolio web application built with **plain PHP 8.1** — zero Composer dependencies, zero frameworks. Designed for Namecheap shared hosting with MySQL.

## 🆕 Recent Updates

- **Setup Wizard**: Added a guided first-time installation wizard (`/install.php`) — no more manual config editing or CLI scripts. Detects existing installations automatically and locks itself after setup.
- **PR #34**: Added social network links to the Contact page and obfuscated the contact email address against scrapers.
- **PR #30**: Added duplicated-images filter in the Admin Image Library.
- **PR #29**: Added admin image library filters and bulk category/gallery actions.
- Added DB index `idx_images_original_filename` to speed up duplicate image filtering.

## ✨ Features

### 📸 Image Management
- Upload JPG images up to 20MB through a secure admin interface
- Import JPG images uploaded via FTP to `public/uploads` (batched import from admin)
- Automatic thumbnail (400px) and display (1600px) image generation via GD library
- EXIF data stripping for privacy protection
- Drag-and-drop reordering of photos within categories
- Image Library filters: all images, unassigned images, and duplicated images
- Bulk actions: assign selected images to a gallery, remove from gallery, or delete from storage
- Images can be assigned to multiple categories (many-to-many)
- Flat category system with name (ES/EN), slug, and cover image

### 📧 Contact & Mail
- Contact form with honeypot + Cloudflare Turnstile spam protection
- Configurable mail driver: PHP `mail()` or SMTP
- Full SMTP support with AUTH LOGIN, TLS/SSL encryption
- SMTP credentials encrypted at rest via AES-256-GCM
- SMTP debug logging toggle in admin (logs written to `storage/logs/smtp-debug.log` or custom path via config)
- Customizable sender name and email address
- Contact email address obfuscated in HTML to deter scrapers
- Social network links (Instagram, Facebook, Twitter/X, LinkedIn, YouTube, GitHub) displayed on the Contact page

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
- **Contact**: Mail driver selection (PHP mail / SMTP), SMTP host, port, encryption, debug logging toggle, credentials, sender name/email
- **Social**: Social network profile URLs (Instagram, Facebook, Twitter/X, LinkedIn, YouTube, GitHub)

### 📄 Public Pages
- **Homepage**: Welcome hero + featured categories
- **Gallery**: Category grid → image grid within category
- **Lightbox**: Full-screen image viewer with ← → keyboard/swipe navigation, image counter
- **About**: Photographer bio page (editable from admin)
- **Contact**: Contact form with honeypot + Cloudflare Turnstile spam protection, sends via configurable mail driver (PHP `mail()` or SMTP); displays social network links and obfuscated contact email
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
│   ├── migration_social_networks.sql # Social network settings
│   └── seed_admin.php              # Create default admin user
├── public/                         # ← Apache document root
│   ├── .htaccess                   # URL rewriting to index.php
│   ├── index.php                   # Front controller + installation guard
│   ├── install.php                 # First-time setup wizard (self-locks after setup)
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
│   ├── originals/                  # Full-size originals (never served)
│   ├── thumbnails/                 # 400px thumbnails
│   ├── display/                    # 1600px display versions
│   └── installed.lock              # Created by wizard after setup; locks the installer
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

### 2. Set Document Root

Point Apache's document root to the `public/` directory. On Namecheap shared hosting, this is typically configured in cPanel.

### 3. Set Directory Permissions

Ensure storage directories are writable:

```bash
chmod -R 755 storage/
chmod -R 755 public/uploads/
```

### 4. Run the Setup Wizard

Visit your site in a browser — `public/index.php` automatically detects that the app is not yet configured and redirects to `/install.php`.

The wizard guides you through six steps:

| Step | What it does |
|---|---|
| **1 – Requirements** | Checks PHP ≥ 8.1, required extensions (PDO, PDO_MySQL, GD/Imagick, mbstring, openssl), and that `storage/` and `config/` are writable |
| **2 – Database** | Enter your MySQL credentials; the wizard tests the connection before proceeding |
| **3 – DB Setup** | Runs `database/schema.sql` automatically; existing tables are skipped safely |
| **4 – Admin Account** | Create the administrator username, email, and password |
| **5 – Configuration** | Set the site name, URL, default language, and auto-generate the encryption key; writes `config/config.php` |
| **6 – Complete** | Links you directly to `/admin/login`; creates `storage/installed.lock` so the wizard can never run again |

> **Existing installations:** If the wizard detects that `config/config.php` exists, the database is reachable, and at least one admin user is present, it automatically stamps `storage/installed.lock` and redirects to `/`. No data is ever overwritten by accident.

> **Manual alternative:** You can still configure the app by hand — copy `config/config.example.php` to `config/config.php`, edit it with your credentials, import `database/schema.sql`, and run `php database/seed_admin.php`. Then create `storage/installed.lock` yourself to suppress the wizard.

### 5. First Login

1. Navigate to `https://yourdomain.com/admin/login`
2. Log in with the credentials you chose in the wizard
3. **⚠️ You will be prompted to set up MFA** — scan the QR code with Microsoft Authenticator
4. After MFA setup, configure site settings, theme, and mail driver via **Admin → Settings**

For large batches that may time out in browser uploads, upload JPG files by FTP to `public/uploads` and use **Admin → Images → Upload → Import from FTP folder**. Imports are processed in batches of 100 files per run.

---

## 🔄 Deployment

### Manual FTP

1. Connect to your Namecheap FTP server
2. Upload all project files maintaining the directory structure
3. Verify `storage/` directories are writable
4. Visit your site — the setup wizard runs automatically on first load

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
| `images` | Photo metadata (filename, original filename, bilingual titles/alt text, dimensions, file size) |
| `image_category` | Many-to-many mapping between images and categories with per-category sort order |
| `settings` | Key-value settings store (site title, theme, watermark config, analytics, SEO, mail driver, SMTP credentials, social network URLs, etc.) |

---

## 🛣️ Routes

### Public Routes

| Method | Route | Description |
|---|---|---|
| GET | `/install.php` | First-time setup wizard (locked after setup) |
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
| GET | `/admin/images` | Image Library (all images) |
| GET | `/admin/images/unassigned` | Filter images without any category |
| GET | `/admin/images/duplicated` | Filter duplicated images by original filename |
| GET/POST | `/admin/images/upload` | Upload images |
| GET | `/admin/categories/{id}/images` | List images in category |
| POST | `/admin/categories/{id}/images/reorder` | Reorder images (AJAX) |
| POST | `/admin/categories/{id}/images/set-cover` | Set category cover (AJAX) |
| POST | `/admin/images/import-ftp` | Import JPG files from `public/uploads` (batch) |
| GET/POST | `/admin/images/assign` | Assign recent uploads/imports to categories |
| POST | `/admin/images/bulk-action` | Bulk assign/remove/delete selected images |
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
| POST | `/admin/settings/social` | Update social network settings |
| GET/POST | `/admin/settings/password` | Change password |

---

## 📦 Storage Structure

Images are stored **outside the public directory** and served through PHP for maximum protection:

```
storage/
├── originals/     # Full-size originals (never served to visitors)
├── thumbnails/    # 400px wide (gallery grid)
└── display/       # 1600px max wide (lightbox view)
```

---

## 🏗️ Architecture Notes

- **Zero external dependencies** — no Composer, no npm, no frameworks
- **Custom PSR-4 autoloader** via `spl_autoload_register()` in `app/bootstrap.php`
- **Front controller pattern** — all requests route through `public/index.php`; an IIFE guard at the top redirects to `/install.php` when the app is not yet configured
- **Setup wizard** — `public/install.php` is a fully standalone script (no dependency on the main app framework); locked permanently after first setup via `storage/installed.lock`
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
- [ ] Set Apache document root to `public/`
- [ ] Set `storage/` and `public/uploads/` to writable (`chmod 755`)
- [ ] Visit your site — the setup wizard (`/install.php`) runs automatically
  - [ ] Confirm all server requirements pass (Step 1)
  - [ ] Enter database credentials and verify connection (Step 2)
  - [ ] Install the database schema (Step 3)
  - [ ] Create the admin account (Step 4)
  - [ ] Set site name, URL, default language, and encryption key (Step 5)
- [ ] Log in at `/admin/login` and complete MFA setup with Microsoft Authenticator
- [ ] Configure site settings (title, theme, contact email, analytics) in **Admin → Settings**
- [ ] Configure mail settings (PHP mail or SMTP) in **Admin → Settings → Contact**
- [ ] Configure social network profile URLs in **Admin → Settings → Social** (optional)
- [ ] Create your first photography category
- [ ] Upload your first photos
- [ ] Configure watermark settings (optional)
- [ ] Set up SEO meta tags and Open Graph image
- [ ] Add Google Analytics tracking ID (optional)
- [ ] Edit About page content

---

## 📄 License

All rights reserved. This is a private photography portfolio.
