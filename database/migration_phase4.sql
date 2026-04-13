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
('meta_title_es', 'Vernocchi Fotografía', 'text', 'seo'),
('meta_title_en', 'Vernocchi Photography', 'text', 'seo'),
('meta_description_es', '', 'textarea', 'seo'),
('meta_description_en', '', 'textarea', 'seo'),
('og_image', NULL, 'image', 'seo');
