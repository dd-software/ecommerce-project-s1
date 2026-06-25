<?php
declare(strict_types=1);

namespace Src\Models;

use Config\Database;
use PDO;

/**
 * Class User
 * Modelo para la entidad de usuarios que interactúa con la base de datos MySQL.
 */
class User {
    /**
     * @var PDO Instancia de la conexión a DB.
     */
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Busca un usuario por su correo electrónico. No retorna usuarios con borrado lógico.
     *
     * @param string $email El correo a buscar.
     * @return array|null Arreglo asociativo con los datos del usuario o null.
     */
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.email = :email AND u.deleted_at IS NULL
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Registra un nuevo usuario en la base de datos con rol 'customer' (ID=2 por defecto de seed).
     *
     * @param array $data Los datos validados del registro.
     * @return bool True si fue exitoso, lanza excepción en caso contrario.
     */
    public function create(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, password_hash) 
            VALUES (:role_id, :first_name, :last_name, :email, :password_hash)
        ");
        return $stmt->execute([
            'role_id' => 2,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT)
        ]);
    }
}
