<?php

declare(strict_types=1);

/**
 * Configuración de conexión a base de datos mediante PDO
 */

// Cargar configuración si no se ha cargado
if (!defined('APP_ENV')) {
    require_once __DIR__ . '/app.php';
}

function obtenerConexionPDO(): PDO
{
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $dbname = $_ENV['DB_NAME'] ?? 'uct_ecommerce';
    $user = $_ENV['DB_USER'] ?? 'ecommerce_app';
    $pass = $_ENV['DB_PASS'] ?? '';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    return new PDO($dsn, $user, $pass, $opciones);
}
