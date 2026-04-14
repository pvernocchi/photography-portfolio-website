INSERT INTO settings (setting_key, setting_value, setting_type, setting_group) VALUES
('smtp_host', '', 'text', 'contact'),
('smtp_port', '587', 'text', 'contact'),
('smtp_encryption', 'tls', 'select', 'contact'),
('smtp_username', '', 'text', 'contact'),
('smtp_password', '', 'text', 'contact'),
('smtp_from_name', '', 'text', 'contact'),
('smtp_from_email', '', 'text', 'contact')
ON DUPLICATE KEY UPDATE
    setting_type = VALUES(setting_type),
    setting_group = VALUES(setting_group);
