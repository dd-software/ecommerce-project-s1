<?php

declare(strict_types=1);

/**
 * ResenasRepository - Acceso a datos de reseñas de productos
 */
namespace App\Resenas;

use App\Core\Database;
use PDO;

class ResenasRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Reseñas aprobadas de un producto, con nombre del autor */
    public function listarPorProducto(int $productoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.id, r.calificacion, r.comentario, r.created_at,
                    u.nombre AS autor
             FROM resenas r
             JOIN usuarios u ON u.id = r.id_usuario
             WHERE r.id_producto = :pid AND r.aprobada = 1
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([':pid' => $productoId]);
        return $stmt->fetchAll();
    }

    /** Promedio y total de reseñas aprobadas */
    public function resumen(int $productoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ROUND(AVG(calificacion), 1) AS promedio, COUNT(*) AS total
             FROM resenas WHERE id_producto = :pid AND aprobada = 1"
        );
        $stmt->execute([':pid' => $productoId]);
        $row = $stmt->fetch();
        return [
            'promedio' => $row && $row['total'] > 0 ? (float)$row['promedio'] : 0,
            'total'    => (int)($row['total'] ?? 0),
        ];
    }

    /** El producto existe y está activo */
    public function productoExiste(int $productoId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM productos WHERE id = :pid AND activo = 1 AND deleted_at IS NULL");
        $stmt->execute([':pid' => $productoId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Crea o actualiza la reseña del usuario para ese producto (unique por par).
     * ponytail: aprobada=1 (auto-aprobada). Agregar moderación admin si se necesita.
     */
    public function guardar(int $productoId, int $userId, int $calificacion, ?string $comentario): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO resenas (id_producto, id_usuario, calificacion, comentario, aprobada)
             VALUES (:pid, :uid, :cal, :com, 1)
             ON DUPLICATE KEY UPDATE calificacion = VALUES(calificacion),
                                     comentario = VALUES(comentario), aprobada = 1"
        );
        $stmt->execute([
            ':pid' => $productoId,
            ':uid' => $userId,
            ':cal' => $calificacion,
            ':com' => $comentario,
        ]);
    }
}
