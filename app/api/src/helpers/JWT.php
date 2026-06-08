<?php
class JWT {
    public static function encode(array $payload): string {
        $header  = self::b64u(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_TTL;
        $body    = self::b64u(json_encode($payload));
        $sig     = self::b64u(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
        return "$header.$body.$sig";
    }

    public static function decode(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$header, $body, $sig] = $parts;
        $expected = self::b64u(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
        if (!hash_equals($expected, $sig)) return null;

        $data = json_decode(self::b64d($body), true);
        if (!$data || ($data['exp'] ?? 0) < time()) return null;

        return $data;
    }

    private static function b64u(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64d(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
