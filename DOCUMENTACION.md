# Documentación Completa — UCT E-Commerce

**Proyecto:** Ecommerce UCT — SDD Academic Edition  
**Repositorio:** `dd-software/ecommerce-project-s1`  
**Equipo:** Team 4 — Los Debians  
**Integrantes:**
- Carlos Sepúlveda (`sidhartaz` / `sepulvedacarloscd@gmail.com`)
- Luis Ignacio Cerda Zurita (`Nylarion`)
- Braulio Palma (`braulio.palma05@gmail.com`)

---

## Índice

1. [Estado actual del proyecto](#1-estado-actual-del-proyecto)
2. [Evolución del proyecto](#2-evolución-del-proyecto)
3. [Fase 1 — Construcción inicial con IA](#3-fase-1--construcción-inicial-con-ia)
4. [Fase 2 — Rama `team-4-los-debians` (seguridad y pagos)](#4-fase-2--rama-team-4-los-debians-seguridad-y-pagos)
5. [Fase 3 — Mejoras de catálogo, buscador y categorías](#5-fase-3--mejoras-de-catálogo-buscador-y-categorías)
6. [Fase 4 — Integración Transbank Webpay Plus](#6-fase-4--integración-transbank-webpay-plus)
7. [Documentación técnica actual](#7-documentación-técnica-actual)
8. [API REST completa](#8-api-rest-completa)
9. [Uso de inteligencia artificial](#9-uso-de-inteligencia-artificial)

---

## 1. Estado actual del proyecto

**Fecha:** 22 de junio de 2026  
**Estado:** ✅ Funcional en entorno local (XAMPP)

### ¿Qué hace el proyecto hoy?

UCT E-Commerce es una plataforma de comercio electrónico completa con:

- **Catálogo de 16 productos** organizados en **6 categorías**
- **Buscador en tiempo real** por nombre, descripción y categoría
- **Filtro por categorías** con pestañas dinámicas
- **Carrito de compras** persistente por usuario en base de datos
- **Proceso de checkout** con transacciones ACID
- **Pago** via Transbank Webpay Plus (tarjeta crédito/débito), PayPal Sandbox o modo simulación
- **Panel de administración** con gestión de pedidos y alertas de stock
- **Historial de compras** por cliente
- **Autenticación JWT** con control de acceso por roles (admin / customer)
- **Seguridad** contra IDOR en pagos y checkout, webhook verificado

### Credenciales de acceso

| Rol | Email | Contraseña |
|-----|-------|-----------|
| Administrador | `admin@uct.cl` | `password` |
| Cliente | `cliente@uct.cl` | `password` |

### Cómo acceder

```
http://localhost/e-commerce-nuevo/public/
```

### Inventario actual

| Categoría | Productos |
|-----------|-----------|
| Electrónica | Smartphone X, Laptop Pro 15", Auriculares Bluetooth Pro, Smart TV 50" 4K |
| Ropa | Camiseta Básica, Polera Deportiva, Chaqueta Impermeable |
| Calzado | Zapatillas Running Ultra, Botines de Cuero Clásico |
| Hogar y Cocina | Cafetera Express, Silla Ergonómica Oficina |
| Deportes y Fitness | Bicicleta MTB Rodado 29", Guantes de Boxeo |
| Computación y Gaming | Teclado Mecánico RGB, Mouse Gamer Pro, Monitor Curvo 27" QHD |

---

## 2. Evolución del proyecto

```
LÍNEA DE TIEMPO
───────────────────────────────────────────────────────────────────────

[Antes de Jun 11]   FASE 1: Construcción inicial
                    → SDD en 6 fases con asistencia de IA (Gemini)
                    → Arquitectura base: PHP 8, PDO, JWT, SPA
                    → 2 productos, 2 categorías, 4 variantes
                    → Módulos: auth, catálogo, carrito, checkout, pagos,
                      inventario, admin, pedidos

[11 Jun 2026]       FASE 2: Rama team-4-los-debians — inicio
                    → Limpieza de documentación desactualizada

[14 Jun 2026]       FASE 2: Correcciones de seguridad críticas
                    → DEB-01: Corrección IDOR en pagos
                    → DEB-02: Webhook seguro (firma + idempotencia)
                    → DEB-03: Reintento de pago tras rechazo
                    → DEB-05: Corrección IDOR en checkout
                    → DEB-06: Contrato API checkout v2.0.0
                    → DEB-07: fecha_pago solo en pagos aprobados

[17 Jun 2026]       FASE 2: Actualización de infraestructura
                    → Reemplazo de módulos Carrito y Admin
                    → Reemplazo carpetas config/ y public/
                    → Nueva sección public/store/
                    → Headers de seguridad HTTP en .htaccess
                    → Corrección de URI en subdirectorios
                    → Scripts de fix de encoding UTF-8

[22 Jun 2026]       FASE 3: Mejoras de catálogo, buscador y categorías
                    → Seed expandido: 16 productos, 6 categorías, 35 variantes
                    → Buscador funcional (backend + frontend)
                    → Filtro por categorías con pestañas dinámicas
                    → 3 nuevos endpoints de API
                    → README.md y documentación técnica

[22 Jun 2026]       FASE 4: Integración Transbank Webpay Plus
                    → Pago con tarjeta Visa/Mastercard/RedCompra
                    → API REST Webpay Plus (ambiente de integración)
                    → Selector de método de pago en el carrito
                    → Página de retorno con resultado de pago
                    → Flujo: create → redirect → confirm → markAsPaid
```

---

## 3. Fase 1 — Construcción inicial con IA

### Metodología: Specification Driven Development (SDD)

El sistema fue construido en **6 fases iterativas** usando SDD con asistencia del LLM **Gemini 3.1 Pro** en modo agéntico, actuando como Arquitecto de Software Senior y Pair-Programmer.

Cada fase produjo:
- Un documento SDD (`specs/0N_phaseN_sdd.md`)
- El código funcional correspondiente
- Tests manuales de validación

### Qué se construyó en esta fase

| Módulo | Archivos creados |
|--------|-----------------|
| Core | `Router.php`, `Response.php`, `AuthMiddleware.php`, `JWTHandler.php` |
| Auth | `AuthController.php`, `User.php` |
| Catálogo | `CatalogController.php`, `Product.php` |
| Carrito | `CartController.php`, `Cart.php` |
| Checkout | `CheckoutController.php`, `Order.php` |
| Pagos | `PaymentController.php` |
| Inventario | `InventoryController.php`, `Inventory.php` |
| Admin | `AdminController.php` |
| Frontend | `index.html`, `login.html`, `cart.html`, `admin.html`, `mis-compras.html` |
| JS | `api.js`, `ui.js`, `auth.js`, `catalog.js`, `cart.js`, `orders.js`, `admin.js` |
| BD | `schema.sql`, `seed.sql` (2 productos) |

### Decisiones arquitectónicas de la fase 1

#### Singleton PDO (`config/database.php`)
Una única instancia de conexión PDO compartida por toda la petición HTTP. Sin Singleton, cada modelo que instanciara `new PDO()` abriría una conexión independiente, agotando el pool de conexiones de MySQL.

#### JWT nativo sin librerías externas
Autenticación stateless con HMAC SHA256 implementado en `JWTHandler.php`. Se eligió implementación propia para no depender de paquetes externos (el proyecto corre sin Composer).

#### Flexibilidad de Router para XAMPP
El `Router.php` localiza el segmento `/api/` en la URI para soportar rutas en subdirectorios (`localhost/e-commerce-nuevo/public/api/...`) sin configuración adicional, haciendo el proyecto plug-and-play para evaluación académica.

#### ACID en checkout (`Order.php`)
Toda la operación de compra (crear pedido → registrar ítems → descontar stock → vaciar carrito) ocurre dentro de una transacción MySQL. Si algún paso falla, se hace `rollBack()` y todo queda como estaba.

---

## 4. Fase 2 — Rama `team-4-los-debians` (seguridad y pagos)

**Período:** 11 junio 2026 — 17 junio 2026  
**Rama:** `team-4-los-debians`

### Historial de commits

| Hash | Fecha | Autor | Descripción |
|------|-------|-------|-------------|
| `733042c` | 2026-06-11 | Braulio Palma | Delete md-files directory |
| `188912a` | 2026-06-13 | sidhartaz | Merge origin/main into team-4-los-debians |
| `cbc77c1` | 2026-06-14 | sidhartaz | DEB-05: Validar propiedad del carrito en checkout |
| `b79be37` | 2026-06-14 | sidhartaz | DEB-06: Actualizar checkout.yaml v2.0.0 |
| `9a65e59` | 2026-06-14 | sidhartaz | DEB-07: fecha_pago solo en pagos aprobados |
| `31d6fb0` | 2026-06-14 | Luis Ignacio Cerda Zurita | DEB-01: Corregir IDOR en pagos |
| `c294c6f` / `04bcd0a` / `f3d73b4` | 2026-06-14 | Luis Ignacio Cerda Zurita | DEB-02: Webhook seguro (firma + idempotencia) |
| `ee65868` | 2026-06-14 | Luis Ignacio Cerda Zurita | DEB-03: Reintento de pago tras rechazo |
| `bdee9cf`…`7a4d2f7` | 2026-06-17 | Braulio Palma | Actualización módulos Carrito y Admin |
| `fb1b1b8` + `340155b` | 2026-06-17 | Luis Ignacio Cerda Zurita | Reemplazo carpetas config/ y public/ |
| `4f303fd` | 2026-06-17 | sidhartaz | src core y pagos |
| `8a3c3ba` | 2026-06-17 | sidhartaz | Actualizar .htaccess con headers de seguridad |
| `fd4f18e` | 2026-06-17 | sidhartaz | Actualizar README y seed.sql |

---

### DEB-01 — Corrección IDOR en pagos
**Autor:** Luis Ignacio Cerda Zurita

**Problema:** Los endpoints `POST /api/pagos/procesar` y `GET /api/pagos/estado/{pedidoId}` no verificaban que el pedido perteneciera al usuario autenticado. Cualquier usuario podía procesar o consultar el pago del pedido de otro (vulnerabilidad IDOR).

**Archivo:** `src/Pagos/PagosController.php` (+30 líneas)

```php
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

Los administradores mantienen acceso completo a todos los pedidos.

---

### DEB-02 — Webhook de pagos: firma + idempotencia
**Autor:** Luis Ignacio Cerda Zurita

**Archivos:** `src/Pagos/PagosController.php`, `src/Pagos/PagosService.php`

**Problema 1 — Spoofing:** El webhook no verificaba que la notificación viniera realmente de la pasarela de pago.  
**Solución:** Se valida el header `X-Webhook-Secret` antes de procesar cualquier notificación.

**Problema 2 — Doble descuento:** Si la pasarela reintentaba el webhook, se procesaba dos veces, descontando inventario el doble.  
**Solución:** Cláusula de guarda que detecta si el pedido ya está en estado `'aprobado'` y retorna inmediatamente sin reprocesar.

> Este ticket requirió tres commits iterativos (`c294c6f`, `04bcd0a`, `f3d73b4`) hasta quedar con el código final.

---

### DEB-03 — Reintento de pago tras rechazo
**Autor:** Luis Ignacio Cerda Zurita

**Archivo:** `src/Pagos/PagosService.php` (+5 líneas, -7 líneas)

**Problema:** Cuando un pago era rechazado, el pedido mutaba a estado `'cancelado'` permanentemente, sin posibilidad de reintentar.

```php
// ANTES — pago rechazado cancelaba el pedido
$this->checkoutService->actualizarEstado(pedidoId: $pedidoId, nuevoEstado: 'cancelado', ...);

// DESPUÉS — pago rechazado preserva el pedido para reintentar
$this->checkoutService->actualizarEstado(
    pedidoId: $pedidoId,
    nuevoEstado: 'pendiente',
    comentario: 'Intento de pago rechazado. Transacción: ' . $transaccionId
);
```

---

### DEB-05 — IDOR en checkout: validar propiedad del carrito
**Autor:** sidhartaz

**Problema:** Un usuario autenticado podía hacer checkout usando el `carrito_id` de otro usuario.

**Archivos:** `src/Carrito/CarritoRepository.php`, `src/Checkout/CheckoutService.php`

```php
// Nuevo método en CarritoRepository
public function obtenerPorId(int $carritoId): ?array {
    $stmt = $this->db->prepare("SELECT id, id_usuario FROM carritos WHERE id = :id");
    $stmt->execute([':id' => $carritoId]);
    return $stmt->fetch() ?: null;
}

// Validación en CheckoutService::crearPedido()
$carrito = (new CarritoRepository())->obtenerPorId($carritoId);
if (!$carrito || (int)$carrito['id_usuario'] !== $userId) {
    throw new \RuntimeException('El carrito no pertenece al usuario autenticado.');
}
```

---

### DEB-06 — Contrato API checkout v2.0.0
**Autor:** sidhartaz

**Archivo:** `specs/03-contratos-api/checkout.yaml` (+249 líneas, versión 1.0.0 → 2.0.0)

Cambios principales:
- Servidor actualizado de `http://localhost:8084` a `http://localhost/Ecomers/public`
- Campos renombrados para coincidir con la implementación real (`carritoId` → `carrito_id`, etc.)
- `security: bearerAuth: []` agregado a todos los endpoints protegidos
- Respuestas de error documentadas: 400, 401, 403, 404, 422, 500

---

### DEB-07 — fecha_pago solo en pagos aprobados
**Autor:** sidhartaz

**Archivo:** `src/Pagos/PagosRepository.php`

**Problema:** La columna `fecha_pago` se guardaba con `NOW()` incluso para pagos rechazados.

```php
// ANTES — registraba fecha aunque el pago fallara
INSERT INTO pagos (..., fecha_pago) VALUES (..., NOW())

// DESPUÉS — fecha solo cuando el estado es 'aprobado'
$fechaPago = $estado === 'aprobado' ? 'NOW()' : 'NULL';
INSERT INTO pagos (..., fecha_pago) VALUES (..., {$fechaPago})
```

---

### Actualización de módulos Carrito y Admin
**Autor:** Braulio Palma (2026-06-17)

| Archivo | Cambio |
|---------|--------|
| `src/Carrito/CarritoService.php` | Reemplazo completo |
| `src/Carrito/CarritoRepository.php` | Reemplazo completo |
| `src/Carrito/CarritoController.php` | Reemplazo completo |
| `src/Admin/AdminService.php` | Reemplazo completo |
| `src/Admin/AdminRepository.php` | Reemplazo completo |
| `src/Admin/AdminController.php` | Reemplazo completo |

---

### Reemplazo de carpetas `config/` y `public/`
**Autor:** Luis Ignacio Cerda Zurita (2026-06-17)

La carpeta `public/` fue reemplazada completamente con archivos JS/HTML/CSS actualizados. Se agregó una nueva sección `public/store/` con su propia app, HTML y CSS.

| Archivo nuevo | Descripción |
|--------------|-------------|
| `public/store/app.js` (553 líneas) | App JS de la nueva sección tienda |
| `public/store/index.html` (263 líneas) | HTML de la nueva sección tienda |
| `public/store/style.css` (344 líneas) | Estilos de la nueva sección tienda |
| `public/js/main.js` (86 líneas) | Inicialización principal JS |

---

### Headers de seguridad HTTP en `.htaccess`
**Autor:** sidhartaz (2026-06-17)

```apache
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "DENY"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; ..."
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
```

---

### Correcciones en `src/Core/` y `src/Pagos/`
**Autor:** sidhartaz (2026-06-17)

**`Request.php`** — URI en subdirectorios: antes fallaba cuando el proyecto estaba en un subdirectorio de XAMPP; ahora stripea el prefijo del directorio del script.

**`Router.php`** — Comparación de path vacío: `'' → '/'` en el dispatch para manejar correctamente la ruta raíz.

**`PagosRepository.php` y `PagosService.php`** — Formato de monto: el monto ya estaba en pesos pero se dividía por 100 erróneamente.

```php
// ANTES (incorrecto)
'$' . number_format($pago['monto'] / 100, 0, ',', '.')

// DESPUÉS (correcto)
'$' . number_format($pago['monto'], 0, ',', '.')
```

**`database/fix_encoding.sql`** y **`database/fix_categorias_encoding.sql`** — Scripts SQL para corregir tildes y caracteres especiales en nombres y descripciones de productos y categorías.

---

### Resumen de impacto — Fase 2

| Tipo de cambio | Cantidad |
|----------------|---------|
| Vulnerabilidades IDOR corregidas | 2 (pagos + checkout) |
| Bugs de lógica corregidos | 3 (fecha_pago, formato monto, reintentos) |
| Endpoint webhook asegurado | 1 |
| Módulos PHP reemplazados | 6 |
| Archivos frontend reemplazados | 14 |
| Archivos nuevos creados | 5 |
| Líneas netas agregadas | ~+1,878 |

---

## 5. Fase 3 — Mejoras de catálogo, buscador y categorías

**Fecha:** 22 junio 2026  
**Autor:** Asistencia Claude Sonnet 4.6

### Resumen de archivos modificados

| Archivo | Estado | Cambio |
|---------|--------|--------|
| `README.md` | **Creado** | Documentación profesional del proyecto |
| `CODIGO.md` | **Creado** | Documentación técnica del código |
| `CAMBIOS.md` | **Creado** | Registro de cambios de esta fase |
| `database/seed.sql` | **Modificado** | De 2 productos → 16 productos en 6 categorías |
| `database/add_products.sql` | **Creado** | Script para agregar productos a BD existente |
| `src/Models/Product.php` | **Modificado** | +3 métodos: `search`, `getCategories`, `getByCategory` |
| `src/Controllers/CatalogController.php` | **Modificado** | +2 métodos, 1 actualizado |
| `public/index.php` | **Modificado** | +2 rutas nuevas, refactorización bloque catálogo |
| `public/js/catalog.js` | **Reescrito** | Buscador, filtros por categoría, caché en memoria |
| `public/js/ui.js` | **Modificado** | Eventos del buscador conectados |
| `public/index.html` | **Modificado** | +div categorías, +id al heading |

---

### Cambio A — `database/seed.sql`

**Antes:** 2 categorías, 2 productos, 4 variantes, 4 inventarios.

**Ahora:** Seed idempotente (se puede re-ejecutar) gracias al bloque de limpieza inicial:

```sql
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE inventory;
TRUNCATE TABLE product_variants;
-- ... todas las tablas en orden inverso
SET FOREIGN_KEY_CHECKS = 1;
```

Luego inserta 6 categorías, 16 productos, 35 variantes y 35 registros de inventario.

---

### Cambio B — `database/add_products.sql` (nuevo)

Alternativa para bases de datos que ya tienen datos. Usa `INSERT IGNORE ... SELECT` con subqueries por slug para no asumir IDs:

```sql
-- No hardcodea IDs, busca por slug
INSERT IGNORE INTO products (category_id, name, slug, description)
SELECT id, 'Laptop Pro 15"', 'laptop-pro-15', 'Descripción...'
FROM categories WHERE slug = 'electronica';
```

Seguro para ejecutar múltiples veces: si el dato ya existe, lo omite sin error.

---

### Cambio C — `src/Models/Product.php`

#### Método `getByCategory(int $categoryId): array`
```php
public function getByCategory(int $categoryId): array {
    $stmt = $this->db->prepare("
        SELECT p.id, p.name, p.slug, p.description, c.name AS category_name
        FROM products p JOIN categories c ON p.category_id = c.id
        WHERE p.deleted_at IS NULL AND p.category_id = ?
        ORDER BY p.name
    ");
    $stmt->execute([$categoryId]);
    return $stmt->fetchAll();
}
```

#### Método `search(string $query): array`
El mismo `%query%` se pasa tres veces porque PDO no permite reutilizar el mismo parámetro nombrado en múltiples posiciones de una misma consulta:

```php
public function search(string $query): array {
    $q = '%' . $query . '%';
    $stmt = $this->db->prepare("
        SELECT p.id, p.name, p.slug, p.description, c.name AS category_name
        FROM products p JOIN categories c ON p.category_id = c.id
        WHERE p.deleted_at IS NULL
          AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)
        ORDER BY p.name
    ");
    $stmt->execute([$q, $q, $q]);
    return $stmt->fetchAll();
}
```

#### Método `getCategories(): array`
```php
public function getCategories(): array {
    $stmt = $this->db->query("
        SELECT id, name, slug, description FROM categories
        WHERE deleted_at IS NULL ORDER BY name
    ");
    return $stmt->fetchAll();
}
```

---

### Cambio D — `src/Controllers/CatalogController.php`

`list()` ahora acepta filtro opcional por categoría:
```php
public function list(): void {
    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
    $products   = $categoryId
        ? $this->productModel->getByCategory($categoryId)
        : $this->productModel->getAll();
    Response::json(['data' => $products]);
}
```

`search()` usa `mb_strlen` (no `strlen`) para contar caracteres Unicode correctamente (una `á` ocupa 2 bytes pero es 1 carácter):
```php
public function search(): void {
    $query = trim($_GET['q'] ?? '');
    if (mb_strlen($query) < 2) { Response::json(['data' => []]); return; }
    Response::json(['data' => $this->productModel->search($query)]);
}
```

---

### Cambio E — `public/index.php`

**Antes:** dos instancias separadas de `CatalogController`, dos conexiones PDO.

**Ahora:** una sola instancia con cuatro rutas. El orden es crítico: `/search` debe ir antes que `/{id}` o la búsqueda sería capturada como un ID:

```php
$catalog = new CatalogController();
$router->add('GET', '/api/catalog/products',        [$catalog, 'list']);
$router->add('GET', '/api/catalog/products/search', [$catalog, 'search']);   // ← antes de {id}
$router->add('GET', '/api/catalog/categories',      [$catalog, 'categories']);
$router->add('GET', '/api/catalog/products/{id}',   [$catalog, 'detail']);
```

---

### Cambio F — `public/js/catalog.js` (reescritura completa)

**`renderProducts(products, heading)`** — función extraída para ser reutilizable desde búsqueda, filtro y carga inicial.

**`allProducts`** — caché en memoria: se llena al cargar la página y se reutiliza cuando el usuario hace clic en "Todos", sin petición al servidor.

**`window.filterByCategory(categoryId, btn)`** — resalta el botón activo, limpia el buscador y filtra productos. Es `window.X` (global) porque se invoca desde `onclick` en HTML generado dinámicamente.

**`window.performSearch(query)`** — llama a `/catalog/products/search?q=...`. Usa `encodeURIComponent()` para que espacios, tildes y caracteres especiales no rompan la URL.

---

### Cambio G — `public/js/ui.js`

Se añadieron 8 líneas al final de `renderHeader()` para conectar los eventos del buscador del header. Van aquí y no en `catalog.js` porque el HTML del header lo crea `ui.js`, y los event listeners solo se pueden agregar a elementos que ya existen en el DOM:

```javascript
const doSearch = () => {
    const q = (searchInput?.value ?? '').trim();
    if (typeof window.performSearch === 'function') window.performSearch(q);
};
if (searchBtn)   searchBtn.addEventListener('click', doSearch);
if (searchInput) searchInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') doSearch();
});
```

El `typeof window.performSearch === 'function'` evita errores en páginas donde `catalog.js` no está cargado (carrito, login, etc.).

---

### Cambio H — `public/index.html`

```html
<!-- ANTES -->
<h3 class="mb-4 fw-bold">Ofertas Destacadas</h3>
<div class="row g-4" id="product-list">

<!-- AHORA -->
<h3 class="mb-4 fw-bold" id="products-heading">Todos los Productos</h3>
<div class="d-flex flex-wrap gap-2 mb-4" id="category-filter"></div>
<div class="row g-4" id="product-list">
```

`id="products-heading"` permite que JS actualice el título dinámicamente ("Resultados para X", nombre de categoría, etc.). `id="category-filter"` es el contenedor vacío que `loadCategories()` rellena con botones al cargar la página.

---

## 6. Fase 4 — Integración Transbank Webpay Plus

**Fecha:** 22 junio 2026  
**Autor:** Asistencia Claude Sonnet 4.6

### Objetivo

Permitir que los usuarios paguen sus pedidos con tarjetas de crédito o débito (Visa, Mastercard, RedCompra) usando Transbank Webpay Plus — la pasarela de pagos más usada en Chile.

### Archivos creados / modificados

| Archivo | Estado | Descripción |
|---------|--------|-------------|
| `src/Services/TransbankService.php` | **Creado** | Cliente REST para la API de Transbank |
| `src/Controllers/TransbankController.php` | **Creado** | Endpoint `POST /api/payment/transbank/create` |
| `public/transbank-return.php` | **Creado** | Página de retorno post-pago |
| `public/js/cart.js` | **Reescrito** | Selector de método de pago (Transbank + PayPal + Simular) |
| `public/index.php` | **Modificado** | +1 ruta Transbank |
| `.env` | **Modificado** | +3 variables TBK_* |

---

### Flujo de pago Transbank

```
[Usuario] → "Pagar con Tarjeta" (cart.js)
    │
    ▼
POST /api/payment/transbank/create
    │  Verifica JWT + propiedad de la orden
    │  Llama TransbankService::createTransaction()
    ▼
Transbank API → { token, url }
    │
    ▼
Frontend crea <form method="POST" action="{url}">
    con campo oculto token_ws={token}
    y hace form.submit()
    │
    ▼
[Usuario ve el formulario de pago de Transbank]
    Ingresa número de tarjeta, CVV, RUT, clave web
    │
    ▼
Transbank redirige a public/transbank-return.php?order_id={id}
    con $_POST['token_ws']
    │
    ▼
transbank-return.php
    → llama TransbankService::confirmTransaction(token_ws)
    → si response_code === 0 → markAsPaidAndDeductStock(orderId)
    → renderiza HTML de éxito o fracaso
```

**Casos de cancelación manejados:**
- Usuario pulsa "volver" en Transbank → Transbank envía `TBK_TOKEN` en lugar de `token_ws`
- Usuario abandona / timeout → ningún token enviado

---

### `src/Services/TransbankService.php`

Clase de infraestructura que envuelve la API REST v1.2 de Transbank.

**Headers requeridos por Transbank:**
```
Tbk-Api-Key-Id: {commerce_code}
Tbk-Api-Key-Secret: {api_key}
Content-Type: application/json
```

**Método `createTransaction()`** — HTTP POST al endpoint raíz:
```php
public function createTransaction(
    string $buyOrder,   // "UCT-{orderId}-{timestamp}" — max 26 chars
    string $sessionId,  // "SID-{userId}-{timestamp}"
    int    $amount,     // CLP sin decimales (entero)
    string $returnUrl   // URL de retorno (debe ser accesible por Transbank)
): array                // ['token' => '...', 'url' => '...']
```

**Método `confirmTransaction()`** — HTTP PUT a `/{token}`:
```php
public function confirmTransaction(string $token): array
// Respuesta incluye: response_code, amount, authorization_code, card_detail, buy_order
```

**Conversión de moneda:** Los precios en la BD están en USD (formato académico). Se multiplican por 1000 para obtener CLP aproximado:
```php
$amountCLP = (int)round((float)$order['total_amount'] * 1000);
```

---

### `src/Controllers/TransbankController.php`

Middleware de seguridad aplicado antes de llamar a Transbank:
1. **Autenticación JWT** vía `AuthMiddleware::authenticate()`
2. **Validación de propiedad**: la orden debe pertenecer al usuario autenticado y estar en estado `pendiente_pago`
3. **Construcción dinámica del `return_url`**: no hardcodeado, se construye desde variables `$_SERVER` para funcionar en cualquier entorno (localhost, subpath, etc.)

```php
$protocol  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$returnUrl = "$protocol://$host$scriptDir/transbank-return.php?order_id=$orderId";
```

---

### `public/transbank-return.php`

Archivo PHP standalone (no pasa por el router de la API). Apache lo sirve directamente porque existe como archivo real (la condición `!-f` del `.htaccess` no se cumple).

**Incluye su propio bootstrap** (autoloader + carga de `.env`) para ser independiente de `index.php`.

**Validación de seguridad post-pago:**
```php
// Verifica que el buy_order de Transbank coincide con el order_id de la URL
$parts          = explode('-', $buyOrder); // "UCT-42-1719000000"
$orderIdFromTbk = (int)$parts[1];
if ($orderIdFromTbk !== $orderId) → error de validación
```

Esta validación evita que alguien manipule el `order_id` en la URL GET para aprobar una orden diferente.

---

### `public/js/cart.js` — nuevo selector de método de pago

Antes del checkout, el usuario debe elegir entre tres métodos:

```
┌─────────────────────────────────────────────┐
│        Método de pago                       │
│  ┌─────────────────────────────────────┐    │
│  │  💳 Pagar con Tarjeta               │    │
│  │  Visa · Mastercard · RedCompra      │    │
│  └─────────────────────────────────────┘    │
│  ┌─────────────────────────────────────┐    │
│  │  🅿 Pagar con PayPal                │    │
│  │  Pago internacional en USD          │    │
│  └─────────────────────────────────────┘    │
│  ─────────────────────────────────────      │
│  [ Simular pago (solo entorno de pruebas) ] │
└─────────────────────────────────────────────┘
```

**Transbank (`checkoutWithTransbank`):** Crea la transacción vía API y redirige con form POST (Transbank exige POST, no GET).

**PayPal (`initPayPal`):** Carga el SDK de PayPal de forma lazy (solo si el usuario elige esta opción) y renderiza los botones nativos de PayPal.

**Simular:** Mantiene el flujo previo para pruebas sin pasarela real.

---

### Credenciales de integración (testing)

Las credenciales de integración son públicas y proporcionadas por Transbank para desarrollo:

```
Commerce Code : 597055555532
API Key       : 579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C
Ambiente      : integración (webpay3gint.transbank.cl)
```

**Tarjetas de prueba:**

| Red | Número | CVV | Vencimiento | RUT | Clave web |
|-----|--------|-----|-------------|-----|-----------|
| Visa | `4051 8856 0044 6623` | `123` | Cualquier futura | `11.111.111-1` | `123` |
| Mastercard | `5186 0595 5959 0568` | `123` | Cualquier futura | `11.111.111-1` | `123` |

---

## 7. Documentación técnica actual

### Arquitectura

```
┌─────────────────────────────────────┐
│  NAVEGADOR (SPA - Vanilla JS)       │
│  Bootstrap 5.3 + js/*.js            │
│  api.js → fetch() con JWT Bearer    │
└──────────────────┬──────────────────┘
                   │ JSON / HTTP
                   ▼
┌─────────────────────────────────────┐
│  SERVIDOR (PHP 8 + Apache/XAMPP)    │
│  public/index.php  ← entrada única  │
│  src/Core/Router.php                │
│  src/Controllers/*.php              │
│  src/Models/*.php                   │
│  config/database.php (PDO Singleton)│
│  MySQL — uct_ecommerce              │
└─────────────────────────────────────┘
```

### Estructura de archivos actual

```
e-commerce-nuevo/
├── .env                          ← Variables de entorno
├── config/
│   └── database.php              ← Conexión PDO (Singleton)
├── database/
│   ├── schema.sql                ← Estructura de tablas (9 tablas)
│   ├── seed.sql                  ← 16 productos, 6 categorías (idempotente)
│   ├── add_products.sql          ← Agrega productos a BD existente
│   ├── fix_encoding.sql          ← Corrección UTF-8 productos
│   └── fix_categorias_encoding.sql ← Corrección UTF-8 categorías
├── public/
│   ├── .htaccess                 ← Rewrite + headers de seguridad HTTP
│   ├── index.php                 ← Front controller / router de API
│   ├── index.html                ← Catálogo (buscador + filtros)
│   ├── login.html                ← Login / Registro
│   ├── cart.html                 ← Carrito de compras
│   ├── admin.html                ← Panel de administración
│   ├── mis-compras.html          ← Historial de pedidos
│   ├── setup.php                 ← Script de instalación
│   ├── transbank-return.php      ← Página de retorno Webpay Plus
│   ├── css/style.css             ← Estilos (branding UCT)
│   ├── js/
│   │   ├── api.js                ← Cliente HTTP + JWT
│   │   ├── ui.js                 ← Header dinámico + buscador
│   │   ├── catalog.js            ← Catálogo, búsqueda, filtros
│   │   ├── auth.js               ← Login / Registro
│   │   ├── cart.js               ← Carrito + Transbank + PayPal + Simular
│   │   ├── orders.js             ← Historial pedidos
│   │   ├── admin.js              ← Panel admin
│   │   ├── app.js                ← App principal
│   │   └── main.js               ← Inicialización
│   └── store/
│       ├── index.html            ← Sección tienda (nueva)
│       ├── app.js                ← App tienda (nueva)
│       └── style.css             ← Estilos tienda (nueva)
├── src/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── CatalogController.php ← list, search, categories, detail
│   │   ├── CartController.php
│   │   ├── CheckoutController.php
│   │   ├── PaymentController.php
│   │   ├── TransbankController.php ← create (Webpay Plus)
│   │   ├── OrderController.php
│   │   ├── AdminController.php
│   │   └── InventoryController.php
│   ├── Core/
│   │   ├── Router.php
│   │   ├── Response.php
│   │   ├── AuthMiddleware.php
│   │   ├── JWTHandler.php
│   │   └── Request.php
│   ├── Services/
│   │   └── TransbankService.php  ← cliente REST Webpay Plus
│   ├── Models/
│   │   ├── User.php
│   │   ├── Product.php           ← getAll, getByCategory, search, getCategories, getWithVariants
│   │   ├── Cart.php
│   │   ├── Order.php
│   │   └── Inventory.php
│   ├── Carrito/
│   │   ├── CarritoController.php
│   │   ├── CarritoService.php
│   │   └── CarritoRepository.php
│   ├── Admin/
│   │   ├── AdminController.php
│   │   ├── AdminService.php
│   │   └── AdminRepository.php
│   ├── Pagos/
│   │   ├── PagosController.php   ← Con protección IDOR y webhook seguro
│   │   ├── PagosService.php      ← Con reintentos y formato de monto corregido
│   │   └── PagosRepository.php   ← Con fecha_pago condicional
│   └── Checkout/
│       └── CheckoutService.php   ← Con validación de propiedad del carrito
└── specs/
    ├── 01_phase1_sdd.md … 06_phase6_sdd.md
    └── 03-contratos-api/
        └── checkout.yaml         ← Contrato API v2.0.0
```

### Esquema de base de datos

```
roles ──────────────────── users
                              │
categories                    │
    │                         │
    └── products              │
            │                 │
            └── product_variants ── inventory
                    │
                    ├── cart_items ─── users
                    │
                    └── order_items ── orders ── users
```

**9 tablas:** `roles`, `users`, `categories`, `products`, `product_variants`, `inventory`, `orders`, `order_items`, `cart_items`

Todas las tablas principales usan **soft delete** (`deleted_at TIMESTAMP NULL`).

---

## 8. API REST completa

### Pública (sin autenticación)

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/auth/login` | Iniciar sesión → devuelve JWT |
| `POST` | `/api/auth/register` | Registrar nueva cuenta |
| `GET` | `/api/catalog/products` | Listar todos los productos |
| `GET` | `/api/catalog/products?category_id={id}` | Filtrar por categoría |
| `GET` | `/api/catalog/products/search?q={term}` | Buscar productos |
| `GET` | `/api/catalog/products/{id}` | Detalle y variantes de un producto |
| `GET` | `/api/catalog/categories` | Listar todas las categorías |
| `GET` | `/api/config/paypal` | Obtener PayPal Client ID |

### Protegida (requiere `Authorization: Bearer <JWT>`)

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/cart` | Ver carrito del usuario |
| `POST` | `/api/cart` | Agregar producto al carrito |
| `DELETE` | `/api/cart/{variant_id}` | Eliminar producto del carrito |
| `POST` | `/api/checkout` | Crear pedido desde el carrito |
| `POST` | `/api/payment/simulate` | Simular pago |
| `POST` | `/api/payment/paypal/create` | Crear orden en PayPal |
| `POST` | `/api/payment/paypal/capture` | Capturar pago de PayPal |
| `POST` | `/api/payment/transbank/create` | Iniciar transacción Webpay Plus |
| `GET` | `/api/orders/me` | Ver mis pedidos |

### Solo administradores

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/admin/orders` | Listar todos los pedidos |
| `PUT` | `/api/admin/orders/{id}/status` | Actualizar estado de pedido |
| `GET` | `/api/inventory/alerts` | Ver alertas de stock bajo |

### Estados de un pedido

```
pendiente_pago → pagado → en_preparacion → enviado → entregado
```

---

## 9. Uso de inteligencia artificial

### Fase 1 — Construcción inicial

**Motor:** Gemini 3.1 Pro (High) en modo agéntico  
**Rol asumido:** Arquitecto de Software Senior + Pair-Programmer  
**Metodología:** Specification Driven Development (SDD) en 6 fases iterativas

La IA generó el sistema completo a partir de restricciones de rúbrica explícitas, produciendo en cada fase un documento SDD y el código funcional correspondiente.

### Fases 3 y 4 — Mejoras de catálogo + Transbank

**Motor:** Claude Sonnet 4.6  
**Rol:** Asistente de desarrollo  
**Tareas realizadas:**
- Expansión del seed de datos (2 → 16 productos, 2 → 6 categorías)
- Implementación del buscador (backend + frontend)
- Implementación de filtros por categoría (backend + frontend)
- Creación de documentación técnica (README.md, CODIGO.md, CAMBIOS.md)
- Integración Transbank Webpay Plus: `TransbankService`, `TransbankController`, `transbank-return.php`
- Reescritura de `cart.js` con selector de método de pago (Transbank / PayPal / Simular)

### Consideraciones éticas

- Todo el código generado por IA fue revisado e integrado por el equipo humano
- Las decisiones arquitectónicas (Singleton, JWT nativo, ACID, Router flexible) responden a necesidades reales del contexto académico-local (XAMPP), no a patrones aplicados mecánicamente
- El uso de IA está documentado explícitamente en este archivo y en `AI_USAGE.md`
