# UCT E-Commerce

Plataforma de comercio electrónico completa desarrollada con PHP 8, JavaScript vanilla y MySQL, diseñada para desplegarse sobre XAMPP.

## Tecnologías

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8, PDO, JWT nativo, RBAC |
| Frontend | Bootstrap 5.3, JavaScript vanilla (SPA) |
| Base de datos | MySQL 5.7+ |
| Pagos | PayPal SDK Sandbox + modo simulación |
| Servidor | XAMPP (Apache + MySQL) |

---

## Credenciales de acceso

| Rol | Correo | Contraseña |
|-----|--------|-----------|
| Administrador | `admin@uct.cl` | `password` |
| Cliente | `cliente@uct.cl` | `password` |

---

## Instalación rápida

### Requisitos

- XAMPP con PHP 8.0+ y MySQL 5.7+
- Apache con `mod_rewrite` habilitado (`AllowOverride All`)

### Pasos

1. **Copiar** el proyecto en:
   ```
   C:\xampp\htdocs\e-commerce-nuevo\
   ```

2. **Iniciar** Apache y MySQL desde el Panel de Control de XAMPP.

3. **Importar la base de datos** desde [phpMyAdmin](http://localhost/phpmyadmin):
   - Importar `database/schema.sql` (crea tablas y base de datos)
   - Importar `database/seed.sql` (16 productos, 6 categorías, usuarios de prueba)

4. **Verificar** el archivo `.env` (ya configurado para XAMPP):
   ```env
   DB_HOST=127.0.0.1
   DB_NAME=uct_ecommerce
   DB_USER=root
   DB_PASS=
   ```

5. **Acceder** a la aplicación:
   ```
   http://localhost/e-commerce-nuevo/public/
   ```

> **¿Base de datos ya existente?** Ejecuta `database/add_products.sql` en phpMyAdmin para agregar las nuevas categorías y productos sin perder datos existentes.

---

## Estructura del proyecto

```
e-commerce-nuevo/
├── .env                      → Variables de entorno (DB, JWT, PayPal)
├── config/
│   └── database.php          → Conexión PDO (patrón Singleton)
├── database/
│   ├── schema.sql            → Estructura de tablas
│   ├── seed.sql              → Datos completos (16 productos, 6 categorías)
│   └── add_products.sql      → Agrega productos a una BD ya existente
├── public/                   → Raíz web (Apache apunta aquí)
│   ├── index.html            → Catálogo con buscador y filtros por categoría
│   ├── login.html            → Inicio de sesión y registro
│   ├── cart.html             → Carrito de compras
│   ├── admin.html            → Panel de administración
│   ├── mis-compras.html      → Historial de pedidos del cliente
│   ├── index.php             → Punto de entrada de la API REST
│   ├── css/style.css         → Estilos personalizados (branding UCT)
│   └── js/
│       ├── api.js            → Cliente HTTP con manejo de JWT
│       ├── ui.js             → Header dinámico, buscador, notificaciones
│       ├── catalog.js        → Catálogo, búsqueda y filtros por categoría
│       ├── auth.js           → Formularios de login y registro
│       ├── cart.js           → Gestión del carrito y pago PayPal
│       ├── orders.js         → Historial de pedidos
│       └── admin.js          → Panel admin (pedidos + alertas de inventario)
└── src/
    ├── Controllers/          → Controladores de la API
    ├── Core/                 → Router, JWT, Middleware, Response
    └── Models/               → Modelos de acceso a datos (PDO)
```

---

## API REST

### Pública (sin autenticación)

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/auth/login` | Iniciar sesión → JWT |
| `POST` | `/api/auth/register` | Registrar nueva cuenta |
| `GET` | `/api/catalog/products` | Listar todos los productos |
| `GET` | `/api/catalog/products?category_id={id}` | Filtrar por categoría |
| `GET` | `/api/catalog/products/search?q={término}` | Buscar productos |
| `GET` | `/api/catalog/products/{id}` | Detalle y variantes de un producto |
| `GET` | `/api/catalog/categories` | Listar todas las categorías |
| `GET` | `/api/config/paypal` | Obtener PayPal Client ID |

### Protegida (requiere JWT)

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/cart` | Ver carrito del usuario |
| `POST` | `/api/cart` | Agregar producto al carrito |
| `DELETE` | `/api/cart/{variant_id}` | Eliminar del carrito |
| `POST` | `/api/checkout` | Crear pedido desde el carrito |
| `POST` | `/api/payment/simulate` | Simular pago |
| `POST` | `/api/payment/paypal/create` | Crear orden en PayPal |
| `POST` | `/api/payment/paypal/capture` | Capturar pago de PayPal |
| `GET` | `/api/orders/me` | Mis pedidos |

### Solo administradores

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/admin/orders` | Todos los pedidos |
| `PUT` | `/api/admin/orders/{id}/status` | Actualizar estado de pedido |
| `GET` | `/api/inventory/alerts` | Alertas de stock bajo |

---

## Flujo de compra

```
Catálogo → Buscar / Filtrar por categoría → Ver producto → Agregar al carrito
         → Checkout → Pago PayPal o Simulación → Pedido confirmado
```

## Estados de un pedido

```
pendiente_pago → pagado → en_preparacion → enviado → entregado
```

---

## Características

- **Autenticación JWT** con expiración configurable (1 hora por defecto)
- **RBAC** — control de acceso por roles: `admin` / `customer`
- **Buscador en tiempo real** por nombre, descripción y categoría
- **Filtro por categorías** con pestañas dinámicas cargadas desde la BD
- **Variantes de producto** (talla, color, almacenamiento, etc.) via atributos JSON
- **Inventario por variante** con alertas de stock mínimo para el admin
- **Carrito persistente** por usuario almacenado en base de datos
- **Integración PayPal Sandbox** con fallback a modo simulación
- **Soft deletes** para auditoría completa de datos
- **Transacciones ACID** en el proceso de checkout
- **Snapshot de precios** en `order_items` (precio inalterable post-compra)

---

## Categorías disponibles

| Categoría | Slug |
|-----------|------|
| Electrónica | `electronica` |
| Ropa | `ropa` |
| Calzado | `calzado` |
| Hogar y Cocina | `hogar-cocina` |
| Deportes y Fitness | `deportes-fitness` |
| Computación y Gaming | `computacion-gaming` |

---

## Variables de entorno (`.env`)

```env
# Base de datos
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=uct_ecommerce
DB_USER=root
DB_PASS=

# JWT
JWT_SECRET=tu_clave_secreta_jwt_muy_segura_1234
JWT_EXPIRATION=3600

# PayPal Sandbox
PAYPAL_CLIENT_ID=<tu_client_id>
PAYPAL_SECRET=<tu_secret>
PAYPAL_ENVIRONMENT=sandbox
```

---

## Licencia

Proyecto académico desarrollado en la Universidad Católica de Temuco (UCT).
