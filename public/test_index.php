<?php
/**
 * Test: intentar cargar index.php paso a paso para encontrar el error exacto
 */
header('Content-Type: application/json');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$results = [];

// 1. Verificar que index.php existe
$indexPath = __DIR__ . '/index.php';
$results['index_exists'] = file_exists($indexPath);
$results['index_size'] = filesize($indexPath);

// 2. Verificar sintaxis de index.php con php -l
$output = [];
$returnCode = -1;
exec("php -l " . escapeshellarg($indexPath) . " 2>&1", $output, $returnCode);
$results['syntax_check'] = [
    'return_code' => $returnCode,
    'output' => implode("\n", $output)
];

// 3. Intentar cargar config/app.php por separado
try {
    require_once __DIR__ . '/../config/app.php';
    $results['config_app'] = 'OK - loaded';
    $results['APP_URL'] = defined('APP_URL') ? APP_URL : 'NOT_DEFINED';
    $results['APP_ENV'] = defined('APP_ENV') ? APP_ENV : 'NOT_DEFINED';
} catch (\Throwable $e) {
    $results['config_app'] = 'ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
}

// 4. Intentar cargar autoloader por separado
try {
    require_once __DIR__ . '/../src/Core/Autoloader.php';
    $results['autoloader'] = 'OK - loaded';
} catch (\Throwable $e) {
    $results['autoloader'] = 'ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
}

// 5. Verificar si las clases existen
$classes = [
    'App\\Core\\Request',
    'App\\Core\\Response',
    'App\\Core\\Router',
    'App\\Core\\Session',
    'App\\Core\\JwtMiddleware',
    'App\\Auth\\AuthController',
    'App\\Catalogo\\CatalogoController',
    'App\\Carrito\\CarritoController',
    'App\\Checkout\\CheckoutController',
    'App\\Pagos\\PagosController',
    'App\\Inventario\\InventarioController',
    'App\\Admin\\AdminController',
    'App\\Integracion\\IntegracionController',
];

foreach ($classes as $cls) {
    try {
        $exists = class_exists($cls);
        $results['class_' . $cls] = $exists ? 'OK' : 'NOT FOUND';
    } catch (\Throwable $e) {
        $results['class_' . $cls] = 'ERROR: ' . $e->getMessage();
    }
}

// 6. Intentar crear Request
try {
    $req = new \App\Core\Request();
    $results['request_create'] = 'OK - uri=' . $req->getUri() . ' method=' . $req->getMethod();
} catch (\Throwable $e) {
    $results['request_create'] = 'ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
}

// 7. Intentar crear Response
try {
    // No enviar headers ni body
    $results['response_class_exists'] = class_exists('App\\Core\\Response') ? 'yes' : 'no';
} catch (\Throwable $e) {
    $results['response_create'] = 'ERROR: ' . $e->getMessage();
}

// 8. Verificar Router
try {
    $results['router_class_exists'] = class_exists('App\\Core\\Router') ? 'yes' : 'no';
} catch (\Throwable $e) {
    $results['router_create'] = 'ERROR: ' . $e->getMessage();
}

// 9. Verificar Session
try {
    $results['session_class_exists'] = class_exists('App\\Core\\Session') ? 'yes' : 'no';
} catch (\Throwable $e) {
    $results['session_check'] = 'ERROR: ' . $e->getMessage();
}

// 10. Verificar JwtMiddleware  
try {
    $results['jwt_class_exists'] = class_exists('App\\Core\\JwtMiddleware') ? 'yes' : 'no';
} catch (\Throwable $e) {
    $results['jwt_check'] = 'ERROR: ' . $e->getMessage();
}

// 11. Verificar primeras 5 líneas de index.php
$lines = file($indexPath);
$results['first_5_lines'] = array_slice($lines, 0, 5);
$results['total_lines'] = count($lines);

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
