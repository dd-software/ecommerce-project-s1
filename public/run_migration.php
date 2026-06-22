<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Core/Database.php';

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    $sql = file_get_contents(__DIR__ . '/../database/update_pedidos_paypal.sql');
    $db->exec($sql);
    http_response_code(200);
    echo "SUCCESS";
} catch (\Exception $e) {
    http_response_code(200);
    echo "ERROR: " . $e->getMessage();
}
