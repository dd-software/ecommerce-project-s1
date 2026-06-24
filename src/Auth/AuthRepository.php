<?php

declare(strict_types=1);

/**
 * AuthRepository - Acceso a datos de usuarios
 * Todas las consultas usan PDO prepared statements
 */
namespace App\Auth;

use App\Core\Database;
use PDO;

class AuthRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Busca un usuario por su email
     */
    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre, apellido, email, password_hash, rol, activo, bloqueado_hasta, intentos_fallidos
             FROM usuarios
             WHERE email = :email AND deleted_at IS NULL"
        );
        $stmt->execute([':email' => strtolower(trim($email))]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Busca un usuario por su ID
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre, apellido, email, rol, activo, created_at, ultimo_login,
                    telefono, direccion, comuna, region, codigo_postal
             FROM usuarios
             WHERE id = :id AND deleted_at IS NULL"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Verifica si un email ya existe
     */
    public function existeEmail(string $email): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as total FROM usuarios WHERE email = :email AND deleted_at IS NULL"
        );
        $stmt->execute([':email' => strtolower(trim($email))]);
        return (int)$stmt->fetch()['total'] > 0;
    }

    /**
     * Crea un nuevo usuario
     * @return int ID del usuario creado
     */
    public function crear(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, activo)
             VALUES (:nombre, :apellido, :email, :password_hash, :rol, :activo)"
        );
        $stmt->execute([
            ':nombre'        => $data['nombre'],
            ':apellido'      => $data['apellido'],
            ':email'         => $data['email'],
            ':password_hash' => $data['password_hash'],
            ':rol'           => $data['rol'],
            ':activo'        => $data['activo'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza datos del usuario
     */
    public function actualizar(int $id, array $data): void
    {
        $sets = [];
        $params = [':id' => $id];

        foreach ($data as $campo => $valor) {
            $sets[] = "{$campo} = :{$campo}";
            $params[":{$campo}"] = $valor;
        }

        if (empty($sets)) {
            return;
        }

        $sql = "UPDATE usuarios SET " . implode(', ', $sets) . " WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Incrementa contador de intentos fallidos y bloquea si excede 5
     */
    public function incrementarIntentos(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE usuarios
             SET intentos_fallidos = intentos_fallidos + 1,
                 bloqueado_hasta = IF(intentos_fallidos + 1 >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NULL)
             WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    /**
     * Resetea el contador de intentos fallidos
     */
    public function resetearIntentos(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    /**
     * Actualiza la fecha de último login
     */
    public function actualizarUltimoLogin(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    /**
     * Guarda el hash del token de reset y su expiración.
     */
    public function guardarTokenReset(int $id, string $tokenHash, string $expira): void
    {
        $stmt = $this->db->prepare(
            "UPDATE usuarios SET reset_token_hash = :hash, reset_expira = :exp
             WHERE id = :id AND deleted_at IS NULL"
        );
        $stmt->execute([':hash' => $tokenHash, ':exp' => $expira, ':id' => $id]);
    }

    /**
     * Busca un usuario por el hash del token de reset, solo si no expiró.
     */
    public function buscarPorTokenReset(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM usuarios
             WHERE reset_token_hash = :hash AND reset_expira > NOW() AND deleted_at IS NULL"
        );
        $stmt->execute([':hash' => $tokenHash]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Fija una nueva contraseña, invalida el token y desbloquea la cuenta.
     */
    public function actualizarPassword(int $id, string $passwordHash): void
    {
        $stmt = $this->db->prepare(
            "UPDATE usuarios
             SET password_hash = :hash, reset_token_hash = NULL, reset_expira = NULL,
                 intentos_fallidos = 0, bloqueado_hasta = NULL
             WHERE id = :id AND deleted_at IS NULL"
        );
        $stmt->execute([':hash' => $passwordHash, ':id' => $id]);
    }
}
