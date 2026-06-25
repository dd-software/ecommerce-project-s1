<?php

declare(strict_types=1);

/**
 * FavoritosRepository - Acceso a datos de la lista de deseos
 */
namespace App\Favoritos;

use App\Core\Database;
use PDO;

class FavoritosRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Productos favoritos del usuario, con el mismo formato que las cards del catálogo */
    public function listar(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.nombre, p.slug, p.precio, p.stock, p.marca,
                    p.imagen_url, p.id_categoria, c.nombre AS categoria_nombre
             FROM lista_deseos d
             JOIN productos p ON p.id = d.id_producto
             LEFT JOIN categorias c ON c.id = p.id_categoria
             WHERE d.id_usuario = :uid AND p.activo = 1 AND p.deleted_at IS NULL
             ORDER BY d.created_at DESC"
        );
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$p) {
            $p['precio'] = (int)$p['precio'];
            $p['stock'] = (int)$p['stock'];
            $p['sin_stock'] = $p['stock'] <= 0;
            $p['precio_formateado'] = '$' . number_format($p['precio'], 0, ',', '.');
        }
        return $rows;
    }

    /** IDs de productos en favoritos (para marcar corazones en el catálogo) */
    public function ids(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT id_producto FROM lista_deseos WHERE id_usuario = :uid");
        $stmt->execute([':uid' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Agrega (idempotente por unique key) */
    public function agregar(int $userId, int $productoId): void
    {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO lista_deseos (id_usuario, id_producto) VALUES (:uid, :pid)"
        );
        $stmt->execute([':uid' => $userId, ':pid' => $productoId]);
    }

    public function eliminar(int $userId, int $productoId): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM lista_deseos WHERE id_usuario = :uid AND id_producto = :pid"
        );
        $stmt->execute([':uid' => $userId, ':pid' => $productoId]);
    }
}
