<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Core\Response;
use Src\Core\AuthMiddleware;
use Src\Services\TransbankService;
use Config\Database;

class TransbankController {

    private TransbankService $tbk;

    public function __construct() {
        $this->tbk = new TransbankService();
    }

    /**
     * POST /api/payment/transbank/create
     *
     * Crea una transacción Webpay Plus y devuelve el token y la URL
     * a la que el frontend debe redirigir al usuario.
     */
    public function create(): void {
        $user    = AuthMiddleware::authenticate();
        $input   = json_decode(file_get_contents('php://input'), true);
        $orderId = (int)($input['order_id'] ?? 0);

        if (!$orderId) {
            Response::error('El parámetro order_id es requerido.', 400);
        }

        // Verificar que la orden existe, pertenece al usuario y está pendiente de pago
        $db   = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT total_amount FROM orders
             WHERE id = ? AND user_id = ? AND status = 'pendiente_pago'"
        );
        $stmt->execute([$orderId, $user['user_id']]);
        $order = $stmt->fetch();

        if (!$order) {
            Response::error('Orden no encontrada o ya fue procesada.', 404);
        }

        // Construir la return_url dinámicamente según el entorno
        $protocol  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        $returnUrl = "$protocol://$host$scriptDir/transbank-return.php?order_id=$orderId";

        // Identificadores únicos para esta transacción
        $buyOrder  = 'UCT-' . $orderId . '-' . time();
        $sessionId = 'SID-' . $user['user_id'] . '-' . time();

        // Convertir monto a CLP (los precios en BD están en USD; tasa demo: 1 USD ≈ 1000 CLP)
        $amountCLP = (int)round((float)$order['total_amount'] * 1000);

        try {
            $result = $this->tbk->createTransaction($buyOrder, $sessionId, $amountCLP, $returnUrl);
            Response::json([
                'token' => $result['token'],
                'url'   => $result['url'],
            ]);
        } catch (\Exception $e) {
            Response::error('No se pudo iniciar el pago con Transbank: ' . $e->getMessage(), 500);
        }
    }
}
