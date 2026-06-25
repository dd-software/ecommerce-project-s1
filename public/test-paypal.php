<?php
/**
 * test-paypal.php — Diagnóstico de credenciales PayPal
 * Solo accesible en modo development.
 *
 * Acceder en: http://localhost/ecommerce-project-s1/public/test-paypal.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

header('Content-Type: text/html; charset=utf-8');

// Proteger el endpoint: solo en desarrollo
if (!defined('APP_ENV') || APP_ENV !== 'development') {
    http_response_code(403);
    echo json_encode(['error' => 'Solo disponible en modo development.']);
    exit;
}

// ──────────────────────────────────────────────
// Recopilar datos de configuración
// ──────────────────────────────────────────────
$clientId = defined('PAYPAL_CLIENT_ID') ? PAYPAL_CLIENT_ID : '(no definido)';
$secret   = defined('PAYPAL_SECRET')    ? PAYPAL_SECRET    : '(no definido)';
$mode     = defined('PAYPAL_MODE')      ? PAYPAL_MODE      : '(no definido)';

$clientIdOk = !empty($clientId) && strlen($clientId) >= 20
    && $clientId !== 'sb'
    && !str_contains(strtolower($clientId), 'placeholder')
    && !str_contains(strtolower($clientId), 'replace');

$secretOk = !empty($secret) && strlen($secret) >= 20
    && !str_contains(strtolower($secret), 'placeholder')
    && !str_contains(strtolower($secret), 'replace');

// ──────────────────────────────────────────────
// Intentar obtener Access Token de PayPal
// ──────────────────────────────────────────────
$tokenResult  = null;
$tokenError   = null;
$tokenHttpCode = null;

if ($clientIdOk && $secretOk) {
    $baseUrl = $mode === 'production'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$clientId}:{$secret}");
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/json",
        "Accept-Language: en_US",
        "Content-Type: application/x-www-form-urlencoded",
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response      = curl_exec($ch);
    $tokenHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError     = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        $tokenError = "cURL error: {$curlError}";
    } elseif ($tokenHttpCode === 200) {
        $data = json_decode($response, true);
        $tokenResult = [
            'token_type' => $data['token_type'] ?? 'N/A',
            'expires_in' => $data['expires_in'] ?? 'N/A',
            'scope'      => substr($data['scope'] ?? '', 0, 80) . '...',
        ];
    } else {
        $tokenError = "HTTP {$tokenHttpCode} — {$response}";
    }
}

// ──────────────────────────────────────────────
// HTML de salida
// ──────────────────────────────────────────────
$ok  = '<span style="color:#16a34a;font-weight:bold">✓ OK</span>';
$bad = '<span style="color:#dc2626;font-weight:bold">✗ ERROR</span>';
$warn = '<span style="color:#d97706;font-weight:bold">⚠ ADVERTENCIA</span>';

function mask(string $val): string {
    if (strlen($val) < 8) return '(demasiado corto)';
    return substr($val, 0, 6) . str_repeat('*', strlen($val) - 10) . substr($val, -4);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>PayPal Diagnóstico</title>
<style>
  body { font-family: system-ui, sans-serif; max-width: 780px; margin: 40px auto; padding: 0 20px; background: #f8fafc; color: #1e293b; }
  h1   { color: #0070ba; border-bottom: 2px solid #0070ba; padding-bottom: 8px; }
  h2   { margin-top: 32px; color: #334155; }
  table { border-collapse: collapse; width: 100%; margin-top: 12px; }
  th, td { border: 1px solid #cbd5e1; padding: 10px 14px; text-align: left; font-size: 0.9rem; }
  th   { background: #e2e8f0; font-weight: 600; }
  .box { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
  .success { background: #f0fdf4; border-color: #86efac; }
  .error   { background: #fef2f2; border-color: #fca5a5; }
  .warn    { background: #fffbeb; border-color: #fcd34d; }
  pre  { background: #1e293b; color: #94a3b8; padding: 14px; border-radius: 6px; overflow-x: auto; font-size: 0.82rem; }
  .step { counter-increment: step; }
  .step::before { content: "Paso " counter(step) ": "; font-weight: bold; color: #0070ba; }
  ol.steps { counter-reset: step; list-style: none; padding: 0; }
  ol.steps li { margin: 10px 0; padding: 12px 16px; background: #f1f5f9; border-radius: 6px; }
</style>
</head>
<body>
<h1>🔍 PayPal — Diagnóstico de Integración</h1>
<p>Entorno: <strong><?= htmlspecialchars(APP_ENV) ?></strong> | Modo PayPal: <strong><?= htmlspecialchars($mode) ?></strong></p>

<div class="box">
  <h2 style="margin-top:0">1. Variables de entorno</h2>
  <table>
    <tr><th>Variable</th><th>Valor (enmascarado)</th><th>Estado</th></tr>
    <tr>
      <td>PAYPAL_CLIENT_ID</td>
      <td><code><?= htmlspecialchars(mask($clientId)) ?></code> (longitud: <?= strlen($clientId) ?>)</td>
      <td><?= $clientIdOk ? $ok : $bad ?></td>
    </tr>
    <tr>
      <td>PAYPAL_SECRET</td>
      <td><code><?= htmlspecialchars(mask($secret)) ?></code> (longitud: <?= strlen($secret) ?>)</td>
      <td><?= $secretOk ? $ok : $bad ?></td>
    </tr>
    <tr>
      <td>PAYPAL_MODE</td>
      <td><code><?= htmlspecialchars($mode) ?></code></td>
      <td><?= in_array($mode, ['sandbox','production']) ? $ok : $bad ?></td>
    </tr>
  </table>
</div>

<div class="box <?= $tokenResult ? 'success' : ($tokenError ? 'error' : 'warn') ?>">
  <h2 style="margin-top:0">2. Test de conexión OAuth (Access Token)</h2>
  <?php if (!$clientIdOk || !$secretOk): ?>
    <p><?= $warn ?> Las credenciales no son válidas — se omitió el test de conexión.</p>
  <?php elseif ($tokenResult): ?>
    <p><?= $ok ?> <strong>¡Conexión exitosa con PayPal!</strong></p>
    <table>
      <tr><th>Campo</th><th>Valor</th></tr>
      <tr><td>token_type</td><td><?= htmlspecialchars($tokenResult['token_type']) ?></td></tr>
      <tr><td>expires_in</td><td><?= htmlspecialchars((string)$tokenResult['expires_in']) ?> segundos</td></tr>
      <tr><td>scope</td><td><small><?= htmlspecialchars($tokenResult['scope']) ?></small></td></tr>
    </table>
  <?php else: ?>
    <p><?= $bad ?> <strong>La conexión con PayPal falló.</strong></p>
    <pre><?= htmlspecialchars($tokenError ?? '') ?></pre>
    <p>Verifica que las credenciales en <code>.env</code> son correctas y pertenecen a la cuenta de <strong>Sandbox</strong>.</p>
  <?php endif; ?>
</div>

<div class="box">
  <h2 style="margin-top:0">3. Estructura de respuesta esperada (create-order)</h2>
  <p>El endpoint <code>POST /api/pagos/paypal/create</code> debe devolver exactamente:</p>
<pre>{
  "success": true,
  "data": {
    "paypal_order_id": "1AB23456CD789012E",   ← string, el Order ID de PayPal
    "pedido_id": 42                            ← integer, el ID interno del pedido
  }
}</pre>
  <p>El <code>checkout.js</code> extrae: <code>orderData.data.paypal_order_id</code> y lo retorna al SDK de PayPal.</p>
  <p>⚠ PayPal SDK requiere que <code>createOrder</code> retorne <strong>únicamente el string del Order ID</strong>, nunca un objeto.</p>
</div>

<?php if (!$clientIdOk || !$secretOk): ?>
<div class="box error">
  <h2 style="margin-top:0">🚨 Acción requerida — Configurar credenciales reales</h2>
  <ol class="steps">
    <li class="step">Ve a <a href="https://developer.paypal.com/dashboard/applications/sandbox" target="_blank">developer.paypal.com → Apps &amp; Credentials → Sandbox</a></li>
    <li class="step">Crea o selecciona una app existente (ej. "Default Application")</li>
    <li class="step">Copia el <strong>Client ID</strong> y el <strong>Secret</strong></li>
    <li class="step">Edita el archivo <code>.env</code> en la raíz del proyecto:
<pre>PAYPAL_CLIENT_ID=AXtu...TuClientIdReal   (≥ 80 caracteres)
PAYPAL_SECRET=EL...TuSecretReal          (≥ 80 caracteres)
PAYPAL_MODE=sandbox</pre>
    </li>
    <li class="step">Guarda el archivo y recarga esta página para verificar</li>
  </ol>
</div>
<?php endif; ?>

<hr style="margin:40px 0;border-color:#e2e8f0">
<p style="color:#64748b;font-size:0.8rem">
  ⚠ Este archivo solo es accesible en modo <code>development</code>.
  Elimínalo o restringe el acceso antes de ir a producción.
</p>
</body>
</html>
