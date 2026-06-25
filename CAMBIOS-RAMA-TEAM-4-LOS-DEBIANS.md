# Documentación de Cambios — Rama `team-4-los-debians`

**Repositorio:** `dd-software/ecommerce-project-s1`  
**Rama:** `team-4-los-debians`  
**Periodo de trabajo:** 11 junio 2026 — 17 junio 2026  
**Integrantes del equipo:**
- **Carlos Sepúlveda** (`sidhartaz` / `sepulvedacarloscd@gmail.com`)
- **Luis Ignacio Cerda Zurita** (`Nylarion`)
- **Braulio Palma** (`braulio.palma05@gmail.com`)

---

## Resumen Ejecutivo

La rama `team-4-los-debians` implementa un conjunto de correcciones de seguridad y mejoras funcionales sobre el módulo de pagos y checkout del e-commerce. Los cambios abordan vulnerabilidades IDOR, aseguran el webhook de pagos, corrigen la lógica de reintentos, y actualizan la infraestructura frontend y de configuración del servidor.

---

## Historial de Commits (orden cronológico ascendente)

| Hash | Fecha | Autor | Mensaje |
|------|-------|-------|---------|
| `733042c` | 2026-06-11 | Braulio Palma | Delete md-files directory |
| `188912a` | 2026-06-13 | sidhartaz | Merge remote-tracking branch 'origin/main' into team-4-los-debians |
| `cbc77c1` | 2026-06-14 | sidhartaz | DEB-05: Validar propiedad del carrito en checkout para prevenir IDOR |
| `b79be37` | 2026-06-14 | sidhartaz | DEB-06: Actualizar checkout.yaml con endpoints, campos y tipos reales |
| `9a65e59` | 2026-06-14 | sidhartaz | DEB-07: fecha_pago solo se registra en pagos aprobados, no en rechazados |
| `31d6fb0` | 2026-06-14 | Luis Ignacio Cerda Zurita | DEB-01: Corregir IDOR en consulta y procesamiento de pagos |
| `c294c6f` | 2026-06-14 | Luis Ignacio Cerda Zurita | DEB-02 — Asegurar webhook de pagos (firma + idempotencia) |
| `04bcd0a` | 2026-06-14 | Luis Ignacio Cerda Zurita | DEB-02 — Asegurar webhook de pagos (firma + idempotencia) |
| `f3d73b4` | 2026-06-14 | Luis Ignacio Cerda Zurita | DEB-02 — Asegurar webhook de pagos (firma + idempotencia) |
| `ee65868` | 2026-06-14 | Luis Ignacio Cerda Zurita | DEB-03 — Permitir reintento de pago tras rechazo |
| `bdee9cf` | 2026-06-17 | Braulio Palma | Add files via upload — `src/Carrito/CarritoService.php` |
| `adfbc4d` | 2026-06-17 | Braulio Palma | Add files via upload — `src/Carrito/CarritoRepository.php` |
| `70cd321` | 2026-06-17 | Braulio Palma | Add files via upload — `src/Carrito/CarritoController.php` |
| `ffe84d5` | 2026-06-17 | Braulio Palma | Add files via upload — `src/Admin/AdminService.php` |
| `cdfa066` | 2026-06-17 | Braulio Palma | Add files via upload — `src/Admin/AdminRepository.php` |
| `7a4d2f7` | 2026-06-17 | Braulio Palma | Add files via upload — `src/Admin/AdminController.php` |
| `fb1b1b8` | 2026-06-17 | Luis Ignacio Cerda Zurita | Delete public directory |
| `148ea96` | 2026-06-17 | Luis Ignacio Cerda Zurita | Delete config directory |
| `a3a8350` | 2026-06-17 | Luis Ignacio Cerda Zurita | Config Folder — Nueva carpeta de configuración |
| `340155b` | 2026-06-17 | Luis Ignacio Cerda Zurita | Public Folder — Nueva carpeta pública |
| `4f303fd` | 2026-06-17 | sidhartaz | src core y pagos |
| `3b5da93` | 2026-06-17 | sidhartaz | Merge branch 'team-4-los-debians' (resolución de conflictos) |
| `8a3c3ba` | 2026-06-17 | sidhartaz | actualizar htaaccess |
| `fd4f18e` | 2026-06-17 | sidhartaz | actualizar — README y seed.sql |

---

## Detalle de Cambios por Ticket / Commit

---

### `733042c` — Delete md-files directory
**Autor:** Braulio Palma | **Fecha:** 2026-06-11

