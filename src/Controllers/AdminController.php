<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Core\Response;
use Src\Core\AuthMiddleware;
use Src\Models\Order;

class AdminController {
    private Order $orderModel;

    public function __construct() {
        $this->orderModel = new Order();
    }

    /**
     * GET /api/admin/orders
     */
    public function listOrders(): void {
        AuthMiddleware::authorize(['admin']);
        $orders = $this->orderModel->getAll();
        Response::json(['data' => $orders]);
    }

    /**
     * PUT /api/admin/orders/{id}/status
     */
    public function updateOrderStatus(array $params): void {
        AuthMiddleware::authorize(['admin']);
        $id = (int)($params['id'] ?? 0);
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['status'])) {
            Response::error('El nuevo status es requerido', 400);
        }

        try {
            $this->orderModel->updateStatus($id, $input['status']);
            Response::json(['message' => "Estado de la orden #{$id} actualizado a '{$input['status']}'"]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
