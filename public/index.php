<?php
declare(strict_types=1);

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

try {
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

    // Despachar la petición
    $router->dispatch($request, $response);
} catch (\Throwable $e) {
    http_response_code(500);
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'SERVER_ERROR',
                'message' => 'Error interno: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]
        ]);
    } else {
        echo "<div style='background: #fee; border: 2px solid red; padding: 20px; font-family: sans-serif; color: black;'>";
        echo "<h1>ERROR DETECTADO EN EJECUCIÓN</h1>";
        echo "<b>Mensaje:</b> " . $e->getMessage() . "<br>";
        echo "<b>Archivo:</b> " . $e->getFile() . "<br>";
        echo "<b>Línea:</b> " . $e->getLine() . "<br>";
        echo "<pre style='background: #333; color: #fff; padding: 10px;'>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
    }
    exit();
}
