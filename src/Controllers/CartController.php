<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Core\Response;
use Src\Core\AuthMiddleware;
use Src\Models\Cart;

class CartController {
    private Cart $cartModel;

    public function __construct() {
        $this->cartModel = new Cart();
    }

    /**
     * GET /api/cart
     */
    public function index(): void {
        $user = AuthMiddleware::authenticate();
        $items = $this->cartModel->getItems((int)$user['user_id']);
        Response::json(['data' => $items]);
    }

    /**
     * POST /api/cart
     */
    public function add(): void {
        $user = AuthMiddleware::authenticate();
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['variant_id'], $input['quantity']) || $input['quantity'] <= 0) {
            Response::error('variant_id y quantity válido son obligatorios', 400);
        }

        $this->cartModel->addItem((int)$user['user_id'], (int)$input['variant_id'], (int)$input['quantity']);
        Response::json(['message' => 'Producto agregado o actualizado en el carrito']);
    }

    /**
     * DELETE /api/cart/{variant_id}
     */
    public function remove(array $params): void {
        $user = AuthMiddleware::authenticate();
        $variantId = (int)($params['variant_id'] ?? 0);
        
        $this->cartModel->removeItem((int)$user['user_id'], $variantId);
        Response::json(['message' => 'Producto removido del carrito']);
    }
}
