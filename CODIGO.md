# Documentación Técnica — UCT E-Commerce

Este documento explica cómo funciona el código del proyecto: su arquitectura general, el flujo de datos y las partes más complejas.

---

## 1. Visión general de la arquitectura

El proyecto sigue un patrón **MVC simplificado** (Modelo - Vista - Controlador) dividido en dos capas que se comunican mediante una API REST:

```
┌─────────────────────────────────────────────┐
│  NAVEGADOR (Frontend - SPA)                 │
│  HTML + Bootstrap 5 + JavaScript vanilla    │
│  js/api.js → hace peticiones HTTP (fetch)   │
└──────────────────┬──────────────────────────┘
                   │ JSON sobre HTTP
                   ▼
┌─────────────────────────────────────────────┐
│  SERVIDOR (Backend - PHP 8)                 │
│  public/index.php  ← punto de entrada único │
│       │                                     │
│  src/Core/Router.php  (enrutador)           │
│       │                                     │
│  src/Controllers/*.php  (lógica)            │
│       │                                     │
│  src/Models/*.php  (acceso a datos)         │
│       │                                     │
│  config/database.php  (PDO ← MySQL)         │
└─────────────────────────────────────────────┘
```

Cada página HTML es independiente y se comunica con el backend exclusivamente a través de llamadas `fetch()` a la API REST. No hay recarga de página completa: es una **SPA** (Single Page Application).

---

## 2. El punto de entrada: `public/index.php`

Todo el tráfico de la API pasa por un único archivo PHP. Apache redirige las URLs gracias al `.htaccess`:

```apache
# public/.htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [QSA,L]
```

Lo que hace `index.php` en orden:

1. **Activa CORS** (permite que el navegador llame a la API desde el mismo dominio).
2. **Registra el autoloader PSR-4** (carga automáticamente las clases PHP sin `require` manual).
3. **Lee el archivo `.env`** y carga las variables de entorno en `$_ENV`.
4. **Registra todas las rutas** del sistema.
5. **Llama a `$router->dispatch()`** para ejecutar el controlador correcto.

```php
// El autoloader convierte namespace → ruta de archivo:
// Src\Controllers\CatalogController → src/Controllers/CatalogController.php
spl_autoload_register(function ($class) {
    $parts    = explode('\\', $class);
    $parts[0] = strtolower($parts[0]); // Src → src
    $file     = __DIR__ . '/../' . implode('/', $parts) . '.php';
    if (file_exists($file)) require_once $file;
});
```

---

## 3. El Router: `src/Core/Router.php`

Esta es una de las partes más técnicas. El router permite definir rutas con parámetros dinámicos como `/api/catalog/products/{id}` y extraer automáticamente el valor `{id}` de la URL.

### Cómo funciona

```php
// Registro de ruta:
$router->add('GET', '/api/catalog/products/{id}', [$catalog, 'detail']);

// Al llegar una petición GET /api/catalog/products/42:
// 1. La ruta se convierte a una expresión regular:
//    /api/catalog/products/{id}  →  #^/api/catalog/products/(?P<id>[a-zA-Z0-9_-]+)$#
//
// 2. Se prueba la regex contra la URI actual.
//
// 3. Si hay coincidencia, se extraen los parámetros nombrados:
//    $params = ['id' => '42']
//
// 4. Se llama al controlador: $catalog->detail(['id' => '42'])
```

El patrón `(?P<id>...)` es una **captura nombrada de regex** en PHP. Permite leer el valor capturado como `$matches['id']` en lugar de `$matches[1]`.

### Por qué el orden de rutas importa

Las rutas se evalúan **en el orden en que se registran**. Por eso la ruta de búsqueda debe estar antes que `{id}`:

```php
// CORRECTO — /search se evalúa antes de /{id}
$router->add('GET', '/api/catalog/products/search', [$catalog, 'search']);
$router->add('GET', '/api/catalog/products/{id}',   [$catalog, 'detail']);

// Si estuvieran al revés, la URL /api/catalog/products/search
// sería interceptada por {id} con id='search', dando error 404.
```

---

## 4. JWT — Autenticación sin sesiones

En lugar de usar las sesiones de PHP (`$_SESSION`), el sistema usa **JWT** (JSON Web Token). Es la forma estándar en APIs REST modernas.

### ¿Qué es un JWT?

Un JWT es una cadena de texto con tres partes separadas por puntos:

```
eyJhbGciOiJIUzI1NiJ9  ←  Header (algoritmo usado)
.
eyJ1c2VyX2lkIjoxfQ    ←  Payload (datos del usuario)
.
SflKxwRJSMeKKF2QT4fw  ←  Firma digital (verifica integridad)
```