**Archivos eliminados:**
- `md-files/api-contracts.md` (122 líneas eliminadas)
- `md-files/ux-ui-system-design.md` (101 líneas eliminadas)

**Motivo:** Limpieza de documentación desactualizada de la carpeta raíz `md-files/`. Los contratos de API fueron migrados a `specs/03-contratos-api/`.

---

### `cbc77c1` — DEB-05: Validar propiedad del carrito en checkout para prevenir IDOR
**Autor:** sidhartaz | **Fecha:** 2026-06-14

**Problema:** Un usuario autenticado podía hacer checkout con el `carrito_id` de otro usuario, ejecutando compras en nombre ajeno (vulnerabilidad IDOR en checkout).

**Archivos modificados:**
- `src/Carrito/CarritoRepository.php`
- `src/Checkout/CheckoutService.php`

#### `src/Carrito/CarritoRepository.php` — Nuevo método `obtenerPorId()`

```php
// NUEVO — permite verificar a quién pertenece un carrito antes del checkout
public function obtenerPorId(int $carritoId): ?array
{
    $stmt = $this->db->prepare(
        "SELECT id, id_usuario FROM carritos WHERE id = :id"
    );
    $stmt->execute([':id' => $carritoId]);
    return $stmt->fetch() ?: null;
}
```

#### `src/Checkout/CheckoutService.php` — Validación de propiedad antes de procesar

```php
// AGREGADO en crearPedido() — antes de obtener los ítems del carrito
// Validar que el carrito pertenece al usuario autenticado (RN-D02)
$carrito = (new CarritoRepository())->obtenerPorId($carritoId);
if (!$carrito || (int)$carrito['id_usuario'] !== $userId) {
    throw new \RuntimeException('El carrito no pertenece al usuario autenticado.');
}
```

---

### `b79be37` — DEB-06: Actualizar checkout.yaml con endpoints, campos y tipos reales
**Autor:** sidhartaz | **Fecha:** 2026-06-14

**Archivo modificado:** `specs/03-contratos-api/checkout.yaml`  
**Estadística:** +249 líneas, -25 líneas (versión 1.0.0 → 2.0.0)

**Cambios principales:**
- Servidor actualizado de `http://localhost:8084` a `http://localhost/Ecomers/public`
- Campos del request body renombrados para coincidir con la implementación real:
  - `carritoId` → `carrito_id` (tipo `integer`)
  - `direccionEnvio` → `direccion_envio`
  - `notasEspeciales` → `notas`
  - Nuevo campo: `telefono` y `cupon`
- Añadido `security: bearerAuth: []` a todos los endpoints protegidos
- Documentados todos los endpoints existentes: `POST /api/checkout`, `GET /api/checkout/pedidos/{pedidoId}`, `GET /api/checkout/mis-pedidos`, y endpoints de administración
- Respuestas de error documentadas: 400, 401, 403, 404, 422, 500

---

### `9a65e59` — DEB-07: fecha_pago solo se registra en pagos aprobados
**Autor:** sidhartaz | **Fecha:** 2026-06-14

**Problema:** La columna `fecha_pago` se guardaba con `NOW()` incluso para pagos rechazados, generando datos inconsistentes en la base de datos.

**Archivo modificado:** `src/Pagos/PagosRepository.php`

```php
// ANTES
$stmt = $this->db->prepare(
    "INSERT INTO pagos (id_pedido, metodo_pago, monto, referencia_externa, estado, respuesta_pasarela, fecha_pago)
     VALUES (:pedido, :metodo, :monto, :ref, :estado, :respuesta, NOW())"
);

// DESPUÉS
$fechaPago = $estado === 'aprobado' ? 'NOW()' : 'NULL';
$stmt = $this->db->prepare(
    "INSERT INTO pagos (id_pedido, metodo_pago, monto, referencia_externa, estado, respuesta_pasarela, fecha_pago)
     VALUES (:pedido, :metodo, :monto, :ref, :estado, :respuesta, {$fechaPago})"
);
```

La `fecha_pago` queda como `NULL` cuando el pago es rechazado y se registra la fecha real solo cuando el estado es `'aprobado'`.

---

### `31d6fb0` — DEB-01: Corregir IDOR en consulta y procesamiento de pagos
**Autor:** Luis Ignacio Cerda Zurita | **Fecha:** 2026-06-14

**Problema:** Los endpoints `POST /api/pagos/procesar` y `GET /api/pagos/estado/{pedidoId}` no verificaban que el pedido perteneciera al usuario autenticado. Cualquier usuario autenticado podía procesar o consultar el pago del pedido de otro usuario (IDOR).

