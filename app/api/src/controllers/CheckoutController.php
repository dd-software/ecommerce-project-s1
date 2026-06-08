<?php
class CheckoutController {
    public function __construct(private PDO $db) {}

    public function handle(string $method, array $segments, array $user): void {
        $resource = $segments[0] ?? '';
        $id       = isset($segments[1]) ? (int)$segments[1] : null;

        match (true) {
            $method === 'POST' && $resource === ''           => $this->crearPedido($user),
            $method === 'GET'  && $resource === 'pedidos' && !$id => $this->listarPedidos($user),
            $method === 'GET'  && $resource === 'pedidos' && $id  => $this->obtenerPedido($user, $id),
            default => Response::notFound(),
        };
    }

    private function crearPedido(array $user): void {
        // Obtener carrito
        $carritoStmt = $this->db->prepare('SELECT id FROM carritos WHERE usuario_id = ?');
        $carritoStmt->execute([$user['id']]);
        $carrito = $carritoStmt->fetch();

        if (!$carrito) {
            Response::error('CARRITO_VACIO', 'No tienes productos en el carrito.', 400);
        }

        $itemsStmt = $this->db->prepare("
            SELECT ic.producto_id, ic.cantidad, ic.precio_unitario,
                   p.nombre, COALESCE(inv.stock,0) AS stock
            FROM   items_carrito ic
            JOIN   productos p ON p.id = ic.producto_id
            LEFT JOIN inventario inv ON inv.producto_id = p.id
            WHERE  ic.carrito_id = ?
        ");
        $itemsStmt->execute([$carrito['id']]);
        $items = $itemsStmt->fetchAll();

        if (empty($items)) {
            Response::error('CARRITO_VACIO', 'No tienes productos en el carrito.', 400);
        }

        // RN-001: verificar stock antes de confirmar
        foreach ($items as $item) {
            if ((int)$item['cantidad'] > (int)$item['stock']) {
                Response::error('STOCK_INSUFICIENTE',
                    "Stock insuficiente para \"{$item['nombre']}\". Disponible: {$item['stock']}.", 409);
            }
        }

        $subtotal = array_sum(array_map(
            fn($i) => $i['cantidad'] * $i['precio_unitario'], $items
        ));
        $impuesto = round($subtotal * TAX_RATE, 2);
        $total    = round($subtotal + $impuesto, 2);

        $this->db->beginTransaction();
        try {
            // Crear pedido
            $this->db->prepare(
                'INSERT INTO pedidos (usuario_id, estado, subtotal, impuesto, total) VALUES (?, ?, ?, ?, ?)'
            )->execute([$user['id'], 'pagado', round($subtotal, 2), $impuesto, $total]);
            $pedidoId = (int)$this->db->lastInsertId();

            // Detalles + descontar stock (RN-003: descuenta tras confirmar pago)
            $detStmt = $this->db->prepare(
                'INSERT INTO detalles_pedido (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $invStmt = $this->db->prepare(
                'UPDATE inventario SET stock = stock - ? WHERE producto_id = ?'
            );

            foreach ($items as $item) {
                $sub = round($item['cantidad'] * $item['precio_unitario'], 2);
                $detStmt->execute([$pedidoId, $item['producto_id'], $item['nombre'],
                                   $item['cantidad'], $item['precio_unitario'], $sub]);
                $invStmt->execute([$item['cantidad'], $item['producto_id']]);
            }

            // RN-005: trazabilidad de estados
            $this->db->prepare(
                'INSERT INTO historial_pedido (pedido_id, estado_anterior, estado_nuevo, nota)
                 VALUES (?, NULL, ?, ?)'
            )->execute([$pedidoId, 'pagado', 'Pedido creado y pago confirmado.']);

            // Vaciar carrito
            $this->db->prepare('DELETE FROM items_carrito WHERE carrito_id = ?')
                ->execute([$carrito['id']]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            Response::error('ERROR_INTERNO', 'No se pudo procesar el pedido. Intenta de nuevo.', 500);
        }

        $pedidoStmt = $this->db->prepare('SELECT * FROM pedidos WHERE id = ?');
        $pedidoStmt->execute([$pedidoId]);
        $pedido = $pedidoStmt->fetch();
        $pedido['total']    = (float)$pedido['total'];
        $pedido['subtotal'] = (float)$pedido['subtotal'];
        $pedido['impuesto'] = (float)$pedido['impuesto'];

        Response::success($pedido, 201);
    }

    private function listarPedidos(array $user): void {
        $stmt = $this->db->prepare(
            'SELECT id, estado, subtotal, impuesto, total, creado_en
             FROM   pedidos WHERE usuario_id = ? ORDER BY creado_en DESC'
        );
        $stmt->execute([$user['id']]);
        $pedidos = $stmt->fetchAll();

        foreach ($pedidos as &$p) {
            $p['total']    = (float)$p['total'];
            $p['subtotal'] = (float)$p['subtotal'];
            $p['impuesto'] = (float)$p['impuesto'];
        }

        Response::success($pedidos);
    }

    private function obtenerPedido(array $user, int $id): void {
        $stmt = $this->db->prepare(
            'SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?'
        );
        $stmt->execute([$id, $user['id']]);
        $pedido = $stmt->fetch();

        if (!$pedido) Response::notFound('Pedido no encontrado.');

        $detStmt = $this->db->prepare(
            'SELECT * FROM detalles_pedido WHERE pedido_id = ?'
        );
        $detStmt->execute([$id]);
        $detalles = $detStmt->fetchAll();
        foreach ($detalles as &$d) {
            $d['subtotal']       = (float)$d['subtotal'];
            $d['precio_unitario'] = (float)$d['precio_unitario'];
            $d['cantidad']        = (int)$d['cantidad'];
        }

        $histStmt = $this->db->prepare(
            'SELECT * FROM historial_pedido WHERE pedido_id = ? ORDER BY cambiado_en'
        );
        $histStmt->execute([$id]);

        $pedido['total']    = (float)$pedido['total'];
        $pedido['subtotal'] = (float)$pedido['subtotal'];
        $pedido['impuesto'] = (float)$pedido['impuesto'];
        $pedido['detalles'] = $detalles;
        $pedido['historial'] = $histStmt->fetchAll();

        Response::success($pedido);
    }
}
