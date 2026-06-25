<?php

declare(strict_types=1);

namespace App\Core;

class PayPalClient
{
    private string $clientId;
    private string $secret;
    private string $baseUrl;

    public function __construct()
    {
        $this->clientId = PAYPAL_CLIENT_ID;
        $this->secret   = PAYPAL_SECRET;

        $mode = PAYPAL_MODE;
        if ($mode === 'production') {
            $this->baseUrl = 'https://api-m.paypal.com';
        } else {
            $this->baseUrl = 'https://api-m.sandbox.paypal.com';
        }
    }

    /**
     * Obtiene un Access Token OAuth 2.0 de PayPal.
     *
     * @throws \RuntimeException con el error exacto devuelto por PayPal
     */
    private function getAccessToken(): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->baseUrl}/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->clientId}:{$this->secret}");
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Accept-Language: en_US",
            "Content-Type: application/x-www-form-urlencoded",
        ]);
        // Desactivar verificación SSL sólo en desarrollo local (sin certificado válido)
        if (defined('APP_ENV') && APP_ENV === 'development') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \RuntimeException("PayPal OAuth — Error cURL: {$curlError}");
        }

        if ($httpCode !== 200 || !$response) {
            // Incluir el body de PayPal para facilitar el diagnóstico
            $detail = $response ? $response : '(sin respuesta)';
            throw new \RuntimeException(
                "PayPal OAuth falló (HTTP {$httpCode}). " .
                "Verifica PAYPAL_CLIENT_ID y PAYPAL_SECRET en .env. " .
                "Respuesta: {$detail}"
            );
        }

        $data = json_decode($response, true);
        if (empty($data['access_token'])) {
            throw new \RuntimeException(
                "PayPal OAuth: access_token no encontrado en respuesta. Respuesta: {$response}"
            );
        }

        return $data['access_token'];
    }

    /**
     * Crea una orden de pago en PayPal.
     *
     * @param float  $amount      Monto en USD con decimales
     * @param string $referenceId ID interno del pedido (usado como referencia)
     *
     * @throws \RuntimeException con el error exacto de PayPal
     */
    public function createOrder(float $amount, string $referenceId): array
    {
        $token = $this->getAccessToken();

        $payload = [
            "intent"         => "CAPTURE",
            "purchase_units" => [
                [
                    "reference_id" => $referenceId,
                    "amount"       => [
                        "currency_code" => "USD",
                        "value"         => number_format($amount, 2, '.', ''),
                    ],
                ],
            ],
        ];

        $body            = json_encode($payload);
        $idempotencyKey  = 'order-' . $referenceId . '-' . bin2hex(random_bytes(8));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->baseUrl}/v2/checkout/orders");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Content-Length: " . strlen($body),
            "Authorization: Bearer {$token}",
            "PayPal-Request-Id: {$idempotencyKey}",
            "Prefer: return=representation",
        ]);
        if (defined('APP_ENV') && APP_ENV === 'development') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \RuntimeException("PayPal createOrder — Error cURL: {$curlError}");
        }

        if ($httpCode !== 200 && $httpCode !== 201) {
            throw new \RuntimeException(
                "PayPal createOrder falló (HTTP {$httpCode}). Respuesta: {$response}"
            );
        }

        $data = json_decode($response, true);
        if (empty($data['id'])) {
            throw new \RuntimeException(
                "PayPal createOrder: 'id' no encontrado en respuesta. Respuesta: {$response}"
            );
        }

        return $data;
    }

    /**
     * Captura los fondos de una orden ya aprobada por el comprador.
     *
     * @throws \RuntimeException con el error exacto de PayPal
     */
    public function captureOrder(string $orderId): array
    {
        $token = $this->getAccessToken();

        // PayPal requiere un body (aunque sea vacío '{}') en el POST de capture
        $body = '{}';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Content-Length: " . strlen($body),
            "Authorization: Bearer {$token}",
            "Prefer: return=representation",
        ]);
        if (defined('APP_ENV') && APP_ENV === 'development') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \RuntimeException("PayPal captureOrder — Error cURL: {$curlError}");
        }

        if ($httpCode !== 200 && $httpCode !== 201) {
            throw new \RuntimeException(
                "PayPal captureOrder falló (HTTP {$httpCode}). Respuesta: {$response}"
            );
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                "PayPal captureOrder: respuesta JSON inválida. Respuesta: {$response}"
            );
        }

        return $data;
    }
}
