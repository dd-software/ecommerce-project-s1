<?php
/**
 * Verificador de requisitos del proyecto Ecommerce UCT
 * 
 * Úsalo para diagnosticar por qué no funciona:
 * Colocar en raíz del proyecto y acceder vía navegador:
 * http://localhost/ecommerce-project-s1/setup.php
 */

$ok = true;
$checks = [];

// PHP version
$checks[] = [
    'name' => 'PHP >= 8.0',
    'pass' => version_compare(PHP_VERSION, '8.0', '>='),
    'info' => PHP_VERSION
];

// .env
$env = file_exists('.env');
$checks[] = [
    'name' => 'Archivo .env existe',
    'pass' => $env,
    'info' => $env ? '✓' : '❌ Copia .env.example a .env'
];

// Modules
$modules = ['Auth', 'Catalogo', 'Carrito', 'Checkout', 'Pagos', 'Inventario', 'Admin', 'Integracion'];
foreach ($modules as $mod) {
    $controller = "src/$mod/{$mod}Controller.php";
    $exists = file_exists($controller);
    $checks[] = [
        'name' => "Módulo $mod",
        'pass' => $exists,
        'info' => $exists ? '✓' : "❌ Falta $controller"
    ];
    if (!$exists) $ok = false;
}

// Database
try {
    require_once __DIR__ . '/config/app.php';
    require_once __DIR__ . '/src/Core/Autoloader.php';
    $db = \App\Core\Database::getInstance();
    $db->getConnection()->query('SELECT 1');
    $checks[] = ['name' => 'Conexión BD', 'pass' => true, 'info' => '✓'];
} catch (\Exception $e) {
    $checks[] = ['name' => 'Conexión BD', 'pass' => false, 'info' => '❌ ' . $e->getMessage()];
    $ok = false;
}

// Mod rewrite
$checks[] = [
    'name' => 'mod_rewrite (Apache)',
    'pass' => function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules()),
    'info' => function_exists('apache_get_modules') ? '✓' : '❓ Usando PHP built-in server?'
];

?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Setup Check - Ecommerce UCT</title>
<style>body{font-family:sans-serif;max-width:700px;margin:2em auto;padding:0 1em}
h1{color:#2563eb}.pass{color:#16a34a}.fail{color:#dc2626}
table{width:100%;border-collapse:collapse;margin:1em 0}
td,th{border:1px solid #ddd;padding:8px;text-align:left}
th{background:#f1f5f9}.summary{font-weight:bold;font-size:1.2em}
</style></head>
<body>
<h1>🔍 Verificación del Proyecto</h1>
<?php if ($ok): ?>
<p class="pass summary">✅ Todo listo. El proyecto debería funcionar.</p>
<?php else: ?>
<p class="fail summary">❌ Hay problemas que corregir</p>
<?php endif; ?>
<table><tr><th>Requisito</th><th>Estado</th><th>Detalle</th></tr>
<?php foreach ($checks as $c): ?>
<tr><td><?= $c['name'] ?></td>
<td class="<?= $c['pass'] ? 'pass' : 'fail' ?>"><?= $c['pass'] ? '✅' : '❌' ?></td>
<td><?= $c['info'] ?></td></tr>
<?php endforeach; ?>
</table>
<h3>📖 Cómo desplegar</h3>
<ol>
<li><code>cp .env.example .env</code> y editar credenciales</li>
<li><code>mysql -u root -p &lt; database/setup.sql</code></li>
<li><code>php -S localhost:8000 -t public/</code></li>
<li>Visitar <a href="http://localhost:8000">http://localhost:8000</a></li>
</ol>
<p>O con Apache: apuntar DocumentRoot a <code>public/</code></p>
</body></html>
