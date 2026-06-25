<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Core\Response;
use Src\Core\AuthMiddleware;
use Src\Models\Order;

class PaymentController {
    private Order $orderModel;

    public function __construct() {
        $this->orderModel = new Order();
    }

    /**
     * POST /api/payment/simulate
     * Simulación de pasarela de pago para validar la RN-003 de descuento de inventario.
     */
    public function simulate(): void {
        $user = AuthMiddleware::authenticate();
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['order_id'])) {
            Response::error('El parámetro order_id es requerido', 400);
        }

        try {
            $this->orderModel->markAsPaidAndDeductStock((int)$input['order_id']);
            Response::json([
                'message' => 'Transacción de pago procesada de forma exitosa. Se ha actualizado el inventario.',
                'status' => 'pagado'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    private function getPayPalAccessToken(): string {
        $clientId = getenv('PAYPAL_CLIENT_ID');
        $secret = getenv('PAYPAL_SECRET');
        $env = getenv('PAYPAL_ENVIRONMENT') ?: 'sandbox';
        
        $baseUrl = $env === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$baseUrl/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Solo para desarrollo local XAMPP
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$secret");
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

        $result = curl_exec($ch);
        curl_close($ch);

        if (empty($result)) {
            throw new \Exception("Error al conectar con PayPal.");
        }

        $json = json_decode($result);
        if (!isset($json->access_token)) {
            throw new \Exception("Credenciales de PayPal inválidas o faltantes en el .env.");
        }

        return $json->access_token;
    }

    /**
     * POST /api/payment/paypal/create
     */
    public function createPayPalOrder(): void {
        $user = AuthMiddleware::authenticate();
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['order_id'])) {
            Response::error('order_id requerido', 400);
        }

        try {
            $db = \Config\Database::getConnection();
            $stmt = $db->prepare("SELECT total_amount, status FROM orders WHERE id = :id AND user_id = :user_id");
            $stmt->execute(['id' => $input['order_id'], 'user_id' => $user['user_id']]);
            $order = $stmt->fetch();

            if (!$order || $order['status'] !== 'pendiente_pago') {
                Response::error('Orden no válida o ya pagada', 400);
            }

            $token = $this->getPayPalAccessToken();
            $env = getenv('PAYPAL_ENVIRONMENT') ?: 'sandbox';
            $baseUrl = $env === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

            $data = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => (string)$input['order_id'],
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => number_format((float)$order['total_amount'], 2, '.', '')
                        ]
                    ]
                ]
            ];

            $ch = curl_init("$baseUrl/v2/checkout/orders");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer $token"
            ]);

            $result = curl_exec($ch);
            curl_close($ch);

            $json = json_decode($result, true);
            if (isset($json['id'])) {
                Response::json(['paypal_order_id' => $json['id']]);
            } else {
                Response::error('Error creando orden en PayPal: ' . ($json['message'] ?? ''), 500);
            }

        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/payment/paypal/capture
     */
    public function capturePayPalOrder(): void {
        $user = AuthMiddleware::authenticate();
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['paypal_order_id']) || !isset($input['order_id'])) {
            Response::error('paypal_order_id y order_id requeridos', 400);
        }

        try {
            $token = $this->getPayPalAccessToken();
            $env = getenv('PAYPAL_ENVIRONMENT') ?: 'sandbox';
            $baseUrl = $env === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

            $ch = curl_init("$baseUrl/v2/checkout/orders/{$input['paypal_order_id']}/capture");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer $token"
            ]);

            $result = curl_exec($ch);
            curl_close($ch);

            $json = json_decode($result, true);

            if (isset($json['status']) && $json['status'] === 'COMPLETED') {
                $this->orderModel->markAsPaidAndDeductStock((int)$input['order_id']);
                Response::json(['message' => 'Pago de PayPal verificado y stock deducido exitosamente.']);
            } else {
                Response::error('El pago no fue completado en PayPal', 400);
            }
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
