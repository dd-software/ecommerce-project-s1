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
     * POST /api/admin/tipo-cambio
     * Actualiza el tipo de cambio del dólar (CLP a USD)
     */
    public function actualizarTipoCambio(Request $request, Response $response, array $params): void
    {
        try {
            $data = $request->getBody();
            $request->validateRequired(['valor']);
            $nuevoValor = (int)$data['valor'];

            if ($nuevoValor <= 0) {
                throw new \InvalidArgumentException('El valor del dólar debe ser mayor a cero.');
            }

            // Actualizar en el archivo .env
            $rutaEnv = dirname(__DIR__, 2) . '/.env';
            if (file_exists($rutaEnv)) {
                $contenido = file_get_contents($rutaEnv);
                if (preg_match('/^PAYPAL_EXCHANGE_RATE\s*=\s*\d+/m', $contenido)) {
                    $contenido = preg_replace('/^PAYPAL_EXCHANGE_RATE\s*=\s*\d+/m', 'PAYPAL_EXCHANGE_RATE=' . $nuevoValor, $contenido);
                } else {
                    $contenido = rtrim($contenido) . "\nPAYPAL_EXCHANGE_RATE=" . $nuevoValor . "\n";
                }
                file_put_contents($rutaEnv, $contenido);
            }

            $_ENV['PAYPAL_EXCHANGE_RATE'] = (string)$nuevoValor;
            putenv("PAYPAL_EXCHANGE_RATE={$nuevoValor}");

            $response->json([
                'success' => true,
                'mensaje' => 'Tipo de cambio actualizado correctamente a $' . $nuevoValor . ' CLP.',
                'exchange_rate' => $nuevoValor
            ]);

        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al actualizar el tipo de cambio.', 500);
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

    public function obtenerProducto(Request $request, Response $response, array $params): void
    {
        try {
            $producto = $this->service->obtenerProductoPorId((int)$params['id']);
            $response->json($producto);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al obtener producto.', 500);
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

    public function toggleUsuario(Request $request, Response $response, array $params): void
    {
        try {
            $id = (int)$params['id'];
            $data = $request->getBody();
            $activo = isset($data['activo']) ? (int)$data['activo'] : null;

            // Evitar que el administrador se deshabilite a sí mismo
            $currentUser = $request->getAttribute('authenticated_user');
            if ($currentUser && (int)$currentUser['id'] === $id && $activo === 0) {
                $response->error('BAD_REQUEST', 'No puedes deshabilitar tu propia cuenta.', 400);
                return;
            }

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

    /**
     * GET /api/admin/usuarios/{id}
     */
    public function obtenerUsuario(Request $request, Response $response, array $params): void
    {
        try {
            $id = (int)$params['id'];
            $usuario = $this->service->obtenerUsuario($id);
            $response->json($usuario);
        } catch (\RuntimeException $e) {
            $response->error('NOT_FOUND', $e->getMessage(), 404);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al obtener detalles del usuario.', 500);
        }
    }

    /**
     * PUT /api/admin/usuarios/{id}
     */
    public function actualizarUsuario(Request $request, Response $response, array $params): void
    {
        try {
            $id = (int)$params['id'];
            $data = $request->getBody();
            $this->service->actualizarUsuario($id, $data);
            $response->json(['mensaje' => 'Usuario actualizado correctamente.']);
        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            $response->error('NOT_FOUND', $e->getMessage(), 404);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al actualizar usuario.', 500);
        }
    }

    /**
     * DELETE /api/admin/usuarios/{id}
     */
    public function eliminarUsuario(Request $request, Response $response, array $params): void
    {
        try {
            $id = (int)$params['id'];

            // Evitar que el administrador se elimine a sí mismo
            $currentUser = $request->getAttribute('authenticated_user');
            if ($currentUser && (int)$currentUser['id'] === $id) {
                $response->error('BAD_REQUEST', 'No puedes eliminarte a ti mismo.', 400);
                return;
            }

            $this->service->eliminarUsuario($id);
            $response->json(['mensaje' => 'Usuario eliminado correctamente.']);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al eliminar usuario.', 500);
        }
    }
}
