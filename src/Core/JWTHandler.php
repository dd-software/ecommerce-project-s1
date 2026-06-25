<?php
declare(strict_types=1);

namespace Src\Core;

/**
 * Class JWTHandler
 * Gestor de JSON Web Tokens sin dependencias externas usando HMAC SHA256.
 */
class JWTHandler {
    /**
     * Genera un nuevo JWT firmado.
     *
     * @param array $payload Datos a incluir en el token.
     * @return string El JWT generado.
     */
    public static function generateToken(array $payload): string {
        $secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: 'default_secret';
        $expiration = (int)($_ENV['JWT_EXPIRATION'] ?? getenv('JWT_EXPIRATION') ?: 3600);

        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['exp'] = time() + $expiration;
        $payload['iat'] = time();
        $payloadJson = json_encode($payload);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payloadJson));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Valida un JWT y retorna su payload si es correcto y no ha expirado.
     *
     * @param string $token El JWT a validar.
     * @return array|null El payload descifrado o null si es inválido.
     */
    public static function validateToken(string $token): ?array {
        $secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: 'default_secret';
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        list($header, $payload, $signature) = $parts;

        $validSignature = hash_hmac('sha256', $header . "." . $payload, $secret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));

        if (!hash_equals($base64UrlSignature, $signature)) {
            return null;
        }

        $payloadData = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return null; // Token expirado
        }

        return $payloadData;
    }
}
