-- Migration 003: Password-protected private galleries
-- Adds private gallery controls on categories:
--   - is_private
--   - private_password_hash
--   - allow_original_download
--
-- HOW TO RUN (replace "your_database" with your actual database name):
--   mysql -u YOUR_USER -p YOUR_DATABASE < 003_private_galleries.sql

USE `your_database`;

ALTER TABLE categories
    ADD COLUMN IF NOT EXISTS is_private TINYINT(1) NOT NULL DEFAULT 0 AFTER is_visible,
    ADD COLUMN IF NOT EXISTS private_password_hash VARCHAR(255) NULL AFTER is_private,
    ADD COLUMN IF NOT EXISTS allow_original_download TINYINT(1) NOT NULL DEFAULT 0 AFTER private_password_hash;
