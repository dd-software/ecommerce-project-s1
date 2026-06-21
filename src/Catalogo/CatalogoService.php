<?php

declare(strict_types=1);

/**
 * CatalogoService - Lógica de negocio del catálogo
 */
namespace App\Catalogo;

class CatalogoService
{
    private CatalogoRepository $repository;

    public function __construct(CatalogoRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Lista productos con filtros y paginación
     */
    public function listarProductos(
        ?int $categoriaId = null,
        ?string $busqueda = null,
        ?int $precioMin = null,
        ?int $precioMax = null,
        ?bool $enStock = null,
        string $ordenar = 'relevancia',
        int $pagina = 1,
        int $porPagina = 20,
        ?array $categoriaIds = null,
        ?array $marcas = null
    ): array {
        // Solo productos activos (RN-A01)
        $filtros = ['activo' => true];

        if ($categoriaId !== null) {
            $filtros['categoria_id'] = $categoriaId;
        }
        if (!empty($categoriaIds)) {
            $filtros['categoria_ids'] = $categoriaIds;
        }
        if (!empty($marcas)) {
            $filtros['marcas'] = $marcas;
        }
        if ($busqueda !== null && trim($busqueda) !== '') {
            $filtros['busqueda'] = trim($busqueda);
        }
        if ($precioMin !== null) {
            $filtros['precio_min'] = $precioMin;
        }
        if ($precioMax !== null) {
            $filtros['precio_max'] = $precioMax;
        }
        if ($enStock === true) {
            $filtros['en_stock'] = true;
        }

        return $this->repository->buscarProductos($filtros, $ordenar, $pagina, $porPagina);
    }

    /**
     * Obtiene el detalle completo de un producto
     */
    public function obtenerProducto(int $id): ?array
    {
        return $this->repository->buscarPorId($id);
    }

    /**
     * Lista categorías activas
     */
    public function listarCategorias(): array
    {
        return $this->repository->obtenerCategorias();
    }

    /**
     * Lista marcas activas con conteo
     */
    public function listarMarcas(): array
    {
        return $this->repository->obtenerMarcas();
    }

    /**
     * Obtiene productos destacados
     */
    public function obtenerDestacados(int $limite = 8): array
    {
        return $this->repository->obtenerDestacados($limite);
    }

    /**
     * Verifica disponibilidad de stock (usado por otros módulos)
     * Retorna true si hay stock suficiente
     */
    public function verificarStock(int $productoId, int $cantidad): bool
    {
        $producto = $this->repository->buscarPorId($productoId);
        if (!$producto) {
            return false;
        }
        return $producto['stock'] >= $cantidad;
    }

    /**
     * Obtiene el stock actual de un producto
     */
    public function obtenerStock(int $productoId): int
    {
        $producto = $this->repository->buscarPorId($productoId);
        return $producto ? (int)$producto['stock'] : 0;
    }
}
