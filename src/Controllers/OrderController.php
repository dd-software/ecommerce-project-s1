<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Core\Response;
use Src\Core\AuthMiddleware;
use Src\Models\Order;

class OrderController {
    /**
     * GET /api/orders/me
     * Retorna el historial de compras del usuario autenticado.
     */
    public function myOrders(): void {
        $user = AuthMiddleware::authenticate();
        $orderModel = new Order();
        
        try {
            $orders = $orderModel->getByUserId((int)$user['user_id']);
            Response::json(['data' => $orders]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}
