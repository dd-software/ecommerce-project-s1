<?php

declare(strict_types=1);

/**
 * PagosController - Procesamiento de pagos y simulación Webpay
 */
namespace App\Pagos;

use App\Core\{Request, Response};

class PagosController
{
    private PagosService $service;

    public function __construct()
    {
        $this->service = new PagosService(new PagosRepository());
    }

    /**
     * POST /api/pagos/procesar
     * Procesa un pago (simulación Webpay)
     */
    public function procesar(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user) {
            $response->error('TOKEN_INVALID', 'Autenticación requerida.', 401);
            return;
        }

        try {
            $data = $request->getBody();
            $request->validateRequired(['pedido_id', 'metodo_pago']);

            $resultado = $this->service->procesarPago(
                pedidoId: (int)$data['pedido_id'],
                metodoPago: $data['metodo_pago'],
                tokenTarjeta: $data['token_tarjeta'] ?? 'sim_tok_' . bin2hex(random_bytes(8)),
                userId: (int)$user['id']
            );

            $response->json($resultado);

        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            $response->error('PAYMENT_ERROR', $e->getMessage(), 402);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al procesar el pago.', 500);
        }
    }

    /**
     * GET /api/pagos/estado/{pedidoId}
     * Consulta el estado del pago de un pedido
     */
    public function estado(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user) {
            $response->error('TOKEN_INVALID', 'Autenticación requerida.', 401);
            return;
        }

        try {
            $pedidoId = (int)$params['pedidoId'];
            $estado = $this->service->consultarEstadoPago($pedidoId);

            if (!$estado) {
                $response->error('PAYMENT_NOT_FOUND', 'No se encontró pago para este pedido.', 404);
                return;
            }

            $response->json($estado);

        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al consultar estado del pago.', 500);
        }
    }

    /**
     * POST /api/pagos/webhook
     * Webhook simulado de confirmación de pasarela de pago
     */
    public function webhook(Request $request, Response $response, array $params): void
    {
        try {
            $data = $request->getBody();

            // En producción: validar firma del webhook
            $pedidoId = (int)($data['pedido_id'] ?? 0);
            $estado = $data['estado'] ?? 'rechazado';
            $transaccionId = $data['transaccion_id'] ?? '';

            if ($pedidoId <= 0) {
                $response->error('INVALID_WEBHOOK', 'pedido_id requerido.', 422);
                return;
            }

            $this->service->procesarWebhook($pedidoId, $estado, $transaccionId);

            $response->json(['mensaje' => 'Webhook procesado exitosamente.']);

        } catch (\Exception $e) {
            $response->error('WEBHOOK_ERROR', 'Error al procesar webhook.', 500);
        }
    }
}
