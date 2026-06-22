<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Configurar CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
if (APP_ENV === 'development') {
    header("Access-Control-Allow-Origin: *");
} else {
    header("Access-Control-Allow-Origin: " . APP_URL);
}

// Devolver el client ID sin el secret
echo json_encode([
    'success' => true,
    'client_id' => defined('PAYPAL_CLIENT_ID') ? PAYPAL_CLIENT_ID : 'test'
]);
