<?php
declare(strict_types=1);

namespace App\Core;

class TOTP
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function getCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $period = (int) app_config('totp.period', 30);
        $digits = (int) app_config('totp.digits', 6);

        $counter = intdiv($timestamp, $period);
        $binaryCounter = pack('N*', 0) . pack('N*', $counter);
        $secretBytes = self::base32Decode($secret);

        $hash = hash_hmac('sha1', $binaryCounter, $secretBytes, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $value = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
        $modulo = 10 ** $digits;

        return str_pad((string) ($value % $modulo), $digits, '0', STR_PAD_LEFT);
    }

    public static function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $cleanCode = preg_replace('/\D+/', '', $code) ?? '';
        $period = (int) app_config('totp.period', 30);

        for ($i = -$window; $i <= $window; $i++) {
            $timestamp = time() + ($i * $period);
            if (hash_equals(self::getCode($secret, $timestamp), $cleanCode)) {
                return true;
            }
        }

        return false;
    }

    public static function getProvisioningUri(string $secret, string $accountName, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $accountName);
        $issuerParam = rawurlencode($issuer);

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            $label,
            rawurlencode($secret),
            $issuerParam,
            (int) app_config('totp.digits', 6),
            (int) app_config('totp.period', 30)
        );
    }

    public static function getQRCodeUrl(string $provisioningUri): string
    {
        // Security note: this sends the provisioning URI (including TOTP secret) to Google Charts.
        // Kept in Phase 1 intentionally to match the project requirements.
        return 'https://chart.googleapis.com/chart?cht=qr&chs=220x220&chl=' . rawurlencode($provisioningUri);
    }

    private static function base32Encode(string $data): string
    {
        $binary = '';
        $length = strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $binary .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }

        $chunks = str_split($binary, 5);
        $output = '';

        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $output .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $output;
    }

    private static function base32Decode(string $value): string
    {
        $value = strtoupper(preg_replace('/[^A-Z2-7]/', '', $value) ?? '');
        $binary = '';

        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $position = strpos(self::BASE32_ALPHABET, $value[$i]);
            if ($position === false) {
                continue;
            }
            $binary .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $bytes = str_split($binary, 8);
        $decoded = '';

        foreach ($bytes as $byte) {
            if (strlen($byte) === 8) {
                $decoded .= chr(bindec($byte));
            }
        }

        return $decoded;
    }
}
