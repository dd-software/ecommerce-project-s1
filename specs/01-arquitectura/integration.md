# Especificación de Integración

**Proyecto:** UCT Ecommerce  

**Equipo:** Los Takas — Arquitectura & DevOps  

**Versión:** 1.0  

**Estado:** Sprint 1  

**Última actualización:** 2025  

---

## 1. Propósito y Objetivos

Este documento especifica la capa de integración del sistema UCT Ecommerce. Define cómo se comunican entre sí el frontend (HTML/CSS/JS), el backend (API REST en PHP 8), la capa de persistencia (MySQL) y el mecanismo de seguridad (JWT), incluyendo formatos de datos, contratos, flujos de autenticación y convenciones de manejo de errores.

El objetivo es proveer una especificación completa e inequívoca de todos los puntos de integración, de modo que cada equipo pueda desarrollar su módulo de forma independiente sin sorpresas en tiempo de ejecución.

---

## 2. Actores

| Actor | Tipo | Descripción |
| --- | --- | --- |
| Cliente (Navegador) | Externo | Usuario final ejecutando la aplicación en un navegador web |
| JS Frontend | Interno | Código JavaScript ejecutándose en el navegador |
| Servidor Apache | Interno | Servidor HTTP que enruta solicitudes hacia PHP |
| API REST PHP | Interno | Backend que maneja la lógica de negocio |
| MySQL | Interno | Base de datos relacional con toda la data persistente |
| Middleware JWT | Interno | Middleware en PHP que valida tokens de autenticación |
| Usuario Admin | Humano | Operador de backoffice con permisos elevados |
| Cliente | Humano | Usuario registrado que navega y compra productos |
| Invitado | Humano | Visitante no autenticado con acceso de solo lectura |

---

## 3. Puntos de Integración

### 3.1 Frontend → Backend (HTTP/JSON)

**Protocolo:** HTTP/1.1  

**Base URL:** `/api/v1`  

**Content-Type:** `application/json` para todas las requests con body  

**Accept:** `application/json` para todas las requests

#### 3.1.1 Formato de Request

```json
POST /api/v1/orders
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json

{
  "items": [
    { "product_id": 42, "quantity": 2 },
    { "product_id": 17, "quantity": 1 }
  ],
  "shipping_address_id": 5
}
```

#### 3.1.2 Formato de Respuesta Exitosa

```json
HTTP/1.1 201 Created
Content-Type: application/json

{
  "success": true,
  "data": {
    "order_id": 1091,
    "status": "pending",
    "total": 89900,
    "created_at": "2025-06-05T14:32:00Z"
  },
  "meta": {
    "request_id": "req_abc123"
  }
}
```

#### 3.1.3 Formato de Respuesta de Error

```json
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/json

{
  "success": false,
  "error": {
    "code": "INSUFFICIENT_STOCK",
    "message": "El producto con ID 42 tiene solo 1 unidad disponible.",
    "field": "items[0].quantity"
  },
  "meta": {
    "request_id": "req_abc123"
  }
}
```

#### 3.1.4 Formato de Respuesta de Lista Paginada

```json
{
  "success": true,
  "data": [ ... ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 142,
    "total_pages": 8
  }
}
```

---

### 3.2 Integración de Autenticación JWT

#### 3.2.1 Emisión del Token

El token JWT se emite por `POST /api/v1/auth/login` tras validar credenciales exitosamente.

**Payload del token:**

```json
{
  "sub": "user_id:42",
  "email": "user@example.com",
  "role": "customer",
  "iat": 1717588320,
  "exp": 1717595520
}
```

| Campo | Tipo | Descripción |
| --- | --- | --- |
| `sub` | string | Identificador único de usuario |
| `email` | string | Email del usuario (solo para mostrar, no para búsquedas) |
| `role` | string | Rol RBAC: `customer` o `admin` |
| `iat` | integer | Timestamp Unix — momento de emisión |
| `exp` | integer | Timestamp Unix — expiración (iat + 7200) |

**Algoritmo de firma:** HS256  

**Secreto:** cargado desde variable de entorno `JWT_SECRET`  

**Expiración:** 2 horas (7200 segundos)

#### 3.2.2 Transmisión del Token

El cliente guarda el token en `localStorage` bajo la clave `uct_auth_token`.

Toda request subsecuente a un endpoint protegido debe incluir:

