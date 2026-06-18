<?php
/**
 * Test: simular la ejecución completa de index.php paso a paso
 */
header('Content-Type: application/json');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$steps = [];

try {
    // Paso 1: Cargar config
    require_once __DIR__ . '/../config/app.php';
    $steps[] = '1. config/app.php loaded OK';
    
    // Paso 2: Cargar autoloader
    require_once __DIR__ . '/../src/Core/Autoloader.php';
    $steps[] = '2. Autoloader loaded OK';
    
    // Paso 3: Session
    try {
        \App\Core\Session::iniciar();
        $steps[] = '3. Session::iniciar() OK';
    } catch (\Throwable $e) {
        $steps[] = '3. Session::iniciar() FAILED: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }
    
    // Paso 4: Request
    try {
        $request = new \App\Core\Request();
        $steps[] = '4. Request created OK - uri=' . $request->getUri() . ' method=' . $request->getMethod();
    } catch (\Throwable $e) {
        $steps[] = '4. Request creation FAILED: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }
    
    // Paso 5: Response
    try {
        $response = new \App\Core\Response();
        $steps[] = '5. Response created OK';
    } catch (\Throwable $e) {
        $steps[] = '5. Response creation FAILED: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }

    // Paso 6: JwtMiddleware
    try {
        \App\Core\JwtMiddleware::procesar($request);
        $steps[] = '6. JwtMiddleware::procesar() OK';
    } catch (\Throwable $e) {
        $steps[] = '6. JwtMiddleware FAILED: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }
    
    // Paso 7: Router
    try {
        $router = new \App\Core\Router();
        $steps[] = '7. Router created OK';
    } catch (\Throwable $e) {
        $steps[] = '7. Router creation FAILED: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }
    
    // Paso 8: Register a test route
    try {
        $router->get('/api/catalogo', [\App\Catalogo\CatalogoController::class, 'listar']);
        $steps[] = '8. Route /api/catalogo registered OK';
    } catch (\Throwable $e) {
        $steps[] = '8. Route registration FAILED: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }

    // Paso 9: Try dispatching directly to CatalogoController
    try {
        $controller = new \App\Catalogo\CatalogoController();
        $steps[] = '9. CatalogoController instantiated OK';
    } catch (\Throwable $e) {
        $steps[] = '9. CatalogoController instantiation FAILED: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }

    // Paso 10: Check if the named args syntax works (auth:, admin:)
    try {
        $router->post('/api/auth/logout', [\App\Auth\AuthController::class, 'logout'], auth: true);
        $steps[] = '10. Named args route (auth: true) registered OK';
    } catch (\Throwable $e) {
        $steps[] = '10. Named args route FAILED: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }

    // Paso 11: Verify .htaccess RewriteBase
    $htaccess = __DIR__ . '/.htaccess';
    if (file_exists($htaccess)) {
        $content = file_get_contents($htaccess);
        if (preg_match('/RewriteBase\s+(.+)/', $content, $m)) {
            $steps[] = '11. .htaccess RewriteBase: ' . trim($m[1]);
        } else {
            $steps[] = '11. .htaccess has NO RewriteBase';
        }
    } else {
        $steps[] = '11. NO .htaccess found!';
    }
    
    // Paso 12: Check SCRIPT_NAME and REQUEST_URI
    $steps[] = '12. SCRIPT_NAME=' . ($_SERVER['SCRIPT_NAME'] ?? 'N/A');
    $steps[] = '12. REQUEST_URI=' . ($_SERVER['REQUEST_URI'] ?? 'N/A');
    
    // Paso 13: Check if declare(strict_types=1) causes issues with error handler closures
    // The error handler uses exit() which should be fine in strict mode
    $steps[] = '13. PHP version=' . phpversion() . ' (strict_types requires >= 7.0)';

    // Paso 14: Check if CRLF line endings cause issues
    $indexContent = file_get_contents(__DIR__ . '/index.php');
    $hasCRLF = strpos($indexContent, "\r\n") !== false;
    $steps[] = '14. index.php line endings: ' . ($hasCRLF ? 'CRLF (Windows)' : 'LF (Unix)');
    
    // Paso 15: Try to actually include index.php in a subprocess
    $cmd = 'php -r "' . "require '" . __DIR__ . "/index.php';" . '" 2>&1';
    $steps[] = '15. Would run: ' . $cmd;

} catch (\Throwable $e) {
    $steps[] = 'FATAL: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    $steps[] = 'TRACE: ' . $e->getTraceAsString();
}

echo json_encode(['steps' => $steps], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
