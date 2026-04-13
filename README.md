# vernocchi.es — Phase 1 Foundation

Plain PHP 8.1 MVC foundation for the Vernocchi Photography portfolio admin and public placeholder pages.

## Requirements

- PHP 8.1+
- MySQL 8+
- PDO MySQL extension
- GD extension (for later image phases)
- Apache with `mod_rewrite`

## Installation

1. Upload project files.
2. Copy config file:
   - `cp config/config.example.php config/config.php`
3. Update database credentials in `config/config.php`.
4. Import schema:
   - `database/schema.sql`
5. Seed default admin:
   - `php database/seed_admin.php`
6. Set Apache document root to `/public`.
7. Ensure `/storage` and `/public/uploads` are writable.

## Default Credentials

- Username: `admin`
- Password: `changeme`

On first login you are forced to set up MFA with Microsoft Authenticator.
Change the default password immediately after first login in production environments.

## MFA Setup Flow

1. Log in at `/admin/login`.
2. Scan the QR code shown at `/admin/mfa/setup` with Microsoft Authenticator.
3. Enter a valid 6-digit TOTP code.
4. MFA is enabled and you are redirected to `/admin/dashboard`.

> Note: Phase 1 uses Google Charts URL rendering for the QR code, which sends the provisioning URI to Google to generate the image. This is kept intentionally to match the specified implementation.

## Directory Structure

```text
vernocchi.es/
├── public/
│   ├── index.php
│   ├── .htaccess
│   ├── assets/
│   │   ├── css/admin.css
│   │   ├── js/admin.js
│   │   └── images/
│   └── uploads/
├── app/
│   ├── bootstrap.php
│   ├── Core/
│   ├── Controllers/
│   ├── Models/
│   └── Views/
├── config/
│   ├── config.example.php
│   └── config.php (ignored)
├── database/
│   ├── schema.sql
│   └── seed_admin.php
└── storage/
    ├── logs/
    └── originals/
```

## Notes for Namecheap / FTP Deployment

- Keep app code outside web root; only `/public` should be the document root.
- Upload with FTP preserving directory structure.
- `config/config.php` stays server-local and is gitignored.
- No Composer dependencies are required.