**Archivo modificado:** `src/Pagos/PagosController.php` (+30 líneas)

#### Protección en `procesar()`:

```php
$pedidoId = (int)$data['pedido_id'];

// Validar existencia del pedido y pertenencia del usuario
$pedido = $this->service->obtenerPedido($pedidoId);
if (!$pedido) {
    $response->error('ORDER_NOT_FOUND', 'Pedido no encontrado.', 404);
    return;
}

$esAdmin = ($user['rol'] ?? '') === 'admin';
if (!$esAdmin && (int)$pedido['id_usuario'] !== (int)$user['id']) {
    $response->error('INSUFFICIENT_PERMISSIONS', 'No tienes permiso para interactuar con este pedido.', 403);
    return;
}
```

#### Protección en `consultarEstado()`:

```php
// Validar existencia del pedido y pertenencia del usuario antes de exponer el estado del pago
$pedido = $this->service->obtenerPedido($pedidoId);
if (!$pedido) {
    $response->error('ORDER_NOT_FOUND', 'Pedido no encontrado.', 404);
    return;
}

$esAdmin = ($user['rol'] ?? '') === 'admin';
if (!$esAdmin && (int)$pedido['id_usuario'] !== (int)$user['id']) {
    $response->error('INSUFFICIENT_PERMISSIONS', 'No tienes permiso para ver este pedido.', 403);
    return;
}
```

Los administradores (`rol === 'admin'`) mantienen acceso completo a todos los pedidos.

---

### `f3d73b4` / `c294c6f` / `04bcd0a` — DEB-02: Asegurar webhook de pagos (firma + idempotencia)
**Autor:** Luis Ignacio Cerda Zurita | **Fecha:** 2026-06-14

**Descripción del commit:**
> (1) Se añade validación del header `X-Webhook-Secret` para mitigar ataques de spoofing en el webhook. (2) Se introduce una cláusula de guarda en `PagosService::procesarWebhook()` que intercepta estados 'aprobado' preexistentes, garantizando la idempotencia del endpoint ante reintentos de la pasarela y protegiendo el inventario de dobles descuentos (RN-E02).

**Archivos modificados:** `src/Pagos/PagosController.php`, `src/Pagos/PagosService.php`

**Cambios clave:**
1. **Verificación de firma del webhook:** El endpoint webhook ahora valida el header `X-Webhook-Secret` antes de procesar cualquier notificación de la pasarela.
2. **Idempotencia:** Se agrega una cláusula de guarda que detecta si un pedido ya está en estado `'aprobado'` y retorna inmediatamente sin reprocesar, evitando dobles descuentos de inventario.

> Nota: Este ticket tuvo tres commits (`c294c6f`, `04bcd0a`, `f3d73b4`) por iteraciones sucesivas antes de quedar con el código final.

---

### `ee65868` — DEB-03: Permitir reintento de pago tras rechazo
**Autor:** Luis Ignacio Cerda Zurita | **Fecha:** 2026-06-14

**Descripción del commit:**
> Se corrige la lógica de `PagosService::procesarPago()` ante transacciones declinadas. El pedido ya no muta a estado 'cancelado'; en su lugar, preserva el estado 'pendiente' y registra el intento fallido en el historial de estados de la orden. Esto aprovecha el modelo relacional 1:N de pagos y optimiza la experiencia de usuario permitiendo reintentar el pago de forma inmediata.

**Archivo modificado:** `src/Pagos/PagosService.php` (+5 líneas, -7 líneas)

```php
// ANTES — pago rechazado cancelaba el pedido permanentemente
$this->checkoutService->actualizarEstado(
    pedidoId: $pedidoId,
    nuevoEstado: 'cancelado',
    userId: $userId,
    comentario: 'Pago rechazado: ' . $respuestaPasarela['mensaje']
);

// DESPUÉS — pago rechazado mantiene el pedido en 'pendiente' para reintentar
// CORRECCIÓN: Mantener el pedido como 'pendiente' registrando el intento fallido
$this->checkoutService->actualizarEstado(
    pedidoId: $pedidoId,
    nuevoEstado: 'pendiente', // Se mantiene pendiente para permitir reintentos
    userId: $userId,
    comentario: 'Intento de pago rechazado. Transacción: ' . $transaccionId . ' - Motivo: ' . $respuestaPasarela['mensaje']
);
```

