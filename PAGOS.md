# Integración de Pagos — UCT E-Commerce

Documentación técnica de Transbank Webpay Plus, la pasarela de pago implementada en el proyecto para permitir pagos con tarjetas Visa, Mastercard y RedCompra.

---

## Índice

1. [Arquitectura general de pagos](#1-arquitectura-general-de-pagos)
2. [Transbank Webpay Plus](#2-transbank-webpay-plus)
3. [Cómo funciona el dinero](#3-cómo-funciona-el-dinero)
4. [Tarjetas de prueba](#4-tarjetas-de-prueba)
5. [Errores comunes](#5-errores-comunes)
6. [Cómo agregar una nueva pasarela](#6-cómo-agregar-una-nueva-pasarela)

---

## 1. Arquitectura general de pagos

### Flujo desde el carrito

```
[Usuario en cart.html]
        │
        │  "Procesar Pago Seguro"
        ▼
POST /api/checkout          ← Crea la orden en BD con status 'pendiente_pago'
        │                      Devuelve { order_id }
        ▼
renderPaymentMethods()      ← Muestra botones: Webpay / PayPal / Simular
        │
        ├──[Webpay]──► POST /api/payment/transbank/create
        │                      → form POST a Transbank → transbank-return.php
        │
        ├──[PayPal]──► POST /api/payment/paypal/create → captura
        │
        └──[Simular]─► POST /api/payment/simulate
```

### Patrón de tres capas

Cada pasarela sigue el mismo patrón:

| Capa | Archivo | Responsabilidad |
|------|---------|-----------------|
| Servicio | `src/Services/{Pasarela}Service.php` | Comunicación HTTP con la API externa |
| Controlador | `src/Controllers/{Pasarela}Controller.php` | JWT, validación de orden, lógica de negocio |
| Retorno | `public/{pasarela}-return.php` | Página HTML de resultado post-pago |

### Seguridad aplicada en todos los pagos

1. **JWT obligatorio** — `AuthMiddleware::authenticate()` en todos los endpoints de creación
2. **Validación de propiedad** — la orden debe pertenecer al usuario autenticado y estar en `status = 'pendiente_pago'`
3. **Verificación server-side** — nunca se confía únicamente en los parámetros del redirect; siempre se consulta la API de la pasarela
4. **Validación de referencia cruzada** — el `buy_order` retornado por Transbank se compara contra el `order_id` de la URL
5. **ACID en `markAsPaidAndDeductStock()`** — el marcado como pagado y el descuento de stock ocurren en una sola transacción MySQL; si algo falla, se revierte todo

---

## 2. Transbank Webpay Plus

### ¿Qué es?

La pasarela de pagos más usada en Chile. Permite pagar con:
- Tarjetas de crédito **Visa** y **Mastercard**
- Tarjetas de débito **RedCompra**

Operada por Transbank, empresa propiedad de los bancos chilenos.

### Credenciales de integración

Las credenciales de integración son **públicas**: Transbank las publica para que cualquier desarrollador pueda probar sin registrarse.

| Variable `.env` | Valor |
|-----------------|-------|
| `TBK_COMMERCE_CODE` | `597055555532` |
| `TBK_API_KEY` | `579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C` |
| `TBK_ENVIRONMENT` | `integration` |

**Endpoints:**
| Ambiente | Host |
|----------|------|
| Integración (pruebas) | `webpay3gint.transbank.cl` |
| Producción | `webpay3g.transbank.cl` |

### Flujo detallado

```
Frontend                   Backend (PHP)              Transbank API
───────────────────────────────────────────────────────────────────
[Clic "Pagar con Tarjeta"]
        │
        ▼
POST /api/payment/transbank/create
  Body: { order_id: 42 }
                    ┌──────────────────────────────┐
                    │ 1. Verificar JWT              │
                    │ 2. Buscar orden en BD         │
                    │    (status = pendiente_pago)  │
                    │ 3. Calcular monto CLP         │
                    │    total_USD × 1000           │
                    │ 4. Construir return_url       │
                    │ 5. Generar buy_order único    │
                    │    "UCT-{orderId}-{timestamp}"│
                    └──────────┬───────────────────┘
                               │
                               │ POST /transactions
                               │ { buy_order, session_id,
                               │   amount, return_url }
                               ├──────────────────────► webpay3gint.transbank.cl
                               │◄────────────────────── { token, url }
        ◄──────────────────────┘
  Response: { token, url }

[Frontend crea un form y lo envía]
  <form method="POST" action="{url}">
    <input name="token_ws" value="{token}">
  </form>.submit()
        │
        ▼
        [Página de pago de Transbank]
        Usuario ingresa tarjeta, CVV, RUT y clave web
        │
        ▼  Redirect POST a return_url
transbank-return.php?order_id=42
  $_POST['token_ws'] = "abc123..."
        │
        ▼ PUT /transactions/{token}  ──► Transbank API
                                    ◄── { response_code, amount,
                                          authorization_code,
                                          card_detail, buy_order }
        │
        ├── response_code === 0
        │       └─► markAsPaidAndDeductStock(42)
        │           ✅ Muestra página de éxito
        │
        └── response_code !== 0
                └─► ❌ Muestra página de rechazo (sin cargo)
```

### Por qué form POST y no window.location

Transbank exige que el `token_ws` llegue en el **body** del request HTTP (método POST), no en la URL. Si se enviara por GET:
- El token quedaría expuesto en el historial del navegador
- Aparecería en los logs del servidor web
- Podría ser reutilizado por un tercero

```javascript
// ✅ Correcto — token en el body vía form POST
const form       = document.createElement('form');
form.method      = 'POST';
form.action      = res.url;
const input      = document.createElement('input');
input.type       = 'hidden';
input.name       = 'token_ws';
input.value      = res.token;
form.appendChild(input);
document.body.appendChild(form);
form.submit();

// ❌ Incorrecto — token expuesto en la URL
window.location.href = res.url + '?token=' + res.token;
```

### Archivos implementados

#### `src/Services/TransbankService.php`

Cliente HTTP para la API REST v1.2 de Transbank.

```php
class TransbankService {
    private const API_URL =
        'https://webpay3gint.transbank.cl/rswebpaytransaction/api/webpay/v1.2/transactions';

    // Autenticación: headers por petición
    // Tbk-Api-Key-Id:     {commerce_code}
    // Tbk-Api-Key-Secret: {api_key}

    public function createTransaction(
        string $buyOrder,   // "UCT-{orderId}-{timestamp}" — máx 26 chars, solo [A-Za-z0-9_-]
        string $sessionId,  // "SID-{userId}-{timestamp}"
        int    $amount,     // CLP entero sin decimales
        string $returnUrl   // URL accesible por los servidores de Transbank
    ): array                // ['token' => '...', 'url' => '...']

    public function confirmTransaction(string $token): array
    // response_code === 0  → aprobado
    // response_code !== 0  → rechazado (ver tabla de códigos abajo)
}
```

**Método HTTP usado por operación:**

| Operación | Método | Path |
|-----------|--------|------|
| Crear transacción | `POST` | `/transactions` |
| Confirmar (commit) | `PUT` | `/transactions/{token}` |

#### `src/Controllers/TransbankController.php`

```php
// POST /api/payment/transbank/create
public function create(): void {
    $user    = AuthMiddleware::authenticate();
    $orderId = (int)$input['order_id'];

    // Verifica que la orden existe, es del usuario y está pendiente
    $stmt = $db->prepare(
        "SELECT total_amount FROM orders
         WHERE id = ? AND user_id = ? AND status = 'pendiente_pago'"
    );

    // return_url construido dinámicamente — funciona en localhost y producción
    $returnUrl = "$protocol://$host$scriptDir/transbank-return.php?order_id=$orderId";

    // Conversión de moneda: precios en BD están en USD (formato académico)
    // Tasa demo: 1 USD ≈ 1000 CLP
    $amountCLP = (int)round((float)$order['total_amount'] * 1000);

    $result = $tbk->createTransaction(
        "UCT-$orderId-" . time(),
        "SID-{$user['user_id']}-" . time(),
        $amountCLP,
        $returnUrl
    );

    Response::json(['token' => $result['token'], 'url' => $result['url']]);
}
```

#### `public/transbank-return.php`

Página PHP standalone con su propio bootstrap (autoloader + `.env`). Maneja tres casos:

| Situación | Señal recibida | Acción |
|-----------|---------------|--------|
| Pago procesado | `$_POST['token_ws']` presente | Confirmar con `PUT` → verificar `response_code` |
| Usuario canceló | Solo `$_POST['TBK_TOKEN']` (sin `token_ws`) | Mostrar "pago cancelado" |
| Acceso directo | Sin parámetros POST | Mostrar error de acceso inválido |

**Validación de referencia cruzada:**
```php
// buy_order enviado: "UCT-42-1750000000"
// Extraemos el orderId de la respuesta de Transbank
$parts          = explode('-', $result['buy_order']); // ["UCT", "42", "1750000000"]
$orderIdFromTbk = (int)$parts[1];                     // 42

if ($orderIdFromTbk !== $orderId) {
    // Alguien manipuló el order_id en la URL GET
    // → mostrar error de validación, NO marcar como pagado
}
```

### Códigos de respuesta de Transbank

| `response_code` | Significado |
|-----------------|-------------|
| `0` | ✅ Transacción aprobada |
| `-1` | Rechazada por el banco emisor |
| `-2` | Error en la tarjeta |
| `-3` | Error en la transacción |
| `-4` | Rechazado por Transbank |
| `-5` | Rechazo por error de tasa |
| `-6` | Cupo insuficiente |
| `-7` | Número de intentos excedido |
| `-8` | Tarjeta bloqueada |

---

## 3. Cómo funciona el dinero

### El ambiente que usa este proyecto

El proyecto está configurado con el **ambiente de integración** de Transbank (`webpay3gint.transbank.cl`). Es un entorno de pruebas simuladas — **no existe dinero real involucrado en ningún momento**.

### ¿Qué pasa con los pagos de prueba?

Cuando se usa una tarjeta de prueba:

1. Transbank simula una autorización bancaria en sus propios servidores de prueba
2. Devuelve `response_code: 0` (aprobado) o un código de error según la tarjeta usada
3. No se mueve ningún dinero — es una simulación completa
4. Los números de tarjeta de prueba no existen como tarjetas reales; son números inventados por Transbank para testing

### ¿Qué pasa si se ingresa una tarjeta real?

**No funcionaría, y no se cobraría nada.** Hay dos razones:

**Razón 1 — El ambiente de integración rechaza tarjetas reales.**
`webpay3gint.transbank.cl` solo acepta los números de tarjeta de prueba que Transbank tiene programados. Cualquier número real es simplemente rechazado (`response_code !== 0`).

**Razón 2 — No hay un comercio afiliado.**
Para que Transbank procese pagos reales se necesita:
- Ser una empresa con RUT chileno
- Firmar un contrato comercial con Transbank
- Pagar una comisión por transacción (aprox. 1.5–2.5% por venta)
- Obtener un `commerce_code` y `api_key` de **producción** (distintos a los de integración)
- Cambiar el host a `webpay3g.transbank.cl`

Sin eso, el servidor de producción rechazaría cualquier intento con HTTP 401.

### ¿A dónde va el dinero en un comercio real?

En un entorno de producción con contrato, el flujo del dinero es:

```
Cliente paga con tarjeta
        │
        ▼
Banco emisor del cliente (ej: Banco de Chile, Santander, BCI...)
  Descuenta el monto de la tarjeta del cliente
        │
        ▼
Transbank actúa como intermediario
  Retiene el dinero temporalmente
  Cobra su comisión por transacción
        │
        ▼
Cuenta bancaria del comercio afiliado
  Transbank deposita el monto neto
  (normalmente 2–3 días hábiles después de la venta)
```

### Resumen: ¿se cobra dinero real?

| Situación | ¿Se cobra dinero real? |
|-----------|----------------------|
| Integración + tarjeta de prueba | ❌ No — es simulación completa |
| Integración + tarjeta real | ❌ No — el servidor de pruebas la rechaza |
| Producción + sin contrato con Transbank | ❌ No — sin commerce code válido no procesa nada |
| Producción + contrato firmado + tarjeta real | ✅ Sí — el dinero va a la cuenta bancaria del comercio |

**Con la configuración actual del proyecto ninguna tarjeta real puede ser cobrada.**

---

## 4. Tarjetas de prueba

Las siguientes tarjetas funcionan en el **ambiente de integración** de Transbank. No realizan cargos reales.

| Red | Número de tarjeta | CVV | Vencimiento |
|-----|-------------------|-----|-------------|
| Visa | `4051 8856 0044 6623` | `123` | Cualquier fecha futura |
| Mastercard | `5186 0595 5959 0568` | `123` | Cualquier fecha futura |

**Datos del titular (en el formulario de Transbank):**

| Campo | Valor |
|-------|-------|
| RUT | `11.111.111-1` |
| Contraseña web | `123` |

**Pasos para completar el pago de prueba:**
1. Agregar productos al carrito e iniciar sesión
2. Hacer clic en "Procesar Pago Seguro"
3. Seleccionar "Pagar con Tarjeta"
4. En el formulario de Transbank, ingresar el número de tarjeta
5. Fecha de vencimiento: cualquier mes/año futuro (ej: `12/26`)
6. CVV: `123`
7. RUT del titular: `11111111-1`
8. Contraseña web: `123`
9. Confirmar el pago

---

## 5. Errores comunes

| Error | Causa | Solución |
|-------|-------|----------|
| `Could not resolve host: webpay3gw.transbank.cl` | Dominio incorrecto (tiene una `w` de más) | Usar `webpay3g**int**.transbank.cl` |
| `HTTP 401 Not Authorized` | Credenciales de integración contra el host de producción | El host `webpay3g.cl` es producción; usar `webpay3gint.cl` |
| `HTTP 400 Message parsing error` | Campo inválido en el body JSON | Verificar que `buy_order` tenga máx 26 chars y solo `[A-Za-z0-9_-]` |
| `response_code !== 0` | Tarjeta de prueba rechazada | Usar exactamente los números de tarjeta listados arriba |
| Página en blanco al volver | `transbank-return.php` no encontró el archivo | Verificar que el archivo existe en `public/` y que `.htaccess` no lo redirige |

---

## 6. Cómo agregar una nueva pasarela

El patrón es siempre el mismo. Para agregar, por ejemplo, **Stripe**:

### Paso 1 — Servicio

Crear `src/Services/StripeService.php`:

```php
namespace Src\Services;

class StripeService {
    private const BASE_URL = 'https://api.stripe.com/v1';
    private string $secretKey;

    public function __construct() {
        $this->secretKey = getenv('STRIPE_SECRET_KEY') ?: '';
    }

    public function createPaymentIntent(int $amountCents, string $currency = 'clp'): array {
        // POST https://api.stripe.com/v1/payment_intents
        // Authorization: Bearer {secretKey}
        // Body: amount, currency, automatic_payment_methods[enabled]=true
    }

    public function retrievePaymentIntent(string $id): array {
        // GET https://api.stripe.com/v1/payment_intents/{id}
    }
}
```

### Paso 2 — Controlador

Crear `src/Controllers/StripeController.php`:

```php
class StripeController {
    public function create(): void {
        $user    = AuthMiddleware::authenticate();   // Siempre JWT primero
        $orderId = (int)$input['order_id'];
        // 1. Verificar orden (status = pendiente_pago, user_id coincide)
        // 2. Instanciar StripeService (lazy, en el método — no en __construct)
        // 3. Llamar createPaymentIntent()
        // 4. Devolver client_secret al frontend
    }
}
```

> **Importante:** instanciar el servicio **dentro del método**, no en el constructor.
> Si el servicio lanza una excepción al arrancar (token inválido, etc.) y se instancia
> en el constructor, el error ocurre al cargar el router y tumba **todos** los endpoints.

### Paso 3 — Ruta

En `public/index.php`:

```php
use Src\Controllers\StripeController;
$router->add('POST', '/api/payment/stripe/create', [new StripeController(), 'create']);
```

### Paso 4 — Página de retorno

Crear `public/stripe-return.php` con su propio bootstrap (igual que `transbank-return.php`).

### Paso 5 — Frontend

En `public/js/cart.js`, dentro de `renderPaymentMethods()`, agregar el botón y su función handler.

### Paso 6 — `.env`

```
STRIPE_SECRET_KEY=sk_test_xxxx
STRIPE_PUBLISHABLE_KEY=pk_test_xxxx
```

### Checklist de seguridad

- [ ] JWT requerido en el endpoint de creación
- [ ] Verificar que la orden pertenece al usuario y está en `pendiente_pago`
- [ ] Instanciar el servicio de forma lazy (en el método, no en el constructor)
- [ ] Verificar el pago server-side (nunca confiar solo en parámetros GET/POST del redirect)
- [ ] Validar referencia cruzada entre la respuesta de la pasarela y el `order_id`
- [ ] Manejar cancelación / timeout / acceso directo a la página de retorno
- [ ] No loguear datos sensibles (números de tarjeta, tokens completos)
