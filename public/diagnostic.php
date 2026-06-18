<?php
/**
 * Diagnóstico rápido del servidor
 * BORRAR DESPUÉS DE DEPURAR
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>PHP " . PHP_VERSION . "</h2>";
echo "<h3>SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "</h3>";
echo "<h3>REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "</h3>";
echo "<h3>DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "</h3>";
echo "<h3>dirname(SCRIPT_NAME): " . dirname($_SERVER['SCRIPT_NAME']) . "</h3>";

echo "<h3>Test .env:</h3>";
$envPath = dirname(__DIR__) . '/.env';
echo "Buscando .env en: " . $envPath . "<br>";
echo file_exists($envPath) ? "<b style='color:green'>✅ .env EXISTE</b>" : "<b style='color:red'>❌ .env NO EXISTE</b>";

echo "<h3>Test config/app.php:</h3>";
try {
    require_once dirname(__DIR__) . '/config/app.php';
    echo "<b style='color:green'>✅ config/app.php cargado OK</b><br>";
    echo "APP_URL: " . APP_URL . "<br>";
    echo "DB_HOST desde ENV: " . ($_ENV['DB_HOST'] ?? 'NO DEFINIDO') . "<br>";
    echo "DB_NAME desde ENV: " . ($_ENV['DB_NAME'] ?? 'NO DEFINIDO') . "<br>";
} catch (Throwable $e) {
    echo "<b style='color:red'>❌ Error en config/app.php: " . $e->getMessage() . "</b><br>";
}

echo "<h3>Test Autoloader:</h3>";
try {
    require_once dirname(__DIR__) . '/src/Core/Autoloader.php';
    echo "<b style='color:green'>✅ Autoloader cargado</b><br>";
} catch (Throwable $e) {
    echo "<b style='color:red'>❌ Error en Autoloader: " . $e->getMessage() . "</b><br>";
}

echo "<h3>Test conexión BD:</h3>";
try {
    $host   = $_ENV['DB_HOST'] ?? 'teclab.uct.cl';
    $port   = $_ENV['DB_PORT'] ?? '3306';
    $dbname = $_ENV['DB_NAME'] ?? 'mvaldebenito_db2';
    $user   = $_ENV['DB_USER'] ?? 'mvaldebenito';
    $pass   = $_ENV['DB_PASS'] ?? 'MvX91mQp#';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    echo "DSN: " . $dsn . "<br>";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->query('SELECT 1');
    echo "<b style='color:green'>✅ Conexión BD exitosa</b><br>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM productos");
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Productos en BD: " . $r['total'] . "<br>";
} catch (Throwable $e) {
    echo "<b style='color:red'>❌ Error BD: " . $e->getMessage() . "</b><br>";
}

echo "<h3>Test Request + Router:</h3>";
try {
    $req = new App\Core\Request();
    echo "URI detectada por Request: " . $req->getUri() . "<br>";
    echo "<b style='color:green'>✅ Request OK</b><br>";
} catch (Throwable $e) {
    echo "<b style='color:red'>❌ Error Request: " . $e->getMessage() . "</b><br>";
}