Adicionalmente, se corrigió un comentario `docblock` duplicado en `procesarWebhook()` (un bloque `/** ... */` extra fue eliminado).

---

### `bdee9cf` / `adfbc4d` / `70cd321` / `ffe84d5` / `cdfa066` / `7a4d2f7` — Actualización de módulos Carrito y Admin
**Autor:** Braulio Palma | **Fecha:** 2026-06-17

Serie de uploads que reemplazaron los archivos de los módulos `Carrito` y `Admin` con versiones actualizadas.

| Commit | Archivo | Cambio |
|--------|---------|--------|
| `bdee9cf` | `src/Carrito/CarritoService.php` | Reemplazo completo (229 ins / 229 del) |
| `adfbc4d` | `src/Carrito/CarritoRepository.php` | Reemplazo completo (199 ins / 199 del) |
| `70cd321` | `src/Carrito/CarritoController.php` | Reemplazo completo (215 ins / 208 del) |
| `ffe84d5` | `src/Admin/AdminService.php` | Reemplazo completo (194 ins / 194 del) |
| `cdfa066` | `src/Admin/AdminRepository.php` | Reemplazo completo (366 ins / 366 del) |
| `7a4d2f7` | `src/Admin/AdminController.php` | Reemplazo completo (222 ins / 222 del) |

---

### `fb1b1b8` + `148ea96` → `a3a8350` + `340155b` — Reemplazo de carpetas `config/` y `public/`
**Autor:** Luis Ignacio Cerda Zurita | **Fecha:** 2026-06-17

Proceso de dos pasos: primero se elimina la carpeta existente y luego se sube la versión actualizada.

#### `config/app.php` y `config/database.php` — Reemplazados

Archivos de configuración actualizados con los valores de conexión, rutas y entorno correctos para el despliegue en XAMPP.

#### Carpeta `public/` — Reemplazada completamente

Archivos creados/actualizados:

| Archivo | Líneas | Descripción |
|---------|--------|-------------|
| `public/css/style.css` | 433 | Estilos del frontend completos |
| `public/index.html` | 322 | Página principal del e-commerce |
| `public/index.php` | 123 | Front controller PHP |
| `public/js/admin.js` | 656 | Panel de administración JS |
| `public/js/app.js` | 263 | Aplicación principal JS |
| `public/js/auth.js` | 158 | Módulo de autenticación JS |
| `public/js/carrito.js` | 251 | Módulo de carrito JS |
| `public/js/catalogo.js` | 448 | Módulo de catálogo JS |
| `public/js/checkout.js` | 246 | Módulo de checkout JS |
| `public/js/main.js` | 86 | Inicialización principal JS |
| `public/setup.php` | 92 | Script de setup/instalación |
| `public/store/app.js` | 553 | App JS de la tienda (nueva sección) |
| `public/store/index.html` | 263 | HTML de la tienda (nueva sección) |
| `public/store/style.css` | 344 | Estilos de la tienda (nueva sección) |

---

### `4f303fd` — src core y pagos
**Autor:** sidhartaz | **Fecha:** 2026-06-17

**Archivos modificados/creados:**

#### `src/Core/Request.php` — Corrección de URI relativa y parsing de headers

```php
// ANTES — URI absoluta, fallaba en subdirectorios
$this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// DESPUÉS — Strip del prefijo del subdirectorio para rutas relativas
$rawUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
if ($scriptDir !== '' && str_starts_with($rawUri, $scriptDir)) {
    $rawUri = substr($rawUri, strlen($scriptDir));
}
$this->uri = $rawUri ?: '/';
```

Headers ahora usan `getallheaders()` cuando está disponible (Apache/mod_php), y el fallback manual normaliza correctamente con `strtoupper()`. Corrige el bug donde `getHeader()` hacía un `str_replace` noop (`'-'` → `'-'`).

#### `src/Core/Router.php` — Corrección de comparación de path vacío

```php
// ANTES — comparación incorrecta
$path = '/' . trim($path, '/');
if ($path === '/') {
    $path = '';
}

// DESPUÉS — comparación correcta
if ($path === '') {
    $path = '/';
}

// Y en dispatch():
// ANTES
$uri = rtrim($request->getUri(), '/') ?: '';
// DESPUÉS
$uri = rtrim($request->getUri(), '/') ?: '/';
```

#### `src/Pagos/PagosRepository.php` — Corrección de formato de monto

