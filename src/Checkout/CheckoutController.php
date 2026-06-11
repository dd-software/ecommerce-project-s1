<?php

declare(strict_types=1);

/**
 * CheckoutController - Procesa la creación de pedidos
 */
namespace App\Checkout;

use App\Core\{Request, Response};

class CheckoutController
{
    private CheckoutService $service;

    public function __construct()
    {
        $this->service = new CheckoutService(new CheckoutRepository());
    }

    /**
     * POST /api/checkout
     * Crea un nuevo pedido desde el carrito
     */
    public function crearPedido(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user) {
            $response->error('TOKEN_INVALID', 'Debe iniciar sesión para realizar un pedido.', 401);
            return;
        }

        try {
            $data = $request->getBody();
            $request->validateRequired(['carrito_id', 'direccion_envio']);

            $pedido = $this->service->crearPedido(
                userId: (int)$user['id'],
                carritoId: (int)$data['carrito_id'],
                direccionEnvio: $data['direccion_envio'],
                telefono: $data['telefono'] ?? '',
                notas: $data['notas'] ?? '',
                cuponCodigo: $data['cupon'] ?? null
            );

            $response->json($pedido, 201);

        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'stock') ? 'INSUFFICIENT_STOCK' : 'CHECKOUT_ERROR';
            $response->error($code, $e->getMessage(), 422);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al crear el pedido.', 500);
        }
    }

    /**
     * GET /api/pedidos
     * Lista los pedidos del usuario autenticado
     */
    public function misPedidos(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user) {
            $response->error('TOKEN_INVALID', 'Autenticación requerida.', 401);
            return;
        }

        try {
            $pedidos = $this->service->obtenerPedidosUsuario((int)$user['id']);
            $response->json($pedidos);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al obtener pedidos.', 500);
        }
    }

    /**
     * GET /api/pedidos/{id}
     * Obtiene el detalle de un pedido específico
     */
    public function detallePedido(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user) {
            $response->error('TOKEN_INVALID', 'Autenticación requerida.', 401);
            return;
        }

        try {
            $pedidoId = (int)$params['id'];
            $pedido = $this->service->obtenerPedido($pedidoId);

            if (!$pedido) {
                $response->error('ORDER_NOT_FOUND', 'Pedido no encontrado.', 404);
                return;
            }

            // Verificar que el pedido pertenece al usuario (o es admin)
            $esAdmin = $user['rol'] === 'admin';
            if (!$esAdmin && (int)$pedido['id_usuario'] !== (int)$user['id']) {
                $response->error('INSUFFICIENT_PERMISSIONS', 'No tienes permiso para ver este pedido.', 403);
                return;
            }

            $response->json($pedido);

        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al obtener el pedido.', 500);
        }
    }
}
