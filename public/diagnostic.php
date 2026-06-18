<?php
/**
 * diagnostic.php — Diagnóstico completo del entorno
 * Accede a: https://teclab.uct.cl/~mvaldebenito2025/public/diagnostic.php
 *
 * IMPORTANTE: Eliminar este archivo del servidor una vez resueltos los problemas.
 */

// Mostrar TODOS los errores
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Capturar errores fatales
$fatalError = null;
register_shutdown_function(function () use (&$fatalError) {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo '<div class="card error"><h3>💀 Error Fatal</h3><pre>' . htmlspecialchars(print_r($e, true)) . '</pre></div>';
    }
});

// ─── Helpers ────────────────────────────────────────────────────────────────
function ok(string $msg): string  { return "<span class='ok'>✅ $msg</span>"; }
function err(string $msg): string { return "<span class='err'>❌ $msg</span>"; }
function warn(string $msg): string { return "<span class='warn'>⚠️ $msg</span>"; }
function row(string $label, string $value): string {
    return "<tr><td class='label'>$label</td><td>$value</td></tr>";
}

// ─── Recopilar datos ANTES de cualquier output ───────────────────────────────
$results = [];

// 1. PHP & Servidor
$results['php_version']   = PHP_VERSION;
$results['server_soft']   = $_SERVER['SERVER_SOFTWARE'] ?? 'desconocido';
$results['script_name']   = $_SERVER['SCRIPT_NAME']     ?? '—';
$results['request_uri']   = $_SERVER['REQUEST_URI']     ?? '—';
$results['document_root'] = $_SERVER['DOCUMENT_ROOT']   ?? '—';
$results['php_self']      = $_SERVER['PHP_SELF']        ?? '—';
$results['script_file']   = __FILE__;
$results['script_dir']    = __DIR__;
$results['parent_dir']    = dirname(__DIR__);

// 2. Extensiones requeridas
$required_ext = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
$ext_status = [];
foreach ($required_ext as $ext) {
    $ext_status[$ext] = extension_loaded($ext);
}

// 3. Rutas críticas
$paths = [
    'public/index.php'     => __DIR__ . '/index.php',
    'config/app.php'       => dirname(__DIR__) . '/config/app.php',
    'config/database.php'  => dirname(__DIR__) . '/config/database.php',
    '.env'                 => dirname(__DIR__) . '/.env',
    'src/Core/Autoloader'  => dirname(__DIR__) . '/src/Core/Autoloader.php',
    'src/Core/Router'      => dirname(__DIR__) . '/src/Core/Router.php',
    'src/Core/Request'     => dirname(__DIR__) . '/src/Core/Request.php',
    'public/js/app.js'     => __DIR__ . '/js/app.js',
    'public/js/carrito.js' => __DIR__ . '/js/carrito.js',
    'public/js/checkout.js'=> __DIR__ . '/js/checkout.js',
];

// 4. Cargar app.php y variables de entorno
$app_load_error = null;
try {
    require_once dirname(__DIR__) . '/config/app.php';
} catch (Throwable $e) {
    $app_load_error = $e->getMessage();
}

// 5. Variables de entorno relevantes
$env_vars = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'APP_ENV', 'APP_URL', 'JWT_SECRET'];
$env_values = [];
foreach ($env_vars as $key) {
    $val = $_ENV[$key] ?? getenv($key) ?: null;
    if ($key === 'DB_PASS' || $key === 'JWT_SECRET') {
        $env_values[$key] = $val ? str_repeat('*', min(strlen($val), 8)) . ' (' . strlen($val) . ' chars)' : '— no encontrado —';
    } else {
        $env_values[$key] = $val ?: '— no encontrado —';
    }
}