```php
// ANTES — dividía el monto entre 100 (error: el monto ya estaba en pesos)
$pago['monto_formateado'] = '$' . number_format($pago['monto'] / 100, 0, ',', '.');

// DESPUÉS — formato directo en pesos
$pago['monto_formateado'] = '$' . number_format($pago['monto'], 0, ',', '.');
```

#### `src/Pagos/PagosService.php` — Corrección de formato de monto en respuesta

```php
// ANTES
'monto_formateado' => '$' . number_format($monto / 100, 0, ',', '.'),

// DESPUÉS
'monto_formateado' => '$' . number_format($monto, 0, ',', '.'),
```

#### `database/fix_encoding.sql` — Nuevo archivo

Script SQL que corrige la codificación UTF-8 de nombres y descripciones de productos con tildes y caracteres especiales en la base de datos:

```sql
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

UPDATE productos SET nombre='Monitor Dell 27 pulg 4K', descripcion='Monitor Dell UltraSharp U2723QE...' WHERE id=3;
UPDATE productos SET nombre='Teclado Mecánico Logitech MX', descripcion='...' WHERE id=4;
-- ... 15 productos corregidos en total
```

#### `database/fix_categorias_encoding.sql` — Nuevo archivo

Script SQL que corrige la codificación de las 10 categorías de la tienda:

```sql
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

UPDATE categorias SET nombre='Tecnología', descripcion='Productos tecnológicos y gadgets' WHERE id=1;
UPDATE categorias SET nombre='Computadores', ... WHERE id=2;
-- ... 10 categorías corregidas en total
```

#### `c.txt` y `cookies.txt` — Archivos temporales

Archivos de prueba/debug agregados durante el desarrollo (5 líneas cada uno).

---

### `8a3c3ba` — actualizar htaaccess
**Autor:** sidhartaz | **Fecha:** 2026-06-17

**Archivo creado:** `public/.htaccess` (29 líneas nuevas)

Configuración de Apache con:
- **Headers de seguridad HTTP** (via `mod_headers`):
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: DENY`
  - `X-XSS-Protection: 1; mode=block`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Content-Security-Policy` con política restrictiva (allow CDN de Bootstrap/Google Fonts)
  - `Permissions-Policy` (deshabilita geolocalización, micrófono, cámara)
  - Headers CORS para métodos y headers permitidos
- **Manejo de preflight OPTIONS** (responde 200 sin tocar PHP)
- **Reglas de rewrite** para el front controller `index.php`

```apache
Options -MultiViews -Indexes
RewriteEngine On

<IfModule headers_module>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; ..."
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Authorization, Content-Type, X-Request-ID, X-Session-Id"
    Header always set Access-Control-Max-Age "86400"
</IfModule>

RewriteCond %{REQUEST_METHOD} OPTIONS
RewriteRule ^ - [R=200,L]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

---

### `fd4f18e` — actualizar
**Autor:** sidhartaz | **Fecha:** 2026-06-17

**Archivos modificados:**
- `README.md` (+156 líneas, -123 líneas) — Actualización de documentación del proyecto
- `database/seed.sql` (+12 líneas, -11 líneas) — Actualización de contraseñas de usuarios semilla

#### `database/seed.sql` — Cambio de hashes de contraseñas

Los hashes bcrypt de todos los usuarios de prueba fueron actualizados. Las contraseñas ahora son:

| Email | Contraseña | Rol |
|-------|-----------|-----|
| `admin@uct.cl` | `admin123` | admin |
| `juan@email.com` | `password123` | cliente |
| `maria@email.com` | `password123` | cliente |
| `pedro@uct.cl` | `password123` | vendedor |
| `ana@uct.cl` | `password123` | supervisor |

```sql
-- ANTES (todos tenían el mismo hash con contraseña 'Password123!')
('Admin', 'Sistema', 'admin@uct.cl', '$2y$12$LJ3m4ys3Gql.ZhkBARVOge7JxSJgXPKCFs7r0dVIQGIgGndOMrHaa', 'admin', 1),

