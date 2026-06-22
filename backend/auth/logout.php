<?php

declare(strict_types=1);

/**
 * Cierra la sesión del administrador
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/functions.php';

iniciarSesionAdmin();

// Destruir todas las variables de sesión
$_SESSION = [];

// Si se desea destruir la sesión completamente, borre también la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// Redirigir al login público o del admin
header('Location: ' . getBasePath() . '/');
exit;
