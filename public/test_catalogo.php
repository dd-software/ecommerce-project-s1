<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Core/Autoloader.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    echo "DB Connection OK\n";
    $repo = new \App\Catalogo\CatalogoRepository();
    $res = $repo->buscarProductos(['activo' => true], 'relevancia', 1, 12);
    echo "Query OK\n";
    echo "Total: " . $res['total'] . "\n";
    echo "Products count: " . count($res['productos']) . "\n";
    $json = json_encode($res);
    if ($json === false) {
        echo "JSON Encode failed: " . json_last_error_msg() . "\n";
    } else {
        echo "JSON Encode OK\n";
    }
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
