<?php

declare(strict_types=1);

/**
 * CheckoutRepository - Acceso a datos de pedidos y checkout
 */
namespace App\Checkout;

use App\Core\Database;
use PDO;

class CheckoutRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crea un nuevo pedido
     * @return int ID del pedido creado
     */
    public function crearPedido(
        int $userId,
        int $subtotal,
        int $iva,
        int $total,
        string $direccionEnvio,
        string $telefono,
        string $notas
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO pedidos (id_usuario, subtotal, iva, total, estado, direccion_envio, telefono_contacto, notas)
             VALUES (:uid, :subtotal, :iva, :total, 'pendiente', :direccion, :telefono, :notas)"
        );
        $stmt->execute([
            ':uid'        => $userId,
            ':subtotal'   => $subtotal,
            ':iva'        => $iva,
            ':total'      => $total,
            ':direccion'  => $direccionEnvio,
            ':telefono'   => $telefono,
            ':notas'      => $notas,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Registra un cambio de estado en el historial
     */
    public function registrarEstado(int $pedidoId, string $estado, ?int $userId, string $comentario = ''): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO estados_pedido (id_pedido, estado, usuario_responsable, comentario)
             VALUES (:pedido_id, :estado, :usuario, :comentario)"
        );
        $stmt->execute([
            ':pedido_id' => $pedidoId,
            ':estado'    => $estado,
            ':usuario'   => $userId,
            ':comentario' => $comentario,
        ]);
    }

    /**
     * Agrega un item al detalle del pedido (snapshot inmutable)
     */
    public function agregarDetalle(
        int $pedidoId,
        int $productoId,
        string $nombreProducto,
        int $cantidad,
        int $precioUnitario
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO detalle_pedido (id_pedido, id_producto, nombre_producto, cantidad, precio_unitario)
             VALUES (:pedido, :producto, :nombre, :cantidad, :precio)"
        );
        $stmt->execute([
            ':pedido'   => $pedidoId,
            ':producto' => $productoId,
            ':nombre'   => $nombreProducto,
            ':cantidad' => $cantidad,
            ':precio'   => $precioUnitario,
        ]);
    }

    /**
     * Obtiene un pedido completo con detalle e historial de estados
     */
    public function obtenerPedido(int $pedidoId): ?array
    {
        // Pedido base
        $stmt = $this->db->prepare(
            "SELECT p.*, u.nombre as cliente_nombre, u.apellido as cliente_apellido, u.email as cliente_email
             FROM pedidos p
             INNER JOIN usuarios u ON p.id_usuario = u.id
             WHERE p.id = :id"
        );
        $stmt->execute([':id' => $pedidoId]);
        $pedido = $stmt->fetch();

        if (!$pedido) {
            return null;
        }

        // Detalle
        $stmt = $this->db->prepare(
            "SELECT dp.*, p.imagen_url
             FROM detalle_pedido dp
             LEFT JOIN productos p ON dp.id_producto = p.id
             WHERE dp.id_pedido = :pedido_id"
        );
        $stmt->execute([':pedido_id' => $pedidoId]);
        $pedido['detalle'] = $stmt->fetchAll();

        // Historial de estados
        $stmt = $this->db->prepare(
            "SELECT ep.*, u.nombre as usuario_nombre
             FROM estados_pedido ep
             LEFT JOIN usuarios u ON ep.usuario_responsable = u.id
             WHERE ep.id_pedido = :pedido_id
             ORDER BY ep.fecha_cambio ASC"
        );
        $stmt->execute([':pedido_id' => $pedidoId]);
        $pedido['historial_estados'] = $stmt->fetchAll();

        // Pago
        $stmt = $this->db->prepare(
            "SELECT id, metodo_pago, monto, estado, referencia_externa, fecha_pago, created_at
             FROM pagos WHERE id_pedido = :pedido_id ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([':pedido_id' => $pedidoId]);
        $pedido['pago'] = $stmt->fetch();

        // Formatear montos
        foreach (['subtotal', 'iva', 'total'] as $campo) {
            $pedido[$campo . '_formateado'] = '$' . number_format($pedido[$campo] , 0, ',', '.');
            $pedido[$campo] = (int)$pedido[$campo];
        }

        return $pedido;
    }

    /**
     * Obtiene todos los pedidos de un usuario
     */
    public function obtenerPedidosUsuario(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.estado, p.total, p.subtotal, p.iva, p.created_at,
                    COUNT(dp.id) as total_items
             FROM pedidos p
             LEFT JOIN detalle_pedido dp ON p.id = dp.id_pedido
             WHERE p.id_usuario = :uid
             GROUP BY p.id
             ORDER BY p.created_at DESC"
        );
        $stmt->execute([':uid' => $userId]);
        $pedidos = $stmt->fetchAll();

        foreach ($pedidos as &$p) {
            $p['total_formateado'] = '$' . number_format($p['total'] , 0, ',', '.');
            $p['total'] = (int)$p['total'];
            $p['total_items'] = (int)$p['total_items'];
        }

        return $pedidos;
    }

    /**
     * Actualiza el estado de un pedido
     */
    public function actualizarEstado(int $pedidoId, string $nuevoEstado): void
    {
        $stmt = $this->db->prepare("UPDATE pedidos SET estado = :estado WHERE id = :id");
        $stmt->execute([':estado' => $nuevoEstado, ':id' => $pedidoId]);
    }

    /**
     * Obtiene el detalle de items de un pedido
     */
    public function obtenerDetallePedido(int $pedidoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id_producto, cantidad FROM detalle_pedido WHERE id_pedido = :pedido_id"
        );
        $stmt->execute([':pedido_id' => $pedidoId]);
        return $stmt->fetchAll();
    }

    /**
     * Descuenta stock de un producto (operación atómica)
     */
    public function descontarStockProducto(int $productoId, int $cantidad): void
    {
        $stmt = $this->db->prepare(
            "UPDATE productos SET stock = stock - :cantidad WHERE id = :id AND stock >= :cantidad2"
        );
        $stmt->execute([
            ':cantidad'   => $cantidad,
            ':id'         => $productoId,
            ':cantidad2'  => $cantidad,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException("No se pudo descontar stock del producto ID {$productoId}");
        }
    }

    /**
     * Valida un cupón de descuento
     */
    public function validarCupon(string $codigo, int $montoCompra): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM cupones
             WHERE codigo = :codigo
               AND activo = 1
               AND fecha_inicio <= NOW()
               AND fecha_fin >= NOW()
               AND (usos_maximos IS NULL OR usos_actuales < usos_maximos)
               AND (monto_minimo IS NULL OR :monto >= monto_minimo)"
        );
        $stmt->execute([':codigo' => $codigo, ':monto' => $montoCompra]);
        return $stmt->fetch() ?: null;
    }

    /**
     * ¿El usuario ya usó este cupón en una compra PAGADA?
     * Cuenta solo pedidos pagados: un pedido abandonado/rechazado no quema el uso.
     */
    public function usuarioYaUsoCupon(int $userId, int $cuponId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM pedido_cupon pc
             INNER JOIN pedidos p ON p.id = pc.id_pedido
             WHERE p.id_usuario = :uid AND pc.id_cupon = :cupon AND p.estado = 'pagado'
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId, ':cupon' => $cuponId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Registra el uso de un cupón en un pedido
     */
    public function aplicarCupon(int $pedidoId, int $cuponId, int $descuento): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO pedido_cupon (id_pedido, id_cupon, descuento) VALUES (:pedido, :cupon, :descuento)"
        );
        $stmt->execute([
            ':pedido'    => $pedidoId,
            ':cupon'     => $cuponId,
            ':descuento' => $descuento,
        ]);

        // Incrementar usos
        $stmt = $this->db->prepare(
            "UPDATE cupones SET usos_actuales = usos_actuales + 1 WHERE id = :id"
        );
        $stmt->execute([':id' => $cuponId]);
    }
}
