<?php

declare(strict_types=1);

/**
 * Configuración de sesiones seguras para el panel de administración.
 * Incluye timeout por inactividad, regeneración de ID y cookies seguras.
 */

// Cargar configuración de la aplicación
require_once __DIR__ . '/../../config/app.php';

// Duración máxima de inactividad (30 minutos)
define('ADMIN_SESSION_TIMEOUT', 1800);

// Nombre de la sesión del admin (separada de la sesión pública)
define('ADMIN_SESSION_NAME', 'UCTADMIN_SESSID');

/**
 * Inicia una sesión segura para el panel de administración
 */
function iniciarSesionAdmin(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Nombre de sesión único para admin
    session_name(ADMIN_SESSION_NAME);

    // Configuración segura de cookies
    session_set_cookie_params([
        'lifetime' => 0, // Se destruye al cerrar el navegador
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly'  => true,
        'samesite' => 'Strict',
    ]);

    session_start();

    // Verificar timeout por inactividad
    if (isset($_SESSION['admin_last_activity'])) {
        $inactivo = time() - $_SESSION['admin_last_activity'];
        if ($inactivo > ADMIN_SESSION_TIMEOUT) {
            // Sesión expirada por inactividad
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['login_error'] = 'Sesión expirada por inactividad. Inicie sesión nuevamente.';
            return;
        }
    }

    // Actualizar timestamp de última actividad
    $_SESSION['admin_last_activity'] = time();

    // Regenerar session ID periódicamente (cada 5 minutos) para prevenir session fixation
    if (!isset($_SESSION['admin_created_at'])) {
        $_SESSION['admin_created_at'] = time();
    } elseif (time() - $_SESSION['admin_created_at'] > 300) {
        session_regenerate_id(true);
        $_SESSION['admin_created_at'] = time();
    }
}
