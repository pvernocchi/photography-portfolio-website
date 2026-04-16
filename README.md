# Photography Portfolio Website

A self-hosted photography portfolio built from the ground up with PHP 8.1. No frameworks, no Composer, no npm — just clean, dependency-free code designed to run on standard shared hosting with MySQL.

You can view this project live at my own [Portfolio website](https://www.vernocchi.es).

---

## Overview

This application gives photographers a complete, self-contained website with a public-facing portfolio and a private admin panel. It supports two languages (Spanish and English), three visual themes, and includes aggressive image-protection measures to discourage unauthorized downloading. A browser-based setup wizard handles first-time installation so you never need to touch config files or run CLI commands.

---

## Key Capabilities

**Portfolio & Gallery**
- Organize photos into bilingual categories with drag-and-drop ordering
- Full-screen lightbox viewer with keyboard and swipe navigation
- Homepage hero section with featured category highlights
- About page and contact form (honeypot + CAPTCHA: Cloudflare Turnstile or Google reCAPTCHA v2)
- Dynamic XML sitemap for search engines

**Image Management**
- Browser upload (JPG, up to 20 MB) or batch import from FTP
- Automatic thumbnail (400 px) and display-size (1600 px) generation via GD
- EXIF stripping for privacy
- Image library with filters (all, unassigned, duplicates) and bulk actions
- Many-to-many assignment: one image can belong to several categories

**Image Protection**
- Images served through PHP — no direct URLs exposed
- Canvas rendering blocks "Save Image As"
- Right-click, drag, print, and keyboard-shortcut blocking
- CSS overlay on every image
- Hotlink protection via referer validation
- `Cache-Control: no-store` and `X-Content-Type-Options: nosniff` headers
- Optional text watermark (position, opacity, font size configurable)

**Themes**
| Theme | Character |
|---|---|
| Minimal Light | White backgrounds, thin typography, lots of whitespace |
| Dark Room | Charcoal background, cinematic feel, images pop |
| Editorial | Magazine-inspired, structured grid with varied card sizes |

All three respond to `prefers-color-scheme` for automatic dark/light switching.

**Security**
- TOTP-based MFA (Microsoft Authenticator) — required on first login
- Session hardening with IP + user-agent binding, inactivity timeout, ID regeneration
- CSRF tokens on every form
- PDO prepared statements everywhere
- AES-256-GCM encryption for sensitive settings (SMTP passwords, etc.)
- 12-character minimum passwords

**Mail**
- Dual driver: PHP `mail()` or SMTP with AUTH LOGIN and TLS/SSL
- SMTP credentials encrypted at rest
- Debug logging toggle in admin
- Contact email obfuscated in HTML to deter scrapers

**SEO & Analytics**
- Meta titles and descriptions in both languages
- Open Graph image and Twitter card support
- Google Analytics GA4 with GDPR cookie-consent banner

**Social Links**
- Instagram, Facebook, Twitter/X, LinkedIn, YouTube, GitHub — configured in admin and displayed on the Contact page

---

## Requirements

| Requirement | Detail |
|---|---|
| PHP | 8.1 or newer |
| MySQL | 8 or newer |
| Web server | Apache with `mod_rewrite` |
| PHP extensions | GD **or** Imagick, OpenSSL, PDO + PDO_MySQL, mbstring |
| Mail | PHP `mail()` function **or** an external SMTP server |

---

## Getting Started

### 1. Upload the project

Copy all files to your server via FTP, or configure the included GitHub Actions workflow for automatic deploys (see [Deployment](#deployment) below).

### 2. Point the document root at `public/`

On shared hosting (e.g. Namecheap), configure this in cPanel. The front controller at `public/index.php` routes every request.

### 3. Set permissions

```bash
chmod -R 755 storage/
chmod -R 755 public/uploads/
```

### 4. Run the setup wizard

Open your site in a browser. The app detects that it hasn't been configured yet and redirects to `/install.php`. The wizard walks through six steps:

1. **Requirements** — verifies PHP version, extensions, and directory write access
2. **Database** — enter MySQL credentials and test the connection
3. **Schema** — imports `database/schema.sql` (existing tables are skipped)
4. **Admin account** — create your username, email, and password
5. **Configuration** — set site name, URL, language, and encryption key; writes `config/config.php`
6. **Done** — creates `storage/installed.lock` and links to the admin login

> If the wizard detects an existing config, reachable database, and admin user, it stamps the lock file automatically and redirects to the homepage.

> **Manual setup** is still possible: copy `config/config.example.php` → `config/config.php`, fill in your values, import the schema, run `php database/seed_admin.php`, and create `storage/installed.lock`.

### 5. First login

1. Go to `/admin/login`
2. Enter the credentials you just created
3. Complete MFA setup (scan the QR code with Microsoft Authenticator)
4. Configure settings, choose a theme, and start uploading photos

> **Tip:** For large batches, FTP your JPGs into `public/uploads/` and use **Admin → Images → Upload → Import from FTP folder** (processes 100 files per run).

---

## Deployment

### Manual FTP

Upload files, ensure `storage/` is writable, and visit the site to trigger the wizard.

### GitHub Actions

A workflow at `.github/workflows/deploy.yml` deploys via FTP on every push to `main` (or manually via `workflow_dispatch`).

Add these secrets in **Settings → Secrets and variables → Actions**:

| Secret | Example |
|---|---|
| `FTP_HOST` | `ftp.yourdomain.com` |
| `FTP_USER` | `user@yourdomain.com` |
| `FTP_PASS` | `your_ftp_password` |
| `FTP_REMOTE_DIR` | `/public_html/` |

Files excluded from deploy: `.git*`, `.github/`, `README.md`, `database/`, `.gitignore`.

---

## Project Layout

```
├── app/
│   ├── bootstrap.php          # Autoloader, config, session, error handling
│   ├── Core/                  # Framework internals
│   │   ├── Auth.php           #   Authentication helper
│   │   ├── Controller.php     #   Base controller
│   │   ├── CSRF.php           #   CSRF token management
│   │   ├── Database.php       #   PDO MySQL singleton
│   │   ├── Encryption.php     #   AES-256-GCM encrypt/decrypt
│   │   ├── ImageProcessor.php #   GD image processing + watermark
│   │   ├── Language.php       #   Multilingual system
│   │   ├── Mailer.php         #   PHP mail + SMTP driver
│   │   ├── Router.php         #   URL routing with middleware
│   │   ├── Session.php        #   Session management + remember-me
│   │   ├── ThemeEngine.php    #   Theme resolution
│   │   └── TOTP.php           #   RFC 6238 TOTP implementation
│   ├── Controllers/           # Request handlers
│   ├── Languages/             # Translation files (en.php, es.php)
│   ├── Models/                # Data access (Category, Image, Setting, User, RememberToken)
│   └── Views/                 # Templates (admin, auth, frontend, layouts)
├── config/
│   └── config.example.php     # Configuration template
├── database/
│   ├── schema.sql             # Full schema + seed data
│   ├── seed_admin.php         # CLI admin user creation
│   └── migrations/            # Incremental SQL migrations for existing installs
│       └── 001_captcha_settings.sql
├── public/                    # Apache document root
│   ├── .htaccess              # Rewrite rules
│   ├── index.php              # Front controller
│   ├── install.php            # Setup wizard (self-locking)
│   └── assets/                # CSS, JS, flag icons
├── storage/                   # Outside the web root
│   ├── originals/             # Full-size uploads (never served directly)
│   ├── thumbnails/            # 400 px thumbnails
│   └── display/               # 1600 px display copies
└── themes/                    # Minimal Light, Dark Room, Editorial
    └── <theme>/
        ├── theme.json
        └── css/
            ├── style.css
            └── dark.css
```

---

## Database

Seven tables, all InnoDB with `utf8mb4`:

| Table | Role |
|---|---|
| `users` | Single admin account (credentials, TOTP secret, MFA flag) |
| `remember_tokens` | Hashed remember-me tokens with expiry |
| `sessions` | Optional database-backed sessions |
| `categories` | Bilingual names, slug, cover image, sort order, visibility |
| `images` | Filename, original filename, bilingual titles/alt text, dimensions, size |
| `image_category` | Many-to-many join with per-category sort order |
| `settings` | Key-value store for all configuration (theme, SEO, mail, social, etc.) |

---

## Architecture

- **Zero dependencies** — no Composer, no npm, no jQuery, no frameworks
- **MVC** — Controllers → Models → Views, cleanly separated
- **Front controller** — every request enters through `public/index.php`
- **Custom autoloader** — PSR-4 style, registered in `app/bootstrap.php`
- **Strict typing** — `declare(strict_types=1)` on all PHP files
- **Theme CSS** served dynamically based on the active setting
- **Setup wizard** — standalone script, independent of the main framework, permanently locked after first run

---

## License

This project is licensed under the [GNU General Public License v3.0](LICENSE).
