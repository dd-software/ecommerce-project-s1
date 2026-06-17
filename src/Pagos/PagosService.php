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

        // Simular procesamiento con pasarela de pago
        // En entorno real, esto llamaría a Webpay/Stripe/MercadoPago
        $transaccionId = 'TX-' . strtoupper(bin2hex(random_bytes(6)));
        $respuestaPasarela = $this->simularPasarelaPago($tokenTarjeta, $monto);

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

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
