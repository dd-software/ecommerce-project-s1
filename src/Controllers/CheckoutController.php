<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Core\Response;
use Src\Core\AuthMiddleware;
use Src\Models\Order;

class CheckoutController {
    private Order $orderModel;

    public function __construct() {
        $this->orderModel = new Order();
    }

    /**
     * POST /api/checkout
     * Realiza el proceso de Checkout creando la orden (Estado: pendiente_pago).
     */
    public function process(): void {
        $user = AuthMiddleware::authenticate();

        try {
            $orderId = $this->orderModel->createFromCart((int)$user['user_id']);
            Response::json([
                'message' => 'Orden creada con éxito, esperando confirmación de pago.',
                'order_id' => $orderId
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
