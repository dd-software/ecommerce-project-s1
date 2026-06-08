<?php
class AdminController {
    public function __construct(private PDO $db) {}

    public function handle(string $method, array $segments): void {
        $resource = $segments[0] ?? '';
        $id       = isset($segments[1]) ? (int)$segments[1] : null;
        $sub      = $segments[2] ?? '';

        match (true) {
            // Stats
            $resource === 'stats'                                    => $this->stats(),
            // Productos
            $method === 'GET'    && $resource === 'productos' && !$id  => $this->listarProductos(),
            $method === 'POST'   && $resource === 'productos'          => $this->crearProducto(),
            $method === 'GET'    && $resource === 'productos' && $id   => $this->obtenerProducto($id),
            $method === 'PUT'    && $resource === 'productos' && $id   => $this->actualizarProducto($id),
            $method === 'DELETE' && $resource === 'productos' && $id   => $this->eliminarProducto($id),
            // Categorías
            $method === 'GET'    && $resource === 'categorias'          => $this->listarCategorias(),
            $method === 'POST'   && $resource === 'categorias'          => $this->crearCategoria(),
            // Pedidos
            $method === 'GET'    && $resource === 'pedidos' && !$id     => $this->listarPedidos(),
            $method === 'GET'    && $resource === 'pedidos' && $id      => $this->obtenerPedido($id),
            $method === 'PUT'    && $resource === 'pedidos' && $id && $sub === 'estado'
                                                                        => $this->cambiarEstado($id),
            // Inventario
            $method === 'GET'    && $resource === 'inventario'          => $this->listarInventario(),
            $method === 'PUT'    && $resource === 'inventario' && $id   => $this->actualizarStock($id),
            // Usuarios
            $method === 'GET'    && $resource === 'usuarios'            => $this->listarUsuarios(),
            $method === 'PUT'    && $resource === 'usuarios' && $id     => $this->toggleUsuario($id),
            default => Response::notFound(),
        };
    }

    private function stats(): void {
        $stats = [];

        $stats['total_productos'] = (int)$this->db
            ->query('SELECT COUNT(*) FROM productos WHERE activo = 1')->fetchColumn();

        $stats['total_usuarios'] = (int)$this->db
            ->query('SELECT COUNT(*) FROM usuarios WHERE rol = "cliente"')->fetchColumn();

        $stats['total_pedidos'] = (int)$this->db
            ->query('SELECT COUNT(*) FROM pedidos')->fetchColumn();

        $stats['ingresos_totales'] = (float)$this->db
            ->query('SELECT COALESCE(SUM(total),0) FROM pedidos WHERE estado != "cancelado"')
            ->fetchColumn();

        $stats['pedidos_por_estado'] = $this->db
            ->query('SELECT estado, COUNT(*) AS cantidad FROM pedidos GROUP BY estado')
            ->fetchAll();

        $stats['productos_sin_stock'] = (int)$this->db
            ->query('SELECT COUNT(*) FROM inventario WHERE stock = 0')->fetchColumn();

        Response::success($stats);
    }

