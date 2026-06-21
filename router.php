<?php

/**
 * Router para el servidor embebido de PHP (php -S).
 *
 * El servidor embebido NO lee .htaccess, así que replicamos aquí su regla:
 *   - Si la URL corresponde a un archivo real (css, js, imágenes) -> servirlo tal cual.
 *   - La raíz "/" -> index.html (la SPA).
 *   - Cualquier otra cosa (incluyendo /api/*) -> front controller public/index.php.
 *
 * Uso:
 *   php -S localhost:8000 -t public router.php
 *
 * En producción (Apache) esto lo hace el .htaccess; este archivo es solo para desarrollo.
 */

$root = $_SERVER['DOCUMENT_ROOT'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Archivos estáticos existentes: dejar que el servidor embebido los sirva.
if ($path !== '/' && is_file($root . $path)) {
    return false;
}

// Raíz -> SPA. (No usamos "return false" porque el servidor embebido prioriza
// index.php sobre index.html como DirectoryIndex y nos enrutaría al API.)
if ($path === '/' && is_file($root . '/index.html')) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($root . '/index.html');
    return true;
}

// Todo lo demás -> front controller.
require $root . '/index.php';