Las primeras dos partes son solo **Base64** (no están cifradas, cualquiera puede decodificarlas). La seguridad viene de la **firma**: si alguien modifica el payload, la firma ya no coincide y el token es rechazado.

### Cómo se genera: `src/Core/JWTHandler.php`

```php
public static function encode(array $payload): string {
    $header  = self::base64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = self::base64url(json_encode($payload));
    $firma   = self::base64url(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$firma";
}
```

El truco está en `hash_hmac('sha256', ..., JWT_SECRET)`: genera una firma usando la clave secreta del `.env`. Sin esa clave, es imposible crear un token válido.

### Cómo se verifica: `src/Core/AuthMiddleware.php`

```php
public static function authenticate(): array {
    // 1. Extraer el token del header HTTP: "Authorization: Bearer <token>"
    $token = self::extractToken();

    // 2. Decodificar y verificar la firma
    $payload = JWTHandler::decode($token);

    // 3. Verificar que no haya expirado
    if ($payload['exp'] < time()) {
        Response::error('Token expirado', 401);
    }

    // 4. Devolver los datos del usuario codificados en el token
    return $payload;
}
```

El frontend guarda el token en `localStorage` y lo envía en cada petición:

```javascript
// js/api.js
headers['Authorization'] = `Bearer ${token}`;
```

---

## 5. RBAC — Control de acceso por roles

RBAC significa *Role-Based Access Control*. El sistema tiene dos roles: `admin` y `customer`. Ciertas rutas solo son accesibles para admins.

### Cómo funciona en el código

```php
// En AdminController.php — solo admins pueden listar pedidos
public function listOrders(): void {
    $user = AuthMiddleware::authenticate();    // 1. ¿Está autenticado?
    AuthMiddleware::authorize($user, 'admin'); // 2. ¿Es admin?

    $orders = $this->orderModel->getAll();
    Response::json(['data' => $orders]);
}
```

```php
// src/Core/AuthMiddleware.php
public static function authorize(array $payload, string ...$roles): void {
    if (!in_array($payload['role'], $roles)) {
        Response::error('Acceso denegado', 403); // 403 Forbidden
        exit;
    }
}
```

El rol del usuario se guarda dentro del JWT en el momento del login, por lo que no se necesita consultar la base de datos en cada petición para verificar el rol.

---

## 6. La conexión a la base de datos: patrón Singleton

`config/database.php` implementa el patrón **Singleton**: garantiza que en todo el ciclo de vida de una petición PHP exista **una sola conexión** a MySQL, sin importar cuántos modelos la soliciten.

```php
class Database {
    private static ?PDO $instance = null; // ← la única instancia

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            // Solo se conecta la primera vez
            self::$instance = new PDO($dsn, $user, $pass, $options);
        }
        return self::$instance; // Las siguientes veces devuelve la misma conexión
    }
}
```

**¿Por qué es importante?** Abrir una conexión MySQL tiene un costo en tiempo y recursos. Si cada modelo creara su propia conexión, una sola petición podría abrir 5-6 conexiones innecesariamente.

### PDO con `ATTR_EMULATE_PREPARES = false`

```php
PDO::ATTR_EMULATE_PREPARES => false
```

Esto desactiva la emulación de prepared statements. Con esta opción, los parámetros (`:id`, `?`) son enviados al servidor MySQL de forma separada a la consulta SQL, lo que **previene inyección SQL** a nivel de protocolo, no solo escapando cadenas.

---

## 7. Transacciones ACID en el checkout

La parte más crítica del sistema es cuando un usuario confirma una compra. Se deben realizar **varias operaciones en la base de datos que deben ocurrir todas o ninguna**:

1. Crear el registro del pedido (`orders`)
2. Insertar cada ítem con el precio actual (`order_items`)
3. Descontar el stock de cada variante (`inventory`)
4. Vaciar el carrito del usuario (`cart_items`)

Si el servidor se cae entre el paso 2 y el 3, el pedido existiría pero el stock no se descontaría. Para evitar esto se usan **transacciones**:

```php
// src/Models/Order.php (simplificado)
public function createFromCart(int $userId): int {
    $db = Database::getConnection();

    try {
        $db->beginTransaction(); // ← Inicia la transacción

        // Paso 1: Crear el pedido
        $stmt = $db->prepare("INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
        $stmt->execute([$userId, $total]);
        $orderId = $db->lastInsertId();

        // Paso 2 y 3: Insertar ítems y descontar stock
        foreach ($cartItems as $item) {
            $db->prepare("INSERT INTO order_items ...")->execute([...]);
            $db->prepare("UPDATE inventory SET stock = stock - ? WHERE variant_id = ?")->execute([...]);
        }

        // Paso 4: Vaciar carrito
        $db->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$userId]);

        $db->commit(); // ← Todo salió bien: confirmar cambios
        return $orderId;

    } catch (\Exception $e) {
        $db->rollBack(); // ← Algo falló: revertir TODO
        throw $e;
    }
}
```