```
Authorization: Bearer <token>
```

#### 3.2.3 Flujo de Validación del Token

```
Request llega a Apache
        │
        ▼
Front Controller PHP (index.php)
        │
        ▼
Route resolver verifica si la ruta es protegida
        │
   ┌────┴────┐
   │         │
Pública   Protegida
   │         │
   ▼         ▼
Continúa  JwtMiddleware::validate()
              │
        ┌─────┴───────────────────┐
        │                         │
     Token válido           Inválido / expirado
        │                         │
        ▼                         ▼
Adjunta contexto de usuario   Retorna 401 error JSON
al objeto request             { "error": "TOKEN_INVALID" }
        │
        ▼
Se ejecuta el handler de la ruta
```

#### 3.2.4 Rutas Protegidas vs Públicas

| Ruta | Método | ¿Requiere auth? | Roles |
| --- | --- | --- | --- |
| `/api/v1/auth/login` | POST | No | — |
| `/api/v1/auth/register` | POST | No | — |
| `/api/v1/products` | GET | No | — |
| `/api/v1/products/{id}` | GET | No | — |
| `/api/v1/cart` | GET, POST, DELETE | Sí | customer |
| `/api/v1/orders` | GET, POST | Sí | customer |
| `/api/v1/orders/{id}` | GET | Sí | customer, admin |
| `/api/v1/admin/*` | ALL | Sí | admin |
| `/api/v1/users/me` | GET, PATCH | Sí | customer, admin |

---

### 3.3 Integración Backend → MySQL

**Driver:** PDO (PHP Data Objects)  

**Charset de conexión:** utf8mb4  

**Modo de error:** `PDO::ERRMODE_EXCEPTION`

#### 3.3.1 Configuración de Conexión

```php
$dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
$pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);
```

Todas las credenciales de base de datos se cargan desde variables de entorno. Las credenciales hardcodeadas están prohibidas.

#### 3.3.2 Patrón de Consultas — Prepared Statements

```php
// CORRECTO — siempre usar prepared statements
$stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = :cat AND active = 1");
$stmt->execute([':cat' => $categoryId]);
$products = $stmt->fetchAll();

// PROHIBIDO — nunca interpolar input de usuario en SQL
$products = $pdo->query("SELECT * FROM products WHERE id = {$_GET['id']}")->fetchAll();
```

#### 3.3.3 Patrón de Transacciones

Operaciones que modifican múltiples tablas deben usar transacciones explícitas:

```php
try {
    $pdo->beginTransaction();

    // insertar order
    // actualizar stock de producto
    // insertar order_items

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

---

## 4. Convenciones de Endpoints API

### 4.1 Estructura de URLs

```
/api/v1/{resource}                 → operaciones sobre colección
/api/v1/{resource}/{id}            → operaciones sobre recurso individual
/api/v1/{resource}/{id}/{sub}      → operaciones sobre sub-recurso
```

Ejemplos:

- `GET /api/v1/products` — listar productos
- `POST /api/v1/products` — crear producto (solo admin)
- `GET /api/v1/products/42` — obtener producto por ID
- `GET /api/v1/orders/1091/items` — listar ítems del pedido 1091

### 4.2 Semántica de Métodos HTTP

| Método | Semántica | Body | Idempotente |
| --- | --- | --- | --- |
| GET | Leer recurso(s) | No | Sí |
| POST | Crear recurso | Sí | No |
| PUT | Reemplazo total del recurso | Sí | Sí |
| PATCH | Actualización parcial del recurso | Sí | No |
| DELETE | Eliminar recurso | No | Sí |

### 4.3 Parámetros de Query

| Parámetro | Tipo | Descripción |
| --- | --- | --- |
| `page` | integer ≥ 1 | Nº de página para paginación (default: 1) |
| `per_page` | integer 1–100 | Resultados por página (default: 20) |
| `sort` | string | Campo por el cual ordenar |
| `order` | `asc` \ | `desc` |
| `q` | string | Búsqueda full-text |

---

## 5. Convenciones de Tipos de Datos

| Tipo PHP | Tipo JSON | Tipo MySQL | Notas |
| --- | --- | --- | --- |
| int | number | INT / BIGINT | IDs siempre son enteros |
| float | number | DECIMAL(10,2) | Valores monetarios en centavos (entero) |
| string | string | VARCHAR / TEXT | UTF-8, trim |
| bool | boolean | TINYINT(1) | 0 o 1 en MySQL |
| null | null | NULL | Solo en campos nulables |
| DateTime | string (ISO 8601) | DATETIME | Formato: `2025-06-05T14:32:00Z` |

**Los montos monetarios** se almacenan y transmiten como **enteros en centavos** (p. ej., $89.90 → `8990`). La conversión a formato de visualización es responsabilidad del frontend.

---

## 6. Configuración CORS

Los headers CORS se configuran en Apache (o `.htaccess`):

```
Header set Access-Control-Allow-Origin "https://uct-ecommerce.local"
Header set Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS"
Header set Access-Control-Allow-Headers "Authorization, Content-Type, X-Request-ID"
Header set Access-Control-Max-Age "86400"
```

Las requests *preflight* (`OPTIONS`) deben responder `200 OK` con esos headers y body vacío.

En producción, `Access-Control-Allow-Origin` debe ser el dominio exacto del frontend. El wildcard `*` está prohibido cuando se usan headers `Authorization`.

---

## 7. Contratos de Integración Entre Módulos

### 7.1 Auth → Todos los Módulos Protegidos

El módulo Auth entrega lo siguiente a los demás módulos vía middleware JWT:

```php
// Disponible en cada controller protegido mediante contexto del request
$user = $request->getAttribute('authenticated_user');

