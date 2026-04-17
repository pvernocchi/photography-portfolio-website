<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Pure-PHP WebAuthn (FIDO2) helpers — no Composer dependencies.
 *
 * Supports ES256 (P-256 ECDSA) and RS256 (RSA PKCS#1 v1.5) public-key types,
 * which covers all mainstream FIDO2 authenticators (YubiKey, Windows Hello,
 * Touch ID, Android authenticators, etc.).
 *
 * Requires: PHP 8.1+, openssl extension, sodium or random_bytes support.
 *
 * Usage
 * ─────
 *   // Registration (post-login, admin settings)
 *   $opts = WebAuthn::generateRegistrationOptions($rpId, $rpName, $userHandle, $username);
 *   // … store $opts['challenge'] in session, send $opts JSON to browser …
 *   $cred = WebAuthn::verifyRegistrationResponse($jsonDecodedResponse, $challenge, $rpId, $origin);
 *   // $cred = ['credential_id' => ..., 'public_key_pem' => ..., 'sign_count' => ...]
 *
 *   // Assertion (at login)
 *   $opts = WebAuthn::generateAssertionOptions($rpId, $storedCredentials);
 *   // … store $opts['challenge'] in session, send $opts JSON to browser …
 *   $newCount = WebAuthn::verifyAssertionResponse($jsonDecodedResponse, $challenge, $rpId, $origin, $pem, $storedCount);
 */
final class WebAuthn
{
    // ── Public: registration ──────────────────────────────────────────────────

    /**
     * Build the PublicKeyCredentialCreationOptions object to send to the browser.
     *
     * @param  string $rpId        Relying-party ID (e.g. "vernocchi.es")
     * @param  string $rpName      Human-readable name (e.g. "Vernocchi Photography")
     * @param  string $userHandle  Stable opaque user identifier (e.g. string user ID)
     * @param  string $username    Displayed user name
     * @return array               JSON-serialisable options; 'challenge' is raw binary.
     */
    public static function generateRegistrationOptions(
        string $rpId,
        string $rpName,
        string $userHandle,
        string $username
    ): array {
        $challenge = random_bytes(32);

        return [
            'challenge'               => self::b64urlEncode($challenge),
            'rp'                      => ['id' => $rpId, 'name' => $rpName],
            'user'                    => [
                'id'          => self::b64urlEncode($userHandle),
                'name'        => $username,
                'displayName' => $username,
            ],
            'pubKeyCredParams'        => [
                ['alg' => -7,   'type' => 'public-key'],   // ES256
                ['alg' => -257, 'type' => 'public-key'],   // RS256
            ],
            'authenticatorSelection'  => [
                'userVerification' => 'preferred',
            ],
            'attestation'             => 'none',
            'timeout'                 => 60000,
        ];
    }

    /**
     * Verify the browser's attestation response and return the new credential.
     *
     * @param  array  $response          JSON-decoded authenticatorAttestationResponse
     * @param  string $expectedChallenge Base64url-encoded challenge stored in session
     * @param  string $rpId             Relying-party ID
     * @param  string $origin           Full origin (e.g. "https://vernocchi.es")
     * @return array  ['credential_id' => string(base64url), 'public_key_pem' => string, 'sign_count' => int]
     * @throws \RuntimeException on any verification failure
     */
    public static function verifyRegistrationResponse(
        array  $response,
        string $expectedChallenge,
        string $rpId,
        string $origin
    ): array {
        // ── 1. Decode clientDataJSON ──────────────────────────────────────────
        $clientDataRaw = self::b64urlDecode((string) ($response['response']['clientDataJSON'] ?? ''));
        $clientData    = json_decode($clientDataRaw, true);
        if (!is_array($clientData)) {
            throw new \RuntimeException('WebAuthn: clientDataJSON is not valid JSON.');
        }

        if (($clientData['type'] ?? '') !== 'webauthn.create') {
            throw new \RuntimeException('WebAuthn: unexpected clientData type.');
        }

        if (!hash_equals($expectedChallenge, (string) ($clientData['challenge'] ?? ''))) {
            throw new \RuntimeException('WebAuthn: challenge mismatch during registration.');
        }

        if (rtrim((string) ($clientData['origin'] ?? ''), '/') !== rtrim($origin, '/')) {
            throw new \RuntimeException('WebAuthn: origin mismatch during registration.');
        }

        // ── 2. Decode and parse attestationObject ─────────────────────────────
        $attObjRaw = self::b64urlDecode((string) ($response['response']['attestationObject'] ?? ''));
        $attObj    = CBOR::decode($attObjRaw);
        if (!is_array($attObj)) {
            throw new \RuntimeException('WebAuthn: attestationObject is not a CBOR map.');
        }

        $authDataRaw = $attObj['authData'] ?? '';
        if (!is_string($authDataRaw) || strlen($authDataRaw) < 37) {
            throw new \RuntimeException('WebAuthn: authData too short.');
        }

        // ── 3. Parse authData ─────────────────────────────────────────────────
        $parsed    = self::parseAuthData($authDataRaw);
        $rpIdHash  = hash('sha256', $rpId, true);

        if (!hash_equals($rpIdHash, $parsed['rpIdHash'])) {
            throw new \RuntimeException('WebAuthn: RP ID hash mismatch during registration.');
        }

        // User-presence flag (bit 0) must be set.
        if (!($parsed['flags'] & 0x01)) {
            throw new \RuntimeException('WebAuthn: user-presence flag not set.');
        }

        // Attested credential data flag (bit 6) must be set for registration.
        if (!($parsed['flags'] & 0x40)) {
            throw new \RuntimeException('WebAuthn: attested credential data flag not set.');
        }

        if ($parsed['credentialId'] === '' || $parsed['coseKey'] === []) {
            throw new \RuntimeException('WebAuthn: credential data missing from authData.');
        }

        // ── 4. Convert COSE key → PEM ─────────────────────────────────────────
        $pem = self::coseToPem($parsed['coseKey']);

        return [
            'credential_id'  => self::b64urlEncode($parsed['credentialId']),
            'public_key_pem' => $pem,
            'sign_count'     => $parsed['signCount'],
        ];
    }

    // ── Public: assertion ─────────────────────────────────────────────────────

    /**
     * Build the PublicKeyCredentialRequestOptions object to send to the browser.
     *
     * @param  string $rpId        Relying-party ID
     * @param  array  $credentials Rows from webauthn_credentials (must have 'credential_id')
     * @return array  JSON-serialisable options; 'challenge' is base64url-encoded.
     */
    public static function generateAssertionOptions(string $rpId, array $credentials): array
    {
        $challenge = random_bytes(32);

        $allowCredentials = array_map(static fn (array $c): array => [
            'type' => 'public-key',
            'id'   => $c['credential_id'],
        ], $credentials);

        return [
            'challenge'        => self::b64urlEncode($challenge),
            'rpId'             => $rpId,
            'userVerification' => 'preferred',
            'allowCredentials' => $allowCredentials,
            'timeout'          => 60000,
        ];
    }

    /**
     * Verify the browser's assertion response.
     *
     * @param  array  $response         JSON-decoded authenticatorAssertionResponse
     * @param  string $expectedChallenge Base64url-encoded challenge stored in session
     * @param  string $rpId             Relying-party ID
     * @param  string $origin           Full origin (e.g. "https://vernocchi.es")
     * @param  string $publicKeyPem     PEM public key stored for this credential
     * @param  int    $storedSignCount  Sign count stored for this credential
     * @return int    New sign count to persist (may be 0 if authenticator doesn't support it)
     * @throws \RuntimeException on any verification failure
     */
    public static function verifyAssertionResponse(
        array  $response,
        string $expectedChallenge,
        string $rpId,
        string $origin,
        string $publicKeyPem,
        int    $storedSignCount
    ): int {
        // ── 1. Decode clientDataJSON ──────────────────────────────────────────
        $clientDataRaw = self::b64urlDecode((string) ($response['response']['clientDataJSON'] ?? ''));
        $clientData    = json_decode($clientDataRaw, true);
        if (!is_array($clientData)) {
            throw new \RuntimeException('WebAuthn: clientDataJSON is not valid JSON.');
        }

        if (($clientData['type'] ?? '') !== 'webauthn.get') {
            throw new \RuntimeException('WebAuthn: unexpected clientData type.');
        }

        if (!hash_equals($expectedChallenge, (string) ($clientData['challenge'] ?? ''))) {
            throw new \RuntimeException('WebAuthn: challenge mismatch during assertion.');
        }

        if (rtrim((string) ($clientData['origin'] ?? ''), '/') !== rtrim($origin, '/')) {
            throw new \RuntimeException('WebAuthn: origin mismatch during assertion.');
        }

        // ── 2. Parse authData ─────────────────────────────────────────────────
        $authDataRaw = self::b64urlDecode((string) ($response['response']['authenticatorData'] ?? ''));
        if (strlen($authDataRaw) < 37) {
            throw new \RuntimeException('WebAuthn: authenticatorData too short.');
        }

        $parsed   = self::parseAuthData($authDataRaw);
        $rpIdHash = hash('sha256', $rpId, true);

        if (!hash_equals($rpIdHash, $parsed['rpIdHash'])) {
            throw new \RuntimeException('WebAuthn: RP ID hash mismatch during assertion.');
        }

        if (!($parsed['flags'] & 0x01)) {
            throw new \RuntimeException('WebAuthn: user-presence flag not set.');
        }

        // ── 3. Verify sign count (replay protection) ──────────────────────────
        $newSignCount = $parsed['signCount'];
        if ($newSignCount !== 0 && $newSignCount <= $storedSignCount) {
            throw new \RuntimeException('WebAuthn: sign count indicates a cloned authenticator.');
        }

        // ── 4. Verify signature ───────────────────────────────────────────────
        $clientDataHash    = hash('sha256', $clientDataRaw, true);
        $verificationData  = $authDataRaw . $clientDataHash;
        $signatureRaw      = self::b64urlDecode((string) ($response['response']['signature'] ?? ''));

        $pubKey = openssl_pkey_get_public($publicKeyPem);
        if ($pubKey === false) {
            throw new \RuntimeException('WebAuthn: could not load stored public key.');
        }

        $result = openssl_verify($verificationData, $signatureRaw, $pubKey, OPENSSL_ALGO_SHA256);

        if ($result !== 1) {
            throw new \RuntimeException('WebAuthn: signature verification failed.');
        }

        return $newSignCount;
    }

    // ── Private: authData parsing ─────────────────────────────────────────────

    /**
     * Parse the binary authenticatorData structure.
     *
     * @return array{
     *   rpIdHash: string,
     *   flags: int,
     *   signCount: int,
     *   credentialId: string,
     *   coseKey: array<mixed>
     * }
     */
    private static function parseAuthData(string $authData): array
    {
        $rpIdHash  = substr($authData, 0, 32);
        $flags     = ord($authData[32]);
        $signCount = (int) unpack('N', substr($authData, 33, 4))[1];

        $credentialId = '';
        $coseKey      = [];

        // AT flag (bit 6): attested credential data present.
        if ($flags & 0x40) {
            if (strlen($authData) < 55) {
                throw new \RuntimeException('WebAuthn: authData too short for credential data.');
            }
            // Skip 16-byte AAGUID
            $credIdLen    = (int) unpack('n', substr($authData, 53, 2))[1];
            $credOffset   = 55;

            if (strlen($authData) < $credOffset + $credIdLen) {
                throw new \RuntimeException('WebAuthn: authData truncated at credential ID.');
            }

            $credentialId = substr($authData, $credOffset, $credIdLen);
            $coseRaw      = substr($authData, $credOffset + $credIdLen);
            $coseKey      = CBOR::decode($coseRaw);

            if (!is_array($coseKey)) {
                throw new \RuntimeException('WebAuthn: COSE key is not a map.');
            }
        }

        return [
            'rpIdHash'     => $rpIdHash,
            'flags'        => $flags,
            'signCount'    => $signCount,
            'credentialId' => $credentialId,
            'coseKey'      => $coseKey,
        ];
    }

    // ── Private: COSE key → PEM conversion ───────────────────────────────────

    /**
     * Convert a decoded COSE_Key map to a PEM-encoded SubjectPublicKeyInfo.
     * Supports kty=2 (EC P-256, ES256) and kty=3 (RSA, RS256).
     */
    private static function coseToPem(array $coseKey): string
    {
        $kty = $coseKey[1] ?? null;

        if ($kty === 2) {
            // EC key — P-256 (crv=1, alg=-7)
            $x = $coseKey[-2] ?? '';
            $y = $coseKey[-3] ?? '';
            if (!is_string($x) || !is_string($y) || strlen($x) !== 32 || strlen($y) !== 32) {
                throw new \RuntimeException('WebAuthn: invalid EC P-256 key coordinates.');
            }

            // OID id-ecPublicKey (1.2.840.10045.2.1)
            $oidEcPubKey = "\x2a\x86\x48\xce\x3d\x02\x01";
            // OID prime256v1 (1.2.840.10045.3.1.7)
            $oidP256     = "\x2a\x86\x48\xce\x3d\x03\x01\x07";

            $algorithmSeq = self::derSeq(self::derOid($oidEcPubKey) . self::derOid($oidP256));
            $pubPoint     = "\x04" . $x . $y; // uncompressed EC point
            $spki         = self::derSeq($algorithmSeq . self::derBitStr($pubPoint));

            return self::derToPem($spki);
        }

        if ($kty === 3) {
            // RSA key (alg=-257, RS256)
            $n = $coseKey[-1] ?? '';
            $e = $coseKey[-2] ?? '';
            if (!is_string($n) || !is_string($e) || $n === '' || $e === '') {
                throw new \RuntimeException('WebAuthn: invalid RSA key components.');
            }

            // OID rsaEncryption (1.2.840.113549.1.1.1)
            $oidRsa      = "\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01";
            $algorithmSeq = self::derSeq(self::derOid($oidRsa) . self::derNull());
            $rsaPubKey   = self::derSeq(self::derInt($n) . self::derInt($e));
            $spki        = self::derSeq($algorithmSeq . self::derBitStr($rsaPubKey));

            return self::derToPem($spki);
        }

        throw new \RuntimeException("WebAuthn: unsupported COSE key type: {$kty}.");
    }

    private static function derToPem(string $der): string
    {
        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    // ── Private: minimal DER/ASN.1 encoding helpers ───────────────────────────

    private static function derTlv(int $tag, string $value): string
    {
        $len = strlen($value);
        if ($len < 128) {
            $lenBytes = chr($len);
        } elseif ($len < 256) {
            $lenBytes = "\x81" . chr($len);
        } else {
            $lenBytes = "\x82" . chr($len >> 8) . chr($len & 0xFF);
        }
        return chr($tag) . $lenBytes . $value;
    }

    private static function derSeq(string $data): string
    {
        return self::derTlv(0x30, $data);
    }

    private static function derBitStr(string $data): string
    {
        // Prepend 0x00 "unused bits" byte required by BIT STRING encoding.
        return self::derTlv(0x03, "\x00" . $data);
    }

    private static function derOid(string $bytes): string
    {
        return self::derTlv(0x06, $bytes);
    }

    /**
     * DER-encode a big-endian integer byte string.
     * Ensures the value is treated as a positive integer (prepends 0x00 when
     * the high bit of the first byte is set, per ASN.1 rules).
     */
    private static function derInt(string $bytes): string
    {
        if ($bytes !== '' && (ord($bytes[0]) & 0x80)) {
            $bytes = "\x00" . $bytes;
        }
        return self::derTlv(0x02, $bytes);
    }

    private static function derNull(): string
    {
        return "\x05\x00";
    }

    // ── Private: base64url helpers ────────────────────────────────────────────

    public static function b64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function b64urlDecode(string $data): string
    {
        $padded = str_pad(strtr($data, '-_', '+/'), (int) (ceil(strlen($data) / 4) * 4), '=');
        $result = base64_decode($padded, true);
        if ($result === false) {
            throw new \RuntimeException('WebAuthn: invalid base64url data.');
        }
        return $result;
    }
}
