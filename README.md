# vernocchi.es — Photography Portfolio Application

Plain PHP 8.1 MVC application for a bilingual photography portfolio with secure admin, MFA, image protection, category/image management, themes, settings, and FTP deployment.

## Requirements

- PHP 8.1+
- MySQL 8+
- PDO MySQL extension
- GD extension
- Apache with `mod_rewrite`

## Installation

1. Upload project files.
2. Copy config file:
   - `cp config/config.example.php config/config.php`
3. Update database credentials in `config/config.php`.
4. Import `database/schema.sql`.
5. Seed default admin:
   - `php database/seed_admin.php`
6. Set Apache document root to `/public`.
7. Ensure `/storage` and `/public/uploads` are writable.

## Default Credentials

- Username: `admin`
- Password: `changeme`

Change the default password immediately after first login.

## Manual FTP Deployment

1. Connect to Namecheap FTP with credentials.
2. Upload all files maintaining directory structure.
3. Set document root to `public/` folder.
4. Copy `config/config.example.php` to `config/config.php` and edit.
5. Import `database/schema.sql` via phpMyAdmin.
6. Ensure `storage/` directories are writable (chmod 755).
7. Navigate to your domain, log in with `admin` / `changeme`.
8. Set up MFA, change password.

## GitHub Actions Auto-Deploy

1. In your GitHub repo, go to **Settings → Secrets and variables → Actions**.
2. Add these repository secrets:
   - `FTP_HOST` — Namecheap FTP hostname
   - `FTP_USER` — FTP username
   - `FTP_PASS` — FTP password
   - `FTP_REMOTE_DIR` — remote directory path (e.g., `/public_html/`)
3. Push to `main` branch to auto-deploy.

Workflow file: `.github/workflows/deploy.yml`

## First-Time Setup Checklist

- [ ] Upload files via FTP or push to trigger GitHub Actions
- [ ] Create MySQL database via Namecheap cPanel
- [ ] Import `database/schema.sql`
- [ ] Configure `config/config.php`
- [ ] Set document root to `public/`
- [ ] Verify storage directories are writable
- [ ] Log in as admin, change password
- [ ] Set up MFA with Microsoft Authenticator
- [ ] Configure site settings (title, theme, analytics, etc.)
- [ ] Create your first category
- [ ] Upload your first photos

## Notes

- Admin is English-only.
- Public frontend supports Spanish and English.
- Images are served via PHP (`/image/thumb/{id}`, `/image/display/{id}`), never direct storage links.
- Storage structure:
  - `storage/originals/{category_id}/`
  - `storage/thumbnails/{category_id}/`
  - `storage/display/{category_id}/`
