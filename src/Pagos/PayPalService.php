<?php

declare(strict_types=1);

namespace App\Pagos;

use App\Core\Database;
use App\Core\PayPalClient;
use App\Checkout\CheckoutService;
use App\Checkout\CheckoutRepository;

class PayPalService
{
    private PagosRepository $repository;
    private CheckoutService $checkoutService;
    private PayPalClient $payPalClient;

    public function __construct()
    {
        $this->repository = new PagosRepository();
        $this->checkoutService = new CheckoutService(new CheckoutRepository());
        $this->payPalClient = new PayPalClient();
    }

    /**
     * Crea una orden en PayPal asegurando el total desde backend
     */
    public function createOrder(int $carritoId, int $userId): array
    {
        // Obtener items directamente por carritoId (no por userId, que puede devolver carrito vacío
        // si crearPedido() ya desactivó el carrito activo del usuario en un reintento).
        $itemsRepo = new \App\Carrito\CarritoRepository();
        $items = $itemsRepo->obtenerItems($carritoId);

        if (empty($items)) {
            throw new \RuntimeException('El carrito está vacío o no existe.');
        }

        // Calcular subtotal real desde los items (en centavos)
        $subtotalCLP = 0;
        foreach ($items as $item) {
            $subtotalCLP += (int)$item['precio_unitario'] * (int)$item['cantidad'];
        }
        // Agregar IVA 19% — igual que CheckoutService::crearPedido()
        $ivaCLP = (int)round($subtotalCLP * 0.19);
        $totalCLP = $subtotalCLP + $ivaCLP;

        // Conversión a USD (1 USD = 900 CLP)
        $totalUSD = round($totalCLP / 900, 2);
        if ($totalUSD < 0.01) {
            throw new \RuntimeException('El monto mínimo para PayPal es $0.01 USD.');
        }

        // Crear pedido en estado pendiente (crearPedido gestiona su propia transacción)
        $resultado = $this->checkoutService->crearPedido(
            $userId,
            $carritoId,
            'Pendiente PayPal',
            '',
            '',
            null
        );
        $pedidoId = $resultado['id'];

        try {
            // Crear orden en PayPal
            $paypalOrder = $this->payPalClient->createOrder((float)$totalUSD, (string)$pedidoId);

            // Guardar paypal_order_id en el pedido (transacción propia, no anidada)
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            try {
                $db->beginTransaction();
                $stmt = $pdo->prepare("UPDATE pedidos SET paypal_order_id = ? WHERE id = ?");
                $stmt->execute([$paypalOrder['id'], $pedidoId]);
                $db->commit();
            } catch (\Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollback();
                }
                throw $e;
            }

            return [
                'paypal_order_id' => $paypalOrder['id'],
                'pedido_id'       => $pedidoId
            ];
        } catch (\Exception $e) {
            // Reactivar el carrito del usuario si la creación de orden falló después de crear el pedido
            try {
                $carritoRepo = new \App\Carrito\CarritoRepository();
                $carritoRepo->reactivarCarritoUsuario($userId);
            } catch (\Exception $ignored) {
                // No interrumpir el error original
            }
            throw $e;
        }
    }

    /**
     * Captura una orden ya aprobada por el usuario en PayPal
     */
    public function captureOrder(string $paypalOrderId, int $userId): array
    {
        $db  = Database::getInstance();
        $pdo = $db->getConnection();

        try {
            // Abrir transacción ANTES del SELECT FOR UPDATE para que el lock sea efectivo
            $db->beginTransaction();

            // 1. Validar que la orden de PayPal pertenece a un pedido y bloquearlo
            $stmt = $pdo->prepare("SELECT id, estado, total FROM pedidos WHERE paypal_order_id = ? FOR UPDATE");
            $stmt->execute([$paypalOrderId]);
            $pedido = $stmt->fetch();

            if (!$pedido) {
                throw new \RuntimeException('Pedido no encontrado con ese Order ID de PayPal.');
            }

            if ($pedido['estado'] !== 'pendiente') {
                // Podría ser un doble-clic o un reintento: responder con éxito idempotente
                $db->rollback();
                return [
                    'success'    => true,
                    'pedido_id'  => $pedido['id'],
                    'capture_id' => '',
                    'mensaje'    => 'El pedido ya fue procesado anteriormente.',
                ];
            }

            // 2. Capturar fondos con la API de PayPal (fuera de la transacción no es posible,
            //    pero el lock sobre el pedido evita capturas concurrentes duplicadas)
            $captureData = $this->payPalClient->captureOrder($paypalOrderId);

            $captureStatus = $captureData['status'] ?? '';
            $captureId     = $captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? '';

            if ($captureStatus !== 'COMPLETED') {
                throw new \RuntimeException(
                    'El pago no fue completado en PayPal. Estado: ' . $captureStatus
                );
            }

            // Obtener detalle completo para movimiento de inventario
            $pedidoCompleto = $this->checkoutService->obtenerPedido((int)$pedido['id']);

            // 3. Registrar captura en BD y actualizar pedido
            $stmt = $pdo->prepare(
                "UPDATE pedidos SET paypal_capture_id = ?, estado = 'pagado', metodo_pago = 'paypal' WHERE id = ?"
            );
            $stmt->execute([$captureId, $pedido['id']]);

            $this->repository->registrarPago(
                pedidoId:         (int)$pedido['id'],
                metodoPago:       'paypal',
                monto:            (int)$pedido['total'],
                referenciaExterna: $captureId,
                estado:           'aprobado',
                respuesta:        json_encode($captureData)
            );

            // 4. Descontar stock (RN-003: solo tras pago confirmado)
            $this->checkoutService->descontarStock((int)$pedido['id']);

            // 5. Registrar movimiento de inventario
            $inventarioService = new \App\Inventario\InventarioService(new \App\Inventario\InventarioRepository());
            foreach ($pedidoCompleto['detalle'] ?? [] as $detalle) {
                $inventarioService->registrarEgreso(
                    productoId: (int)$detalle['id_producto'],
                    cantidad:   (int)$detalle['cantidad'],
                    pedidoId:   (int)$pedido['id'],
                    motivo:     'Venta PayPal - Pedido #' . $pedido['id']
                );
            }

            // 6. Historial de estado
            $this->checkoutService->actualizarEstado(
                pedidoId:     (int)$pedido['id'],
                nuevoEstado:  'pagado',
                userId:       $userId,
                comentario:   'Pago capturado vía PayPal. Capture ID: ' . $captureId
            );

            $db->commit();

            return [
                'success'    => true,
                'pedido_id'  => $pedido['id'],
                'capture_id' => $captureId,
            ];

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            error_log('[PayPalService::captureOrder] ' . $e->getMessage());
            throw $e;
        }
    }
}
