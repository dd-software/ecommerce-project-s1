<?php
declare(strict_types=1);

/**
 * Script de Diagnóstico del Sistema v2
 * Prueba el entorno, la BD y simula llamadas a la API del carrito
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Diagnóstico de E-commerce System v2</h1>";
echo "<pre>";

// 1. Verificar PHP
echo "PHP Version: " . PHP_VERSION . "\n";
echo "✅ PHP Version OK.\n";

// 2. Verificar Extensiones
$extensions = ['pdo', 'pdo_mysql', 'json', 'session', 'mbstring', 'openssl'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ Extensión '{$ext}' cargada.\n";
    } else {
        echo "❌ ERROR: Falta extensión '{$ext}'.\n";
    }
}

// 3. Verificar Autoload y Directorios
$basePath = dirname(__DIR__);
echo "\nBase Path: {$basePath}\n";

$dirs = ['src', 'src/Core', 'config', 'public'];
foreach ($dirs as $dir) {
    if (is_dir($basePath . '/' . $dir)) {
        echo "✅ Directorio '{$dir}' encontrado.\n";
    } else {
        echo "❌ ERROR: No se encuentra '{$dir}'.\n";
    }
}

// 4. Probar Configuración y Base de Datos
try {
    require_once $basePath . '/config/app.php';
    echo "✅ Configuración cargada (APP_NAME: " . APP_NAME . ")\n";
    
    require_once $basePath . '/src/Core/Autoloader.php';
    echo "✅ Autoloader registrado.\n";
    
    $db = \App\Core\Database::getInstance()->getConnection();
    echo "✅ Conexión a Base de Datos exitosa.\n";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM productos");
    $total = $stmt->fetch()['total'];
    echo "✅ Base de Datos operativa (Total productos: {$total})\n";

    // Verificar tabla carritos
    $stmt = $db->query("SHOW TABLES LIKE 'carritos'");
    if ($stmt->fetch()) {
        echo "✅ Tabla 'carritos' existe.\n";
    } else {
        echo "❌ ERROR: Tabla 'carritos' NO existe.\n";
    }

    // Verificar tabla items_carrito
    $stmt = $db->query("SHOW TABLES LIKE 'items_carrito'");
    if ($stmt->fetch()) {
        echo "✅ Tabla 'items_carrito' existe.\n";
    } else {
        echo "❌ ERROR: Tabla 'items_carrito' NO existe.\n";
    }

    // Verificar columnas de items_carrito
    $stmt = $db->query("DESCRIBE items_carrito");
    $cols = array_column($stmt->fetchAll(), 'Field');
    echo "   Columnas items_carrito: " . implode(', ', $cols) . "\n";

    // Verificar columnas de carritos
    $stmt = $db->query("DESCRIBE carritos");
    $cols = array_column($stmt->fetchAll(), 'Field');
    echo "   Columnas carritos: " . implode(', ', $cols) . "\n";

} catch (\Throwable $e) {
    echo "❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "Stack Trace: " . $e->getTraceAsString() . "\n";
}

// 5. Prueba directa del endpoint del carrito (simulando lo que hace el frontend)
echo "\n--- Test Interno de la API del Carrito ---\n";
$apiUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
        . '://' . $_SERVER['HTTP_HOST'];
// Intentar detectar la base URL
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseApi = rtrim($scriptDir, '/') . '/api';
echo "API Base detectada: {$apiUrl}{$baseApi}\n";

// Test carrito GET
echo "\nTest GET /api/carrito:\n";
$ch = curl_init("{$apiUrl}{$baseApi}/carrito");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Session-Id: diag-test-session'],
    CURLOPT_TIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ cURL error: {$error}\n";
    echo "   (Esto puede pasar si el servidor no permite conexiones cURL a sí mismo)\n";
} else {
    echo "HTTP Status: {$httpCode}\n";
    echo "Response (primeros 200 chars): " . substr($result, 0, 200) . "\n";
    
    // Verificar si es JSON válido
    $decoded = json_decode($result, true);
    if ($decoded !== null) {
        echo "✅ Respuesta es JSON válido.\n";
        if (isset($decoded['success']) && $decoded['success']) {
            echo "✅ API responde correctamente.\n";
        } else {
            echo "⚠️  API respondió con error: " . ($decoded['error']['message'] ?? 'desconocido') . "\n";
        }
    } else {
        echo "❌ PROBLEMA: La respuesta NO es JSON válido.\n";
        echo "   Contenido crudo: " . htmlspecialchars(substr($result, 0, 500)) . "\n";
    }
}

// 6. Verificar output buffering
echo "\n--- Estado del Output Buffer ---\n";
echo "ob_get_level(): " . ob_get_level() . "\n";
echo "Esto debería ser 0 o muy bajo. Si es alto, hay buffers anidados.\n";

// 7. Headers de Request
echo "\n--- Request Headers ---\n";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        echo "{$key}: {$value}\n";
    }
}

echo "</pre>";
