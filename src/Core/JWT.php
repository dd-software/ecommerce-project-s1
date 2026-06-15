<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Clase JWT - Utilidad para manejo de JSON Web Tokens (HS256).
 * Implementación vanilla sin dependencias externas.
 */
class JWT
{
    /**
     * Codifica un payload en un token JWT firmado.
     *
     * @param array $payload Datos a incluir en el token.
     * @param string $secret Clave secreta para la firma HMAC.
     * @param int $expiry Tiempo de vida en segundos.
     * @return string Token JWT en formato header.payload.signature
     */
    public static function encode(array $payload, string $secret, int $expiry = 7200): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        // Claims estándar
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;

        $base64Header = self::base64UrlEncode(json_encode($header));
        $base64Payload = self::base64UrlEncode(json_encode($payload));

        $signature = self::sign("{$base64Header}.{$base64Payload}", $secret);

        return "{$base64Header}.{$base64Payload}.{$signature}";
    }

    /**
     * Decodifica y valida la firma de un token JWT.
     *
     * @param string $token Token JWT.
     * @param string $secret Clave secreta para validación.
     * @return array|null Payload decodificado o null si el token es inválido o expiró.
     */
    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureProvided] = $parts;

        // Verificar la firma
        $signatureExpected = self::sign("{$headerEncoded}.{$payloadEncoded}", $secret);
        if (!hash_equals($signatureExpected, $signatureProvided)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        if (!$payload) {
            return null;
        }

        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    /**
     * Firma un string usando HMAC-SHA256 y codifica el resultado en base64url.
     */
    private static function sign(string $data, string $secret): string
    {
        return self::base64UrlEncode(
            hash_hmac('sha256', $data, $secret, true)
        );
    }

    /**
     * Codifica datos en formato Base64 URL Safe.
     */
    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodifica datos desde formato Base64 URL Safe.
     */
    public static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Helper para generar un token a partir de un arreglo de datos de usuario.
     */
    public static function generarToken(array $usuario): string
    {
        $payload = [
            'sub'    => 'user_id:' . $usuario['id'],
            'email'  => $usuario['email'],
            'rol'    => $usuario['rol'] ?? 'cliente',
            'nombre' => ($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''),
        ];

        return self::encode($payload, JWT_SECRET, (int)JWT_EXPIRY);
    }
}
