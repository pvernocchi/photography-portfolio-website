<div align="center">

# 📸 Photography Portfolio Website

### A beautifully crafted, self-hosted portfolio for photographers

*Zero dependencies. Zero compromises. Pure PHP.*

[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL 8+](https://img.shields.io/badge/MySQL-8%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue?style=for-the-badge)](LICENSE)

[🌐 Live Demo](https://www.vernocchi.es) · [🚀 Getting Started](#-getting-started) · [🎨 Themes](#-themes) · [🗂️ Project Layout](#%EF%B8%8F-project-layout)

---

</div>

## ✨ What Is This?

A **complete, self-contained** photography portfolio built from the ground up with **PHP 8.1** — no frameworks, no Composer, no npm. Just clean, dependency-free code designed to run effortlessly on standard shared hosting with MySQL.

Everything a photographer needs is baked in: a stunning public gallery, a powerful admin panel, bilingual support (🇪🇸 Spanish & 🇬🇧 English), three handcrafted themes, and aggressive image-protection to keep your work safe.

> 💡 A browser-based setup wizard handles the entire installation — no CLI, no config files, no headaches.

---

## 🎯 Features at a Glance

<table>
<tr>
<td width="50%" valign="top">

### 🖼️ Portfolio & Gallery
- 📂 Bilingual categories with drag-and-drop ordering
- 🔍 Full-screen lightbox with keyboard & swipe navigation
- 🏠 Hero section with featured category highlights
- 📝 About page & contact form
- 🗺️ Dynamic XML sitemap for SEO

</td>
<td width="50%" valign="top">

### 📤 Image Management
- ⬆️ Browser upload (JPG, up to 20 MB)
- 📦 Batch import from FTP folder
- 🔄 Auto-generate thumbnails (400 px) & display copies (1600 px)
- 🧹 Automatic EXIF stripping for privacy
- 🏷️ Many-to-many: one image → multiple categories

</td>
</tr>
<tr>
<td width="50%" valign="top">

### 🛡️ Image Protection
- 🚫 No direct image URLs — served through PHP
- 🖥️ Canvas rendering blocks "Save Image As"
- 🔒 Right-click, drag, print & shortcut blocking
- 🎭 CSS overlay on every image
- 🔗 Hotlink protection via referer validation
- 💧 Optional configurable text watermark

</td>
<td width="50%" valign="top">

### 🔐 Security
- 🔑 TOTP-based MFA (Microsoft Authenticator)
- 🛡️ Session hardening (IP + UA binding, timeout)
- 🎫 CSRF tokens on every form
- 🗃️ PDO prepared statements everywhere
- 🔏 AES-256-GCM encryption for sensitive data
- 🔤 12-character minimum passwords

</td>
</tr>
<tr>
<td width="50%" valign="top">

### ✉️ Mail System
- 📬 Dual driver: PHP `mail()` or SMTP (TLS/SSL)
- 🔐 SMTP credentials encrypted at rest
- 🐛 Debug logging toggle in admin
- 🕵️ Contact email obfuscated in HTML

</td>
<td width="50%" valign="top">

### 📈 SEO & Analytics
- 🏷️ Meta titles & descriptions (both languages)
- 🌍 Open Graph & Twitter Card support
- 📊 Google Analytics GA4 integration
- 🍪 GDPR cookie-consent banner

</td>
</tr>
</table>

### 🌐 Social Links

Connect your audience across platforms — **Instagram**, **Facebook**, **Twitter/X**, **LinkedIn**, **YouTube**, and **GitHub** — all configurable from the admin panel.

---

## 🎨 Themes

Three beautiful, responsive themes — each with automatic **light/dark mode** via `prefers-color-scheme`:

| Theme | ✨ Vibe |
|:---:|:---|
| ☀️ **Minimal Light** | Clean white backgrounds, thin typography, generous whitespace |
| 🌑 **Dark Room** | Cinematic charcoal backdrop that makes images *pop* |
| 📰 **Editorial** | Magazine-inspired grid with dynamic, varied card sizes |

---

## 📋 Requirements

| | Requirement | Detail |
|:---:|:---|:---|
| 🐘 | **PHP** | 8.1 or newer |
| 🗄️ | **MySQL** | 8 or newer |
| 🌐 | **Web Server** | Apache with `mod_rewrite` |
| 🧩 | **PHP Extensions** | GD **or** Imagick, OpenSSL, PDO + PDO_MySQL, mbstring |
| ✉️ | **Mail** | PHP `mail()` **or** external SMTP server |

---

## 🚀 Getting Started

### Step 1 — Upload the Project

Copy all files to your server via FTP, or set up the included [GitHub Actions workflow](#-cicd-with-github-actions) for automatic deploys.

### Step 2 — Point Document Root to `public/`

On shared hosting (e.g., cPanel), set your document root to the `public/` directory. The front controller at `public/index.php` routes every request.

### Step 3 — Set Permissions

```bash
chmod -R 755 storage/
chmod -R 755 public/uploads/
```

### Step 4 — Run the Setup Wizard 🧙

Open your site in a browser. The app auto-detects that it hasn't been configured and redirects to the **interactive setup wizard**:

| Step | What Happens |
|:---:|:---|
| 1️⃣ | **Requirements** — verifies PHP version, extensions & directory permissions |
| 2️⃣ | **Database** — enter MySQL credentials & test the connection |
| 3️⃣ | **Schema** — imports `database/schema.sql` (existing tables are skipped) |
| 4️⃣ | **Admin Account** — create your username, email & password |
| 5️⃣ | **Configuration** — set site name, URL, language & encryption key |
| 6️⃣ | **Done!** — creates `storage/installed.lock` & links to admin login |

> 🔄 If the wizard detects an existing config, reachable database, and admin user, it auto-completes and redirects to the homepage.

<details>
<summary>🔧 <strong>Manual setup</strong> (advanced users)</summary>
<br>

1. Copy `config/config.example.php` → `config/config.php` and fill in your values
2. Import `database/schema.sql` into your MySQL database
3. Run `php database/seed_admin.php` to create the admin account
4. Create `storage/installed.lock` to mark installation as complete

</details>

### Step 5 — First Login 🔐

1. Navigate to `/admin/login`
2. Enter the credentials you just created
3. Scan the QR code with **Microsoft Authenticator** to set up MFA
4. Configure settings, pick a theme, and start uploading! 🎉

> 💡 **Pro tip:** For large batches, FTP your JPGs into `public/uploads/` and use **Admin → Images → Upload → Import from FTP folder** (processes 100 files per run).

---

## 🚢 Deployment

### 📁 Manual FTP

Upload files → ensure `storage/` is writable → visit the site to trigger the wizard. Done!

### ⚙️ CI/CD with GitHub Actions

A workflow at `.github/workflows/deploy.yml` deploys via FTP on every push to `main` (or manually via `workflow_dispatch`).

Add these secrets in **Settings → Secrets and variables → Actions**:

| 🔑 Secret | 📝 Example |
|:---|:---|
| `FTP_HOST` | `ftp.yourdomain.com` |
| `FTP_USER` | `user@yourdomain.com` |
| `FTP_PASS` | `your_ftp_password` |
| `FTP_REMOTE_DIR` | `/public_html/` |

> 📌 Excluded from deploy: `.git*`, `.github/`, `README.md`, `database/`, `.gitignore`

---

## 🗂️ Project Layout

```
📦 photography-portfolio-website
├── 📁 app/
│   ├── 📄 bootstrap.php              # Autoloader, config, session, error handling
│   ├── 📁 Core/                      # Framework internals
│   │   ├── 🔐 Auth.php               #   Authentication helper
│   │   ├── 🏗️ Controller.php          #   Base controller
│   │   ├── 🎫 CSRF.php               #   CSRF token management
│   │   ├── 🗄️ Database.php            #   PDO MySQL singleton
│   │   ├── 🔏 Encryption.php          #   AES-256-GCM encrypt/decrypt
│   │   ├── 🖼️ ImageProcessor.php      #   GD image processing + watermark
│   │   ├── 🌍 Language.php            #   Multilingual system
│   │   ├── ✉️ Mailer.php              #   PHP mail + SMTP driver
│   │   ├── 🛤️ Router.php              #   URL routing with middleware
│   │   ├── 🔑 Session.php             #   Session management + remember-me
│   │   ├── 🎨 ThemeEngine.php         #   Theme resolution
│   │   └── 🔢 TOTP.php               #   RFC 6238 TOTP implementation
│   ├── 📁 Controllers/               # Request handlers
│   ├── 📁 Languages/                 # Translation files (en.php, es.php)
│   ├── 📁 Models/                    # Data access (Category, Image, Setting, User, RememberToken)
│   └── 📁 Views/                     # Templates (admin, auth, frontend)
├── 📁 config/
│   └── 📄 config.example.php         # Configuration template
├── 📁 database/
│   ├── 📄 schema.sql                 # Full schema + seed data
│   ├── 📄 seed_admin.php             # CLI admin user creation
│   └── 📁 migrations/               # Incremental SQL migrations
├── 📁 public/                        # Apache document root
│   ├── 📄 .htaccess                  # Rewrite rules
│   ├── 📄 index.php                  # Front controller
│   ├── 📄 install.php                # Setup wizard (self-locking)
│   └── 📁 assets/                   # CSS, JS, icons
├── 📁 storage/                       # Outside the web root
│   ├── 📁 originals/                # Full-size uploads (never served directly)
│   ├── 📁 thumbnails/               # 400 px thumbnails
│   └── 📁 display/                  # 1600 px display copies
└── 📁 themes/                        # Minimal Light, Dark Room, Editorial
    └── 📁 <theme>/
        ├── 📄 theme.json
        └── 📁 css/
            ├── 🎨 style.css
            └── 🌙 dark.css
```

---

## 🗃️ Database

Seven tables, all **InnoDB** with `utf8mb4`:

| Table | 📝 Role |
|:---|:---|
| `users` | Single admin account (credentials, TOTP secret, MFA flag) |
| `remember_tokens` | Hashed remember-me tokens with expiry |
| `sessions` | Optional database-backed sessions |
| `categories` | Bilingual names, slug, cover image, sort order, visibility |
| `images` | Filename, original filename, bilingual titles/alt text, dimensions, size |
| `image_category` | Many-to-many join with per-category sort order |
| `settings` | Key-value store for all config (theme, SEO, mail, social, etc.) |

---

## 🏛️ Architecture

| Principle | Description |
|:---:|:---|
| 📦 **Zero Dependencies** | No Composer, no npm, no jQuery, no frameworks |
| 🏗️ **MVC Pattern** | Controllers → Models → Views, cleanly separated |
| 🚪 **Front Controller** | Every request enters through `public/index.php` |
| ⚡ **Custom Autoloader** | PSR-4 style, registered in `app/bootstrap.php` |
| ✅ **Strict Typing** | `declare(strict_types=1)` on all PHP files |
| 🎨 **Dynamic Themes** | CSS served based on the active theme setting |
| 🧙 **Setup Wizard** | Standalone, framework-independent, permanently locked after first run |

---

## 📄 License

This project is licensed under the [**GNU General Public License v3.0**](LICENSE).

---

<div align="center">

**Made with ❤️ for photographers**

*If you find this project useful, consider giving it a ⭐*

</div>
