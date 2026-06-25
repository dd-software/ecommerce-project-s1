<?php
declare(strict_types=1);

namespace Src\Services;

/**
 * Cliente para la API REST de Transbank Webpay Plus.
 *
 * Flujo:
 *  1. createTransaction()  → obtiene token + URL de Transbank
 *  2. Frontend redirige al usuario a esa URL (form POST con token_ws)
 *  3. Usuario paga en la página de Transbank
 *  4. Transbank redirige al return_url enviando token_ws por POST
 *  5. confirmTransaction() → confirma y obtiene el resultado (response_code 0 = aprobado)
 *
 * Endpoint de integración (testing): webpay3gint.transbank.cl  (producción: webpay3g.transbank.cl)
 * Credenciales de integración (testing):
 *   Commerce code : 597055555532
 *   API Key       : 579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C
 *
 * Tarjetas de prueba:
 *   Visa      : 4051 8856 0044 6623
 *   Mastercard: 5186 0595 5959 0568
 *   CVV       : 123  |  Vencimiento: cualquier fecha futura
 *   RUT       : 11.111.111-1  |  Clave web: 123
 */
class TransbankService {

    private const API_URL = 'https://webpay3gint.transbank.cl/rswebpaytransaction/api/webpay/v1.2/transactions';

    private string $commerceCode;
    private string $apiKey;

    public function __construct() {
        $this->commerceCode = getenv('TBK_COMMERCE_CODE') ?: '597055555532';
        $this->apiKey       = getenv('TBK_API_KEY')       ?: '579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C';
    }

    /**
     * Realiza una petición HTTP a la API de Transbank.
     */
    private function request(string $method, string $path, ?array $body = null): array {
        $url = self::API_URL . $path;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, // Solo entorno local XAMPP
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Tbk-Api-Key-Id: '     . $this->commerceCode,
                'Tbk-Api-Key-Secret: ' . $this->apiKey,
                'Content-Type: application/json',
            ],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body ?? []));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body ?? new \stdClass()));
        }

        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($raw === false || !empty($curlErr)) {
            throw new \Exception('Error de conexión con Transbank: ' . $curlErr);
        }

        $data = json_decode($raw, true);
        if ($data === null) {
            throw new \Exception('Respuesta inválida de Transbank (JSON malformado).');
        }

        if ($httpCode >= 400) {
            $errMsg = $data['error_message'] ?? $data['message'] ?? 'Error desconocido';
            throw new \Exception("Transbank respondió con HTTP $httpCode: $errMsg");
        }

        return $data;
    }

    /**
     * Crea una transacción Webpay Plus y devuelve el token y la URL de pago.
     *
     * @param string $buyOrder   Identificador único del pedido (ej: UCT-42-1719000000)
     * @param string $sessionId  Identificador de la sesión del usuario
     * @param int    $amount     Monto en pesos chilenos (CLP), sin decimales
     * @param string $returnUrl  URL a la que Transbank redirige al usuario tras el pago
     * @return array ['token' => '...', 'url' => '...']
     */
    public function createTransaction(
        string $buyOrder,
        string $sessionId,
        int    $amount,
        string $returnUrl
    ): array {
        return $this->request('POST', '', [
            'buy_order'  => $buyOrder,
            'session_id' => $sessionId,
            'amount'     => $amount,
            'return_url' => $returnUrl,
        ]);
    }

    /**
     * Confirma (commit) una transacción procesada por el usuario.
     * Debe llamarse una única vez con el token recibido en el return_url.
     *
     * @param  string $token  El token_ws recibido en el POST del return_url
     * @return array  Detalles de la transacción. response_code === 0 indica aprobación.
     */
    public function confirmTransaction(string $token): array {
        return $this->request('PUT', '/' . $token);
    }
}
