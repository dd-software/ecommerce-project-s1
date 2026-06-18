<?php
// diag4.php - Reads the route debug log written by index.php
header('Content-Type: application/json; charset=utf-8');

$logFile = sys_get_temp_dir() . '/uct_route_debug.json';

echo json_encode([
    'sys_temp_dir' => sys_get_temp_dir(),
    'log_file' => $logFile,
    'log_exists' => file_exists($logFile),
    'log_readable' => is_readable($logFile),
    'log_content' => file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : null,
    'log_age_seconds' => file_exists($logFile) ? (time() - filemtime($logFile)) : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
