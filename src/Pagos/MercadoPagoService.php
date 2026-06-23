<?php

declare(strict_types=1);

/**
 * MercadoPagoService - Integración con API de Mercado Pago
 * Maneja creación de preferencias y consulta de pagos
 */
namespace App\Pagos;

use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;

class MercadoPagoService
{
    private string $accessToken;
    private string $publicKey;
    private bool $isDevelopment;

    public function __construct()
    {
        $this->accessToken = $_ENV['MP_ACCESS_TOKEN'] ?? '';
        $this->publicKey = $_ENV['MP_PUBLIC_KEY'] ?? '';
        $this->isDevelopment = $_ENV['APP_ENV'] === 'development' || $_ENV['APP_ENV'] === 'testing';

        if (!$this->accessToken) {
            throw new \RuntimeException('MP_ACCESS_TOKEN no configurado en variables de entorno.');
        }

        // Configurar SDK de Mercado Pago
        MercadoPagoConfig::setAccessToken($this->accessToken);
    }

    /**
     * Crea una preferencia de pago en Mercado Pago
     * @param array $pedido Datos del pedido (id, monto, cliente, etc)
     * @return array Con 'preference_id' y 'init_point' (URL de pago)
     */
    public function crearPreferencia(array $pedido): array
    {
        try {
            $client = new PreferenceClient();

            $request = [
                'items' => [
                    [
                        'id'          => (string)$pedido['id'],
                        'title'       => 'Orden #' . $pedido['id'],
                        'description' => 'Compra en Ecommerce UCT',
                        'quantity'    => 1,
                        'unit_price'  => $pedido['monto'] / 100, // Convertir centavos a pesos
                    ]
                ],
                'payer' => [
                    'email' => $pedido['email'] ?? '',
                    'first_name' => $pedido['nombre'] ?? '',
                    'last_name' => $pedido['apellido'] ?? '',
                    'phone' => [
                        'number' => $pedido['telefono'] ?? ''
                    ]
                ],
                'back_urls' => [
                    'success' => $this->getReturnUrl('success'),
                    'failure' => $this->getReturnUrl('failure'),
                    'pending' => $this->getReturnUrl('pending'),
                ],
                'auto_return' => 'approved',
                'external_reference' => (string)$pedido['id'],
                'notification_url' => $this->getWebhookUrl(),
                'statement_descriptor' => 'Ecommerce UCT',
            ];

            $response = $client->create($request);

            return [
                'success' => true,
                'preference_id' => $response->id,
                'init_point' => $response->init_point,
                'public_key' => $this->publicKey,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al crear preferencia: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Consulta el estado de un pago en Mercado Pago
     * @param string $paymentId ID del pago en Mercado Pago
     * @return array Con estado y detalles del pago
     */
    public function obtenerEstadoPago(string $paymentId): array
    {
        try {
            $client = new PaymentClient();
            $response = $client->get($paymentId);

            return [
                'success' => true,
                'payment_id' => $response->id,
                'status' => $response->status, // approved, rejected, pending, cancelled
                'status_detail' => $response->status_detail ?? null,
                'external_reference' => $response->external_reference ?? null,
                'transaction_amount' => $response->transaction_amount ?? 0,
                'description' => $response->description ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener estado del pago: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Procesa un webhook de Mercado Pago
     * @param array $data Datos del webhook
     * @return array Con resultado del procesamiento
     */
    public function procesarWebhook(array $data): array
    {
        try {
            $type = $data['type'] ?? null;
            $resourceId = $data['data']['id'] ?? null;

            if ($type !== 'payment' || !$resourceId) {
                return ['success' => true, 'message' => 'Webhook ignorado'];
            }

            // Obtener detalles del pago
            $pago = $this->obtenerEstadoPago($resourceId);

            if (!$pago['success']) {
                return $pago;
            }

            return [
                'success' => true,
                'payment_id' => $pago['payment_id'],
                'status' => $pago['status'],
                'external_reference' => $pago['external_reference'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error procesando webhook: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene la URL de retorno después del pago
     */
    private function getReturnUrl(string $status): string
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        return $baseUrl . '/api/pagos/retorno?status=' . $status;
    }

    /**
     * Obtiene la URL del webhook
     */
    private function getWebhookUrl(): string
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        return $baseUrl . '/api/pagos/webhook';
    }

    /**
     * Mapea estado de Mercado Pago a estado interno
     */
    public static function mapearEstado(string $mpStatus): string
    {
        return match($mpStatus) {
            'approved' => 'aprobado',
            'rejected' => 'rechazado',
            'pending', 'in_process' => 'pendiente',
            default => 'pendiente',
        };
    }
}
