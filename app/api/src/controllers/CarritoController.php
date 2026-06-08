<?php
class CarritoController {
    public function __construct(private PDO $db) {}

    public function handle(string $method, array $segments, array $user): void {
        $resource = $segments[0] ?? '';
        $id       = isset($segments[1]) ? (int)$segments[1] : null;

        match (true) {
            $method === 'GET'    && $resource === ''      => $this->obtenerCarrito($user),
            $method === 'DELETE' && $resource === ''      => $this->vaciarCarrito($user),
            $method === 'POST'   && $resource === 'items' => $this->agregarItem($user),
            $method === 'PUT'    && $resource === 'items' && $id => $this->actualizarItem($user, $id),
            $method === 'DELETE' && $resource === 'items' && $id => $this->eliminarItem($user, $id),
            default => Response::notFound(),
        };
    }

    private function getOrCreateCarrito(int $userId): int {
        $stmt = $this->db->prepare('SELECT id FROM carritos WHERE usuario_id = ?');
        $stmt->execute([$userId]);
        $carrito = $stmt->fetch();

        if ($carrito) return (int)$carrito['id'];

        $this->db->prepare('INSERT INTO carritos (usuario_id) VALUES (?)')->execute([$userId]);
        return (int)$this->db->lastInsertId();
    }

