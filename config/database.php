<?php

declare(strict_types=1);

namespace Config;

use PDO;
use PDOException;

/**
 * Class Database
 * Maneja la conexión a la base de datos MySQL usando PDO.
 * Aplica el patrón Singleton para asegurar una única instancia de conexión.
 */
class Database
{
    private static ?PDO $connection = null;

    /**
     * Obtiene la instancia de la conexión a la base de datos.
     *
     * @return PDO
     * @throws PDOException Si hay un error de conexión.
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '3306';
            $dbName = getenv('DB_NAME') ?: 'uct_ecommerce';
            $username = getenv('DB_USER') ?: 'root';
            $password = getenv('DB_PASS') ?: '';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$connection = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // Lanza excepción detallada; en fases posteriores se capturará para JSON response.
                throw new PDOException("Database connection error: " . $e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$connection;
    }
}
