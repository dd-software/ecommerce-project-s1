<?php

declare(strict_types=1);

/**
 * MercadoPago - Cliente mínimo de la API REST (Checkout Pro).
 * Sin SDK/composer: cURL directo. Solo lo que usamos: crear preferencia y consultar pago.
 * Docs: https://www.mercadopago.cl/developers/es/reference
 */
namespace App\Pagos;

class MercadoPago
{
    private const API = 'https://api.mercadopago.com';

    /** ¿Hay credenciales reales configuradas? Si no, el flujo cae a simulación. */
    public static function habilitado(): bool
    {
        return MP_ACCESS_TOKEN !== '' && !str_starts_with(MP_ACCESS_TOKEN, 'change_');
    }

    /**
     * Crea una preferencia de pago y devuelve el punto de inicio (URL a la que se redirige al usuario).
     * @param array $items  [['title'=>, 'quantity'=>, 'unit_price'=> (int CLP)], ...]
     * @return array{preference_id:string, init_point:string}
     */
    public static function crearPreferencia(array $items, int $pedidoId, string $payerEmail): array
    {
        $base = rtrim(APP_URL, '/');
        $body = [
            'items'              => $items,
            'external_reference' => (string)$pedidoId,        // así el webhook sabe a qué pedido pertenece
            'payer'              => ['email' => $payerEmail],
            'back_urls'          => [
                'success' => $base . '/?pago=ok&pedido=' . $pedidoId,
                'failure' => $base . '/?pago=fail&pedido=' . $pedidoId,
                'pending' => $base . '/?pago=pending&pedido=' . $pedidoId,
            ],
            'auto_return'        => 'approved',
            'notification_url'   => $base . '/api/pagos/webhook',
        ];

        $resp = self::request('POST', '/checkout/preferences', $body);

        if (empty($resp['init_point']) || empty($resp['id'])) {
            throw new \RuntimeException('MercadoPago no devolvió un punto de pago válido.');
        }

        return ['preference_id' => (string)$resp['id'], 'init_point' => (string)$resp['init_point']];
    }

    /**
     * Consulta un pago por su ID. Fuente de verdad del estado (no confiar en el body del webhook).
     * @return array  payload del pago (incluye 'status' y 'external_reference')
     */
    public static function obtenerPago(string $paymentId): array
    {
        return self::request('GET', '/v1/payments/' . urlencode($paymentId));
    }

    /** Llamada HTTP autenticada con Bearer. Lanza RuntimeException ante error de red o HTTP >= 400. */
    private static function request(string $metodo, string $path, ?array $body = null): array
    {
        $ch = curl_init(self::API . $path);
        $headers = [
            'Authorization: Bearer ' . MP_ACCESS_TOKEN,
            'Content-Type: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $metodo,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $raw  = curl_exec($ch);
        $errno = curl_errno($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException('Error de conexión con MercadoPago.');
        }

        $data = json_decode((string)$raw, true) ?? [];

        if ($code >= 400) {
            $msg = $data['message'] ?? ('HTTP ' . $code);
            throw new \RuntimeException('MercadoPago: ' . $msg);
        }

        return $data;
    }
}
