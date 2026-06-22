<?php

declare(strict_types=1);

/**
 * Procesa el login del administrador.
 * Valida credenciales contra la BD, crea sesión segura.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/functions.php';

iniciarSesionAdmin();

// Si ya está autenticado, ir directo al dashboard
if (AuthMiddleware::estaAutenticado()) {
    header('Location: ' . getBackendPath() . '/views/dashboard.php');
    exit;
}

// Solo procesar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . getBackendPath() . '/auth/login-view.php');
    exit;
}

// Validar CSRF
if (!validateCsrf()) {
    $_SESSION['login_error'] = 'Token de seguridad inválido. Intente nuevamente.';
    header('Location: ' . getBackendPath() . '/auth/login-view.php');
    exit;
}

// Obtener datos del formulario
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validar campos vacíos
if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = 'Todos los campos son requeridos.';
    $_SESSION['login_email'] = $email;
    header('Location: ' . getBackendPath() . '/auth/login-view.php');
    exit;
}

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['login_error'] = 'El formato del email no es válido.';
    $_SESSION['login_email'] = $email;
    header('Location: ' . getBackendPath() . '/auth/login-view.php');
    exit;
}

try {
    $pdo = obtenerConexionPDO();

    // Buscar usuario por email
    $stmt = $pdo->prepare(
        "SELECT id, nombre, apellido, email, password_hash, rol, activo, bloqueado_hasta, intentos_fallidos
         FROM usuarios
         WHERE email = :email AND deleted_at IS NULL"
    );
    $stmt->execute([':email' => strtolower($email)]);
    $usuario = $stmt->fetch();

    // Verificar que el usuario existe
    if (!$usuario) {
        $_SESSION['login_error'] = 'Credenciales incorrectas.';
        $_SESSION['login_email'] = $email;
        header('Location: ' . getBackendPath() . '/auth/login-view.php');
        exit;
    }

    // Verificar que es administrador
    if ($usuario['rol'] !== 'admin') {
        $_SESSION['login_error'] = 'Acceso denegado. Solo administradores pueden ingresar.';
        $_SESSION['login_email'] = $email;
        header('Location: ' . getBackendPath() . '/auth/login-view.php');
        exit;
    }

    // Verificar que la cuenta está activa
    if (!$usuario['activo']) {
        $_SESSION['login_error'] = 'Cuenta deshabilitada. Contacte al administrador.';
        $_SESSION['login_email'] = $email;
        header('Location: ' . getBackendPath() . '/auth/login-view.php');
        exit;
    }

    // Verificar bloqueo por intentos fallidos
    if ($usuario['bloqueado_hasta'] && strtotime($usuario['bloqueado_hasta']) > time()) {
        $minutosRestantes = ceil((strtotime($usuario['bloqueado_hasta']) - time()) / 60);
        $_SESSION['login_error'] = "Cuenta temporalmente bloqueada. Intente en {$minutosRestantes} minuto(s).";
        $_SESSION['login_email'] = $email;
        header('Location: ' . getBackendPath() . '/auth/login-view.php');
        exit;
    }

    // Verificar contraseña
    if (!password_verify($password, $usuario['password_hash'])) {
        // Incrementar intentos fallidos
        $stmt = $pdo->prepare(
            "UPDATE usuarios
             SET intentos_fallidos = intentos_fallidos + 1,
                 bloqueado_hasta = IF(intentos_fallidos + 1 >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NULL)
             WHERE id = :id"
        );
        $stmt->execute([':id' => $usuario['id']]);

        $_SESSION['login_error'] = 'Credenciales incorrectas.';
        $_SESSION['login_email'] = $email;
        header('Location: ' . getBackendPath() . '/auth/login-view.php');
        exit;
    }

    // ✅ Login exitoso

    // Resetear intentos fallidos y actualizar último login
    $stmt = $pdo->prepare(
        "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_login = NOW() WHERE id = :id"
    );
    $stmt->execute([':id' => $usuario['id']]);

    // Regenerar session ID para prevenir session fixation
    session_regenerate_id(true);

    // Almacenar datos en sesión
    $_SESSION['admin_id'] = $usuario['id'];
    $_SESSION['admin_email'] = $usuario['email'];
    $_SESSION['admin_nombre'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
    $_SESSION['admin_role'] = $usuario['rol'];
    $_SESSION['admin_last_activity'] = time();
    $_SESSION['admin_created_at'] = time();
    $_SESSION['admin_fingerprint'] = AuthMiddleware::generarFingerprint();

    // Generar JWT para las llamadas API del dashboard
    // Usamos las clases existentes del proyecto
    require_once __DIR__ . '/../../src/Core/Autoloader.php';
    $token = \App\Core\JWT::generarToken($usuario);
    $_SESSION['admin_jwt_token'] = $token;

    // Limpiar errores previos
    unset($_SESSION['login_error'], $_SESSION['login_email']);

    // Redirigir al dashboard
    header('Location: ' . getBackendPath() . '/views/dashboard.php');
    exit;

} catch (\Exception $e) {
    $_SESSION['login_error'] = 'Error del servidor. Intente más tarde.';
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $_SESSION['login_error'] .= ' Debug: ' . $e->getMessage();
    }
    header('Location: ' . getBackendPath() . '/auth/login-view.php');
    exit;
}
