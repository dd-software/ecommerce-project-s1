<?php
// Iniciar buffer de salida INMEDIATAMENTE para capturar cualquier output inesperado
ob_start();
// Desactivar display_errors de inmediato - los errores se manejan por JSON, no HTML
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
declare(strict_types=1);
/**
 * Front Controller - Punto de entrada único de la aplicación
 * Maneja todas las peticiones HTTP y enruta a los controladores correspondientes
 */

// Capturar errores y convertirlos en JSON para evitar romper el frontend
set_exception_handler(function ($e) {
    // Limpiar TODOS los niveles de buffer para no corromper el JSON con warnings anteriores
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'SERVER_ERROR',
            'message' => $e->getMessage(),
            'file'    => basename($e->getFile()),
            'line'    => $e->getLine()
        ]
    ]);
    exit;
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return;
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

// Cargar configuración
require_once __DIR__ . '/../config/app.php';
// Cargar autoloader
require_once __DIR__ . '/../src/Core/Autoloader.php';
use App\Core\{Request, Response, Router, Session, JwtMiddleware};
use App\Auth\AuthController;
use App\Catalogo\CatalogoController;
use App\Carrito\CarritoController;
use App\Checkout\CheckoutController;
use App\Pagos\PagosController;
use App\Inventario\InventarioController;
use App\Admin\AdminController;
use App\Integracion\IntegracionController;
// Iniciar sesión
Session::iniciar();
// Crear request y response
$request = new Request();
$response = new Response();
// Aplicar middleware JWT (adjunta usuario autenticado si hay token válido)
JwtMiddleware::procesar($request);
// Configurar CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: {$origin}");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Session-Id, X-HTTP-Method-Override");
header("Access-Control-Allow-Credentials: true");

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(204);
    exit;
}
// Crear router y registrar rutas
$router = new Router();
// ============================================
// Rutas de API y Frontend
// ============================================
// ─── Inicio / Frontend ────────────────────────────────────────────────────
// Función helper para servir un HTML con la URL de API inyectada
$servirHtml = function(string $archivo, \App\Core\Response $res): void {
    $ruta = __DIR__ . '/' . $archivo;
    if (!file_exists($ruta)) {
        $res->error('NOT_FOUND', "Página '{$archivo}' no encontrada", 404);
        return;
    }
    $html = file_get_contents($ruta);
    $base = APP_URL . '/api';
    $configScript = "<script>window.SERVER_API_BASE = '{$base}'; console.log('[UCT] API base: ' + window.SERVER_API_BASE);</script>";
    $html = str_ireplace('<html', "<html data-php-active='true'", $html);
    $html = str_ireplace('<head>', "<head>{$configScript}", $html);
    $res->html($html);
};
// Inicio — tanto / como /index.html van al mismo handler
$router->get('/', function($req, $res) use ($servirHtml) {
    $servirHtml('index.html', $res);
});
$router->get('/index.html', function($req, $res) use ($servirHtml) {
    $servirHtml('index.html', $res);
});
// Login
$router->get('/login', function($req, $res) use ($servirHtml) {
    $servirHtml('login.html', $res);
});
$router->get('/login.html', function($req, $res) use ($servirHtml) {
    $servirHtml('login.html', $res);
});
// Admin
$router->get('/admin', function($req, $res) use ($servirHtml) {
    $servirHtml('admin.html', $res);
});
$router->get('/admin.html', function($req, $res) use ($servirHtml) {
    $servirHtml('admin.html', $res);
});
// Producto
$router->get('/producto.html', function($req, $res) use ($servirHtml) {
    $servirHtml('producto.html', $res);
});
// Pedido confirmado
$router->get('/pedido-confirmado.html', function($req, $res) use ($servirHtml) {
    $servirHtml('pedido-confirmado.html', $res);
});
// --- Módulo C: Autenticación ---
$router->post('/api/auth/registro', [AuthController::class, 'registro']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/logout', [AuthController::class, 'logout'], auth: true);
$router->get('/api/auth/perfil', [AuthController::class, 'perfil'], auth: true);
$router->patch('/api/auth/perfil', [AuthController::class, 'actualizarPerfil'], auth: true);
// --- Módulo A: Catálogo ---
$router->get('/api/catalogo', [CatalogoController::class, 'listar']);
$router->get('/api/catalogo/categorias', [CatalogoController::class, 'categorias']);
$router->get('/api/catalogo/destacados', [CatalogoController::class, 'destacados']);
$router->get('/api/catalogo/{id}', [CatalogoController::class, 'detalle']);
// --- Módulo B: Carrito ---
$router->get('/api/carrito', [CarritoController::class, 'ver']); // auth opcional
$router->post('/api/carrito', [CarritoController::class, 'agregar']); // auth opcional
$router->patch('/api/carrito/{id}', [CarritoController::class, 'actualizar']);
$router->delete('/api/carrito/{id}', [CarritoController::class, 'eliminar']);
$router->delete('/api/carrito', [CarritoController::class, 'vaciar']);
$router->post('/api/carrito/sincronizar', [CarritoController::class, 'sincronizar'], auth: true);
// --- Módulo D: Checkout ---
$router->post('/api/checkout', [CheckoutController::class, 'crearPedido'], auth: true);
$router->get('/api/pedidos', [CheckoutController::class, 'misPedidos'], auth: true);
$router->get('/api/pedidos/{id}', [CheckoutController::class, 'detallePedido'], auth: true);
// --- Módulo E: Pagos ---
$router->post('/api/pagos/procesar', [PagosController::class, 'procesar'], auth: true);
$router->get('/api/pagos/estado/{pedidoId}', [PagosController::class, 'estado'], auth: true);
$router->post('/api/pagos/webhook', [PagosController::class, 'webhook']);
// --- Módulo F: Inventario ---
$router->get('/api/inventario', [InventarioController::class, 'verificar']);
$router->get('/api/inventario/movimientos', [InventarioController::class, 'movimientos'], auth: true, admin: false); // admin+supervisor+vendedor (validado en controller)
$router->get('/api/inventario/alertas', [InventarioController::class, 'alertas'], auth: true);
$router->post('/api/inventario/ajustar', [InventarioController::class, 'ajustar'], auth: true);
// --- Módulo G: Administración ---
$router->get('/api/admin/dashboard', [AdminController::class, 'dashboard'], auth: true, admin: true);
$router->get('/api/admin/productos', [AdminController::class, 'listarProductos'], auth: true, admin: true);
$router->post('/api/admin/productos', [AdminController::class, 'crearProducto'], auth: true, admin: true);
$router->put('/api/admin/productos/{id}', [AdminController::class, 'actualizarProducto'], auth: true, admin: true);
$router->delete('/api/admin/productos/{id}', [AdminController::class, 'eliminarProducto'], auth: true, admin: true);
$router->get('/api/admin/pedidos', [AdminController::class, 'listarPedidos'], auth: true, admin: true);
$router->patch('/api/admin/pedidos/{id}/estado', [AdminController::class, 'cambiarEstadoPedido'], auth: true, admin: true);
$router->get('/api/admin/usuarios', [AdminController::class, 'listarUsuarios'], auth: true, admin: true);
$router->patch('/api/admin/usuarios/{id}/estado', [AdminController::class, 'toggleUsuario'], auth: true, admin: true);
$router->get('/api/admin/reportes/ventas', [AdminController::class, 'reporteVentas'], auth: true, admin: true);
$router->get('/api/admin/reportes/productos-mas-vendidos', [AdminController::class, 'productosMasVendidos'], auth: true, admin: true);
// --- Módulo H: Integración ---
$router->get('/api/health', [IntegracionController::class, 'health']);
$router->post('/api/notificaciones/email', [IntegracionController::class, 'enviarEmail']);
$router->get('/api/notificaciones/cola', [IntegracionController::class, 'listarCola'], auth: true, admin: true);
$router->post('/api/notificaciones/confirmacion-pedido', [IntegracionController::class, 'notificarConfirmacion']);
$router->get('/api/exportar/pedidos', [IntegracionController::class, 'exportarPedidos'], auth: true);
$router->get('/api/exportar/productos', [IntegracionController::class, 'exportarProductos'], auth: true);
$router->get('/api/exportar/reporte-ventas', [IntegracionController::class, 'exportarReporteVentas'], auth: true);
// ============================================
// Despachar la petición
// ============================================
try {
    $router->dispatch($request, $response);
} catch (\Exception $e) {
    $response->error('SERVER_ERROR', 'Error interno del servidor: ' . $e->getMessage(), 500);
}