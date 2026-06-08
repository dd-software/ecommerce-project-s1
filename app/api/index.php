<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$base = dirname(__DIR__) . '/api/src';

require_once $base . '/config/config.php';
require_once $base . '/config/database.php';
require_once $base . '/helpers/JWT.php';
require_once $base . '/helpers/Response.php';
require_once $base . '/middleware/Auth.php';
require_once $base . '/controllers/AuthController.php';
require_once $base . '/controllers/CatalogoController.php';
require_once $base . '/controllers/CarritoController.php';
require_once $base . '/controllers/CheckoutController.php';
require_once $base . '/controllers/AdminController.php';

try {
    $db = Database::getInstance()->getConnection();
} catch (PDOException $e) {
    Response::error('DB_ERROR',
        'No se pudo conectar a la base de datos. ¿Ejecutaste php database/setup.php?', 503);
}

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Strip everything up to and including /api/ — works with any subdirectory prefix (XAMPP)
$path = preg_replace('#^.*/api/?#', '', $uri);
$path = trim($path, '/');

$segments = $path !== '' ? explode('/', $path) : [];
$resource = $segments[0] ?? '';
$rest     = array_slice($segments, 1);

switch ($resource) {
    case 'auth':
        (new AuthController($db))->handle($method, $rest);
        break;

    case 'catalogo':
        (new CatalogoController($db))->handle($method, $rest);
        break;

    case 'carrito':
        $user = Auth::requireAuth($db);
        (new CarritoController($db))->handle($method, $rest, $user);
        break;

    case 'checkout':
        $user = Auth::requireAuth($db);
        (new CheckoutController($db))->handle($method, $rest, $user);
        break;

    case 'admin':
        $user = Auth::requireAdmin($db);
        (new AdminController($db))->handle($method, $rest);
        break;

    default:
        Response::error('RUTA_NO_ENCONTRADA', "Ruta /$resource no existe.", 404);
}
