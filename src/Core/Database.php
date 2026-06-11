<?php

declare(strict_types=1);

/**
 * Clase Database - Singleton de conexión PDO
 * Proporciona acceso centralizado a la base de datos
 */
namespace App\Core;

use PDO;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $dbname = $_ENV['DB_NAME'] ?? 'uct_ecommerce';
        $user = $_ENV['DB_USER'] ?? 'ecommerce_app';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
    }

    /**
     * Obtiene la instancia única de Database (Singleton)
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtiene la conexión PDO
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Inicia una transacción
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Confirma una transacción
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Revierte una transacción
     */
    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Obtiene el último ID insertado
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Previene clonación del Singleton
     */
    private function __clone() {}

    /**
     * Previene deserialización
     */
    public function __wakeup()
    {
        throw new \Exception("No se puede deserializar un Singleton");
    }
}
