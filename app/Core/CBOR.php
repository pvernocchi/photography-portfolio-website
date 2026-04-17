<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal CBOR (RFC 7049) decoder — covers the subset used by WebAuthn:
 * unsigned/negative integers, byte strings, text strings, arrays, maps, tags.
 *
 * Not a full CBOR implementation; indefinite-length items and floats are
 * intentionally not supported as they are not produced by FIDO2 authenticators.
 */
final class CBOR
{
    private string $data;
    private int    $pos;

    private function __construct(string $data)
    {
        $this->data = $data;
        $this->pos  = 0;
    }

    /** Decode a CBOR-encoded binary string and return the PHP value. */
    public static function decode(string $data): mixed
    {
        $decoder = new self($data);
        return $decoder->decodeItem();
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function readByte(): int
    {
        if ($this->pos >= strlen($this->data)) {
            throw new \RuntimeException('Unexpected end of CBOR data.');
        }
        return ord($this->data[$this->pos++]);
    }

    private function readBytes(int $length): string
    {
        if ($length < 0 || $this->pos + $length > strlen($this->data)) {
            throw new \RuntimeException('Unexpected end of CBOR data.');
        }
        $slice       = substr($this->data, $this->pos, $length);
        $this->pos  += $length;
        return $slice;
    }

    /** Read the unsigned-integer argument that follows the initial byte. */
    private function readArgument(int $additionalInfo): int
    {
        if ($additionalInfo <= 23) {
            return $additionalInfo;
        }
        if ($additionalInfo === 24) {
            return $this->readByte();
        }
        if ($additionalInfo === 25) {
            return (int) unpack('n', $this->readBytes(2))[1];
        }
        if ($additionalInfo === 26) {
            return (int) unpack('N', $this->readBytes(4))[1];
        }
        if ($additionalInfo === 27) {
            // 64-bit big-endian — safe on 64-bit PHP builds.
            $hi = (int) unpack('N', $this->readBytes(4))[1];
            $lo = (int) unpack('N', $this->readBytes(4))[1];
            return ($hi << 32) | $lo;
        }
        throw new \RuntimeException("Unsupported CBOR additional-info value: {$additionalInfo}.");
    }

    private function decodeItem(): mixed
    {
        $initial        = $this->readByte();
        $majorType      = $initial >> 5;
        $additionalInfo = $initial & 0x1F;

        switch ($majorType) {
            case 0: // unsigned integer
                return $this->readArgument($additionalInfo);

            case 1: // negative integer  (-1 - n)
                return -1 - $this->readArgument($additionalInfo);

            case 2: // byte string
                $len = $this->readArgument($additionalInfo);
                return $this->readBytes($len);

            case 3: // text string
                $len = $this->readArgument($additionalInfo);
                return $this->readBytes($len);

            case 4: // array
                $count = $this->readArgument($additionalInfo);
                $out   = [];
                for ($i = 0; $i < $count; $i++) {
                    $out[] = $this->decodeItem();
                }
                return $out;

            case 5: // map
                $count = $this->readArgument($additionalInfo);
                $out   = [];
                for ($i = 0; $i < $count; $i++) {
                    $key        = $this->decodeItem();
                    $out[$key]  = $this->decodeItem();
                }
                return $out;

            case 6: // tag — consume tag number and decode the wrapped item
                $this->readArgument($additionalInfo);
                return $this->decodeItem();

            case 7: // simple values / floats
                if ($additionalInfo === 20) {
                    return false;
                }
                if ($additionalInfo === 21) {
                    return true;
                }
                if ($additionalInfo === 22) {
                    return null;
                }
                // Skip half / single / double floats (not used in WebAuthn CBOR)
                if ($additionalInfo === 25) {
                    $this->readBytes(2);
                    return null;
                }
                if ($additionalInfo === 26) {
                    $this->readBytes(4);
                    return null;
                }
                if ($additionalInfo === 27) {
                    $this->readBytes(8);
                    return null;
                }
                return null;
        }

        throw new \RuntimeException("Unknown CBOR major type: {$majorType}.");
    }
}
