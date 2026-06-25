<?php
/**
 * transbank-return.php
 *
 * Página de retorno de Transbank Webpay Plus.
 * Transbank redirige aquí al usuario al finalizar (o cancelar) el pago,
 * enviando el token_ws por POST.
 *
 * Esta página NO pasa por el router de la API: Apache la sirve directamente
 * porque el archivo existe (la condición !-f del .htaccess no se cumple).
 */
declare(strict_types=1);

// ── Bootstrap ────────────────────────────────────────────────────────────────
$rootDir = __DIR__ . '/..';

spl_autoload_register(function (string $class) use ($rootDir): void {
    $parts    = explode('\\', $class);
    $parts[0] = strtolower($parts[0]);
    $file     = $rootDir . '/' . implode('/', $parts) . '.php';
    if (file_exists($file)) require_once $file;
});

$envFile = $rootDir . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$name, $value] = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
    }
}
// ─────────────────────────────────────────────────────────────────────────────

$orderId  = (int)($_GET['order_id'] ?? 0);
$tokenWs  = trim($_POST['token_ws']  ?? '');
$tbkToken = trim($_POST['TBK_TOKEN'] ?? ''); // Enviado solo si el usuario cancela

$success     = false;
$message     = '';
$detail      = '';
$amount      = 0;
$cardSuffix  = '';
$authCode    = '';

// ── Determinar situación ──────────────────────────────────────────────────────
if (!empty($tbkToken) && empty($tokenWs)) {
    // Usuario abandonó el formulario (pulsó "volver" o cerró la ventana)
    $message = 'Pago cancelado. No se realizó ningún cargo.';
    $detail  = 'Regresa al carrito para intentarlo nuevamente.';

} elseif (empty($tokenWs)) {
    // Acceso directo sin parámetros (p.ej. alguien escribe la URL a mano)
    $message = 'Acceso inválido.';
    $detail  = 'No se recibió un token de pago.';

} else {
    try {
        $tbk    = new \Src\Services\TransbankService();
        $result = $tbk->confirmTransaction($tokenWs);

        $responseCode = (int)($result['response_code'] ?? -1);
        $amount       = (int)($result['amount']        ?? 0);
        $authCode     = $result['authorization_code']        ?? '';
        $cardSuffix   = $result['card_detail']['card_number'] ?? '****';
        $buyOrder     = $result['buy_order']                  ?? '';

        // Validar que el buy_order coincide con el order_id recibido (seguridad)
        $parts           = explode('-', $buyOrder); // UCT-{id}-{timestamp}
        $orderIdFromTbk  = isset($parts[1]) ? (int)$parts[1] : 0;

        if ($orderIdFromTbk !== $orderId) {
            $message = 'Error de validación: el identificador de orden no coincide.';
            $detail  = 'Contacta a soporte si crees que esto es un error.';

        } elseif ($responseCode === 0) {
            // ✅ Pago aprobado
            $orderModel = new \Src\Models\Order();
            $orderModel->markAsPaidAndDeductStock($orderId);
            $success = true;
            $message = '¡Tu pago fue aprobado!';
            $detail  = 'Tu pedido está siendo preparado. Puedes revisar el estado en "Mis Compras".';

        } else {
            // ❌ Pago rechazado por el banco (código distinto de 0)
            $codes = [
                -1  => 'Transacción rechazada por el banco.',
                -2  => 'Transacción rechazada: error en tarjeta.',
                -3  => 'Error en la transacción.',
                -4  => 'Rechazado por Transbank.',
                -5  => 'Rechazo por error de tasa.',
                -6  => 'Cupo insuficiente.',
                -7  => 'Número de intentos excedido.',
                -8  => 'Tarjeta bloqueada.',
            ];
            $message = $codes[$responseCode] ?? "Transacción rechazada (código $responseCode).";
            $detail  = 'No se realizó ningún cargo. Puedes intentar con otra tarjeta.';
        }

    } catch (\Exception $e) {
        $message = 'Error al confirmar el pago con Transbank.';
        $detail  = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $success ? 'Pago Exitoso' : 'Resultado del Pago' ?> — UCT Ecommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .result-icon { font-size: 5rem; line-height: 1; }
        .tbk-badge { font-size: .75rem; color: #6c757d; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow border-0 rounded-4 p-5 text-center">

                <?php if ($success): ?>
                    <div class="result-icon text-success mb-3">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h2 class="fw-bold text-success mb-1">¡Pago exitoso!</h2>
                    <p class="text-muted mb-4"><?= htmlspecialchars($detail) ?></p>

                    <?php if ($amount): ?>
                        <div class="bg-light rounded-3 p-3 mb-3 text-start">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Monto pagado</span>
                                <strong>$<?= number_format($amount, 0, ',', '.') ?> CLP</strong>
                            </div>
                            <?php if ($authCode): ?>
                            <div class="d-flex justify-content-between mt-1">
                                <span class="text-muted small">Código autorización</span>
                                <strong><?= htmlspecialchars($authCode) ?></strong>
                            </div>
                            <?php endif; ?>
                            <?php if ($cardSuffix && strlen($cardSuffix) >= 4): ?>
                            <div class="d-flex justify-content-between mt-1">
                                <span class="text-muted small">Tarjeta</span>
                                <strong>**** <?= htmlspecialchars(substr($cardSuffix, -4)) ?></strong>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2">
                        <a href="mis-compras.html" class="btn btn-primary btn-lg rounded-3">
                            <i class="bi bi-box-seam me-2"></i>Ver mis compras
                        </a>
                        <a href="index.html" class="btn btn-outline-secondary rounded-3">
                            Seguir comprando
                        </a>
                    </div>

                <?php else: ?>
                    <div class="result-icon text-danger mb-3">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <h2 class="fw-bold text-danger mb-1">Pago no completado</h2>
                    <p class="text-dark mb-1 fw-semibold"><?= htmlspecialchars($message) ?></p>
                    <p class="text-muted small mb-4"><?= htmlspecialchars($detail) ?></p>

                    <div class="d-grid gap-2">
                        <a href="cart.html" class="btn btn-warning btn-lg rounded-3">
                            <i class="bi bi-cart3 me-2"></i>Volver al carrito
                        </a>
                        <a href="index.html" class="btn btn-outline-secondary rounded-3">
                            Ir al catálogo
                        </a>
                    </div>
                <?php endif; ?>

                <hr class="my-4">
                <p class="tbk-badge">
                    Transacción procesada por
                    <strong>Transbank Webpay Plus</strong> · Ambiente de integración
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
