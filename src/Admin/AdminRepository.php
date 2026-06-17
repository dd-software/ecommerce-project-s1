<?php

declare(strict_types=1);

/**
 * AdminRepository - Acceso a datos del panel de administración
 */
namespace App\Admin;

use App\Core\Database;
use PDO;

class AdminRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ========== Dashboard ==========

    public function contarProductos(): int
    {
        return (int)$this->db->query(
            "SELECT COUNT(*) as total FROM productos WHERE deleted_at IS NULL"
        )->fetch()['total'];
    }

    public function contarPedidos(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) as total FROM pedidos")->fetch()['total'];
    }

    public function contarUsuarios(): int
    {
        return (int)$this->db->query(
            "SELECT COUNT(*) as total FROM usuarios WHERE deleted_at IS NULL"
        )->fetch()['total'];
    }

    public function ventasHoy(): array
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total), 0) as total_ventas, COUNT(*) as total_pedidos
             FROM pedidos
             WHERE estado IN ('pagado', 'en_preparacion', 'enviado', 'entregado')
               AND DATE(created_at) = CURDATE()"
        );
        $stmt->execute();
        $result = $stmt->fetch();
        $result['total_ventas_formateado'] = '$' . number_format((int)$result['total_ventas'] / 100, 0, ',', '.');
        return $result;
    }

    public function ventasMes(): array
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total), 0) as total_ventas, COUNT(*) as total_pedidos
             FROM pedidos
             WHERE estado IN ('pagado', 'en_preparacion', 'enviado', 'entregado')
               AND MONTH(created_at) = MONTH(CURDATE())
               AND YEAR(created_at) = YEAR(CURDATE())"
        );
        $stmt->execute();
        $result = $stmt->fetch();
        $result['total_ventas_formateado'] = '$' . number_format((int)$result['total_ventas'] / 100, 0, ',', '.');
        return $result;
    }

    public function contarPedidosPorEstado(string $estado): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM pedidos WHERE estado = :estado");
        $stmt->execute([':estado' => $estado]);
        return (int)$stmt->fetch()['total'];
    }

    public function contarProductosAgotados(): int
    {
        return (int)$this->db->query(
            "SELECT COUNT(*) as total FROM productos WHERE stock = 0 AND activo = 1 AND deleted_at IS NULL"
        )->fetch()['total'];
    }

    public function obtenerAlertasStock(): array
    {
        return $this->db->query(
            "SELECT id, nombre, stock, stock_minimo, imagen_url
             FROM productos
             WHERE activo = 1 AND deleted_at IS NULL AND stock <= stock_minimo
             ORDER BY (stock_minimo - stock) DESC LIMIT 10"
        )->fetchAll();
    }

    public function obtenerUltimosPedidos(int $limite): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.estado, p.total, p.created_at, u.nombre as cliente_nombre, u.apellido
             FROM pedidos p
             INNER JOIN usuarios u ON p.id_usuario = u.id
             ORDER BY p.created_at DESC LIMIT :limite"
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        $pedidos = $stmt->fetchAll();

        foreach ($pedidos as &$p) {
            $p['total_formateado'] = '$' . number_format($p['total'] / 100, 0, ',', '.');
        }

        return $pedidos;
    }

    // ========== Productos Admin ==========

    public function listarProductosAdmin(?string $busqueda, int $pagina, int $porPagina): array
    {
        $where = ["p.deleted_at IS NULL"];
        $params = [];

        if ($busqueda) {
            $where[] = "(p.nombre LIKE :q OR p.descripcion LIKE :q2)";
            $params[':q'] = '%' . $busqueda . '%';
            $params[':q2'] = '%' . $busqueda . '%';
        }

        $whereSQL = implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM productos p WHERE {$whereSQL}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];

        $offset = ($pagina - 1) * $porPagina;
        $stmt = $this->db->prepare(
            "SELECT p.*, c.nombre as categoria_nombre
             FROM productos p
             LEFT JOIN categorias c ON p.id_categoria = c.id
             WHERE {$whereSQL}
             ORDER BY p.created_at DESC
             LIMIT {$porPagina} OFFSET {$offset}"
        );
        $stmt->execute($params);
        $productos = $stmt->fetchAll();

        foreach ($productos as &$p) {
            $p['precio_formateado'] = '$' . number_format($p['precio'] / 100, 0, ',', '.');
        }

        return ['productos' => $productos, 'total' => $total];
    }

    public function crearProducto(array $data): array
    {
        $stmt = $this->db->prepare(
            "INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, imagen_url, activo)
             VALUES (:cat, :nombre, :slug, :desc, :precio, :stock, :stock_min, :img, :activo)"
        );
        $stmt->execute([
            ':cat'       => $data['id_categoria'],
            ':nombre'    => $data['nombre'],
            ':slug'      => $data['slug'],
            ':desc'      => $data['descripcion'],
            ':precio'    => $data['precio'],
            ':stock'     => $data['stock'],
            ':stock_min' => $data['stock_minimo'],
            ':img'       => $data['imagen_url'],
            ':activo'    => $data['activo'],
        ]);

        $id = (int)$this->db->lastInsertId();
        $data['id'] = $id;
        return $data;
    }

    public function actualizarProducto(int $id, array $campos): array
    {
        if (empty($campos)) {
            $stmt = $this->db->prepare("SELECT * FROM productos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: [];
        }

        $sets = [];
        $params = [':id' => $id];
        foreach ($campos as $campo => $valor) {
            $sets[] = "{$campo} = :{$campo}";
            $params[":{$campo}"] = $valor;
        }

        $sql = "UPDATE productos SET " . implode(', ', $sets) . " WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $stmt = $this->db->prepare("SELECT * FROM productos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: [];
    }

    public function softDeleteProducto(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE productos SET deleted_at = NOW(), activo = 0 WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    public function existeSlug(string $slug, ?int $excluirId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM productos WHERE slug = :slug";
        $params = [':slug' => $slug];

        if ($excluirId !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $excluirId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetch()['total'] > 0;
    }

    // ========== Pedidos Admin ==========

    public function listarPedidosAdmin(?string $estado, int $pagina, int $porPagina): array
    {
        $where = [];
        $params = [];

        if ($estado) {
            $where[] = "p.estado = :estado";
            $params[':estado'] = $estado;
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM pedidos p {$whereSQL}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];

        $offset = ($pagina - 1) * $porPagina;
        $stmt = $this->db->prepare(
            "SELECT p.*, u.nombre as cliente_nombre, u.apellido, u.email,
                    COUNT(dp.id) as total_items
             FROM pedidos p
             INNER JOIN usuarios u ON p.id_usuario = u.id
             LEFT JOIN detalle_pedido dp ON p.id = dp.id_pedido
             {$whereSQL}
             GROUP BY p.id
             ORDER BY p.created_at DESC
             LIMIT {$porPagina} OFFSET {$offset}"
        );
        $stmt->execute($params);
        $pedidos = $stmt->fetchAll();

        foreach ($pedidos as &$p) {
            $p['total_formateado'] = '$' . number_format($p['total'] / 100, 0, ',', '.');
        }

        return ['pedidos' => $pedidos, 'total' => $total];
    }

    public function actualizarEstadoPedido(int $pedidoId, string $estado, int $userId, string $comentario): void
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("UPDATE pedidos SET estado = :estado WHERE id = :id");
            $stmt->execute([':estado' => $estado, ':id' => $pedidoId]);

            $stmt = $this->db->prepare(
                "INSERT INTO estados_pedido (id_pedido, estado, usuario_responsable, comentario)
                 VALUES (:pedido, :estado, :usuario, :comentario)"
            );
            $stmt->execute([
                ':pedido'     => $pedidoId,
                ':estado'     => $estado,
                ':usuario'    => $userId,
                ':comentario' => $comentario,
            ]);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    // ========== Usuarios ==========

    public function listarUsuarios(int $pagina, int $porPagina): array
    {
        $total = (int)$this->db->query(
            "SELECT COUNT(*) as total FROM usuarios WHERE deleted_at IS NULL"
        )->fetch()['total'];

        $offset = ($pagina - 1) * $porPagina;
        $stmt = $this->db->prepare(
            "SELECT id, nombre, apellido, email, rol, activo, created_at, ultimo_login
             FROM usuarios WHERE deleted_at IS NULL
             ORDER BY created_at DESC
             LIMIT :limite OFFSET :offset"
        );
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['usuarios' => $stmt->fetchAll(), 'total' => $total];
    }

    public function actualizarEstadoUsuario(int $id, ?int $activo): void
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET activo = :activo WHERE id = :id");
        $stmt->execute([':activo' => $activo ?? 1, ':id' => $id]);
    }

    // ========== Reportes ==========

    public function obtenerReporteVentas(string $periodo): array
    {
        $fechaInicio = match ($periodo) {
            'hoy'     => "CURDATE()",
            'semana'  => "DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
            'mes'     => "DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            'anio'    => "DATE_SUB(CURDATE(), INTERVAL 365 DAY)",
            default   => "DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
        };

        $stmt = $this->db->prepare(
            "SELECT DATE(created_at) as fecha,
                    COUNT(*) as total_pedidos,
                    COALESCE(SUM(total), 0) as total_ventas
             FROM pedidos
             WHERE estado IN ('pagado', 'en_preparacion', 'enviado', 'entregado')
               AND created_at >= {$fechaInicio}
             GROUP BY DATE(created_at)
             ORDER BY fecha ASC"
        );
        $stmt->execute();
        $ventas = $stmt->fetchAll();

        foreach ($ventas as &$v) {
            $v['total_ventas_formateado'] = '$' . number_format($v['total_ventas'] / 100, 0, ',', '.');
        }

        return $ventas;
    }

    public function obtenerProductosMasVendidos(int $limite = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT dp.id_producto, dp.nombre_producto,
                    SUM(dp.cantidad) as total_vendido,
                    SUM(dp.cantidad * dp.precio_unitario) as total_recaudado
             FROM detalle_pedido dp
             INNER JOIN pedidos p ON dp.id_pedido = p.id
             WHERE p.estado IN ('pagado', 'en_preparacion', 'enviado', 'entregado')
             GROUP BY dp.id_producto, dp.nombre_producto
             ORDER BY total_vendido DESC
             LIMIT :limite"
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
