<?php

declare(strict_types=1);

/**
 * InventarioController — Gestión de stock y movimientos de inventario.
 *
 * CAMBIOS ARQUITECTÓNICOS Y DE INTEGRACIÓN APLICADOS:
 * 1. DEPENDENCY INJECTION: constructor con inyección de dependencia opcional
 *    de InventarioService para facilitar testing.
 * 2. SEGURIDAD CENTRALIZADA: requireRole() centraliza la autorización RBAC.
 * 3. COMPATIBILIDAD DE VARIABLES: admite tanto parámetros en español (producto_ids, pagina, etc.)
 *    como en inglés (product_ids, page, etc.) para evitar fallos de integración.
 * 4. COMPATIBILIDAD DE ROLES: verifica la propiedad 'rol' (en español) provista por JwtMiddleware.
 * 5. EVITA DOBLE ENVOLTORIO: las respuestas usan la estructura de Response.php sin doble-envoltura.
 */

namespace App\Inventario;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;

class InventarioController
{
    /**
     * Roles autorizados para operaciones administrativas de inventario.
     */
    private const ALLOWED_ROLES = ['admin', 'supervisor', 'vendedor'];

    private readonly InventarioService $service;

    /**
     * Constructor con inyección de dependencias opcional.
     */
    public function __construct(?InventarioService $service = null)
    {
        $this->service = $service ?? new InventarioService(new InventarioRepository());
    }

    /**
     * GET /api/inventario
     * Verifica disponibilidad de stock para uno o más productos.
     * Ruta pública — no requiere autenticación.
     *
     * Query params:
     *   productoIds, producto_ids, o product_ids (string): IDs separados por coma. Ej: "1,2,42"
     */
    public function verificar(Request $request, Response $response, array $params): void
    {
        $rawIds = $request->getQuery('producto_ids') 
            ?? $request->getQuery('productoIds') 
            ?? $request->getQuery('product_ids') 
            ?? '';

        if (empty($rawIds)) {
            $response->error('VALIDATION_ERROR', 'Debe especificar al menos un producto_id.', 422);
            return;
        }

        // Convertir a enteros y filtrar valores inválidos (0, negativos)
        $ids = array_values(
            array_filter(
                array_map('intval', explode(',', (string)$rawIds)),
                fn(int $id): bool => $id > 0
            )
        );

        if (empty($ids)) {
            $response->error('VALIDATION_ERROR', 'Debe especificar al menos un producto_id válido.', 422);
            return;
        }

        try {
            $disponibilidad = $this->service->verificarDisponibilidad($ids);
            // $response->json() envolverá automáticamente en {"success": true, "data": $disponibilidad}
            $response->json($disponibilidad);
        } catch (\Throwable $e) {
            $response->error('SERVER_ERROR', 'Error al verificar inventario.', 500);
        }
    }

    /**
     * GET /api/inventario/movimientos
     * Lista movimientos de inventario con paginación.
     * Requiere rol: admin, supervisor o vendedor.
     *
     * Query params:
     *   producto_id o product_id (int, opcional)
     *   pagina o page (int, default 1)
     *   por_pagina o per_page (int, default 20)
     */
    public function movimientos(Request $request, Response $response, array $params): void
    {
        if (!$this->requireRole($request, $response)) {
            return;
        }

        try {
            $productoId = $request->getQuery('producto_id') ?? $request->getQuery('product_id');
            
            $rawPage = $request->getQuery('pagina') ?? $request->getQuery('page') ?? '1';
            $pagina = max(1, (int)$rawPage);
            
            $rawPerPage = $request->getQuery('por_pagina') ?? $request->getQuery('per_page') ?? '20';
            $porPagina = min(100, max(1, (int)$rawPerPage));

            $resultado = $this->service->listarMovimientos(
                productoId: $productoId !== null ? (int)$productoId : null,
                pagina: $pagina,
                porPagina: $porPagina
            );

            $response->paginated(
                $resultado['movimientos'],
                $resultado['total'],
                $pagina,
                $porPagina
            );
        } catch (\Throwable $e) {
            $response->error('SERVER_ERROR', 'Error al listar movimientos.', 500);
        }
    }

