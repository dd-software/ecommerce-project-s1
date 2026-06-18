<?php
// diag2.php - Diagnóstico de DB y endpoint
header('Content-Type: application/json; charset=utf-8');

$results = [];

// 1) Load env
function cargarEnv2(string $ruta): void {
    if (!file_exists($ruta)) return;
    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        if (str_starts_with(trim($linea), '#')) continue;
        if (str_contains($linea, '=')) {
            [$clave, $valor] = explode('=', $linea, 2);
            $clave = trim($clave); $valor = trim(trim($valor), '"\'');
            if (!array_key_exists($clave, $_ENV) && getenv($clave) === false) {
                $_ENV[$clave] = $valor; putenv("{$clave}={$valor}");
            }
        }
    }
}
cargarEnv2(dirname(__DIR__) . '/.env');

$host   = $_ENV['DB_HOST'] ?? 'localhost';
$port   = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? 'mvaldebenito_db2';
$user   = $_ENV['DB_USER'] ?? 'mvaldebenito';
$pass   = $_ENV['DB_PASS'] ?? 'MvX91mQp#';

$results['db_host']   = $host;
$results['db_name']   = $dbname;
$results['db_user']   = $user;
$results['pass_len']  = strlen($pass);

// 2) Try connecting
try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $results['db_connect'] = 'OK';
} catch (\Throwable $e) {
    $results['db_connect'] = 'FAILED: ' . $e->getMessage();
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 3) Check tables
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $results['tables'] = $tables;
} catch (\Throwable $e) {
    $results['tables'] = 'ERROR: ' . $e->getMessage();
}

// 4) Try categorias query
try {
    $stmt = $pdo->query("SELECT c.id, c.nombre, c.slug, COUNT(p.id) as total_productos
        FROM categorias c
        LEFT JOIN productos p ON p.id_categoria = c.id AND p.activo = 1 AND p.deleted_at IS NULL
        WHERE c.activo = 1
        GROUP BY c.id
        ORDER BY c.nombre ASC
        LIMIT 5");
    $categorias = $stmt->fetchAll();
    $results['categorias_query'] = 'OK';
    $results['categorias_count'] = count($categorias);
    $results['categorias_sample'] = $categorias;
} catch (\Throwable $e) {
    $results['categorias_query'] = 'ERROR: ' . $e->getMessage();
}

// 5) Try productos query
try {
    $stmt = $pdo->query("SELECT id, nombre, precio, stock FROM productos WHERE activo=1 AND deleted_at IS NULL LIMIT 5");
    $prods = $stmt->fetchAll();
    $results['productos_query'] = 'OK';
    $results['productos_count'] = count($prods);
    $results['productos_sample'] = $prods;
} catch (\Throwable $e) {
    $results['productos_query'] = 'ERROR: ' . $e->getMessage();
}

// 6) Try loading full app and calling categorias
try {
    require_once __DIR__ . '/../config/app.php';
    require_once __DIR__ . '/../src/Core/Autoloader.php';
    $repo = new \App\Catalogo\CatalogoRepository();
    $service = new \App\Catalogo\CatalogoService($repo);
    $cats = $service->listarCategorias();
    $results['full_app_categorias'] = 'OK - count: ' . count($cats);
} catch (\Throwable $e) {
    $results['full_app_categorias'] = 'ERROR: ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine();
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
