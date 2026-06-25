<?php
declare(strict_types=1);

// Configuraciones globales CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Autoloader PSR-4 Simplificado
spl_autoload_register(function ($class) {
    $base_dir = __DIR__ . '/../';
    
    // Transformamos el namespace. Ej: Src\Core\Router -> src/Core/Router.php
    $parts = explode('\\', $class);
    $parts[0] = strtolower($parts[0]);
    $file = $base_dir . implode('/', $parts) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Cargar variables de entorno desde .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
    }
}

// Manejador global de excepciones para asegurar respuesta estricta en JSON 
set_exception_handler(function (\Throwable $exception) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Error Interno del Servidor',
        'details' => $exception->getMessage() // Útil en desarrollo/evaluación académica
    ]);
    exit;
});

use Src\Core\Router;
use Src\Controllers\AuthController;

// Instanciar Enrutador
$router = new Router();

// ==========================================
// Definición de Endpoints
// ==========================================

// Módulo C (Autenticación)
$router->add('POST', '/api/auth/login', [new AuthController(), 'login']);
$router->add('POST', '/api/auth/register', [new AuthController(), 'register']);

// Módulo A (Catálogo)
use Src\Controllers\CatalogController;
$catalog = new CatalogController();
$router->add('GET', '/api/catalog/products',        [$catalog, 'list']);
$router->add('GET', '/api/catalog/products/search', [$catalog, 'search']);
$router->add('GET', '/api/catalog/categories',      [$catalog, 'categories']);
$router->add('GET', '/api/catalog/products/{id}',   [$catalog, 'detail']);

// Módulo F (Inventario)
use Src\Controllers\InventoryController;
$router->add('GET', '/api/inventory/alerts', [new InventoryController(), 'alerts']);

// Módulo B (Carrito)
use Src\Controllers\CartController;
$router->add('GET', '/api/cart', [new CartController(), 'index']);
$router->add('POST', '/api/cart', [new CartController(), 'add']);
$router->add('DELETE', '/api/cart/{variant_id}', [new CartController(), 'remove']);

// Módulos D y E (Checkout y Pagos)
use Src\Controllers\CheckoutController;
use Src\Controllers\PaymentController;
use Src\Controllers\TransbankController;
$router->add('POST', '/api/checkout', [new CheckoutController(), 'process']);
$router->add('POST', '/api/payment/simulate', [new PaymentController(), 'simulate']);
$router->add('POST', '/api/payment/paypal/create', [new PaymentController(), 'createPayPalOrder']);
$router->add('POST', '/api/payment/paypal/capture', [new PaymentController(), 'capturePayPalOrder']);
$router->add('POST', '/api/payment/transbank/create', [new TransbankController(), 'create']);
$router->add('GET', '/api/config/paypal', function() {
    \Src\Core\Response::json(['client_id' => getenv('PAYPAL_CLIENT_ID')]);
});

// Módulo G (Administración)
use Src\Controllers\AdminController;
$router->add('GET', '/api/admin/orders', [new AdminController(), 'listOrders']);
$router->add('PUT', '/api/admin/orders/{id}/status', [new AdminController(), 'updateOrderStatus']);

// Módulo H (Mis Compras - Cliente)
use Src\Controllers\OrderController;
$router->add('GET', '/api/orders/me', [new OrderController(), 'myOrders']);

// Despachar la petición
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'] ?? '/');
