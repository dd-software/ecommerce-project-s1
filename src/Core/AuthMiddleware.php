<?php
declare(strict_types=1);

namespace Src\Core;

/**
 * Class AuthMiddleware
 * Middleware para validar tokens JWT y roles (RBAC).
 */
class AuthMiddleware {
    /**
     * Extrae y valida el JWT del header de autorización.
     * 
     * @return array Los datos descifrados del token (payload).
     */
    public static function authenticate(): array {
        $headers = self::getHeaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            Response::error('Token no proporcionado o formato inválido', 401);
        }

        $token = $matches[1];
        $payload = JWTHandler::validateToken($token);

        if (!$payload) {
            Response::error('Token inválido o expirado', 401);
        }

        return $payload;
    }

    /**
     * Autoriza solo si el usuario autenticado tiene uno de los roles permitidos.
     *
     * @param array $allowedRoles Lista de roles permitidos (e.g. ['admin']).
     * @return array El payload del usuario.
     */
    public static function authorize(array $allowedRoles): array {
        $user = self::authenticate();
        if (!in_array($user['role'], $allowedRoles)) {
            Response::error('Acceso denegado: permisos insuficientes', 403);
        }
        return $user;
    }

    /**
     * Helper para obtener los headers en distintos entornos (Apache, Nginx, CGI).
     */
    private static function getHeaders(): array {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }
}
