<?php

declare(strict_types=1);

/**
 * InventarioRepository - Acceso a datos de stock y movimientos
 */
namespace App\Inventario;

use App\Core\Database;
use PDO;

class InventarioRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene el stock actual de un producto
     */
    public function obtenerStock(int $productoId): int
    {
        $stmt = $this->db->prepare(
            "SELECT stock FROM productos WHERE id = :id AND deleted_at IS NULL"
        );
        $stmt->execute([':id' => $productoId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['stock'] : 0;
    }

    /**
     * Actualiza el stock de un producto (valor absoluto)
     */
    public function actualizarStock(int $productoId, int $nuevoStock): void
    {
        $stmt = $this->db->prepare(
            "UPDATE productos SET stock = :stock, updated_at = NOW() WHERE id = :id"
        );
        $stmt->execute([':stock' => $nuevoStock, ':id' => $productoId]);
    }

    /**
     * Registra un movimiento de inventario
     */
    public function registrarMovimiento(
        int $productoId,
        int $cantidad,
        string $tipo,
        string $motivo,
        ?int $pedidoId,
        int $stockAnterior,
        int $stockNuevo,
        ?int $userId = null
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO movimientos_inventario
             (id_producto, id_pedido, cantidad, tipo, motivo, stock_anterior, stock_nuevo, id_usuario)
             VALUES (:producto, :pedido, :cantidad, :tipo, :motivo, :anterior, :nuevo, :usuario)"
        );
        $stmt->execute([
            ':producto'  => $productoId,
            ':pedido'    => $pedidoId,
            ':cantidad'  => $cantidad,
            ':tipo'      => $tipo,
            ':motivo'    => $motivo,
            ':anterior'  => $stockAnterior,
            ':nuevo'     => $stockNuevo,
            ':usuario'   => $userId,
        ]);
    }

    /**
     * Lista movimientos de inventario
     */
    public function listarMovimientos(?int $productoId, int $pagina, int $porPagina): array
    {
        $where = [];
        $params = [];

        if ($productoId !== null) {
            $where[] = "mi.id_producto = :producto_id";
            $params[':producto_id'] = $productoId;
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM movimientos_inventario mi {$whereSQL}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];

        $offset = ($pagina - 1) * $porPagina;

        $sql = "SELECT mi.*, p.nombre as producto_nombre, p.slug as producto_slug,
                       u.nombre as usuario_nombre
                FROM movimientos_inventario mi
                INNER JOIN productos p ON mi.id_producto = p.id
                LEFT JOIN usuarios u ON mi.id_usuario = u.id
                {$whereSQL}
                ORDER BY mi.created_at DESC
                LIMIT {$porPagina} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'movimientos' => $stmt->fetchAll(),
            'total'       => $total,
        ];
    }

    /**
     * Obtiene productos con stock bajo el mínimo
     */
    public function obtenerAlertasStock(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre, slug, stock, stock_minimo, imagen_url, id_categoria
             FROM productos
             WHERE activo = 1 AND deleted_at IS NULL AND stock <= stock_minimo
             ORDER BY (stock_minimo - stock) DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
