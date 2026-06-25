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
    /**
     * GET /api/pagos/paypal/config
     * Devuelve el Client ID de PayPal para inicializar el SDK en el frontend.
     * Retorna 'test' si las credenciales no han sido configuradas correctamente.
     */
    public function getPayPalConfig(Request $request, Response $response, array $params): void
    {
        $clientId = defined('PAYPAL_CLIENT_ID') ? PAYPAL_CLIENT_ID : '';

        // Detectar valores placeholder o inválidos
        $esPlaceholder = (
            empty($clientId)
            || $clientId === 'test'
            || $clientId === 'sb'
            || strlen($clientId) < 20
            || str_contains(strtolower($clientId), 'placeholder')
            || str_contains(strtolower($clientId), 'replace')
        );

        $response->json([
            'client_id'   => $esPlaceholder ? 'test' : $clientId,
            'configurado' => !$esPlaceholder,
        ]);
    }

    /**
     * POST /api/pagos/paypal/create
     * Crea una orden en PayPal y devuelve el paypal_order_id para el SDK frontend.
     */
    public function createPayPalOrder(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user) {
            $response->error('TOKEN_INVALID', 'Autenticación requerida.', 401);
            return;
        }

        try {
            $data = $request->getBody();
            if (empty($data['carrito_id'])) {
                $response->error('VALIDATION_ERROR', 'carrito_id requerido.', 422);
                return;
            }

            // Validar que las credenciales de PayPal estén configuradas antes de intentar la llamada
            $clientId = defined('PAYPAL_CLIENT_ID') ? PAYPAL_CLIENT_ID : '';
            $secret   = defined('PAYPAL_SECRET')    ? PAYPAL_SECRET    : '';
            if (
                empty($clientId) || strlen($clientId) < 20
                || empty($secret)  || strlen($secret)   < 20
                || str_contains(strtolower($clientId), 'placeholder')
                || str_contains(strtolower($secret),   'placeholder')
                || $clientId === 'sb'
            ) {
                $response->error(
                    'PAYPAL_NOT_CONFIGURED',
                    'Las credenciales de PayPal no están configuradas. ' .
                    'Actualiza PAYPAL_CLIENT_ID y PAYPAL_SECRET en el archivo .env con credenciales reales de Sandbox.',
                    503
                );
                return;
            }

            $paypalService = new PayPalService();
            $result = $paypalService->createOrder((int)$data['carrito_id'], (int)$user['id']);
            $response->json($result);

        } catch (\Exception $e) {
            // Loggear el error completo para facilitar el diagnóstico
            error_log('[PayPal createOrder] ' . $e->getMessage());
            $response->error('PAYPAL_CREATE_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/pagos/paypal/capture
     * Captura los fondos de una orden PayPal aprobada por el comprador.
     */
    public function capturePayPalOrder(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user) {
            $response->error('TOKEN_INVALID', 'Autenticación requerida.', 401);
            return;
        }

        try {
            $data = $request->getBody();
            if (empty($data['paypal_order_id'])) {
                $response->error('VALIDATION_ERROR', 'paypal_order_id requerido.', 422);
                return;
            }

            $paypalService = new PayPalService();
            $result = $paypalService->captureOrder($data['paypal_order_id'], (int)$user['id']);
            $response->json($result);

        } catch (\Exception $e) {
            // Loggear el error completo para facilitar el diagnóstico
            error_log('[PayPal captureOrder] ' . $e->getMessage());
            $response->error('PAYPAL_CAPTURE_ERROR', $e->getMessage(), 500);
        }
    }
}
