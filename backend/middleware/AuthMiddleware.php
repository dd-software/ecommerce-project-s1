<?php

declare(strict_types=1);

/**
 * AuthMiddleware - Protege todas las vistas administrativas.
 * Verifica sesión válida y rol de administrador.
 * Si falla, redirige al login.
 */

require_once __DIR__ . '/../config/session.php';

class AuthMiddleware
{
    /**
     * Verifica que el usuario tenga una sesión admin activa.
     * Debe llamarse al inicio de cada vista protegida.
     */
    public static function verificar(): void
    {
        iniciarSesionAdmin();

        // Verificar que existe sesión de admin
        if (
            empty($_SESSION['admin_id']) ||
            empty($_SESSION['admin_email']) ||
            empty($_SESSION['admin_role']) ||
            $_SESSION['admin_role'] !== 'admin'
        ) {
            // No hay sesión válida → redirigir al login
            self::redirigirAlLogin();
        }

        // Verificar que la sesión no fue manipulada (fingerprint)
        $fingerprint = self::generarFingerprint();
        if (isset($_SESSION['admin_fingerprint']) && $_SESSION['admin_fingerprint'] !== $fingerprint) {
            // Posible session hijacking
            session_unset();
            session_destroy();
            self::redirigirAlLogin();
        }
    }

    /**
     * Obtiene datos del admin autenticado
     */
    public static function getAdmin(): array
    {
        return [
            'id'     => $_SESSION['admin_id'] ?? 0,
            'email'  => $_SESSION['admin_email'] ?? '',
            'nombre' => $_SESSION['admin_nombre'] ?? '',
            'role'   => $_SESSION['admin_role'] ?? '',
        ];
    }

    /**
     * Verifica si hay una sesión activa sin redirigir
     */
    public static function estaAutenticado(): bool
    {
        iniciarSesionAdmin();
        return !empty($_SESSION['admin_id']) && ($_SESSION['admin_role'] ?? '') === 'admin';
    }

    /**
     * Genera un fingerprint del navegador para detectar session hijacking
     */
    public static function generarFingerprint(): string
    {
        $data = ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        return hash('sha256', $data);
    }

    /**
     * Redirige al login del admin
     */
    private static function redirigirAlLogin(): void
    {
        require_once __DIR__ . '/../helpers/functions.php';
        header('Location: ' . getBackendPath() . '/auth/login-view.php');
        exit;
    }
}
