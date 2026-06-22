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
        $this->secret = PAYPAL_SECRET;
        
        $mode = PAYPAL_MODE;
        if ($mode === 'production') {
            $this->baseUrl = 'https://api-m.paypal.com';
        } else {
            $this->baseUrl = 'https://api-m.sandbox.paypal.com';
        }
    }

    private function getAccessToken(): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->baseUrl}/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->clientId}:{$this->secret}");
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Accept-Language: en_US"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            throw new \RuntimeException("Error obteniendo Access Token de PayPal. HTTP: $httpCode");
        }

        $data = json_decode($response, true);
        return $data['access_token'] ?? '';
    }

    public function createOrder(float $amount, string $referenceId): array
    {
        $token = $this->getAccessToken();

        $payload = [
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "reference_id" => $referenceId,
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => number_format($amount, 2, '.', '')
                    ]
                ]
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->baseUrl}/v2/checkout/orders");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer {$token}"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 && $httpCode !== 201) {
            throw new \RuntimeException("Error al crear la orden en PayPal. HTTP: $httpCode. Response: $response");
        }

        return json_decode($response, true);
    }

    public function captureOrder(string $orderId): array
    {
        $token = $this->getAccessToken();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer {$token}"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 && $httpCode !== 201) {
            throw new \RuntimeException("Error al capturar el pago en PayPal. HTTP: $httpCode. Response: $response");
        }

        return json_decode($response, true);
    }
}
