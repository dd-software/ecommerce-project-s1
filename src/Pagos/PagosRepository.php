<?php

declare(strict_types=1);

/**
 * PagosRepository - Acceso a datos de pagos y transacciones
 */
namespace App\Pagos;

use App\Core\Database;
use PDO;

class PagosRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Registra un pago (intento de transacción)
     * @return int ID del pago registrado
     */
    public function registrarPago(
        int $pedidoId,
        string $metodoPago,
        int $monto,
        string $referenciaExterna,
        string $estado,
        string $respuesta
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO pagos (id_pedido, metodo_pago, monto, referencia_externa, estado, respuesta_pasarela, fecha_pago)
             VALUES (:pedido, :metodo, :monto, :ref, :estado, :respuesta, NOW())"
        );
        $stmt->execute([
            ':pedido'    => $pedidoId,
            ':metodo'    => $metodoPago,
            ':monto'     => $monto,
            ':ref'       => $referenciaExterna,
            ':estado'    => $estado,
            ':respuesta' => $respuesta,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Obtiene el último pago de un pedido
     */
    public function obtenerPagoPorPedido(int $pedidoId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, id_pedido, metodo_pago, monto, estado, referencia_externa, fecha_pago, created_at
             FROM pagos
             WHERE id_pedido = :pedido_id
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([':pedido_id' => $pedidoId]);
        $pago = $stmt->fetch();

        if ($pago) {
            $pago['monto_formateado'] = '$' . number_format($pago['monto'] , 0, ',', '.');
            $pago['monto'] = (int)$pago['monto'];
        }

        return $pago ?: null;
    }

    /**
     * Actualiza el estado de un pago
     */
    public function actualizarEstadoPago(int $pagoId, string $estado, string $referenciaExterna): void
    {
        $stmt = $this->db->prepare(
            "UPDATE pagos SET estado = :estado, referencia_externa = :ref, fecha_pago = IF(:estado2 IN ('aprobado','reembolsado'), NOW(), fecha_pago)
             WHERE id = :id"
        );
        $stmt->execute([
            ':estado'  => $estado,
            ':ref'     => $referenciaExterna,
            ':estado2' => $estado,
            ':id'      => $pagoId,
        ]);
    }

    /**
     * Obtiene todos los pagos (admin)
     */
    public function obtenerTodos(int $pagina = 1, int $porPagina = 20): array
    {
        $offset = ($pagina - 1) * $porPagina;

        $countStmt = $this->db->query("SELECT COUNT(*) as total FROM pagos");
        $total = (int)$countStmt->fetch()['total'];

        $stmt = $this->db->prepare(
            "SELECT pg.*, p.estado as estado_pedido, u.nombre as cliente_nombre
             FROM pagos pg
             INNER JOIN pedidos p ON pg.id_pedido = p.id
             INNER JOIN usuarios u ON p.id_usuario = u.id
             ORDER BY pg.created_at DESC
             LIMIT :limite OFFSET :offset"
        );
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'pagos' => $stmt->fetchAll(),
            'total' => $total,
        ];
    }
}