**ACID** son las cuatro garantías de una transacción:
- **A**tomicidad: todo o nada.
- **C**onsistencia: la BD siempre queda en estado válido.
- **I**solamiento: otras peticiones no ven los cambios a medias.
- **D**urabilidad: una vez confirmado, el cambio persiste aunque el servidor se caiga.

---

## 8. Variantes de producto con atributos JSON

El sistema permite que un producto tenga variantes completamente flexibles (talla, color, almacenamiento, etc.) sin modificar el esquema de la base de datos. Esto se logra con una columna de tipo `JSON`:

```sql
-- En la tabla product_variants:
attributes JSON
-- Ejemplos de valores almacenados:
-- '{"talla": "M", "color": "Rojo"}'
-- '{"ram": "16GB", "almacenamiento": "512GB SSD"}'
-- '{"switches": "Azul (Táctil/Clicky)"}'
```

En PHP, se lee y convierte a array asociativo:

```php
// src/Models/Product.php
foreach ($variants as &$var) {
    $var['attributes'] = json_decode($var['attributes'], true);
    // '{"talla":"M","color":"Rojo"}' → ['talla' => 'M', 'color' => 'Rojo']
}
```

En el frontend JavaScript, se muestra como texto limpio:

```javascript
// js/catalog.js
const attrs = JSON.stringify(v.attributes)
    .replace(/["{}]/g, ' ')  // elimina comillas y llaves
    .trim();
// → '  talla : M ,  color : Rojo  '
```

---

## 9. Soft Deletes (borrado lógico)

Todas las tablas principales tienen una columna `deleted_at TIMESTAMP NULL`. En lugar de eliminar filas con `DELETE`, se marca la fecha de borrado:

```sql
-- Borrado físico (destruye el dato para siempre):
DELETE FROM products WHERE id = 5;

-- Soft delete (el dato queda, solo se "oculta"):
UPDATE products SET deleted_at = NOW() WHERE id = 5;
```

Todas las consultas de lectura filtran los registros borrados:

```php
// src/Models/Product.php
WHERE p.deleted_at IS NULL  -- Solo productos activos
```

**¿Para qué sirve?**
- Los pedidos históricos siguen referenciando el producto y variante originales.
- Se puede "restaurar" un producto borrado.
- Existe un registro de auditoría completo.

---

## 10. Snapshot de precios en pedidos

Una decisión de diseño importante: cuando se crea un pedido, se guarda el **precio en ese momento** en `order_items.unit_price`, no una referencia al precio del producto.

```sql
-- order_items guarda el precio histórico:
INSERT INTO order_items (order_id, variant_id, quantity, unit_price)
VALUES (42, 7, 2, 89.99);
-- El precio de la variante 7 puede cambiar mañana a $95.
-- Este pedido siempre mostrará $89.99 porque lo tiene grabado.
```

Si se guardara solo `variant_id` y se leyera el precio actual, los totales históricos cambiarían cada vez que se actualiza un precio. Eso es incorrecto contablemente.

---

## 11. La API del frontend: `js/api.js`

El módulo `api` centraliza todas las llamadas HTTP y resuelve automáticamente la URL base de la API:

```javascript
async request(endpoint, method = 'GET', body = null) {
    // Calcula la URL base dinámica:
    // Si la página está en: http://localhost/e-commerce-nuevo/public/cart.html
    // → basePath = /e-commerce-nuevo/public
    // → apiUrl   = /e-commerce-nuevo/public/api/catalog/products
    const basePath = window.location.pathname
        .substring(0, window.location.pathname.lastIndexOf('/'));
    const apiUrl = basePath + '/api' + endpoint;

    const response = await fetch(apiUrl, config);
    // ...
}
```

Esto resuelve el problema de que XAMPP sirve el proyecto desde un subdirectorio (`localhost/e-commerce-nuevo/`), no desde la raíz del servidor. La URL de la API se construye dinámicamente desde la posición actual de la página.

---

## 12. Buscador: flujo completo de una búsqueda

Cuando el usuario escribe "laptop" y presiona Enter, ocurre lo siguiente:

