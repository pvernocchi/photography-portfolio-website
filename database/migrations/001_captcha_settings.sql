-- Migration 001: Captcha settings refactor
-- Moves Turnstile keys from the 'general' group to 'contact',
-- and adds captcha_enabled, captcha_provider, recaptcha_site_key,
-- and recaptcha_secret_key settings.
--
-- Safe to run on existing installs: INSERT IGNORE skips rows that
-- already exist, and UPDATE only touches the setting_group column
-- for the Turnstile keys.

-- 1. Move existing Turnstile keys from 'general' to 'contact' group.
--    If they were never saved via the old Security tab, these rows
--    may not exist yet — INSERT IGNORE below handles that case.
UPDATE settings
SET setting_group = 'contact'
WHERE setting_key IN ('turnstile_site_key', 'turnstile_secret_key')
  AND setting_group = 'general';

-- 2. Insert new settings (skipped silently if they already exist).
INSERT IGNORE INTO settings (setting_key, setting_value, setting_type, setting_group) VALUES
('captcha_enabled',    '0',         'boolean', 'contact'),
('captcha_provider',   'turnstile', 'select',  'contact'),
('turnstile_site_key', '',          'text',    'contact'),
('turnstile_secret_key', '',        'text',    'contact'),
('recaptcha_site_key', '',          'text',    'contact'),
('recaptcha_secret_key', '',        'text',    'contact');
