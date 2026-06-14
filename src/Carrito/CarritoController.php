<?php

declare(strict_types=1);

/**
 * CarritoController - Maneja el carrito de compras
 */
namespace App\Carrito;

use App\Core\{Request, Response};

class CarritoController
{
    private CarritoService $service;

    public function __construct()
    {
        $this->service = new CarritoService(new CarritoRepository());
    }

    /**
     * GET /api/carrito
     * Obtiene el carrito actual
     */
    public function ver(Request $request, Response $response, array $params): void
    {
        try {
            ['userId' => $userId, 'sessionId' => $sessionId] = $this->obtenerContextoUsuario($request);
            
            $carrito = $this->service->obtenerCarrito(
                userId: $userId,
                sessionId: $sessionId
            );

            $response->json($carrito);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al obtener carrito.', 500);
        }
    }

    /**
     * POST /api/carrito
     * Agrega un producto al carrito
     */
    public function agregar(Request $request, Response $response, array $params): void
    {
        try {
            $data = $request->getBody();
            $request->validateRequired(['producto_id', 'cantidad']);

            $productoId = (int)$data['producto_id'];
            $cantidad   = (int)$data['cantidad'];

            if ($cantidad < 1 || $cantidad > 999) {
                $response->error('INVALID_QUANTITY', 'La cantidad debe estar entre 1 y 999.', 422, 'cantidad');
                return;
            }

            ['userId' => $userId, 'sessionId' => $sessionId] = $this->obtenerContextoUsuario($request, $data);

            $this->service->agregarItem(
                productoId: $productoId,
                cantidad: $cantidad,
                userId: $userId,
                sessionId: $sessionId
            );

            // Retornar carrito actualizado
            $carrito = $this->obtenerCarritoConContexto($userId, $sessionId);
            $response->json($carrito, 201);

        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            $response->error('INSUFFICIENT_STOCK', $e->getMessage(), 409);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al agregar al carrito.', 500);
        }
    }

    /**
     * PATCH /api/carrito/{id}
     * Actualiza la cantidad de un item
     */
    public function actualizar(Request $request, Response $response, array $params): void
    {
        try {
            $itemId = (int)$params['id'];
            $data = $request->getBody();

            if (!isset($data['cantidad'])) {
                $response->error('VALIDATION_ERROR', 'Cantidad requerida.', 422, 'cantidad');
                return;
            }

            $cantidad = (int)$data['cantidad'];

            if ($cantidad < 0) {
                $response->error('INVALID_QUANTITY', 'La cantidad no puede ser negativa.', 422, 'cantidad');
                return;
            }

            if ($cantidad === 0) {
                $this->service->eliminarItem($itemId);
            } else {
                $this->service->actualizarCantidad($itemId, $cantidad);
            }

            ['userId' => $userId, 'sessionId' => $sessionId] = $this->obtenerContextoUsuario($request);
            $carrito = $this->obtenerCarritoConContexto($userId, $sessionId);
            $response->json($carrito);

        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            $response->error('INSUFFICIENT_STOCK', $e->getMessage(), 409);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al actualizar carrito.', 500);
        }
    }

    /**
     * DELETE /api/carrito/{id}
     * Elimina un item del carrito
     */
    public function eliminar(Request $request, Response $response, array $params): void
    {
        try {
            $itemId = (int)$params['id'];
            $this->service->eliminarItem($itemId);

            ['userId' => $userId, 'sessionId' => $sessionId] = $this->obtenerContextoUsuario($request);
            $carrito = $this->obtenerCarritoConContexto($userId, $sessionId);
            $response->json($carrito);

        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al eliminar del carrito.', 500);
        }
    }

    /**
     * DELETE /api/carrito
     * Vacía el carrito completo
     */
    public function vaciar(Request $request, Response $response, array $params): void
    {
        try {
            ['userId' => $userId, 'sessionId' => $sessionId] = $this->obtenerContextoUsuario($request);

            $this->service->vaciarCarrito(
                userId: $userId,
                sessionId: $sessionId
            );

            $response->json(['mensaje' => 'Carrito vaciado exitosamente.']);

        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al vaciar carrito.', 500);
        }
    }

    /**
     * POST /api/carrito/sincronizar
     * Sincroniza carrito de visitante con usuario al hacer login
     */
    public function sincronizar(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user) {
            $response->error('TOKEN_INVALID', 'Autenticación requerida.', 401);
            return;
        }

        try {
            $data = $request->getBody();
            $sessionId = $data['session_id'] ?? null;

            if ($sessionId) {
                $this->service->sincronizarCarrito((int)$user['id'], $sessionId);
            }

            $carrito = $this->service->obtenerCarrito(userId: (int)$user['id']);
            $response->json($carrito);

        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al sincronizar carrito.', 500);
        }
    }

    /**
     * MÉTODO PRIVADO: Extrae contexto de usuario/session de Request
     * Refactor para eliminar redundancia (DRY principle)
     * 
     * @param Request $request
     * @param array $data Datos opcionales del body (para agregar, contiene session_id)
     * @return array ['userId' => ?int, 'sessionId' => ?string]
     */
    private function obtenerContextoUsuario(Request $request, array $data = []): array
    {
        $user = $request->getAttribute('authenticated_user');
        $userId = $user ? (int)$user['id'] : null;
        
        // Intenta obtener sessionId de: body → headers → null
        $sessionId = $data['session_id'] 
            ?? $request->getHeader('X-Session-Id')
            ?? null;

        return ['userId' => $userId, 'sessionId' => $sessionId];
    }

    /**
     * MÉTODO PRIVADO: Obtiene carrito usando contexto extraído
     * Refactor para eliminar repetición de llamadas a obtenerCarrito()
     * 
     * @param ?int $userId
     * @param ?string $sessionId
     * @return array Carrito con items y totales
     */
    private function obtenerCarritoConContexto(?int $userId, ?string $sessionId): array
    {
        return $this->service->obtenerCarrito(
            userId: $userId,
            sessionId: $sessionId
        );
    }
}