    /**
     * GET /api/inventario/alertas
     * Productos con stock igual o por debajo del stock mínimo configurado.
     * Requiere rol: admin, supervisor o vendedor.
     */
    public function alertas(Request $request, Response $response, array $params): void
    {
        if (!$this->requireRole($request, $response)) {
            return;
        }

        try {
            $alertas = $this->service->obtenerAlertasStock();
            $response->json($alertas);
        } catch (\Throwable $e) {
            $response->error('SERVER_ERROR', 'Error al obtener alertas de stock.', 500);
        }
    }

    /**
     * POST /api/inventario/ajustar
     * Ajusta el stock de un producto manualmente.
     * Requiere rol: admin, supervisor o vendedor.
     *
     * Body JSON:
     *   producto_id o product_id (int)    : ID del producto
     *   cantidad o quantity      (int)    : Cantidad a ingresar (entrada) o nuevo valor absoluto (ajuste)
     *   tipo                     (string) : "entrada" | "ajuste"
     *   motivo                   (string) : Descripción del motivo del movimiento
     */
    public function ajustar(Request $request, Response $response, array $params): void
    {
        if (!$this->requireRole($request, $response)) {
            return;
        }

        try {
            $data = $request->getBody();

            // Normalizar las llaves del request
            $productoIdKey = isset($data['producto_id']) ? 'producto_id' : (isset($data['product_id']) ? 'product_id' : null);
            $cantidadKey = isset($data['cantidad']) ? 'cantidad' : (isset($data['quantity']) ? 'quantity' : null);

            if ($productoIdKey === null || $cantidadKey === null || !isset($data['tipo']) || !isset($data['motivo'])) {
                $response->error('VALIDATION_ERROR', 'Campos requeridos faltantes: producto_id, cantidad, tipo y motivo son necesarios.', 422);
                return;
            }

            $productoId = filter_var($data[$productoIdKey], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $cantidad = filter_var($data[$cantidadKey], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

            if ($productoId === false) {
                $response->error('VALIDATION_ERROR', 'El ID del producto debe ser un entero positivo.', 422);
                return;
            }

            if ($cantidad === false) {
                $response->error('VALIDATION_ERROR', 'La cantidad debe ser un entero no negativo.', 422);
                return;
            }

            $tipo = trim((string)$data['tipo']);
            $motivo = trim((string)$data['motivo']);

            if (strlen($motivo) < 3 || strlen($motivo) > 500) {
                $response->error('VALIDATION_ERROR', 'El motivo debe tener entre 3 y 500 caracteres.', 422);
                return;
            }

            $user = $request->getAttribute('authenticated_user');

            $this->service->ajustarStock(
                productoId: $productoId,
                cantidad: $cantidad,
                tipo: $tipo,
                motivo: $motivo,
                userId: (int)$user['id']
            );

            $response->json(['mensaje' => 'Stock ajustado exitosamente.'], 200);

        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            $response->error('STOCK_ERROR', $e->getMessage(), 409);
        } catch (\Throwable $e) {
            $response->error('SERVER_ERROR', 'Error al ajustar stock: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verifica que el usuario autenticado tenga uno de los roles permitidos.
     * Retorna true si está autorizado, false si ya se envió una respuesta 403.
     */
    private function requireRole(Request $request, Response $response): bool
    {
        $user = $request->getAttribute('authenticated_user');

        if ($user === null) {
            $response->error('UNAUTHORIZED', 'Autenticación requerida.', 401);
            return false;
        }

        // Usamos la propiedad 'rol' (español) y comparación estricta
        if (!in_array($user['rol'], self::ALLOWED_ROLES, true)) {
            $response->error('INSUFFICIENT_PERMISSIONS', 'Acceso denegado.', 403);
            return false;
        }

        return true;
    }
}