    private function listarProductos(): void {
        $stmt = $this->db->query("
            SELECT p.id, p.nombre, p.precio, p.activo, p.creado_en,
                   c.nombre AS categoria_nombre,
                   COALESCE(inv.stock,0) AS stock,
                   img.url AS imagen_url
            FROM   productos p
            JOIN   categorias c ON c.id = p.categoria_id
            LEFT JOIN inventario inv ON inv.producto_id = p.id
            LEFT JOIN imagenes_producto img ON img.producto_id = p.id AND img.es_principal = 1
            ORDER BY p.id DESC
        ");
        $productos = $stmt->fetchAll();
        foreach ($productos as &$p) {
            $p['precio'] = (float)$p['precio'];
            $p['stock']  = (int)$p['stock'];
            $p['activo'] = (bool)$p['activo'];
        }
        Response::success($productos);
    }

    private function obtenerProducto(int $id): void {
        $stmt = $this->db->prepare("
            SELECT p.*, c.nombre AS categoria_nombre, COALESCE(inv.stock,0) AS stock
            FROM   productos p
            JOIN   categorias c ON c.id = p.categoria_id
            LEFT JOIN inventario inv ON inv.producto_id = p.id
            WHERE  p.id = ?
        ");
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if (!$p) Response::notFound();

        $imgStmt = $this->db->prepare('SELECT url, es_principal FROM imagenes_producto WHERE producto_id = ?');
        $imgStmt->execute([$id]);
        $p['imagenes'] = $imgStmt->fetchAll();
        Response::success($p);
    }

    private function crearProducto(): void {
        $b = $this->body();
        $nombre      = trim($b['nombre']      ?? '');
        $descripcion = trim($b['descripcion'] ?? '');
        $precio      = isset($b['precio']) ? (float)$b['precio'] : 0;
        $categoriaId = isset($b['categoria_id']) ? (int)$b['categoria_id'] : 0;
        $stock       = isset($b['stock']) ? (int)$b['stock'] : 0;
        $imagenUrl   = trim($b['imagen_url']  ?? '');

        if (!$nombre || $precio <= 0 || !$categoriaId) {
            Response::error('DATOS_INVALIDOS', 'nombre, precio y categoria_id son obligatorios.');
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                'INSERT INTO productos (categoria_id, nombre, descripcion, precio) VALUES (?, ?, ?, ?)'
            )->execute([$categoriaId, $nombre, $descripcion, $precio]);
            $pid = (int)$this->db->lastInsertId();

            if ($imagenUrl) {
                $this->db->prepare(
                    'INSERT INTO imagenes_producto (producto_id, url, es_principal) VALUES (?, ?, 1)'
                )->execute([$pid, $imagenUrl]);
            }

            $this->db->prepare(
                'INSERT INTO inventario (producto_id, stock) VALUES (?, ?) ON DUPLICATE KEY UPDATE stock = ?'
            )->execute([$pid, $stock, $stock]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            Response::error('ERROR_INTERNO', 'No se pudo crear el producto.', 500);
        }

        $this->obtenerProducto($pid);
    }

    private function actualizarProducto(int $id): void {
        $b = $this->body();
        $updates = [];
        $params  = [];

        if (isset($b['nombre']))       { $updates[] = 'nombre = ?';       $params[] = trim($b['nombre']); }
        if (isset($b['descripcion']))  { $updates[] = 'descripcion = ?';  $params[] = trim($b['descripcion']); }
        if (isset($b['precio']))       { $updates[] = 'precio = ?';       $params[] = (float)$b['precio']; }
        if (isset($b['categoria_id'])) { $updates[] = 'categoria_id = ?'; $params[] = (int)$b['categoria_id']; }
        if (isset($b['activo']))       { $updates[] = 'activo = ?';       $params[] = $b['activo'] ? 1 : 0; }

        if ($updates) {
            $params[] = $id;
            $this->db->prepare('UPDATE productos SET ' . implode(', ', $updates) . ' WHERE id = ?')
                ->execute($params);
        }

        if (isset($b['stock'])) {
            $this->db->prepare(
                'INSERT INTO inventario (producto_id, stock) VALUES (?, ?) ON DUPLICATE KEY UPDATE stock = ?'
            )->execute([$id, (int)$b['stock'], (int)$b['stock']]);
        }

        if (!empty($b['imagen_url'])) {
            $this->db->prepare('DELETE FROM imagenes_producto WHERE producto_id = ?')->execute([$id]);
            $this->db->prepare(
                'INSERT INTO imagenes_producto (producto_id, url, es_principal) VALUES (?, ?, 1)'
            )->execute([$id, trim($b['imagen_url'])]);
        }

        $this->obtenerProducto($id);
    }

    private function eliminarProducto(int $id): void {
        $this->db->prepare('UPDATE productos SET activo = 0 WHERE id = ?')->execute([$id]);
        Response::success(['ok' => true, 'mensaje' => 'Producto desactivado.']);
    }

    private function listarCategorias(): void {
        $stmt = $this->db->query('SELECT * FROM categorias ORDER BY nombre');
        Response::success($stmt->fetchAll());
    }

    private function crearCategoria(): void {
        $b = $this->body();
        $nombre = trim($b['nombre'] ?? '');
        if (!$nombre) Response::error('DATOS_INVALIDOS', 'El nombre es obligatorio.');
        $this->db->prepare(
            'INSERT INTO categorias (nombre, descripcion, imagen_url) VALUES (?, ?, ?)'
        )->execute([$nombre, $b['descripcion'] ?? '', $b['imagen_url'] ?? '']);
        $cat = $this->db->query('SELECT * FROM categorias WHERE id = ' . $this->db->lastInsertId())->fetch();
        Response::success($cat, 201);
    }

    private function listarPedidos(): void {
        $estado = $_GET['estado'] ?? null;
        $sql = "
            SELECT p.id, p.estado, p.total, p.subtotal, p.impuesto, p.creado_en,
                   u.nombre AS usuario_nombre, u.email AS usuario_email
            FROM   pedidos p
            JOIN   usuarios u ON u.id = p.usuario_id
        ";
        $params = [];
        if ($estado) { $sql .= ' WHERE p.estado = ?'; $params[] = $estado; }
        $sql .= ' ORDER BY p.creado_en DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $pedidos = $stmt->fetchAll();
        foreach ($pedidos as &$p) {
            $p['total']    = (float)$p['total'];
            $p['subtotal'] = (float)$p['subtotal'];
            $p['impuesto'] = (float)$p['impuesto'];
        }
        Response::success($pedidos);
    }

    private function obtenerPedido(int $id): void {
        $stmt = $this->db->prepare(
            'SELECT p.*, u.nombre AS usuario_nombre, u.email AS usuario_email
             FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        $pedido = $stmt->fetch();
        if (!$pedido) Response::notFound();

        $detStmt = $this->db->prepare('SELECT * FROM detalles_pedido WHERE pedido_id = ?');
        $detStmt->execute([$id]);
        $pedido['detalles'] = $detStmt->fetchAll();

        $histStmt = $this->db->prepare('SELECT * FROM historial_pedido WHERE pedido_id = ? ORDER BY cambiado_en');
        $histStmt->execute([$id]);
        $pedido['historial'] = $histStmt->fetchAll();
        $pedido['total']    = (float)$pedido['total'];
        $pedido['subtotal'] = (float)$pedido['subtotal'];
        $pedido['impuesto'] = (float)$pedido['impuesto'];

        Response::success($pedido);
    }

    private function cambiarEstado(int $id): void {
        $b = $this->body();
        $nuevoEstado = $b['estado'] ?? '';
        $nota        = $b['nota']   ?? '';
        $validos     = ['pendiente', 'pagado', 'enviado', 'entregado', 'cancelado'];

        if (!in_array($nuevoEstado, $validos)) {
            Response::error('ESTADO_INVALIDO', 'Estado no válido. Valores: ' . implode(', ', $validos));
        }

        $stmt = $this->db->prepare('SELECT estado FROM pedidos WHERE id = ?');
        $stmt->execute([$id]);
        $pedido = $stmt->fetch();
        if (!$pedido) Response::notFound('Pedido no encontrado.');

        $this->db->prepare('UPDATE pedidos SET estado = ? WHERE id = ?')->execute([$nuevoEstado, $id]);

        // RN-005: registrar trazabilidad
        $this->db->prepare(
            'INSERT INTO historial_pedido (pedido_id, estado_anterior, estado_nuevo, nota) VALUES (?, ?, ?, ?)'
        )->execute([$id, $pedido['estado'], $nuevoEstado, $nota]);

        Response::success(['ok' => true, 'estado' => $nuevoEstado]);
    }

    private function listarInventario(): void {
        $stmt = $this->db->query("
            SELECT p.id AS producto_id, p.nombre, p.activo,
                   COALESCE(inv.stock,0) AS stock,
                   img.url AS imagen_url
            FROM   productos p
            LEFT JOIN inventario inv ON inv.producto_id = p.id
            LEFT JOIN imagenes_producto img ON img.producto_id = p.id AND img.es_principal = 1
            WHERE  p.activo = 1
            ORDER BY stock ASC, p.nombre
        ");
        $inv = $stmt->fetchAll();
        foreach ($inv as &$i) $i['stock'] = (int)$i['stock'];
        Response::success($inv);
    }

    private function actualizarStock(int $productoId): void {
        $b     = $this->body();
        $stock = isset($b['stock']) ? (int)$b['stock'] : -1;
        if ($stock < 0) Response::error('DATOS_INVALIDOS', 'El stock debe ser >= 0.');

        $this->db->prepare(
            'INSERT INTO inventario (producto_id, stock) VALUES (?, ?) ON DUPLICATE KEY UPDATE stock = ?'
        )->execute([$productoId, $stock, $stock]);

        Response::success(['producto_id' => $productoId, 'stock' => $stock]);
    }

    private function listarUsuarios(): void {
        $stmt = $this->db->query(
            'SELECT id, nombre, email, rol, habilitado, creado_en FROM usuarios ORDER BY id DESC'
        );
        $users = $stmt->fetchAll();
        foreach ($users as &$u) $u['habilitado'] = (bool)$u['habilitado'];
        Response::success($users);
    }

    private function toggleUsuario(int $id): void {
        $b = $this->body();
        $habilitado = isset($b['habilitado']) ? ($b['habilitado'] ? 1 : 0) : -1;
        if ($habilitado === -1) Response::error('DATOS_INVALIDOS', 'habilitado es requerido (true/false).');

        $this->db->prepare('UPDATE usuarios SET habilitado = ? WHERE id = ?')->execute([$habilitado, $id]);
        Response::success(['ok' => true, 'habilitado' => (bool)$habilitado]);
    }

    private function body(): array {
        return (array)json_decode(file_get_contents('php://input'), true);
    }
}
