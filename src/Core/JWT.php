<?php

declare(strict_types=1);

/**
 * Clase JWT - Implementación vanilla de JSON Web Tokens (HS256)
 * Sin dependencias externas, usando hash_hmac con SHA256
 */
namespace App\Core;

class JWT
{
    /**
     * Codifica un payload en un token JWT
     *
     * @param array $payload Datos a incluir en el token
     * @param string $secret Clave secreta para firmar
     * @param int $expiry Tiempo de expiración en segundos (default 7200 = 2 horas)
     * @return string Token JWT firmado
     */
    public static function encode(array $payload, string $secret, int $expiry = 7200): string
    {
        // Headers del token
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        // Agregar claims estándar
        $payload['iat'] = $payload['iat'] ?? time();
        $payload['exp'] = $payload['exp'] ?? (time() + $expiry);

        // Codificar header y payload en base64url
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        // Firmar
        $signature = self::sign("{$headerEncoded}.{$payloadEncoded}", $secret);

        return "{$headerEncoded}.{$payloadEncoded}.{$signature}";
    }

    /**
     * Decodifica y verifica un token JWT
     *
     * @param string $token Token JWT a verificar
     * @param string $secret Clave secreta para verificar firma
     * @return array|null Payload decodificado o null si es inválido
     */
    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureProvided] = $parts;

        // Verificar firma
        $signatureExpected = self::sign("{$headerEncoded}.{$payloadEncoded}", $secret);
        if (!hash_equals($signatureExpected, $signatureProvided)) {
            return null;
        }

        // Decodificar payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        if (!$payload) {
            return null;
        }

        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null; // Token expirado
        }

        return $payload;
    }

    /**
     * Firma un string con HMAC-SHA256
     */
    private static function sign(string $data, string $secret): string
    {
        return self::base64UrlEncode(
            hash_hmac('sha256', $data, $secret, true)
        );
    }

    /**
     * Codifica en base64url (compatible con JWT)
     */
    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodifica desde base64url
     */
    public static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Genera un token para un usuario
     *
     * @param array $usuario Datos del usuario (debe contener id, email, rol)
     * @return string Token JWT
     */
    public static function generarToken(array $usuario): string
    {
        $payload = [
            'sub'   => 'user_id:' . $usuario['id'],
            'email' => $usuario['email'],
            'rol'   => $usuario['rol'],
            'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
        ];

        return self::encode($payload, JWT_SECRET, JWT_EXPIRY);
    }
}
