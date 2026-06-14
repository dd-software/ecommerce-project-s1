<?php

declare(strict_types=1);

/**
 * JwtMiddleware - Middleware de autenticación JWT
 * Valida tokens y adjunta usuario autenticado a la request
 */
namespace App\Core;

class JwtMiddleware
{
    /**
     * Procesa la request y valida el token JWT si está presente
     * Adjunta los datos del usuario al request si el token es válido
     */
    public static function procesar(Request $request): void
    {
        $token = $request->getBearerToken();

        if ($token === null) {
            return; // Sin token, puede ser ruta pública
        }

        $payload = JWT::decode($token, JWT_SECRET);

        if ($payload === null) {
            // Token inválido o expirado
            return;
        }

        // Extraer ID de usuario del subject
        $userId = null;
        if (isset($payload['sub']) && str_starts_with($payload['sub'], 'user_id:')) {
            $userId = (int) substr($payload['sub'], 8);
        }

        // Adjuntar usuario autenticado a la request
        $request->setAttribute('authenticated_user', [
            'id'     => $userId,
            'email'  => $payload['email'] ?? '',
            'rol'    => $payload['rol'] ?? 'cliente',
            'nombre' => $payload['nombre'] ?? '',
        ]);

        $request->setAttribute('jwt_payload', $payload);
    }
}
