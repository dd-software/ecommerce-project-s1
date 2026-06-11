<?php

declare(strict_types=1);

/**
 * AdminController - Panel de administración y gestión
 */
namespace App\Admin;

use App\Core\{Request, Response};

class AdminController
{
    private AdminService $service;

    public function __construct()
    {
        $this->service = new AdminService(new AdminRepository());
    }

    /**
     * GET /api/admin/dashboard
     * Obtiene métricas y KPIs del dashboard
     */
    public function dashboard(Request $request, Response $response, array $params): void
    {
        try {
            $metricas = $this->service->obtenerDashboard();
            $response->json($metricas);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al obtener dashboard.', 500);
        }
    }

    /**
     * GET /api/admin/productos
     * Lista todos los productos (admin, incluye inactivos)
     */
    public function listarProductos(Request $request, Response $response, array $params): void
    {
        try {
            $pagina = max(1, (int)($request->getQuery('pagina', 1)));
            $porPagina = min(100, max(1, (int)($request->getQuery('por_pagina', 20))));
            $busqueda = $request->getQuery('q');

            $resultado = $this->service->listarProductosAdmin($busqueda, $pagina, $porPagina);
            $response->paginated($resultado['productos'], $resultado['total'], $pagina, $porPagina);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al listar productos.', 500);
        }
    }

    /**
     * POST /api/admin/productos
     * Crea un nuevo producto
     */
    public function crearProducto(Request $request, Response $response, array $params): void
    {
        try {
            $data = $request->getBody();
            $request->validateRequired(['nombre', 'precio', 'id_categoria']);

            $producto = $this->service->crearProducto($data);
            $response->json($producto, 201);

        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al crear producto.', 500);
        }
    }

    /**
     * PUT /api/admin/productos/{id}
     * Actualiza un producto existente
     */
    public function actualizarProducto(Request $request, Response $response, array $params): void
    {
        try {
            $id = (int)$params['id'];
            $data = $request->getBody();

            $producto = $this->service->actualizarProducto($id, $data);
            $response->json($producto);

        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            $response->error('NOT_FOUND', $e->getMessage(), 404);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al actualizar producto.', 500);
        }
    }

    /**
     * DELETE /api/admin/productos/{id}
     * Soft-delete de un producto
     */
    public function eliminarProducto(Request $request, Response $response, array $params): void
    {
        try {
            $id = (int)$params['id'];
            $this->service->eliminarProducto($id);
            $response->json(['mensaje' => 'Producto eliminado exitosamente.']);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al eliminar producto.', 500);
        }
    }

    /**
     * GET /api/admin/pedidos
     * Lista todos los pedidos (admin)
     */
    public function listarPedidos(Request $request, Response $response, array $params): void
    {
        try {
            $estado = $request->getQuery('estado');
            $pagina = max(1, (int)($request->getQuery('pagina', 1)));
            $porPagina = min(100, max(1, (int)($request->getQuery('por_pagina', 20))));

            $resultado = $this->service->listarPedidosAdmin($estado, $pagina, $porPagina);
            $response->paginated($resultado['pedidos'], $resultado['total'], $pagina, $porPagina);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al listar pedidos.', 500);
        }
    }

    /**
     * PATCH /api/admin/pedidos/{id}/estado
     * Cambia el estado de un pedido
     */
    public function cambiarEstadoPedido(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');

        try {
            $pedidoId = (int)$params['id'];
            $data = $request->getBody();
            $request->validateRequired(['estado']);

            $this->service->cambiarEstadoPedido(
                pedidoId: $pedidoId,
                nuevoEstado: $data['estado'],
                userId: (int)$user['id'],
                comentario: $data['comentario'] ?? ''
            );

            $response->json(['mensaje' => 'Estado actualizado exitosamente.']);

        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            $response->error('STATE_ERROR', $e->getMessage(), 422);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al cambiar estado.', 500);
        }
    }

    /**
     * GET /api/admin/reportes/ventas
     * Reporte de ventas
     */
    public function reporteVentas(Request $request, Response $response, array $params): void
    {
        try {
            $periodo = $request->getQuery('periodo', 'mes');
            $reporte = $this->service->obtenerReporteVentas($periodo);
            $response->json($reporte);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al generar reporte.', 500);
        }
    }

    /**
     * GET /api/admin/usuarios
     * Lista usuarios (admin)
     */
    public function listarUsuarios(Request $request, Response $response, array $params): void
    {
        try {
            $pagina = max(1, (int)($request->getQuery('pagina', 1)));
            $porPagina = min(100, max(1, (int)($request->getQuery('por_pagina', 20))));

            $resultado = $this->service->listarUsuarios($pagina, $porPagina);
            $response->paginated($resultado['usuarios'], $resultado['total'], $pagina, $porPagina);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al listar usuarios.', 500);
        }
    }

    /**
     * PATCH /api/admin/usuarios/{id}/estado
     * Activa/desactiva un usuario
     */
    public function toggleUsuario(Request $request, Response $response, array $params): void
    {
        try {
            $id = (int)$params['id'];
            $data = $request->getBody();
            $activo = isset($data['activo']) ? (int)$data['activo'] : null;

            $this->service->cambiarEstadoUsuario($id, $activo);
            $response->json(['mensaje' => 'Estado de usuario actualizado.']);

        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al cambiar estado de usuario.', 500);
        }
    }

    /**
     * GET /api/admin/reportes/productos-mas-vendidos
     */
    public function productosMasVendidos(Request $request, Response $response, array $params): void
    {
        try {
            $reporte = $this->service->obtenerProductosMasVendidos();
            $response->json($reporte);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al obtener reporte.', 500);
        }
    }
}
