-- Admin users table
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    totp_secret VARCHAR(64) DEFAULT NULL,
    mfa_enabled TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Remember-me tokens
CREATE TABLE remember_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token_hash (token_hash),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sessions table (optional, for DB-backed sessions)
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT,
    last_activity INT UNSIGNED,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Photography categories
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name_es VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    cover_image_id INT UNSIGNED DEFAULT NULL,
    sort_order INT UNSIGNED DEFAULT 0,
    is_visible TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Images/photographs (no category_id — assigned via join table)
CREATE TABLE images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    title_es VARCHAR(255) DEFAULT NULL,
    title_en VARCHAR(255) DEFAULT NULL,
    alt_es VARCHAR(255) DEFAULT NULL,
    alt_en VARCHAR(255) DEFAULT NULL,
    width INT UNSIGNED DEFAULT NULL,
    height INT UNSIGNED DEFAULT NULL,
    file_size INT UNSIGNED DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_images_original_filename (original_filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE categories ADD FOREIGN KEY (cover_image_id) REFERENCES images(id) ON DELETE SET NULL;

-- Many-to-many: images ↔ categories
CREATE TABLE image_category (
    image_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    sort_order INT UNSIGNED DEFAULT 0,
    PRIMARY KEY (image_id, category_id),
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category_sort (category_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    setting_type ENUM('text', 'textarea', 'html', 'number', 'boolean', 'select', 'image') DEFAULT 'text',
    setting_group VARCHAR(50) DEFAULT 'general',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (setting_key, setting_value, setting_type, setting_group) VALUES
('site_title', 'Vernocchi Photography', 'text', 'general'),
('site_description_es', '', 'textarea', 'general'),
('site_description_en', '', 'textarea', 'general'),
('default_language', 'es', 'select', 'general'),
('contact_email', 'admin@vernocchi.es', 'text', 'general'),
('turnstile_site_key', '', 'text', 'general'),
('turnstile_secret_key', '', 'text', 'general'),
('active_theme', 'minimal-light', 'select', 'theme'),
('about_content_es', '<p>Placeholder about content in Spanish</p>', 'html', 'about'),
('about_content_en', '<p>Placeholder about content in English</p>', 'html', 'about'),
('about_photo', NULL, 'image', 'about'),
('watermark_enabled', '0', 'boolean', 'watermark'),
('watermark_text', 'vernocchi.es', 'text', 'watermark'),
('watermark_position', 'bottom-right', 'select', 'watermark'),
('watermark_opacity', '30', 'number', 'watermark'),
('watermark_font_size', '16', 'number', 'watermark'),
('google_analytics_id', '', 'text', 'analytics'),
('smtp_host', '', 'text', 'contact'),
('smtp_port', '587', 'text', 'contact'),
('smtp_encryption', 'tls', 'select', 'contact'),
('smtp_username', '', 'text', 'contact'),
('smtp_password', '', 'text', 'contact'),
('smtp_from_name', '', 'text', 'contact'),
('smtp_from_email', '', 'text', 'contact'),
('meta_title_es', 'Vernocchi Fotografía', 'text', 'seo'),
('meta_title_en', 'Vernocchi Photography', 'text', 'seo'),
('meta_description_es', '', 'textarea', 'seo'),
('meta_description_en', '', 'textarea', 'seo'),
('og_image', NULL, 'image', 'seo'),
('social_instagram', '', 'text', 'social'),
('social_facebook', '', 'text', 'social'),
('social_twitter', '', 'text', 'social'),
('social_linkedin', '', 'text', 'social'),
('social_youtube', '', 'text', 'social'),
('social_github', '', 'text', 'social');
