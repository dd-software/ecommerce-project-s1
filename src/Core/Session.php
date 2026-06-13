<?php

declare(strict_types=1);

/**
 * Clase Session - Manejo de sesiones para carrito de visitantes
 * y almacenamiento temporal
 */
namespace App\Core;

class Session
{
    private static bool $iniciada = false;

    /**
     * Inicia la sesión PHP si no está iniciada
     */
    public static function iniciar(): void
    {
        if (!self::$iniciada && session_status() === PHP_SESSION_NONE) {
            // Configuración segura de cookies de sesión
            session_set_cookie_params([
                'httponly' => true,
                'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'samesite' => 'Strict',
            ]);
            session_start();
            self::$iniciada = true;
        }
    }

    /**
     * Obtiene el ID de sesión actual
     */
    public static function getId(): string
    {
        self::iniciar();
        return session_id();
    }

    /**
     * Establece un valor en la sesión
     */
    public static function set(string $key, mixed $value): void
    {
        self::iniciar();
        $_SESSION[$key] = $value;
    }

    /**
     * Obtiene un valor de la sesión
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::iniciar();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Verifica si existe una clave en sesión
     */
    public static function has(string $key): bool
    {
        self::iniciar();
        return isset($_SESSION[$key]);
    }

    /**
     * Elimina un valor de la sesión
     */
    public static function remove(string $key): void
    {
        self::iniciar();
        unset($_SESSION[$key]);
    }

    /**
     * Destruye la sesión completa (logout)
     */
    public static function destruir(): void
    {
        self::iniciar();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        self::$iniciada = false;
    }

    /**
     * Regenera el ID de sesión (previene session fixation)
     */
    public static function regenerar(): void
    {
        self::iniciar();
        session_regenerate_id(true);
    }

    /**
     * Establece un mensaje flash (vive solo una petición)
     */
    public static function flash(string $key, mixed $value): void
    {
        self::iniciar();
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Obtiene y limpia un mensaje flash
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::iniciar();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Obtiene o crea un session_id para visitantes (carrito anónimo)
     */
    public static function getSessionId(): string
    {
        self::iniciar();
        if (!isset($_SESSION['visitor_id'])) {
            $_SESSION['visitor_id'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['visitor_id'];
    }
}
