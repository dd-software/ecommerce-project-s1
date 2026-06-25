<?php

declare(strict_types=1);

/**
 * PagosController - Inicio de pago (MercadoPago / simulado), confirmación y webhook.
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
     * POST /api/pagos/iniciar
     * Inicia el pago de un pedido. Devuelve init_point (MercadoPago) o estado aprobado (simulado).
     */
    public function iniciar(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user) {
            $response->error('TOKEN_INVALID', 'Autenticación requerida.', 401);
            return;
        }

        try {
            $data = $request->getBody();
            $request->validateRequired(['pedido_id']);

            $resultado = $this->service->iniciarPago(
                pedidoId: (int)$data['pedido_id'],
                userId: (int)$user['id'],
                payerEmail: $data['email'] ?? ($user['email'] ?: 'comprador@quadcore.cl')
            );

            $response->json($resultado);

        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            $response->error('PAYMENT_ERROR', $e->getMessage(), 402);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al iniciar el pago.', 500);
        }
    }

    /**
     * POST /api/pagos/confirmar  { payment_id }
     * Lo llama el front al volver de MercadoPago: confirma contra la API de MP.
     * (El webhook hace lo mismo del lado servidor; esto cubre el retorno inmediato del usuario.)
     */
    public function confirmar(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user) {
            $response->error('TOKEN_INVALID', 'Autenticación requerida.', 401);
            return;
        }

        try {
            $paymentId = (string)$request->getBody('payment_id', '');
            if ($paymentId === '') {
                $response->error('VALIDATION_ERROR', 'payment_id requerido.', 422);
                return;
            }

            $resultado = $this->service->confirmarPagoMercadoPago($paymentId);
            $response->json($resultado);

        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al confirmar el pago.', 500);
        }
    }

    /**
     * GET /api/pagos/estado/{pedidoId}
     * Estado del pago de un pedido (polling del front).
     */
    public function estado(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user) {
            $response->error('TOKEN_INVALID', 'Autenticación requerida.', 401);
            return;
        }

        try {
            $estado = $this->service->consultarEstadoPago((int)$params['pedidoId']);
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
     * POST /api/pagos/webhook  (público — lo llama MercadoPago)
     * MP avisa con ?type=payment&data.id=... (o JSON {type, data:{id}}). Confirmamos contra su API.
     */
    public function webhook(Request $request, Response $response, array $params): void
    {
        try {
            // PHP convierte "data.id" del query string en "data_id".
            $tipo = (string)($request->getQuery('type') ?? $request->getBody('type', ''));
            $bodyData = $request->getBody('data');
            $dataId = (string)(
                $request->getQuery('data_id')
                ?? $request->getQuery('id')
                ?? (is_array($bodyData) ? ($bodyData['id'] ?? '') : '')
            );

            $this->service->procesarWebhook($tipo, $dataId);

            // MP espera 200/201 para no reintentar.
            $response->json(['recibido' => true]);

        } catch (\Exception $e) {
            $response->error('WEBHOOK_ERROR', 'Error al procesar webhook.', 500);
        }
    }
}
