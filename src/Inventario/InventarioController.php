<?php

declare(strict_types=1);

/**
 * InventarioController - Gestión de stock y movimientos
 */
namespace App\Inventario;

use App\Core\{Request, Response};

class InventarioController
{
    private InventarioService $service;

    public function __construct()
    {
        $this->service = new InventarioService(new InventarioRepository());
    }

    /**
     * GET /api/inventario
     * Verifica disponibilidad de stock para productos
     */
    public function verificar(Request $request, Response $response, array $params): void
    {
        try {
            $productoIds = $request->getQuery('producto_ids', '');
            $ids = array_filter(array_map('intval', explode(',', $productoIds)));

            if (empty($ids)) {
                $response->error('VALIDATION_ERROR', 'Debe especificar al menos un producto_id.', 422);
                return;
            }

            $disponibilidad = $this->service->verificarDisponibilidad($ids);
            $response->json($disponibilidad);

        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al verificar inventario.', 500);
        }
    }

    /**
     * GET /api/inventario/movimientos
     * Lista movimientos de inventario (admin)
     */
    public function movimientos(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user || !in_array($user['rol'], ['admin', 'supervisor', 'vendedor'])) {
            $response->error('INSUFFICIENT_PERMISSIONS', 'Acceso denegado.', 403);
            return;
        }

        try {
            $productoId = $request->getQuery('producto_id');
            $pagina = max(1, (int)($request->getQuery('pagina', 1)));
            $porPagina = min(100, max(1, (int)($request->getQuery('por_pagina', 20))));

            $resultado = $this->service->listarMovimientos(
                productoId: $productoId ? (int)$productoId : null,
                pagina: $pagina,
                porPagina: $porPagina
            );

            $response->paginated($resultado['movimientos'], $resultado['total'], $pagina, $porPagina);

        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al listar movimientos.', 500);
        }
    }

    /**
     * GET /api/inventario/alertas
     * Obtiene productos con stock bajo (admin)
     */
    public function alertas(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user || !in_array($user['rol'], ['admin', 'supervisor', 'vendedor'])) {
            $response->error('INSUFFICIENT_PERMISSIONS', 'Acceso denegado.', 403);
            return;
        }

        try {
            $alertas = $this->service->obtenerAlertasStock();
            $response->json($alertas);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al obtener alertas.', 500);
        }
    }

    /**
     * POST /api/inventario/ajustar
     * Ajusta el stock de un producto (admin)
     */
    public function ajustar(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user || !in_array($user['rol'], ['admin', 'supervisor', 'vendedor'])) {
            $response->error('INSUFFICIENT_PERMISSIONS', 'Acceso denegado.', 403);
            return;
        }

        try {
            $data = $request->getBody();
            $request->validateRequired(['producto_id', 'cantidad', 'tipo', 'motivo']);

            $this->service->ajustarStock(
                productoId: (int)$data['producto_id'],
                cantidad: (int)$data['cantidad'],
                tipo: $data['tipo'],
                motivo: $data['motivo'],
                userId: (int)$user['id']
            );

            $response->json(['mensaje' => 'Stock ajustado exitosamente.'], 200);

        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            $response->error('STOCK_ERROR', $e->getMessage(), 409);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al ajustar stock.', 500);
        }
    }
}
