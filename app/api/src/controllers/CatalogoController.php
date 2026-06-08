<?php
class CatalogoController {
    public function __construct(private PDO $db) {}

    public function handle(string $method, array $segments): void {
        if ($method !== 'GET') {
            Response::error('METODO_NO_PERMITIDO', 'Solo GET está permitido en catálogo.', 405);
        }

        $resource = $segments[0] ?? '';
        $id       = isset($segments[1]) ? (int)$segments[1] : null;
        $sub      = $segments[2] ?? '';

        match (true) {
            $resource === 'productos' && $id === null  => $this->listarProductos(),
            $resource === 'productos' && $id !== null  => $this->obtenerProducto($id),
            $resource === 'categorias' && $id === null => $this->listarCategorias(),
            $resource === 'categorias' && $id !== null && $sub === 'productos'
                                                        => $this->productosPorCategoria($id),
            $resource === 'categorias' && $id !== null => $this->obtenerCategoria($id),
            default => Response::notFound(),
        };
    }

    private function listarProductos(): void {
        $q          = trim($_GET['q'] ?? '');
        $catId      = isset($_GET['categoriaId']) ? (int)$_GET['categoriaId'] : null;
        $precioMin  = isset($_GET['precioMin'])   ? (float)$_GET['precioMin'] : null;
        $precioMax  = isset($_GET['precioMax'])   ? (float)$_GET['precioMax'] : null;
        $page       = max(1, (int)($_GET['page']  ?? 1));
        $limit      = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        $orden      = $_GET['orden'] ?? 'nombre';
        $offset     = ($page - 1) * $limit;

        $orderBy = match ($orden) {
            'precio_asc'  => 'p.precio ASC',
            'precio_desc' => 'p.precio DESC',
            'creado_en'   => 'p.creado_en DESC',
            default       => 'p.nombre ASC',
        };

        $where  = ['p.activo = 1'];
        $params = [];

        if ($q) {
            $where[]  = '(p.nombre LIKE ? OR p.descripcion LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        if ($catId) {
            $where[]  = 'p.categoria_id = ?';
            $params[] = $catId;
        }
        if ($precioMin !== null) {
            $where[]  = 'p.precio >= ?';
            $params[] = $precioMin;
        }
        if ($precioMax !== null) {
            $where[]  = 'p.precio <= ?';
            $params[] = $precioMax;
        }

        $whereStr = implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM productos p WHERE $whereStr");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "
            SELECT p.id, p.nombre, p.precio, p.categoria_id,
                   c.nombre AS categoria_nombre,
                   img.url  AS imagen_url,
                   COALESCE(inv.stock, 0) AS stock
            FROM   productos p
            JOIN   categorias c   ON c.id = p.categoria_id
            LEFT JOIN imagenes_producto img ON img.producto_id = p.id AND img.es_principal = 1
            LEFT JOIN inventario inv ON inv.producto_id = p.id
            WHERE  $whereStr
            ORDER BY $orderBy
            LIMIT $limit OFFSET $offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $productos = $stmt->fetchAll();

        foreach ($productos as &$p) {
            $p['tiene_stock'] = (int)$p['stock'] > 0;
            $p['precio']      = (float)$p['precio'];
            $p['stock']       = (int)$p['stock'];
        }

        Response::success([
            'data'         => $productos,
            'total'        => $total,
            'page'         => $page,
            'limit'        => $limit,
            'total_paginas' => (int)ceil($total / $limit),
        ]);
    }

    private function obtenerProducto(int $id): void {
        $stmt = $this->db->prepare("
            SELECT p.*, c.nombre AS categoria_nombre,
                   COALESCE(inv.stock, 0) AS stock
            FROM   productos p
            JOIN   categorias c   ON c.id = p.categoria_id
            LEFT JOIN inventario inv ON inv.producto_id = p.id
            WHERE  p.id = ? AND p.activo = 1
        ");
        $stmt->execute([$id]);
        $producto = $stmt->fetch();

        if (!$producto) Response::notFound('Producto no encontrado.');

        $imgStmt = $this->db->prepare(
            'SELECT url, es_principal FROM imagenes_producto WHERE producto_id = ? ORDER BY es_principal DESC'
        );
        $imgStmt->execute([$id]);
        $producto['imagenes']   = array_column($imgStmt->fetchAll(), 'url');
        $producto['tiene_stock'] = (int)$producto['stock'] > 0;
        $producto['precio']      = (float)$producto['precio'];
        $producto['stock']       = (int)$producto['stock'];

        Response::success($producto);
    }

    private function listarCategorias(): void {
        $stmt = $this->db->query('SELECT * FROM categorias WHERE activa = 1 ORDER BY nombre');
        Response::success($stmt->fetchAll());
    }

    private function obtenerCategoria(int $id): void {
        $stmt = $this->db->prepare('SELECT * FROM categorias WHERE id = ? AND activa = 1');
        $stmt->execute([$id]);
        $cat = $stmt->fetch();
        if (!$cat) Response::notFound('Categoría no encontrada.');
        Response::success($cat);
    }

    private function productosPorCategoria(int $catId): void {
        $_GET['categoriaId'] = $catId;
        $this->listarProductos();
    }
}