    private function buildCarritoResponse(int $userId): array {
        $carritoId = $this->getOrCreateCarrito($userId);

        $stmt = $this->db->prepare("
            SELECT ic.id, ic.producto_id, ic.cantidad, ic.precio_unitario,
                   p.nombre, img.url AS imagen_url,
                   (ic.cantidad * ic.precio_unitario) AS subtotal
            FROM   items_carrito ic
            JOIN   productos p  ON p.id = ic.producto_id
            LEFT JOIN imagenes_producto img ON img.producto_id = p.id AND img.es_principal = 1
            WHERE  ic.carrito_id = ?
            ORDER BY ic.id
        ");
        $stmt->execute([$carritoId]);
        $items = $stmt->fetchAll();

        $subtotal = 0;
        foreach ($items as &$item) {
            $item['cantidad']        = (int)$item['cantidad'];
            $item['precio_unitario'] = (float)$item['precio_unitario'];
            $item['subtotal']        = (float)$item['subtotal'];
            $subtotal               += $item['subtotal'];
        }

        $impuesto       = round($subtotal * TAX_RATE, 2);
        $total          = round($subtotal + $impuesto, 2);
        $cantidadItems  = array_sum(array_column($items, 'cantidad'));

        return [
            'id'             => $carritoId,
            'usuario_id'     => $userId,
            'items'          => $items,
            'subtotal'       => round($subtotal, 2),
            'impuesto'       => $impuesto,
            'total'          => $total,
            'cantidad_items' => $cantidadItems,
        ];
    }

    private function obtenerCarrito(array $user): void {
        Response::success($this->buildCarritoResponse($user['id']));
    }

    private function vaciarCarrito(array $user): void {
        $carritoId = $this->getOrCreateCarrito($user['id']);
        $this->db->prepare('DELETE FROM items_carrito WHERE carrito_id = ?')->execute([$carritoId]);
        Response::success(['ok' => true, 'mensaje' => 'Carrito vaciado.'], 204);
    }

    private function agregarItem(array $user): void {
        $body       = $this->body();
        $productoId = isset($body['producto_id']) ? (int)$body['producto_id'] : 0;
        $cantidad   = isset($body['cantidad'])    ? (int)$body['cantidad']    : 1;

        if ($productoId <= 0 || $cantidad < 1) {
            Response::error('DATOS_INVALIDOS', 'producto_id y cantidad son obligatorios y deben ser positivos.');
        }

        // Verificar que el producto existe y obtener precio
        $prodStmt = $this->db->prepare(
            'SELECT p.id, p.precio, COALESCE(inv.stock,0) AS stock FROM productos p
             LEFT JOIN inventario inv ON inv.producto_id = p.id
             WHERE p.id = ? AND p.activo = 1'
        );
        $prodStmt->execute([$productoId]);
        $producto = $prodStmt->fetch();

        if (!$producto) Response::notFound('Producto no encontrado.');

        // RN-001: no vender sin stock
        if ((int)$producto['stock'] <= 0) {
            Response::error('STOCK_INSUFICIENTE', 'El producto no tiene stock disponible.', 409);
        }

        $carritoId = $this->getOrCreateCarrito($user['id']);

        // Verificar si ya existe en el carrito
        $existeStmt = $this->db->prepare(
            'SELECT id, cantidad FROM items_carrito WHERE carrito_id = ? AND producto_id = ?'
        );
        $existeStmt->execute([$carritoId, $productoId]);
        $existe = $existeStmt->fetch();

        $nuevaCantidad = ($existe ? (int)$existe['cantidad'] : 0) + $cantidad;

        if ($nuevaCantidad > (int)$producto['stock']) {
            Response::error('STOCK_INSUFICIENTE',
                "Solo hay {$producto['stock']} unidades disponibles.", 409);
        }

        if ($existe) {
            $this->db->prepare(
                'UPDATE items_carrito SET cantidad = ? WHERE id = ?'
            )->execute([$nuevaCantidad, $existe['id']]);
        } else {
            $this->db->prepare(
                'INSERT INTO items_carrito (carrito_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)'
            )->execute([$carritoId, $productoId, $cantidad, $producto['precio']]);
        }

        Response::success($this->buildCarritoResponse($user['id']), 201);
    }

    private function actualizarItem(array $user, int $itemId): void {
        $body     = $this->body();
        $cantidad = isset($body['cantidad']) ? (int)$body['cantidad'] : -1;

        if ($cantidad < 0) {
            Response::error('DATOS_INVALIDOS', 'La cantidad debe ser un número mayor o igual a 0.');
        }

        $carritoId = $this->getOrCreateCarrito($user['id']);

        $itemStmt = $this->db->prepare(
            'SELECT ic.id, ic.producto_id FROM items_carrito ic WHERE ic.id = ? AND ic.carrito_id = ?'
        );
        $itemStmt->execute([$itemId, $carritoId]);
        $item = $itemStmt->fetch();

        if (!$item) Response::notFound('Ítem no encontrado en el carrito.');

        if ($cantidad === 0) {
            $this->db->prepare('DELETE FROM items_carrito WHERE id = ?')->execute([$itemId]);
            Response::success($this->buildCarritoResponse($user['id']));
        }

        // Validar stock (RN-001)
        $stockStmt = $this->db->prepare('SELECT COALESCE(stock,0) AS stock FROM inventario WHERE producto_id = ?');
        $stockStmt->execute([$item['producto_id']]);
        $stock = (int)($stockStmt->fetchColumn() ?: 0);

        if ($cantidad > $stock) {
            Response::error('STOCK_INSUFICIENTE', "Solo hay {$stock} unidades disponibles.", 409);
        }

        $this->db->prepare('UPDATE items_carrito SET cantidad = ? WHERE id = ?')
            ->execute([$cantidad, $itemId]);

        Response::success($this->buildCarritoResponse($user['id']));
    }

    private function eliminarItem(array $user, int $itemId): void {
        $carritoId = $this->getOrCreateCarrito($user['id']);

        $stmt = $this->db->prepare(
            'DELETE FROM items_carrito WHERE id = ? AND carrito_id = ?'
        );
        $stmt->execute([$itemId, $carritoId]);

        if ($stmt->rowCount() === 0) Response::notFound('Ítem no encontrado en el carrito.');

        Response::success($this->buildCarritoResponse($user['id']));
    }

    private function body(): array {
        return (array)json_decode(file_get_contents('php://input'), true);
    }
}
