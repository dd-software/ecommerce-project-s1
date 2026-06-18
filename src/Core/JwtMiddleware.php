<?php

declare(strict_types=1);

namespace App\Core;

/**
 * JwtMiddleware - Interceptor de autenticación basado en JWT.
 * Extrae el token de los headers, lo valida y adjunta la identidad del usuario a la request.
 */
class JwtMiddleware
{
    /**
     * Procesa la request para identificar al usuario autenticado.
     * 
     * @param Request $request Instancia de la petición actual.
     */
    public static function procesar(Request $request): void
    {
        $token = $request->getBearerToken();

        if ($token === null) {
            // El usuario no envió token, se considera visitante.
            return;
        }

        // Validar el token usando el secreto definido en la config
        $payload = JWT::decode($token, JWT_SECRET);

        if ($payload === null) {
            // Token inválido, expirado o manipulado.
            return;
        }

        // Extraer información del usuario desde el payload
        $userId = null;
        if (isset($payload['sub']) && strpos($payload['sub'], 'user_id:') === 0) {
            $userId = (int) substr($payload['sub'], 8);
        }

        // Adjuntar los datos al objeto Request para que los controladores puedan acceder a ellos
        $request->setAttribute('authenticated_user', [
            'id'     => $userId,
            'email'  => $payload['email'] ?? '',
            'rol'    => $payload['rol'] ?? 'cliente',
            'nombre' => $payload['nombre'] ?? '',
        ]);

        // Opcionalmente guardar el payload completo
        $request->setAttribute('jwt_payload', $payload);
    }
}