// 6. Conexión a Base de Datos
$db_status  = false;
$db_error   = null;
$db_tables  = [];
$db_version = null;
try {
    require_once dirname(__DIR__) . '/config/database.php';
    $pdo = obtenerConexionPDO();
    $db_status  = true;
    $db_version = $pdo->query('SELECT VERSION()')->fetchColumn();

    // Listar tablas
    $stmt = $pdo->query("SHOW TABLES");
    $db_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Contar productos si existe la tabla
    $db_counts = [];
    $check_tables = ['productos', 'categorias', 'usuarios', 'pedidos', 'carrito_items'];
    foreach ($check_tables as $tbl) {
        if (in_array($tbl, $db_tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
            $db_counts[$tbl] = $count;
        }
    }
} catch (Throwable $e) {
    $db_error = $e->getMessage();
}

// 7. Autoloader
$autoload_error = null;
try {
    require_once dirname(__DIR__) . '/src/Core/Autoloader.php';
} catch (Throwable $e) {
    $autoload_error = $e->getMessage();
}

// 8. Simular Request y Router
$router_error = null;
$detected_uri = null;
try {
    if (!$autoload_error) {
        $req = new \App\Core\Request();
        $detected_uri = $req->getUri();
    }
} catch (Throwable $e) {
    $router_error = $e->getMessage();
}

// 9. Test API interna: llamar /api/catalogo directamente (sin HTTP)
$api_test = null;
$api_error = null;
try {
    if ($db_status && !$autoload_error) {
        $ctrl = new \App\Catalogo\CatalogoController();
        // Verificar que la clase existe
        $api_test = 'CatalogoController cargado correctamente';
    }
} catch (Throwable $e) {
    $api_error = $e->getMessage();
}

// 10. Detectar URI que usará el router
$raw_uri   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$known_pfx = ['/~mvaldebenito2025/public', '/~mvaldebenito2025'];
$clean_uri = $raw_uri;
foreach ($known_pfx as $pfx) {
    if (strpos($clean_uri, $pfx) === 0) {
        $clean_uri = substr($clean_uri, strlen($pfx));
        break;
    }
}
$clean_uri = '/' . ltrim($clean_uri, '/');

// ─── OUTPUT HTML ─────────────────────────────────────────────────────────────
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Diagnóstico — Ecommerce UCT</title>
    <style>
        :root {
            --ok:   #16a34a;
            --err:  #dc2626;
            --warn: #d97706;
            --bg:   #0f172a;
            --card: #1e293b;
            --border: #334155;
            --text: #e2e8f0;
            --muted:#94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif; padding: 2rem; }
        h1 { color: #38bdf8; font-size: 1.8rem; margin-bottom: 0.25rem; }
        .subtitle { color: var(--muted); font-size: 0.9rem; margin-bottom: 2rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(560px, 1fr)); gap: 1.5rem; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; }
        .card h2 { font-size: 1rem; color: #7dd3fc; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        td { padding: 0.4rem 0.5rem; border-bottom: 1px solid var(--border); vertical-align: top; }
        td:last-child { color: var(--muted); word-break: break-all; }
        .label { color: var(--text); font-weight: 600; white-space: nowrap; width: 200px; }
        .ok   { color: var(--ok);   font-weight: 600; }
        .err  { color: var(--err);  font-weight: 600; }
        .warn { color: var(--warn); font-weight: 600; }
        pre { background: #0f172a; border-radius: 8px; padding: 1rem; font-size: 0.78rem; overflow-x: auto; color: #fbbf24; margin-top: 0.5rem; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 0.75rem; font-weight: 700; }
        .badge-ok  { background: #14532d; color: #86efac; }
        .badge-err { background: #7f1d1d; color: #fca5a5; }
        .warn-box  { background: #451a03; border: 1px solid #92400e; border-radius: 8px; padding: 0.75rem 1rem; margin-top: 0.75rem; color: #fde68a; font-size: 0.85rem; }
        .tables-list { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.5rem; }
        .tag { background: #1e3a5f; color: #93c5fd; padding: 2px 10px; border-radius: 999px; font-size: 0.78rem; }
    </style>
</head>
<body>

<h1>🔍 Diagnóstico del Sistema</h1>
<p class="subtitle">
    Ecommerce UCT — Generado el <?= date('Y-m-d H:i:s') ?>
    &nbsp;|&nbsp; <strong>ELIMINAR</strong> este archivo del servidor tras resolver los problemas.
</p>

<div class="grid">

    <!-- ── 1. PHP & Servidor ── -->
    <div class="card">
        <h2>🖥️ PHP & Servidor</h2>
        <table>
            <?= row('PHP Version', PHP_VERSION . (version_compare(PHP_VERSION, '8.0') >= 0 ? ok(' OK') : err(' Requiere ≥ 8.0'))) ?>
            <?= row('SERVER_SOFTWARE', htmlspecialchars($results['server_soft'])) ?>
            <?= row('SCRIPT_NAME', htmlspecialchars($results['script_name'])) ?>
            <?= row('REQUEST_URI', htmlspecialchars($results['request_uri'])) ?>
            <?= row('DOCUMENT_ROOT', htmlspecialchars($results['document_root'])) ?>
            <?= row('__DIR__ (public)', htmlspecialchars($results['script_dir'])) ?>
            <?= row('dirname(__DIR__) (raíz)', htmlspecialchars($results['parent_dir'])) ?>
            <?= row('URI detectada por router', '<strong style="color:#38bdf8">' . htmlspecialchars($clean_uri) . '</strong>') ?>
        </table>
    </div>

    <!-- ── 2. Extensiones PHP ── -->
    <div class="card">
        <h2>🔌 Extensiones PHP Requeridas</h2>
        <table>
            <?php foreach ($ext_status as $ext => $loaded): ?>
                <?= row($ext, $loaded ? ok('Cargada') : err('NO cargada — crítico')) ?>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- ── 3. Archivos críticos ── -->
    <div class="card">
        <h2>📁 Archivos Críticos</h2>
        <table>
            <?php foreach ($paths as $label => $path): ?>
                <?= row($label, file_exists($path) ? ok('Existe') . ' <span style="color:var(--muted);font-size:0.75rem">(' . number_format(filesize($path)) . ' B)</span>' : err('NO encontrado')) ?>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- ── 4. Variables de entorno ── -->
    <div class="card">
        <h2>⚙️ Variables de Entorno (.env)</h2>
        <?php if ($app_load_error): ?>
            <div class="warn-box">❌ Error al cargar config/app.php: <?= htmlspecialchars($app_load_error) ?></div>
        <?php else: ?>
            <?= ok('config/app.php cargado correctamente') ?>
        <?php endif; ?>
        <table style="margin-top:0.75rem">
            <?php foreach ($env_values as $key => $val): ?>
                <?= row($key, htmlspecialchars($val)) ?>
            <?php endforeach; ?>
            <?= row('APP_URL (constante)', defined('APP_URL') ? htmlspecialchars(APP_URL) : err('No definida')) ?>
            <?= row('APP_ENV (constante)', defined('APP_ENV') ? htmlspecialchars(APP_ENV) : err('No definida')) ?>
        </table>
    </div>

    <!-- ── 5. Base de Datos ── -->
    <div class="card">
        <h2>🗄️ Conexión a Base de Datos</h2>
        <?php if ($db_status): ?>
            <?= ok('Conexión exitosa') ?><br>
            <table style="margin-top:0.75rem">
                <?= row('MySQL version', htmlspecialchars($db_version)) ?>
                <?= row('DB_HOST efectivo', htmlspecialchars($_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost')) ?>
                <?= row('DB_NAME efectivo', htmlspecialchars($_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '')) ?>
                <?= row('Tablas encontradas', count($db_tables)) ?>
            </table>
            <?php if (!empty($db_tables)): ?>
                <div class="tables-list">
                    <?php foreach ($db_tables as $t): ?>
                        <span class="tag"><?= htmlspecialchars($t) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($db_counts)): ?>
                <table style="margin-top:0.75rem">
                    <?php foreach ($db_counts as $tbl => $cnt): ?>
                        <?= row("Registros en $tbl", $cnt > 0 ? ok((string)$cnt . ' filas') : warn('0 filas — tabla vacía')) ?>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
            <?php if (!in_array('productos', $db_tables)): ?>
                <div class="warn-box">⚠️ La tabla <strong>productos</strong> no existe. ¿Se ejecutó el SQL de migración?</div>
            <?php endif; ?>
        <?php else: ?>
            <?= err('Error de conexión') ?>
            <pre><?= htmlspecialchars($db_error ?? 'Error desconocido') ?></pre>
        <?php endif; ?>
    </div>

    <!-- ── 6. Autoloader & Router ── -->
    <div class="card">
        <h2>🔄 Autoloader & Router</h2>
        <table>
            <?php if ($autoload_error): ?>
                <?= row('Autoloader', err('Error: ' . htmlspecialchars($autoload_error))) ?>
            <?php else: ?>
                <?= row('Autoloader', ok('OK')) ?>
            <?php endif; ?>
            <?= row('URI detectada (Request)', $detected_uri !== null ? ok(htmlspecialchars($detected_uri)) : err($router_error ?? 'No se pudo instanciar Request')) ?>
            <?= row('Clase CatalogoController', $api_test ? ok($api_test) : err(htmlspecialchars($api_error ?? 'No cargó'))) ?>
        </table>
        <?php if ($router_error): ?>
            <pre><?= htmlspecialchars($router_error) ?></pre>
        <?php endif; ?>
        <?php if ($api_error): ?>
            <pre><?= htmlspecialchars($api_error) ?></pre>
        <?php endif; ?>
    </div>

    <!-- ── 7. Test de endpoints via HTTP ── -->
    <div class="card" style="grid-column: 1 / -1">
        <h2>🌐 Test de Endpoints de la API (HTTP interno)</h2>
        <?php
        $base = rtrim(APP_URL ?? 'https://teclab.uct.cl/~mvaldebenito2025/public', '/');
        $endpoints = [
            '/api/catalogo'            => 'GET',
            '/api/catalogo/categorias' => 'GET',
            '/api/carrito'             => 'GET',
            '/api/health'              => 'GET',
        ];
        ?>
        <table>
            <tr><th style="text-align:left;color:#7dd3fc;padding:0.4rem 0.5rem">Endpoint</th>
                <th style="text-align:left;color:#7dd3fc;padding:0.4rem 0.5rem">Estado HTTP</th>
                <th style="text-align:left;color:#7dd3fc;padding:0.4rem 0.5rem">Content-Type</th>
                <th style="text-align:left;color:#7dd3fc;padding:0.4rem 0.5rem">Respuesta (primeros 200 chars)</th></tr>
        <?php
        foreach ($endpoints as $endpoint => $method):
            $url = $base . $endpoint;
            $ctx = stream_context_create(['http' => [
                'method'          => $method,
                'timeout'         => 8,
                'ignore_errors'   => true,
                'follow_location' => true,
            ]]);
            $body = @file_get_contents($url, false, $ctx);
            $headers = $http_response_header ?? [];

            // Extraer status code
            $statusLine = $headers[0] ?? 'Error de conexión';
            preg_match('/HTTP\/\S+\s+(\d+)/', $statusLine, $m);
            $code = $m[1] ?? '—';

            // Extraer content-type
            $ct = '—';
            foreach ($headers as $h) {
                if (stripos($h, 'content-type:') === 0) {
                    $ct = trim(substr($h, 13));
                }
            }

            $isOk   = $code >= 200 && $code < 300;
            $isJson = str_contains($ct, 'application/json');
            $preview = htmlspecialchars(substr($body ?: '(sin respuesta)', 0, 200));

            // Parsear JSON para ver si success=true
            $decoded = $body ? @json_decode($body, true) : null;
            $jsonOk = $decoded && isset($decoded['success']) && $decoded['success'];
        ?>
            <tr>
                <td class="label"><code><?= htmlspecialchars($endpoint) ?></code></td>
                <td><?= $isOk ? ok($code) : err($code) ?></td>
                <td><?= $isJson ? ok('JSON') : err(htmlspecialchars($ct)) ?></td>
                <td style="font-size:0.75rem;color:<?= $jsonOk ? 'var(--ok)' : 'var(--err)' ?>">
                    <?= $preview ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </table>
    </div>

    <!-- ── 8. $_SERVER completo ── -->
    <div class="card" style="grid-column: 1 / -1">
        <h2>📋 Variables $_SERVER relevantes</h2>
        <table>
            <?php
            $server_keys = ['HTTP_HOST','HTTPS','SERVER_NAME','SERVER_PORT','REQUEST_METHOD',
                            'SCRIPT_FILENAME','PATH_TRANSLATED','REDIRECT_URL','REDIRECT_STATUS',
                            'PHP_SELF','QUERY_STRING','HTTP_ACCEPT','HTTP_REFERER'];
            foreach ($server_keys as $k):
                if (isset($_SERVER[$k])):
            ?>
                <?= row($k, htmlspecialchars($_SERVER[$k])) ?>
            <?php
                endif;
            endforeach; ?>
        </table>
    </div>

</div><!-- /grid -->

<p style="margin-top:2rem;color:var(--muted);font-size:0.8rem;text-align:center">
    ⚠️ <strong>ELIMINA</strong> este archivo (<code>diagnostic.php</code>) del servidor cuando ya no lo necesites.
</p>

</body>
</html>
