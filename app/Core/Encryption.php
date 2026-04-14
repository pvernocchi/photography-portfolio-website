<?php
declare(strict_types=1);

namespace App\Core;

class Encryption
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;
    private const FALLBACK_KEY = 'vernocchi-default-encryption-key-change-me';

    public static function encrypt(string $plaintext): string
    {
        if ($plaintext === '') {
            return '';
        }

        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false || strlen($tag) !== self::TAG_LENGTH) {
            error_log('Encryption: failed to encrypt payload.');
            return '';
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt(string $encoded): string
    {
        if ($encoded === '') {
            return '';
        }

        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            return '';
        }

        if (strlen($decoded) <= self::IV_LENGTH + self::TAG_LENGTH) {
            return '';
        }

        $iv = substr($decoded, 0, self::IV_LENGTH);
        $tag = substr($decoded, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($decoded, self::IV_LENGTH + self::TAG_LENGTH);
        if ($iv === false || $tag === false || $ciphertext === false || $ciphertext === '') {
            return '';
        }

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        return $plaintext === false ? '' : $plaintext;
    }

    private static function key(): string
    {
        $appKey = (string) app_config('app.key', '');
        if ($appKey === '') {
            error_log('Encryption: app.key is empty. Falling back to default key; set app.key in config.');
            $appKey = self::FALLBACK_KEY;
        }

        return hash('sha256', $appKey, true);
    }
}
