-- Migration 002: WebAuthn / FIDO2 (YubiKey) support
-- Adds a webauthn_enabled flag to users and creates a table for FIDO2 credentials.
--
-- HOW TO RUN (replace "your_database" with your actual database name):
--   mysql -u YOUR_USER -p YOUR_DATABASE < 002_webauthn.sql
--
-- Safe to run on existing installs: ALTER TABLE uses IF NOT EXISTS / IF EXISTS
-- guards where supported, and INSERT IGNORE skips already-present rows.

USE `your_database`;

-- 1. Add webauthn_enabled column if it doesn't exist yet.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS webauthn_enabled TINYINT(1) NOT NULL DEFAULT 0;

-- 2. Store FIDO2 / WebAuthn public-key credentials.
--    A user may have multiple registered security keys.
CREATE TABLE IF NOT EXISTS webauthn_credentials (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED    NOT NULL,
    credential_id   VARCHAR(1024)   NOT NULL COMMENT 'base64url-encoded credential ID from the authenticator',
    public_key_pem  TEXT            NOT NULL COMMENT 'PEM-encoded SubjectPublicKeyInfo for signature verification',
    sign_count      INT UNSIGNED    NOT NULL DEFAULT 0,
    name            VARCHAR(255)    NOT NULL DEFAULT 'Security Key',
    created_at      DATETIME        DEFAULT CURRENT_TIMESTAMP,
    UNIQUE  KEY idx_credential_id (credential_id(255)),
    INDEX   idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