// $user es un array: ['id' => 42, 'email' => '...', 'role' => 'customer']
```

Ningún módulo puede decodificar JWT por su cuenta. Todos deben usar el valor inyectado por el middleware.

### 7.2 Products → Orders

Al crear un pedido, el módulo Orders debe validar disponibilidad de stock llamando al servicio de Products. No debe consultar la tabla `products` directamente.

```php
// CORRECTO
$available = $productService->checkStock($productId, $quantity);

// PROHIBIDO — acceso directo cross-módulo a BD
$stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
```

### 7.3 Orders → Payments

El módulo Orders crea un pedido en estado `pending`. El módulo Payments actualiza el estado a `paid` o `failed` tras confirmar el pago.

Esta actualización debe hacerse mediante la interfaz del servicio de Orders, no escribiendo directo en la tabla `orders`.

---

## 8. Reglas de Negocio para Integración

1. Una request con JWT expirado debe responder `401` con código `TOKEN_EXPIRED`. El cliente debe re-autenticarse.
2. Una request con JWT válido pero rol insuficiente debe responder `403` con código `INSUFFICIENT_PERMISSIONS`.
3. Todos los montos monetarios en requests y responses usan centavos enteros.
4. Todos los datetimes en responses están en UTC y en formato ISO 8601.
5. Defaults de paginación: `page=1`, `per_page=20`. `per_page` no debe exceder `100`.
6. Si un endpoint de colección retorna 0 resultados, responde `200 OK` con `"data": []`, nunca `404`.

---

## 9. Reglas de Validación

| Campo | Regla |
| --- | --- |
| `email` | Requerido, formato RFC 5322, máx 255 chars |
| `password` | Requerido, mín 8 chars, máx 72 chars |
| `product_id` | Requerido, entero positivo, debe existir en `products` |
| `quantity` | Requerido, entero, mín 1, máx 999 |
| `page` | Opcional, entero ≥ 1, default 1 |
| `per_page` | Opcional, entero 1–100, default 20 |
| `JWT token` | Requerido en rutas protegidas, HS256, no expirado |

---

## 10. Criterios de Aceptación

- [ ]  Todas las respuestas de endpoints protegidos sin JWT válido retornan `401` con `TOKEN_INVALID` o `TOKEN_EXPIRED`.
- [ ]  Todos los endpoints `POST` y `PATCH` validan inputs y retornan `400` o `422` con mensajes de error por campo.
- [ ]  Los montos monetarios se transmiten como enteros en todas las respuestas del API.
- [ ]  Los campos datetime en todas las respuestas son strings ISO 8601 en UTC.
- [ ]  Los headers CORS están presentes en todas las respuestas, incluyendo errores.
- [ ]  `Authorization: Bearer` es el único mecanismo de transmisión de JWT (no se usan cookies).
- [ ]  Ningún módulo accede directamente a las tablas de BD de otro módulo.
- [ ]  Todas las queries PDO usan prepared statements (sin interpolación de strings).