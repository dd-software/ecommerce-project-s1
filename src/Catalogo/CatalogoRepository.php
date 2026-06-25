<?php

declare(strict_types=1);

/**
 * CatalogoRepository - Acceso a datos de productos y categorías
 */
namespace App\Catalogo;

use App\Core\Database;
use PDO;

class CatalogoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Busca productos con filtros dinámicos
     * Solo retorna productos activos y no eliminados
     */
    public function buscarProductos(array $filtros, string $ordenar, int $pagina, int $porPagina): array
    {
        $where = ["p.activo = 1", "p.deleted_at IS NULL"];
        $params = [];

        if (isset($filtros['categoria_id'])) {
            $where[] = "(p.id_categoria = :categoria_id OR p.id_categoria IN (SELECT id FROM categorias WHERE id_padre = :categoria_id_parent))";
            $params[':categoria_id'] = $filtros['categoria_id'];
            $params[':categoria_id_parent'] = $filtros['categoria_id'];
        }

        if (isset($filtros['busqueda'])) {
            $where[] = "(p.nombre LIKE :busqueda OR p.descripcion LIKE :busqueda2)";
            $params[':busqueda'] = '%' . $filtros['busqueda'] . '%';
            $params[':busqueda2'] = '%' . $filtros['busqueda'] . '%';
        }

        if (isset($filtros['precio_min'])) {
            $where[] = "p.precio >= :precio_min";
            $params[':precio_min'] = $filtros['precio_min'];
        }

        if (isset($filtros['precio_max'])) {
            $where[] = "p.precio <= :precio_max";
            $params[':precio_max'] = $filtros['precio_max'];
        }

        if (isset($filtros['en_stock']) && $filtros['en_stock'] === true) {
            $where[] = "p.stock > 0";
        }

        $whereSQL = implode(' AND ', $where);

        // Ordenamiento
        $orderSQL = match ($ordenar) {
            'precio_asc'  => 'p.precio ASC',
            'precio_desc' => 'p.precio DESC',
            'nombre_asc'  => 'p.nombre ASC',
            'nombre_desc' => 'p.nombre DESC',
            'nuevos'      => 'p.created_at DESC',
            default       => 'p.nombre ASC',
        };

        // Contar total
        $countSQL = "SELECT COUNT(*) as total FROM productos p WHERE {$whereSQL}";
        $stmt = $this->db->prepare($countSQL);
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['total'];

        // Calcular offset
        $offset = ($pagina - 1) * $porPagina;

        // Consulta paginada
        $sql = "SELECT p.id, p.nombre, p.slug, p.descripcion, p.precio, p.stock,
                       p.imagen_url, p.id_categoria, c.nombre as categoria_nombre,
                       p.created_at
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id
                WHERE {$whereSQL}
                ORDER BY {$orderSQL}
                LIMIT {$porPagina} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $productos = $stmt->fetchAll();

        // Formatear precios para el frontend
        foreach ($productos as &$p) {
            $p['precio_formateado'] = '$' . number_format((float)$p['precio'] / 100, 0, ',', '.');
            $p['precio'] = (int)$p['precio'];
            $p['stock'] = (int)$p['stock'];
            $p['sin_stock'] = $p['stock'] <= 0; // RN-A02
        }

        return [
            'productos' => $productos,
            'total'     => $total,
        ];
    }

    /**
     * Busca un producto por ID (solo activos)
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.nombre as categoria_nombre
             FROM productos p
             LEFT JOIN categorias c ON p.id_categoria = c.id
             WHERE p.id = :id AND p.activo = 1 AND p.deleted_at IS NULL"
        );
        $stmt->execute([':id' => $id]);
        $producto = $stmt->fetch();

        if ($producto) {
            $producto['precio_formateado'] = '$' . number_format((float)$producto['precio'] / 100, 0, ',', '.');
            $producto['precio'] = (int)$producto['precio'];
            $producto['stock'] = (int)$producto['stock'];
            $producto['sin_stock'] = (int)$producto['stock'] <= 0;
        }

        return $producto ?: null;
    }

    /**
     * Busca producto por ID incluyendo inactivos (para admin)
     */
    public function buscarPorIdAdmin(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.nombre as categoria_nombre
             FROM productos p
             LEFT JOIN categorias c ON p.id_categoria = c.id
             WHERE p.id = :id AND p.deleted_at IS NULL"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtiene categorías activas con conteo de productos
     */
    public function obtenerCategorias(): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.nombre, c.slug, c.descripcion, c.id_padre,
                    COUNT(p.id) as total_productos
             FROM categorias c
             LEFT JOIN productos p ON p.id_categoria = c.id AND p.activo = 1 AND p.deleted_at IS NULL
             WHERE c.activo = 1
             GROUP BY c.id
             ORDER BY c.id_padre IS NULL DESC, c.nombre ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene productos destacados (los más recientes)
     */
    public function obtenerDestacados(int $limite = 8): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre, slug, descripcion, precio, stock, imagen_url, id_categoria, created_at
             FROM productos
             WHERE activo = 1 AND deleted_at IS NULL
             ORDER BY created_at DESC
             LIMIT :limite"
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        $productos = $stmt->fetchAll();

        foreach ($productos as &$p) {
            $p['precio_formateado'] = '$' . number_format((float)$p['precio'] / 100, 0, ',', '.');
            $p['precio'] = (int)$p['precio'];
            $p['stock'] = (int)$p['stock'];
            $p['sin_stock'] = (int)$p['stock'] <= 0;
        }

        return $productos;
    }

    /**
     * Obtiene las reseñas de un producto con paginación
     */
    public function listarResenasProducto(int $productoId, int $pagina, int $porPagina): array
    {
        // Contar el total de reseñas
        $countSql = "SELECT COUNT(*) as total FROM resenas WHERE id_producto = :id";
        $stmtCount = $this->db->prepare($countSql);
        $stmtCount->execute([':id' => $productoId]);
        $total = (int)$stmtCount->fetch()['total'];

        // Calcular paginación
        $offset = ($pagina - 1) * $porPagina;

        // Traer reseñas con el nombre del usuario 
        $sql = "SELECT r.id, r.calificacion, r.comentario, r.created_at, 
                       u.nombre, u.apellido
                FROM resenas r
                INNER JOIN usuarios u ON r.id_usuario = u.id
                WHERE r.id_producto = :id
                ORDER BY r.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $productoId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $resenas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'resenas' => $resenas,
            'total'   => $total
        ];
    }

    /**
     * Guarda una nueva reseña en la base de datos
     */
    public function crearResena(array $data): array
    {
        $sql = "INSERT INTO resenas (id_producto, id_usuario, calificacion, comentario)
                VALUES (:id_producto, :id_usuario, :calificacion, :comentario)";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_producto', $data['id_producto'], PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $data['id_usuario'], PDO::PARAM_INT);
        $stmt->bindValue(':calificacion', $data['calificacion'], PDO::PARAM_INT);
        $stmt->bindValue(':comentario', $data['comentario'], PDO::PARAM_STR);
        $stmt->execute();

        // Obtener el ID de la reseña recién creada
        $idResena = (int)$this->db->lastInsertId();

        // Devolver la reseña completa con el nombre del usuario para que el Frontend la muestre al instante
        $fetchSql = "SELECT r.*, u.nombre, u.apellido 
                     FROM resenas r 
                     JOIN usuarios u ON r.id_usuario = u.id 
                     WHERE r.id = :id";
        $fetchStmt = $this->db->prepare($fetchSql);
        $fetchStmt->execute([':id' => $idResena]);
        
        return $fetchStmt->fetch(PDO::FETCH_ASSOC);
    }
}