-- DESPUÉS (contraseñas diferenciadas y comentadas)
-- Contraseñas: admin@uct.cl => 'admin123' ; el resto => 'password123'
('Admin', 'Sistema', 'admin@uct.cl', '$2y$12$mnCE8v0ryLtPhYjMerpeiuGMayZrtQclzZJNS79gli6MZBcaYsALu', 'admin', 1),
('Juan', 'Pérez', 'juan@email.com', '$2y$12$asiPEUDSMGaYf86W8fBNZORPkB.ic1HEN2HDasOImS8u1ALZJTAYq', 'cliente', 1),
```

---

## Resumen de Archivos Modificados (total rama vs main)

| Archivo | Inserciones | Eliminaciones | Descripción |
|---------|-------------|---------------|-------------|
| `public/store/app.js` | 553 | 0 | Nueva sección de tienda |
| `public/js/admin.js` | 1312 | ~656 | Panel admin actualizado |
| `public/js/catalogo.js` | 865 | ~448 | Catálogo actualizado |
| `public/js/carrito.js` | 511 | ~251 | Carrito actualizado |
| `public/js/checkout.js` | 477 | ~246 | Checkout actualizado |
| `public/js/app.js` | 511 | ~263 | App principal actualizada |
| `src/Admin/AdminRepository.php` | 732 | 366 | Repositorio admin |
| `src/Carrito/CarritoService.php` | 458 | 229 | Servicio carrito |
| `src/Carrito/CarritoRepository.php` | 386 | ~199 | Repositorio carrito |
| `src/Admin/AdminService.php` | 388 | 194 | Servicio admin |
| `public/css/style.css` | 866 | ~433 | Estilos frontend |
| `src/Admin/AdminController.php` | 444 | 222 | Controlador admin |
| `src/Carrito/CarritoController.php` | 423 | ~208 | Controlador carrito |
| `public/js/auth.js` | 316 | ~158 | Autenticación JS |
| `public/index.html` | 355 | ~33 | Página principal |
| `public/store/index.html` | 263 | 0 | Nueva página tienda |
| `public/store/style.css` | 344 | 0 | Nuevos estilos tienda |
| `public/index.php` | 240 | ~123 | Front controller |
| `specs/03-contratos-api/checkout.yaml` | 274 | ~25 | Contrato API actualizado |
| `README.md` | 268 | ~123 | Documentación proyecto |
| `config/app.php` | 140 | ~72 | Configuración app |
| `src/Pagos/PagosController.php` | 46 | ~1 | Seguridad IDOR en pagos |
| `public/setup.php` | 184 | ~92 | Setup instalación |
| `config/database.php` | 64 | ~32 | Configuración DB |
| `public/js/main.js` | 86 | 0 | Nuevo archivo init JS |
| `src/Pagos/PagosService.php` | 27 | ~12 | Lógica reintentos y formato |
| `src/Core/Request.php` | 30 | ~15 | Parsing URI y headers |
| `database/fix_encoding.sql` | 18 | 0 | Nuevo: fix encoding productos |
| `database/fix_categorias_encoding.sql` | 13 | 0 | Nuevo: fix encoding categorías |
| `src/Checkout/CheckoutService.php` | 6 | 0 | Validación IDOR carrito |
| `src/Core/Router.php` | 6 | ~3 | Fix comparación de rutas |
| `src/Pagos/PagosRepository.php` | 5 | ~2 | Fix formato monto + fecha_pago |
| `database/seed.sql` | 11 | ~11 | Actualización contraseñas |
| `public/.htaccess` | 29 | 0 | Nuevo: headers seguridad Apache |
| `c.txt` / `cookies.txt` | 5 / 5 | 0 | Archivos de prueba |
| **TOTAL** | **~6,257** | **~4,379** | |

---

## Clasificación de Cambios por Tipo

### Seguridad (tickets DEB)
| Ticket | Descripción | Archivos |
|--------|-------------|---------|
| DEB-01 | Corrección IDOR en pagos (procesar y consultar) | `PagosController.php` |
| DEB-02 | Webhook de pagos: validación de firma + idempotencia | `PagosController.php`, `PagosService.php` |
| DEB-03 | Reintento de pago: pedido permanece 'pendiente' tras rechazo | `PagosService.php` |
| DEB-05 | Corrección IDOR en checkout: validar propiedad del carrito | `CarritoRepository.php`, `CheckoutService.php` |
| DEB-06 | Actualización contrato API checkout (v2.0.0) | `checkout.yaml` |
| DEB-07 | fecha_pago solo se registra en pagos aprobados | `PagosRepository.php` |

### Infraestructura y Configuración
- `.htaccess` con headers de seguridad HTTP completos
- Corrección de parsing de URI en subdirectorios (`Request.php`, `Router.php`)
- Scripts SQL de corrección de encoding UTF-8

### Frontend
- Reemplazo completo de la carpeta `public/` con archivos JS/HTML/CSS actualizados
- Nueva sección `public/store/` con su propia app, HTML y CSS

### Base de Datos
- Actualización de contraseñas en `seed.sql`
- Scripts de fix de encoding para productos y categorías