```
1. ui.js detecta el evento 'keydown' con key='Enter'
   ↓
2. Llama a window.performSearch('laptop')   [catalog.js]
   ↓
3. api.request('/catalog/products/search?q=laptop')
   ↓ HTTP GET
4. Router.php matchea /api/catalog/products/search
   (antes de que {id} pueda interceptarla)
   ↓
5. CatalogController::search()
   → $_GET['q'] = 'laptop'
   → mb_strlen('laptop') >= 2 ✓
   ↓
6. Product::search('laptop')
   SQL: WHERE (p.name LIKE '%laptop%'
            OR p.description LIKE '%laptop%'
            OR c.name LIKE '%laptop%')
   ↓
7. JSON response: { "data": [...productos...] }
   ↓ HTTP response
8. renderProducts(res.data, 'Resultados para "laptop"')
   → Actualiza el DOM con los productos encontrados
```

---

## 13. Filtro por categorías: flujo completo

```
1. DOMContentLoaded → loadCategories()   [catalog.js]
   ↓
2. api.request('/catalog/categories')
   ↓ HTTP GET
3. Product::getCategories()
   SQL: SELECT id, name, slug FROM categories WHERE deleted_at IS NULL
   ↓
4. Se renderizan botones dinámicos en #category-filter
   ↓
5. Usuario hace clic en "Computación y Gaming"
   → filterByCategory(6, btn)
   ↓
6. api.request('/catalog/products?category_id=6')
   ↓ HTTP GET
7. Router matchea /api/catalog/products
   → CatalogController::list()
   → $_GET['category_id'] = '6'
   → Product::getByCategory(6)
   ↓
8. renderProducts(res.data, 'Computación y Gaming')
```

---

## 14. Prepared Statements y prevención de SQL Injection

Todos los parámetros de las consultas SQL se pasan como **prepared statements**, nunca concatenados como texto:

```php
// INSEGURO (vulnerable a SQL injection):
$stmt = $db->query("SELECT * FROM products WHERE id = " . $_GET['id']);
// Si $_GET['id'] = "1 OR 1=1", borra toda la tabla.

// SEGURO (el valor se envía separado de la consulta):
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$_GET['id']]);
// MySQL trata el parámetro como dato puro, nunca como código SQL.
```

En el método `search()`, el parámetro `LIKE` también se prepara así:

```php
$q = '%' . $query . '%';         // Se arma el patrón en PHP
$stmt->execute([$q, $q, $q]);   // Se envía como valor, no como SQL
```

---

## 15. Resumen de patrones de diseño usados

| Patrón | Dónde | Para qué |
|--------|-------|---------|
| **Singleton** | `config/database.php` | Una sola conexión PDO por petición |
| **MVC** | `src/Controllers/` + `src/Models/` | Separar lógica de negocio y acceso a datos |
| **Repository** | `src/Models/*.php` | Encapsular todas las consultas SQL |
| **Front Controller** | `public/index.php` | Un único punto de entrada para la API |
| **JWT Stateless Auth** | `src/Core/JWTHandler.php` | Autenticación sin sesiones del lado servidor |
| **RBAC** | `src/Core/AuthMiddleware.php` | Permisos por rol en los endpoints |
| **Soft Delete** | Todas las tablas | Borrado lógico con auditoría |
| **Price Snapshot** | `order_items.unit_price` | Precios históricos inmutables |
| **SPA** | `public/*.html` + `js/*.js` | Navegación sin recargas de página |

---

## 16. Flujo completo de una compra (el más complejo del sistema)

```
Usuario                  Frontend                 Backend                  DB
   │                        │                        │                      │
   │── Clic "Agregar" ─────>│                        │                      │
   │                        │── POST /api/cart ─────>│                      │
   │                        │   {variant_id: 7}      │── INSERT cart_items ─>│
   │                        │<── 200 OK ─────────────│                      │
   │                        │                        │                      │
   │── Ir al carrito ───────>│                        │                      │
   │                        │── GET /api/cart ───────>│                      │
   │                        │                        │── SELECT cart_items ─>│
   │                        │<── [{variante, precio}]─│                      │
   │                        │                        │                      │
   │── Clic "Pagar" ────────>│                        │                      │
   │                        │── POST /api/checkout ──>│                      │
   │                        │                        │─ BEGIN TRANSACTION    │
   │                        │                        │── INSERT orders ─────>│
   │                        │                        │── INSERT order_items ─>│
   │                        │                        │── UPDATE inventory ───>│
   │                        │                        │── DELETE cart_items ──>│
   │                        │                        │─ COMMIT               │
   │                        │<── {order_id: 42} ─────│                      │
   │                        │                        │                      │
   │                        │── POST /api/payment/simulate ──>│             │
   │                        │                        │── UPDATE orders ─────>│
   │                        │                        │   SET status='pagado' │
   │                        │<── {success: true} ────│                      │
   │<── "¡Pago exitoso!" ───│                        │                      │
```
