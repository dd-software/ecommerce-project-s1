<?php

declare(strict_types=1);

/**
 * IntegracionRepository - Acceso a datos de notificaciones y exportaciones
 */
namespace App\Integracion;

use App\Core\Database;
use PDO;

class IntegracionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Encola una notificación para envío
     * @return int ID de la notificación
     */
    public function encolarNotificacion(string $destinatario, string $asunto, string $cuerpo, string $tipo): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notificaciones (destinatario, asunto, cuerpo, tipo, estado)
             VALUES (:dest, :asunto, :cuerpo, :tipo, 'pendiente')"
        );
        $stmt->execute([
            ':dest'   => $destinatario,
            ':asunto' => $asunto,
            ':cuerpo' => $cuerpo,
            ':tipo'   => $tipo,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Marca una notificación como enviada
     */
    public function marcarEnviada(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE notificaciones SET estado = 'enviado', enviado_en = NOW() WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    /**
     * Marca una notificación como fallida
     */
    public function marcarFallida(int $id, string $error): void
    {
        $stmt = $this->db->prepare(
            "UPDATE notificaciones
             SET estado = 'fallido', reintentos = reintentos + 1, error = :error
             WHERE id = :id"
        );
        $stmt->execute([':error' => $error, ':id' => $id]);
    }

    /**
     * Lista notificaciones en cola
     */
    public function listarCola(): array
    {
        return $this->db->query(
            "SELECT * FROM notificaciones
             WHERE estado = 'pendiente'
             ORDER BY created_at ASC LIMIT 50"
        )->fetchAll();
    }

    /**
     * Obtiene pedidos para exportación JSON
     */
    public function obtenerPedidosParaExportar(?string $desde, ?string $hasta): array
    {
        $where = [];
        $params = [];

        if ($desde) {
            $where[] = "p.created_at >= :desde";
            $params[':desde'] = $desde;
        }
        if ($hasta) {
            $where[] = "p.created_at <= :hasta";
            $params[':hasta'] = $hasta . ' 23:59:59';
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare(
            "SELECT p.id, p.estado, p.subtotal, p.iva, p.total, p.direccion_envio,
                    p.created_at, u.nombre as cliente_nombre, u.apellido, u.email,
                    COUNT(dp.id) as total_items
             FROM pedidos p
             INNER JOIN usuarios u ON p.id_usuario = u.id
             LEFT JOIN detalle_pedido dp ON p.id = dp.id_pedido
             {$whereSQL}
             GROUP BY p.id
             ORDER BY p.created_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene todos los productos para exportación
     */
    public function obtenerProductosParaExportar(): array
    {
        return $this->db->query(
            "SELECT p.*, c.nombre as categoria_nombre
             FROM productos p
             LEFT JOIN categorias c ON p.id_categoria = c.id
             WHERE p.deleted_at IS NULL
             ORDER BY p.id"
        )->fetchAll();
    }

    /**
     * Obtiene reporte de ventas para exportación
     */
    public function obtenerReporteVentasExportar(): array
    {
        return $this->db->query(
            "SELECT DATE(p.created_at) as fecha,
                    COUNT(*) as total_pedidos,
                    SUM(p.total) as total_ventas,
                    SUM(CASE WHEN p.estado = 'pagado' THEN 1 ELSE 0 END) as pagados,
                    SUM(CASE WHEN p.estado = 'entregado' THEN 1 ELSE 0 END) as entregados,
                    SUM(CASE WHEN p.estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados
             FROM pedidos p
             GROUP BY DATE(p.created_at)
             ORDER BY fecha DESC"
        )->fetchAll();
    }
}
