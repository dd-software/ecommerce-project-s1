<?php
// Clear log
file_put_contents(__DIR__ . '/debug_log.txt', "Starting debug log...\n");

function logMsg($msg) {
    file_put_contents(__DIR__ . '/debug_log.txt', $msg . "\n", FILE_APPEND);
}

try {
    logMsg("Requiring config/app.php...");
    require_once __DIR__ . '/../config/app.php';
    logMsg("config/app.php loaded.");

    logMsg("Requiring src/Core/Autoloader.php...");
    require_once __DIR__ . '/../src/Core/Autoloader.php';
    logMsg("Autoloader loaded.");

    logMsg("Importing classes...");
    // Let's manually trigger autoloader for each class to see if any fails to load or parse
    $classes = [
        'App\Core\Request',
        'App\Core\Response',
        'App\Core\Router',
        'App\Core\Session',
        'App\Core\JwtMiddleware',
        'App\Auth\AuthController',
        'App\Catalogo\CatalogoController',
        'App\Carrito\CarritoController',
        'App\Checkout\CheckoutController',
        'App\Pagos\PagosController',
        'App\Inventario\InventarioController',
        'App\Admin\AdminController',
        'App\Integracion\IntegracionController',
    ];

    foreach ($classes as $cls) {
        logMsg("Loading class $cls...");
        if (class_exists($cls)) {
            logMsg("Class $cls loaded successfully.");
        } else {
            logMsg("Class $cls NOT found.");
        }
    }

    logMsg("Initializing Session...");
    \App\Core\Session::iniciar();
    logMsg("Session initialized.");

    logMsg("Creating Request...");
    $request = new \App\Core\Request();
    logMsg("Request created.");

    logMsg("Creating Response...");
    $response = new \App\Core\Response();
    logMsg("Response created.");

    logMsg("Processing JWT Middleware...");
    \App\Core\JwtMiddleware::procesar($request);
    logMsg("JWT Middleware processed.");

    logMsg("Creating Router...");
    $router = new \App\Core\Router();
    logMsg("Router created.");

    logMsg("Done test!");
} catch (\Throwable $e) {
    logMsg("FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    logMsg($e->getTraceAsString());
}
echo "Debug finished. Read public/debug_log.txt";
