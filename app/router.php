<?php
/**
 * Router para PHP built-in server.
 * Uso: php -S localhost:8000 router.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Rutas de API → api/index.php
if (str_starts_with($uri, '/api')) {
    require __DIR__ . '/api/index.php';
    return true;
}

// Archivos estáticos (css, js, imágenes) → servir directamente
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Páginas HTML sin extensión → intentar añadir .html
$htmlPath = __DIR__ . $uri . '.html';
if ($uri !== '/' && file_exists($htmlPath)) {
    include $htmlPath;
    return true;
}

// Página de inicio
if ($uri === '/') {
    include __DIR__ . '/index.html';
    return true;
}

// 404 para rutas desconocidas no-API
http_response_code(404);
echo "404 — Página no encontrada.";
return true;
