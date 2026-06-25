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
        // BUG A fix: Usar CarritoService (no CheckoutService::obtenerResumenCarrito que no existe)
        $carritoService = new \App\Carrito\CarritoService(new \App\Carrito\CarritoRepository());
        $carrito = $carritoService->obtenerCarrito(userId: $userId, sessionId: null);

        if (empty($carrito['items'])) {
            throw new \RuntimeException('El carrito está vacío o no existe.');
        }

        // Calcular total real desde los items
        $totalCLP = 0;
        foreach ($carrito['items'] as $item) {
            $totalCLP += (int)$item['precio_unitario'] * (int)$item['cantidad'];
        }

        // Simulación: conversión a USD (asumiendo 1 USD = 900 CLP)
        $totalUSD = round($totalCLP / 900, 2);

        // BUG B fix: crearPedido retorna array; argumentos en orden correcto
        $resultado = $this->checkoutService->crearPedido(
            $userId,
            $carritoId,
            'Pendiente PayPal',
            '',
            '',
            null
        );
        $pedidoId = $resultado['id'];

        // 3. Crear orden en PayPal
        $paypalOrder = $this->payPalClient->createOrder((float)$totalUSD, (string)$pedidoId);

        // BUG C fix: solo el UPDATE de paypal_order_id va en su propia transacción
        $db = Database::getInstance();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE pedidos SET paypal_order_id = ? WHERE id = ?");
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
            'pedido_id' => $pedidoId
        ];
    }

    /**
     * Captura una orden ya aprobada por el usuario en PayPal
     */
    public function captureOrder(string $paypalOrderId, int $userId): array
    {
        $db = Database::getInstance();
        
        try {
            // 1. Validar que la orden de PayPal pertenece a un pedido
            $stmt = $db->prepare("SELECT id, estado, total FROM pedidos WHERE paypal_order_id = ? FOR UPDATE");
            $stmt->execute([$paypalOrderId]);
            $pedido = $stmt->fetch();

            if (!$pedido) {
                throw new \RuntimeException('Pedido no encontrado con ese Order ID.');
            }

            if ($pedido['estado'] !== 'pendiente') {
                throw new \RuntimeException('El pedido ya ha sido procesado.');
            }

            // 2. Capturar fondos con la API
            $captureData = $this->payPalClient->captureOrder($paypalOrderId);

            $captureStatus = $captureData['status'] ?? '';
            $captureId = $captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? '';

            if ($captureStatus !== 'COMPLETED') {
                throw new \RuntimeException('El pago no fue completado en PayPal.');
            }

            $db->beginTransaction();

            // 3. Registrar pago en BD y actualizar pedido
            $stmt = $db->prepare("UPDATE pedidos SET paypal_capture_id = ?, estado = 'pagado', metodo_pago = 'paypal' WHERE id = ?");
            $stmt->execute([$captureId, $pedido['id']]);

            $this->repository->registrarPago(
                pedidoId: (int)$pedido['id'],
                metodoPago: 'paypal',
                monto: (int)$pedido['total'],
                referenciaExterna: $captureId,
                estado: 'aprobado',
                respuesta: json_encode($captureData)
            );

            // 4. Descontar stock (RN)
            $this->checkoutService->descontarStock((int)$pedido['id']);

            // 5. Historial
            $this->checkoutService->actualizarEstado(
                pedidoId: (int)$pedido['id'],
                nuevoEstado: 'pagado',
                userId: $userId,
                comentario: 'Pago capturado vía PayPal API.'
            );

            $db->commit();

            return [
                'success' => true,
                'pedido_id' => $pedido['id'],
                'capture_id' => $captureId
            ];

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            throw $e;
        }
    }
}
