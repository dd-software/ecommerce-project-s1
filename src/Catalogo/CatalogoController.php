<?php

declare(strict_types=1);

/**
 * CatalogoController - Endpoints de catálogo de productos
 */
namespace App\Catalogo;

use App\Core\{Request, Response};

class CatalogoController
{
    private CatalogoService $service;

    public function __construct()
    {
        $this->service = new CatalogoService(new CatalogoRepository());
    }

    /**
     * GET /api/catalogo
     * Lista productos con filtros, búsqueda y paginación
     */
    public function listar(Request $request, Response $response, array $params): void
    {
        try {
            $categoria = $request->getQuery('categoria');
            $busqueda  = $request->getQuery('q');
            $precioMin = $request->getQuery('precio_min');
            $precioMax = $request->getQuery('precio_max');
            $enStock   = $request->getQuery('en_stock');
            $ordenar   = $request->getQuery('ordenar', 'relevancia');
            $pagina    = max(1, (int)($request->getQuery('pagina', 1)));
            $porPagina = min(100, max(1, (int)($request->getQuery('por_pagina', 20))));

            $resultado = $this->service->listarProductos(
                categoriaId: $categoria ? (int)$categoria : null,
                busqueda: $busqueda,
                precioMin: $precioMin !== null ? (int)$precioMin : null,
                precioMax: $precioMax !== null ? (int)$precioMax : null,
                enStock: $enStock !== null ? filter_var($enStock, FILTER_VALIDATE_BOOLEAN) : null,
                ordenar: $ordenar,
                pagina: $pagina,
                porPagina: $porPagina
            );

            $response->paginated(
                $resultado['productos'],
                $resultado['total'],
                $pagina,
                $porPagina
            );

        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al obtener productos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/catalogo/{id}
     * Obtiene el detalle de un producto
     */
    public function detalle(Request $request, Response $response, array $params): void
    {
        try {
            $id = (int)$params['id'];
            $producto = $this->service->obtenerProducto($id);

            if (!$producto) {
                $response->error('PRODUCT_NOT_FOUND', 'Producto no encontrado.', 404);
                return;
            }

            $response->json($producto);

        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al obtener producto.', 500);
        }
    }

    /**
     * GET /api/catalogo/categorias
     * Lista todas las categorías activas
     */
    public function categorias(Request $request, Response $response, array $params): void
    {
        try {
            $categorias = $this->service->listarCategorias();
            $response->json($categorias);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al obtener categorías.', 500);
        }
    }

    /**
     * GET /api/catalogo/destacados
     * Obtiene productos destacados (más vendidos)
     */
    public function destacados(Request $request, Response $response, array $params): void
    {
        try {
            $productos = $this->service->obtenerDestacados();
            $response->json($productos);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al obtener destacados.', 500);
        }
    }
}
