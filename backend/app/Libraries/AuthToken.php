<?php

namespace App\Libraries;

/**
 * JWT compact HS256 minimal (sub, role, exp) pour cookie HttpOnly.
 */
final class AuthToken
{
    /**
     * @return non-empty-string
     */
    public static function mint(int $userId, string $role, string $secret, int $ttlSeconds = 604800): string
    {
        $header  = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = [
            'sub'  => $userId,
            'role' => $role,
            'exp'  => time() + $ttlSeconds,
        ];

        $h = self::b64urlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $p = self::b64urlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $sig = hash_hmac('sha256', $h . '.' . $p, $secret, true);

        return $h . '.' . $p . '.' . self::b64urlEncode($sig);
    }

    /**
     * @return array{sub:int, role:string, exp:int}|null
     */
    public static function parse(?string $jwt, string $secret): ?array
    {
        if ($jwt === null || $jwt === '') {
            return null;
        }

        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            return null;
        }

        [$encH, $encP, $encS] = $parts;
        $sig = self::b64urlDecode($encS);

        if ($sig === false) {
            return null;
        }

        $expected = hash_hmac('sha256', $encH . '.' . $encP, $secret, true);

        if (! hash_equals($expected, $sig)) {
            return null;
        }

        $jsonP = self::b64urlDecode($encP);

        if ($jsonP === false) {
            return null;
        }

        try {
            /** @var mixed $data */
            $data = json_decode($jsonP, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($data) || ! isset($data['sub'], $data['role'], $data['exp'])) {
            return null;
        }

        if (! is_int($data['exp']) && ! is_numeric($data['exp'])) {
            return null;
        }

        if ((int) $data['exp'] < time()) {
            return null;
        }

        return [
            'sub'  => (int) $data['sub'],
            'role' => (string) $data['role'],
            'exp'  => (int) $data['exp'],
        ];
    }

    private static function b64urlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function b64urlDecode(string $data): string|false
    {
        $remainder = strlen($data) % 4;

        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $out = base64_decode(strtr($data, '-_', '+/'), true);

        return $out === false ? false : $out;
    }
}
