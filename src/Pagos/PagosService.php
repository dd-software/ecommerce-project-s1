<?php

declare(strict_types=1);

/**
 * PagosService - Lógica de negocio de procesamiento de pagos
 * Simula integración con pasarela tipo Webpay
 */
namespace App\Pagos;

use App\Core\Database;
use App\Checkout\CheckoutService;
use App\Checkout\CheckoutRepository;
use App\Inventario\InventarioService;
use App\Inventario\InventarioRepository;

class PagosService
{
    private PagosRepository $repository;
    private CheckoutService $checkoutService;

    public function __construct(PagosRepository $repository)
    {
        $this->repository = $repository;
        $this->checkoutService = new CheckoutService(new CheckoutRepository());
    }

    /**
     * Procesa un pago simulado para un pedido
     * RN-E01: El stock se descuenta tras confirmar el pago
     * RN-E02: El pedido cambia de 'pendiente' a 'pagado' solo con confirmación exitosa
     */
    public function procesarPago(int $pedidoId, string $metodoPago, string $tokenTarjeta, int $userId): array
    {
        // Verificar que el pedido existe y está pendiente
        $pedido = $this->checkoutService->obtenerPedido($pedidoId);
        if (!$pedido) {
            throw new \RuntimeException('Pedido no encontrado.');
        }

        if ($pedido['estado'] !== 'pendiente') {
            throw new \RuntimeException('El pedido ya fue procesado. Estado actual: ' . $pedido['estado']);
        }

        $monto = (int)$pedido['total'];

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            if ($metodoPago === 'paypal') {
                $respuestaPasarela = $this->verificarPagoPaypal($tokenTarjeta, $monto);
                $transaccionId = $tokenTarjeta; // El tokenTarjeta es el PayPal Order ID
            } else {
                $transaccionId = 'TX-' . strtoupper(bin2hex(random_bytes(6)));
                $respuestaPasarela = $this->simularPasarelaPago($tokenTarjeta, $monto);
            }

            // Registrar el intento de pago
            $pagoId = $this->repository->registrarPago(
                pedidoId: $pedidoId,
                metodoPago: $metodoPago,
                monto: $monto,
                referenciaExterna: $transaccionId,
                estado: $respuestaPasarela['estado'],
                respuesta: json_encode($respuestaPasarela)
            );

            if ($respuestaPasarela['estado'] === 'aprobado') {
                // RN-E02 + RN-003: Solo descontar stock tras pago confirmado
                $this->checkoutService->descontarStock($pedidoId);

                // Actualizar estado del pedido
                $this->checkoutService->actualizarEstado(
                    pedidoId: $pedidoId,
                    nuevoEstado: 'pagado',
                    userId: $userId,
                    comentario: 'Pago aprobado. Transacción: ' . $transaccionId
                );

                // Registrar movimiento de inventario
                $inventarioService = new InventarioService(new InventarioRepository());
                $detalles = $pedido['detalle'] ?? [];
                foreach ($detalles as $detalle) {
                    $inventarioService->registrarEgreso(
                        productoId: (int)$detalle['id_producto'],
                        cantidad: (int)$detalle['cantidad'],
                        pedidoId: $pedidoId,
                        motivo: 'Venta - Pedido #' . $pedidoId
                    );
                }

                $mensaje = 'Pago aprobado exitosamente.';
            } else {
                $this->checkoutService->actualizarEstado(
                    pedidoId: $pedidoId,
                    nuevoEstado: 'cancelado',
                    userId: $userId,
                    comentario: 'Pago rechazado: ' . $respuestaPasarela['mensaje']
                );
                $mensaje = 'Pago rechazado: ' . $respuestaPasarela['mensaje'];
            }

            $db->commit();

            return [
                'transaccion_id'  => $transaccionId,
                'estado'          => $respuestaPasarela['estado'],
                'mensaje'         => $mensaje,
                'pedido_id'       => $pedidoId,
                'monto'           => $monto,
                'monto_formateado' => '$' . number_format($monto / 100, 0, ',', '.'),
            ];

        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Simula una pasarela de pago (Webpay)
     * Aprueba pagos con token que no empiecen con "fail_"
     */
    private function simularPasarelaPago(string $tokenTarjeta, int $monto): array
    {
        // Simular delay de procesamiento
        usleep(100000); // 100ms

        // Tokens que empiezan con "fail_" simulan rechazo
        if (str_starts_with($tokenTarjeta, 'fail_')) {
            return [
                'estado'  => 'rechazado',
                'codigo'  => 'REJECTED',
                'mensaje' => 'Tarjeta rechazada por el emisor.',
            ];
        }

        // 90% de probabilidad de aprobación en simulación
        if (random_int(1, 10) <= 9) {
            return [
                'estado'  => 'aprobado',
                'codigo'  => 'APPROVED',
                'mensaje' => 'Transacción aprobada.',
                'cuotas'  => 1,
            ];
        }

        return [
            'estado'  => 'rechazado',
            'codigo'  => 'INSUFFICIENT_FUNDS',
            'mensaje' => 'Fondos insuficientes.',
        ];
    }

    /**
     * Verifica un pago con la API de PayPal (o simulación si no hay credenciales)
     */
    private function verificarPagoPaypal(string $paypalOrderId, int $montoEsperadoCLP): array
    {
        $clientId = $_ENV['PAYPAL_CLIENT_ID'] ?? '';
        $clientSecret = $_ENV['PAYPAL_SECRET'] ?? '';
        $mode = $_ENV['PAYPAL_MODE'] ?? 'sandbox';
        $rate = (float)($_ENV['PAYPAL_EXCHANGE_RATE'] ?? 930.0);

        if (empty($clientId) || empty($clientSecret)) {
            return [
                'estado' => 'aprobado',
                'codigo' => 'APPROVED',
                'mensaje' => 'Pago verificado con éxito (Simulación PayPal sin credenciales en .env).',
                'detalles' => [
                    'id' => $paypalOrderId,
                    'status' => 'COMPLETED',
                    'intent' => 'CAPTURE'
                ]
            ];
        }

        try {
            $baseUrl = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
            
            // 1. Obtener Token OAuth
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1/oauth2/token');
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $clientId . ":" . $clientSecret);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
            
            $result = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status !== 200) {
                return [
                    'estado' => 'rechazado',
                    'codigo' => 'PAYPAL_AUTH_ERROR',
                    'mensaje' => 'Error de autenticación con la API de PayPal.'
                ];
            }

            $tokenData = json_decode($result, true);
            $accessToken = $tokenData['access_token'] ?? null;

            if (!$accessToken) {
                return [
                    'estado' => 'rechazado',
                    'codigo' => 'PAYPAL_TOKEN_ERROR',
                    'mensaje' => 'No se pudo obtener el token de acceso de PayPal.'
                ];
            }

            // 2. Obtener Detalles de la Orden
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v2/checkout/orders/' . $paypalOrderId);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $accessToken,
                "Content-Type: application/json"
            ]);

            $result = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status !== 200) {
                return [
                    'estado' => 'rechazado',
                    'codigo' => 'PAYPAL_ORDER_ERROR',
                    'mensaje' => 'Error al consultar la orden en PayPal.'
                ];
            }

            $orderData = json_decode($result, true);
            
            $paypalStatus = $orderData['status'] ?? '';
            if ($paypalStatus !== 'APPROVED' && $paypalStatus !== 'COMPLETED') {
                return [
                    'estado' => 'rechazado',
                    'codigo' => 'PAYPAL_PAYMENT_NOT_APPROVED',
                    'mensaje' => 'La transacción en PayPal no está aprobada. Estado: ' . $paypalStatus
                ];
            }

            $paypalAmount = 0.0;
            if (!empty($orderData['purchase_units'])) {
                $paypalAmount = (float)($orderData['purchase_units'][0]['amount']['value'] ?? 0.0);
            }

            $montoEsperadoUSD = ($montoEsperadoCLP / 100.0) / $rate;
            
            if (abs($paypalAmount - $montoEsperadoUSD) > 0.50) {
                return [
                    'estado' => 'rechazado',
                    'codigo' => 'PAYPAL_AMOUNT_MISMATCH',
                    'mensaje' => sprintf(
                        'El monto pagado en PayPal ($%.2f USD) no coincide con el total esperado del pedido ($%.2f USD).',
                        $paypalAmount,
                        $montoEsperadoUSD
                    )
                ];
            }

            return [
                'estado' => 'aprobado',
                'codigo' => 'APPROVED',
                'mensaje' => 'Pago verificado con éxito en PayPal.',
                'detalles' => $orderData
            ];

        } catch (\Exception $e) {
            return [
                'estado' => 'rechazado',
                'codigo' => 'PAYPAL_EXCEPTION',
                'mensaje' => 'Excepción al verificar pago con PayPal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Consulta el estado del pago de un pedido
     */
    public function consultarEstadoPago(int $pedidoId): ?array
    {
        return $this->repository->obtenerPagoPorPedido($pedidoId);
    }

    /**
     * Procesa un webhook de confirmación de la pasarela
     */
    public function procesarWebhook(int $pedidoId, string $estado, string $transaccionId): void
    {
        $pago = $this->repository->obtenerPagoPorPedido($pedidoId);
        if (!$pago) {
            throw new \RuntimeException('No se encontró pago para este pedido.');
        }

        $nuevoEstado = match ($estado) {
            'approved', 'aprobado' => 'aprobado',
            'rejected', 'rechazado' => 'rechazado',
            'refunded', 'reembolsado' => 'reembolsado',
            default => 'pendiente',
        };

        $this->repository->actualizarEstadoPago((int)$pago['id'], $nuevoEstado, $transaccionId);

        if ($nuevoEstado === 'aprobado') {
            $this->checkoutService->descontarStock($pedidoId);
            $this->checkoutService->actualizarEstado($pedidoId, 'pagado', null, 'Confirmado por webhook');
        }
    }
}
