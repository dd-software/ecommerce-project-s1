# Guía de instalación — Beta

## Requisitos
- PHP 8.1 o superior (con extensión `pdo_mysql`)
- MySQL 8.0+ (o MariaDB 10.6+)
- MySQL corriendo en `localhost`

---

## Pasos

### 1. Configurar la base de datos

Edita `api/src/config/config.php` con tus credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_db');
define('DB_USER', 'root');       // tu usuario MySQL
define('DB_PASS', '');           // tu contraseña MySQL
```

### 2. Ejecutar el instalador

Desde la carpeta `app/`:

```bash
php database/setup.php
```

Esto crea la base de datos, todas las tablas y datos de prueba.

### 3. Iniciar el servidor

```bash
php -S localhost:8000 router.php
```

### 4. Abrir en el navegador

```
http://localhost:8000
```

---

## Credenciales de prueba

| Rol           | Email                      | Contraseña    |
|---------------|----------------------------|---------------|
| Administrador | admin@ecommerce.com        | Admin123!     |
| Cliente       | cliente@ecommerce.com      | Cliente123!   |

---

## Páginas disponibles

| URL                        | Descripción                     |
|----------------------------|---------------------------------|
| `/`                        | Catálogo con filtros y búsqueda |
| `/login.html`              | Inicio de sesión                |
| `/registro.html`           | Crear cuenta nueva              |
| `/producto.html?id=1`      | Detalle de un producto          |
| `/carrito.html`            | Carrito de compras              |
| `/checkout.html`           | Confirmación de compra          |
| `/pedidos.html`            | Historial de pedidos del cliente|
| `/admin/`                  | Panel de administración         |

---

## Endpoints API

Base URL: `http://localhost:8000/api`

### Auth
| Método | Ruta              | Auth | Descripción               |
|--------|-------------------|------|---------------------------|
| POST   | /auth/login       | No   | Iniciar sesión → JWT      |
| POST   | /auth/registro    | No   | Crear cuenta → JWT        |
| GET    | /auth/me          | JWT  | Info del usuario actual   |

### Catálogo (público)
| Método | Ruta                              | Descripción                    |
|--------|-----------------------------------|--------------------------------|
| GET    | /catalogo/productos               | Lista paginada con filtros     |
| GET    | /catalogo/productos/{id}          | Detalle del producto           |
| GET    | /catalogo/categorias              | Todas las categorías           |
| GET    | /catalogo/categorias/{id}/productos | Productos por categoría      |

### Carrito (requiere JWT)
| Método | Ruta                      | Descripción              |
|--------|---------------------------|--------------------------|
| GET    | /carrito                  | Ver carrito              |
| DELETE | /carrito                  | Vaciar carrito           |
| POST   | /carrito/items            | Agregar ítem             |
| PUT    | /carrito/items/{id}       | Actualizar cantidad      |
| DELETE | /carrito/items/{id}       | Eliminar ítem            |

### Checkout (requiere JWT)
| Método | Ruta                          | Descripción              |
|--------|-------------------------------|--------------------------|
| POST   | /checkout                     | Confirmar pedido         |
| GET    | /checkout/pedidos             | Mis pedidos              |
| GET    | /checkout/pedidos/{id}        | Detalle de pedido        |

### Admin (requiere JWT + rol administrador)
| Método | Ruta                              | Descripción              |
|--------|-----------------------------------|--------------------------|
| GET    | /admin/stats                      | Estadísticas dashboard   |
| GET    | /admin/productos                  | Listar productos         |
| POST   | /admin/productos                  | Crear producto           |
| PUT    | /admin/productos/{id}             | Editar producto          |
| DELETE | /admin/productos/{id}             | Desactivar producto      |
| GET    | /admin/categorias                 | Listar categorías        |
| POST   | /admin/categorias                 | Crear categoría          |
| GET    | /admin/pedidos                    | Todos los pedidos        |
| PUT    | /admin/pedidos/{id}/estado        | Cambiar estado pedido    |
| GET    | /admin/inventario                 | Ver inventario           |
| PUT    | /admin/inventario/{productoId}    | Actualizar stock         |
| GET    | /admin/usuarios                   | Listar usuarios          |
| PUT    | /admin/usuarios/{id}              | Habilitar/deshabilitar   |

---

## Reglas de negocio implementadas

- **RN-001** No se pueden agregar al carrito productos sin stock.
- **RN-002** Solo administradores pueden acceder a `/admin/*`.
- **RN-003** El stock se descuenta al confirmar el pago (POST /checkout).
- **RN-004** Usuarios deshabilitados reciben 403 al intentar autenticarse.
- **RN-005** Cada cambio de estado de pedido queda registrado en `historial_pedido`.
