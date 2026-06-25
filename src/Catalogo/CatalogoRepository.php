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
            $where[] = "p.id_categoria = :categoria_id";
            $params[':categoria_id'] = $filtros['categoria_id'];
        }

        if (!empty($filtros['categoria_ids']) && is_array($filtros['categoria_ids'])) {
            $ph = [];
            foreach (array_values($filtros['categoria_ids']) as $i => $cid) {
                $ph[] = ":cat_$i";
                $params[":cat_$i"] = (int)$cid;
            }
            $where[] = "p.id_categoria IN (" . implode(',', $ph) . ")";
        }

        if (!empty($filtros['marcas']) && is_array($filtros['marcas'])) {
            $ph = [];
            foreach (array_values($filtros['marcas']) as $i => $m) {
                $ph[] = ":marca_$i";
                $params[":marca_$i"] = $m;
            }
            $where[] = "p.marca IN (" . implode(',', $ph) . ")";
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
        $sql = "SELECT p.id, p.nombre, p.slug, p.descripcion, p.precio, p.precio_anterior, p.stock,
                       p.imagen_url, p.id_categoria, c.nombre as categoria_nombre, p.marca,
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
            $p['precio_formateado'] = '$' . number_format($p['precio'] , 0, ',', '.');
            $p['precio'] = (int)$p['precio'];
            $p['stock'] = (int)$p['stock'];
            $p['sin_stock'] = $p['stock'] <= 0; // RN-A02
            $this->aplicarOferta($p);
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
            $producto['precio_formateado'] = '$' . number_format($producto['precio'] , 0, ',', '.');
            $producto['precio'] = (int)$producto['precio'];
            $producto['stock'] = (int)$producto['stock'];
            $producto['sin_stock'] = (int)$producto['stock'] <= 0;
            $this->aplicarOferta($producto);
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

    /** Resuelve el id de una categoría activa a partir de su slug (o null) */
    public function buscarCategoriaIdPorSlug(string $slug): ?int
    {
        $stmt = $this->db->prepare("SELECT id FROM categorias WHERE slug = :slug AND activo = 1 LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    }

    /**
     * Obtiene marcas activas con conteo de productos
     */
    public function obtenerMarcas(): array
    {
        $stmt = $this->db->query(
            "SELECT p.marca, COUNT(*) as total
             FROM productos p
             WHERE p.activo = 1 AND p.deleted_at IS NULL AND p.marca IS NOT NULL AND p.marca <> ''
             GROUP BY p.marca
             ORDER BY p.marca ASC"
        );
        return array_map(
            fn($r) => ['marca' => $r['marca'], 'total' => (int)$r['total']],
            $stmt->fetchAll()
        );
    }

    /**
     * Obtiene productos destacados (los más recientes)
     */
    public function obtenerDestacados(int $limite = 8): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre, slug, descripcion, precio, precio_anterior, stock, imagen_url, id_categoria, marca, created_at
             FROM productos
             WHERE activo = 1 AND deleted_at IS NULL
             ORDER BY created_at DESC
             LIMIT :limite"
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $this->formatearLista($stmt->fetchAll());
    }

    /** Productos en oferta (precio_anterior > precio), ordenados por mayor descuento */
    public function obtenerOfertas(int $limite = 12): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre, slug, descripcion, precio, precio_anterior, stock, imagen_url, id_categoria, marca, created_at
             FROM productos
             WHERE activo = 1 AND deleted_at IS NULL
               AND precio_anterior IS NOT NULL AND precio_anterior > precio
             ORDER BY (precio_anterior - precio) / precio_anterior DESC
             LIMIT :limite"
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $this->formatearLista($stmt->fetchAll());
    }

    /** Formatea precios/stock/oferta de una lista de productos */
    private function formatearLista(array $productos): array
    {
        foreach ($productos as &$p) {
            $p['precio_formateado'] = '$' . number_format($p['precio'], 0, ',', '.');
            $p['precio'] = (int)$p['precio'];
            $p['stock'] = (int)$p['stock'];
            $p['sin_stock'] = $p['stock'] <= 0;
            $this->aplicarOferta($p);
        }
        return $productos;
    }

    /** Agrega precio_anterior (int|null), su formato y el % de descuento */
    private function aplicarOferta(array &$p): void
    {
        $pa = isset($p['precio_anterior']) && $p['precio_anterior'] !== null ? (int)$p['precio_anterior'] : null;
        $p['precio_anterior'] = $pa;
        if ($pa && $pa > (int)$p['precio']) {
            $p['precio_anterior_formateado'] = '$' . number_format($pa, 0, ',', '.');
            $p['descuento_pct'] = (int)round((1 - (int)$p['precio'] / $pa) * 100);
        } else {
            $p['precio_anterior_formateado'] = null;
            $p['descuento_pct'] = null;
        }
    }
}
