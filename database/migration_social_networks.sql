-- Social network settings
INSERT INTO settings (setting_key, setting_value, setting_type, setting_group) VALUES
('social_instagram', '', 'text', 'social'),
('social_facebook',  '', 'text', 'social'),
('social_twitter',   '', 'text', 'social'),
('social_linkedin',  '', 'text', 'social'),
('social_youtube',   '', 'text', 'social'),
('social_github',    '', 'text', 'social')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
