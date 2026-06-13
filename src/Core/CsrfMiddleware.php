<?php

declare(strict_types=1);

/**
 * CsrfMiddleware - Protección CSRF para formularios
 * Genera y valida tokens CSRF
 */
namespace App\Core;

class CsrfMiddleware
{
    /**
     * Genera un token CSRF y lo guarda en sesión
     */
    public static function generarToken(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set('csrf_token', $token);
        return $token;
    }

    /**
     * Valida un token CSRF contra el guardado en sesión
     */
    public static function validar(string $token): bool
    {
        $storedToken = Session::get('csrf_token');
        if ($storedToken === null || $token === '') {
            return false;
        }
        return hash_equals($storedToken, $token);
    }

    /**
     * Obtiene el token CSRF actual (o genera uno nuevo)
     */
    public static function getToken(): string
    {
        $token = Session::get('csrf_token');
        if ($token === null) {
            $token = self::generarToken();
        }
        return $token;
    }
}
