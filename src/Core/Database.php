<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use Exception;

/**
 * Clase Database - Singleton para la gestión de la conexión PDO.
 * Proporciona un punto de acceso único a la base de datos MySQL.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    /**
     * Constructor privado para prevenir instanciación externa.
     * Configura la conexión PDO usando variables de entorno.
     */
    private function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $dbname = $_ENV['DB_NAME'] ?? 'uct_ecommerce';
        $user = $_ENV['DB_USER'] ?? 'ecommerce_app';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        } catch (PDOException $e) {
            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    /**
     * Obtiene la instancia única de la clase (Singleton).
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna la conexión PDO activa.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Inicia una transacción de base de datos.
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Confirma la transacción actual.
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Revierte la transacción actual.
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Obtiene el último ID insertado.
     */
    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Evita la clonación de la instancia.
     */
    private function __clone() {}

    /**
     * Evita la deserialización de la instancia.
     */
    public function __wakeup()
    {
        throw new Exception("No se puede deserializar un Singleton.");
    }
}
