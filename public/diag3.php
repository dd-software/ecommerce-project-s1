<?php
// diag3.php - Diagnóstico de routing
header('Content-Type: application/json; charset=utf-8');

$results = [];

// Simulate exactly what index.php does to compute the URI
$fullUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$results['raw_REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? 'NOT SET';
$results['raw_SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? 'NOT SET';
$results['parsed_path'] = $fullUri;

$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$results['scriptDir_initial'] = $scriptDir;

$iter = 0;
while ($scriptDir !== '' && $scriptDir !== '/') {
    $iter++;
    if (strpos($fullUri, $scriptDir) === 0) {
        $fullUri = substr($fullUri, strlen($scriptDir));
        $results['stripped_by'] = $scriptDir;
        break;
    }
    $lastSlash = strrpos($scriptDir, '/');
    if ($lastSlash === false) break;
    $scriptDir = substr($scriptDir, 0, $lastSlash);
    if ($iter > 10) break;
}

$resolvedUri = '/' . ltrim($fullUri, '/');
$results['resolved_uri'] = $resolvedUri;

// Now check what routes would match
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Core/Autoloader.php';

$request = new \App\Core\Request();
$results['request_uri_via_class'] = $request->getUri();
$results['request_method'] = $request->getMethod();

// Try to manually dispatch /api/catalogo/categorias
$testUri = '/api/catalogo/categorias';
$pattern = '#^/api/catalogo/categorias$#';
$results['pattern_match_test'] = preg_match($pattern, $testUri) ? 'MATCH' : 'NO MATCH';
$results['pattern_match_actual'] = preg_match($pattern, $request->getUri()) ? 'MATCH' : 'NO MATCH';

// Show all server variables that matter
$results['SERVER_SOFTWARE'] = $_SERVER['SERVER_SOFTWARE'] ?? 'N/A';
$results['SCRIPT_FILENAME'] = $_SERVER['SCRIPT_FILENAME'] ?? 'N/A';
$results['PHP_SELF'] = $_SERVER['PHP_SELF'] ?? 'N/A';

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
