<?php

// Reporte de errores forzado a archivo para depuración
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Log de errores manual (ahora directo a pantalla o JSON según el contexto)
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'PHP_ERROR',
                'message' => $errstr,
                'file' => $errfile,
                'line' => $errline
            ]
        ]);
    } else {
        echo "<div style='border: 2px solid red; padding: 20px; font-family: sans-serif;'>";
        echo "<h1>ERROR DETECTADO</h1>";
        echo "<b>Mensaje:</b> $errstr<br>";
        echo "<b>Archivo:</b> $errfile<br>";
        echo "<b>Línea:</b> $errline<br>";
        echo "</div>";
    }
    exit();
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api') !== false) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'FATAL_ERROR',
                    'message' => $error['message'],
                    'file' => $error['file'],
                    'line' => $error['line']
                ]
            ]);
        } else {
            echo "<div style='background: #fee; border: 2px solid red; padding: 20px; font-family: sans-serif;'>";
            echo "<h1>ERROR FATAL DETECTADO</h1>";
            echo "<b>Mensaje:</b> " . $error['message'] . "<br>";
            echo "<b>Archivo:</b> " . $error['file'] . "<br>";
            echo "<b>Línea:</b> " . $error['line'] . "<br>";
            echo "</div>";
        }
        exit();
    }
});

declare(strict_types=1);

/**
 * Front Controller - Punto de entrada único de la aplicación
 * Maneja todas las peticiones HTTP y enruta a los controladores correspondientes
 */

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

// Configurar CORS para requests cross-origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
if (APP_ENV === 'development') {
    header("Access-Control-Allow-Origin: *");
} else {
    header("Access-Control-Allow-Origin: " . APP_URL);
}

// Crear router y registrar rutas
$router = new Router();

// ============================================
// Rutas de API y Frontend
// ============================================

// --- Inicio / Frontend ---
$router->get('/', function($req, $res) {
    if (file_exists(__DIR__ . '/index_template.html')) {
        $html = file_get_contents(__DIR__ . '/index_template.html');
        $base = APP_URL . '/api';
        $configScript = "<script>window.SERVER_API_BASE = '{$base}'; console.log('SISTEMA: API BASE OK');</script>";
        $html = str_ireplace('<html', "<html data-php-active='true'", $html);
        $html = str_ireplace('<head>', "<head>{$configScript}", $html);
        $res->html($html);
    } else {
        $res->error('NOT_FOUND', 'Página de inicio no encontrada', 404);
    }
});

$router->get('/login', function($req, $res) {
    if (file_exists(__DIR__ . '/login_template.html')) {
        $html = file_get_contents(__DIR__ . '/login_template.html');
        $base = APP_URL . '/api';
        $configScript = "<script>window.SERVER_API_BASE = '{$base}';</script>";
        $html = str_ireplace('<head>', "<head>{$configScript}", $html);
        $res->html($html);
    } else {
        $res->error('NOT_FOUND', 'Página de login no encontrada', 404);
    }
});

$router->get('/admin', function($req, $res) {
    if (file_exists(__DIR__ . '/admin.html')) {
        $html = file_get_contents(__DIR__ . '/admin.html');
        $base = APP_URL . '/api';
        $configScript = "<script>window.SERVER_API_BASE = '{$base}'; console.log('API Base set to:', window.SERVER_API_BASE);</script>";
        $html = str_ireplace('<head>', "<head>{$configScript}", $html);
        $res->html($html);
    } else {
        $res->error('NOT_FOUND', 'Página de administración no encontrada', 404);
    }
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
