<?php
header('Content-Type: application/json');
echo json_encode([
    'env' => $_ENV,
    'server' => $_SERVER,
    'paypal_client_id' => getenv('PAYPAL_CLIENT_ID'),
    'paypal_secret' => getenv('PAYPAL_SECRET'),
    'paypal_mode' => getenv('PAYPAL_MODE')
], JSON_PRETTY_PRINT);
