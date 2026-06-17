# Especificación de Base de Datos MySQL

**Proyecto:** UCT Ecommerce  

**Equipo:** Los Takas — Arquitectura & DevOps  

**Versión:** 1.0  

**Estado:** Sprint 1  

**Última actualización:** 2025  

---

## 1. Propósito y Objetivos

Este documento especifica el entorno de base de datos MySQL, convenciones de esquema, requisitos de configuración y restricciones operacionales para el sistema UCT Ecommerce. Define la estructura de base de datos que deben usar todos los módulos del backend y sirve como fuente autorizada para decisiones de diseño del esquema.

---

## 2. Motor de Base de Datos

| Propiedad | Valor |
| --- | --- |
| DBMS | MySQL |
| Versión requerida | 8.0 o superior |
| Motor de almacenamiento por defecto | InnoDB |
| Charset por defecto | `utf8mb4` |
| Collation por defecto | `utf8mb4_unicode_ci` |
| Zona horaria | UTC |

Todas las tablas deben usar el motor de almacenamiento **InnoDB** para soportar constraints de llaves foráneas y transacciones ACID.

**Por qué `utf8mb4`:** El encoding `utf8` de MySQL solo soporta caracteres de 3 bytes y no puede almacenar emojis o ciertos puntos de código Unicode. `utf8mb4` soporta el rango completo de Unicode de 4 bytes y es obligatorio para todas las tablas y columnas que almacenen texto generado por usuarios.

---

## 3. Parámetros de Conexión

```
Host:     DB_HOST (desde .env)
Port:     DB_PORT (default: 3306)
Database: DB_NAME (desde .env)
User:     DB_USER (desde .env)
Password: DB_PASS (desde .env)
Charset:  utf8mb4
```

La aplicación se conecta vía PDO usando la configuración definida en el documento de especificación de PHP.

---

## 4. Convenciones de Diseño del Esquema

### 4.1 Nomenclatura

- Nombres de tablas: **snake_case**, en plural (p. ej., `products`, `order_items`, `user_addresses`).
- Nombres de columnas: **snake_case** (p. ej., `created_at`, `product_id`, `unit_price`).
- Llaves primarias: siempre `id`, tipo `BIGINT UNSIGNED AUTO_INCREMENT`.
- Llaves foráneas: `{tabla_referenciada_en_singular}_id` (p. ej., `user_id`, `product_id`).
- Columnas booleanas: prefijo `is_` o `has_` (p. ej., `is_active`, `has_discount`).
- Columnas de timestamps: `created_at` y `updated_at` en todas las tablas.

### 4.2 Llaves Primarias

```sql
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
```

Todas las llaves primarias son `BIGINT UNSIGNED AUTO_INCREMENT`. No se usan UUID como llaves primarias para preservar el rendimiento de índices en tablas grandes.

### 4.3 Timestamps

Cada tabla debe incluir:

```sql
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

### 4.4 Soft Deletes

Los registros que deban preservarse por auditoría usan una columna `deleted_at` nulable en lugar de borrado físico:

```sql
deleted_at DATETIME NULL DEFAULT NULL
```

Los módulos que apliquen soft delete deben filtrar `WHERE deleted_at IS NULL` en todas las consultas estándar.

### 4.5 Valores Monetarios

Todos los montos monetarios se almacenan como **`INT UNSIGNED`** representando **centavos** (p. ej., $89.90 → `8990`). No se usan `DECIMAL` ni `FLOAT` para dinero, para evitar errores de redondeo por coma flotante.

---

## 5. Esquema Base (*Core Schema*)

### 5.1 `users`

```sql
CREATE TABLE users (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email         VARCHAR(255)     NOT NULL,
    password_hash VARCHAR(255)     NOT NULL,
    first_name    VARCHAR(100)     NOT NULL,
    last_name     VARCHAR(100)     NOT NULL,
    role          ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    is_active     TINYINT(1)       NOT NULL DEFAULT 1,
    created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at    DATETIME         NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5.2 `categories`

```sql
CREATE TABLE categories (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(150)     NOT NULL,
    slug       VARCHAR(150)     NOT NULL,
    parent_id  BIGINT UNSIGNED  NULL DEFAULT NULL,
    is_active  TINYINT(1)       NOT NULL DEFAULT 1,
    created_at DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug),
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5.3 `products`

```sql
CREATE TABLE products (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id BIGINT UNSIGNED NOT NULL,
    name        VARCHAR(255)    NOT NULL,
    slug        VARCHAR(255)    NOT NULL,
    description TEXT            NULL,
    price       INT UNSIGNED    NOT NULL COMMENT 'Precio en centavos',
    stock       INT UNSIGNED    NOT NULL DEFAULT 0,
    image_url   VARCHAR(500)    NULL,
    is_active   TINYINT(1)      NOT NULL DEFAULT 1,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME        NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_category (category_id),
    KEY idx_products_active (is_active),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5.4 `orders`

```sql
CREATE TABLE orders (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id             BIGINT UNSIGNED NOT NULL,
    status              ENUM('pending','paid','processing','shipped','delivered','cancelled','refunded')
                        NOT NULL DEFAULT 'pending',
    total_cents         INT UNSIGNED    NOT NULL COMMENT 'Total del pedido en centavos',
    shipping_address_id BIGINT UNSIGNED NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_orders_user (user_id),
    KEY idx_orders_status (status),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5.5 `order_items`

```sql
CREATE TABLE order_items (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id   BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED    NOT NULL,
    unit_price INT UNSIGNED    NOT NULL COMMENT 'Precio al momento de compra, en centavos',
    PRIMARY KEY (id),
    KEY idx_order_items_order (order_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5.6 `payments`

```sql
CREATE TABLE payments (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id     BIGINT UNSIGNED NOT NULL,
    amount_cents INT UNSIGNED    NOT NULL COMMENT 'Monto cobrado en centavos',
    status       ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
    provider     VARCHAR(50)     NOT NULL COMMENT 'p. ej. stripe, paypal',
    provider_ref VARCHAR(255)    NULL     COMMENT 'ID de transacción externo del proveedor',
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payments_order (order_id),
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 6. Índices

| Tabla | Índice | Tipo | Propósito |
| --- | --- | --- | --- |
| `users` | `email` | UNIQUE | Login y unicidad |
| `products` | `category_id` | INDEX | Filtrar por categoría |
| `products` | `is_active` | INDEX | Filtrar productos activos |
| `products` | `slug` | UNIQUE | Búsqueda por URL |
| `orders` | `user_id` | INDEX | Obtener pedidos por usuario |
| `orders` | `status` | INDEX | Filtrar por estado |
| `order_items` | `order_id` | INDEX | Obtener ítems de un pedido |
| `payments` | `order_id` | INDEX | Buscar pago por pedido |

Los índices deben definirse al crear la tabla y documentarse cualquier índice agregado post-despliegue.

---

## 7. Transacciones

Toda operación que modifique más de una tabla debe ejecutarse dentro de una transacción. Ejemplos:

- Crear un pedido: insertar en `orders`, insertar en `order_items`, decrementar `products.stock`.
- Procesar un pago: insertar en `payments`, actualizar `orders.status`.
- Cancelar un pedido: actualizar `orders.status`, restaurar `products.stock`.

El rollback debe ejecutarse ante cualquier excepción. Las escrituras parciales en la base de datos nunca son aceptables.

---

## 8. Migraciones

Los cambios de esquema se gestionan mediante archivos de migración en `database/migrations/`. Cada archivo sigue la convención:

```
YYYY_MM_DD_HHMMSS_description.sql
```

Ejemplo:

```
2025_06_05_120000_create_users_table.sql
2025_06_05_120100_create_products_table.sql
```

Las migraciones se ejecutan en orden cronológico. Una tabla `migrations` registra cuáles migraciones ya fueron aplicadas:

```sql
CREATE TABLE migrations (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    filename   VARCHAR(255) NOT NULL,
    applied_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_migrations_filename (filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 9. Restricciones de Seguridad

1. El usuario de base de datos usado por la aplicación PHP debe tener solo privilegios `SELECT`, `INSERT`, `UPDATE`, `DELETE` sobre la base de datos de la aplicación. No se permiten `DROP`, `ALTER`, `CREATE` ni `GRANT` para el usuario de la app.
2. Un usuario admin separado con privilegios completos se usa solo para ejecutar migraciones.
3. El login remoto de root está deshabilitado.
4. El puerto MySQL (3306) no debe estar expuesto a Internet pública. Debe ser accesible solo desde el servidor de la aplicación.
5. Todas las contraseñas en la tabla `users` son hashes bcrypt; nunca se almacena el password en texto plano.

---

## 10. Backup y Recuperación

- Backups diarios automatizados usando `mysqldump` en producción.
- Retención mínima de backups: 7 días.
- Comando de backup:

```bash
mysqldump -u root -p --single-transaction --quick --lock-tables=false \
  uct_ecommerce > /backups/uct_ecommerce_$(date +%Y%m%d_%H%M%S).sql
```

---

## 11. Criterios de Aceptación

- [ ]  Todas las tablas usan motor `InnoDB` con charset `utf8mb4` y collation `utf8mb4_unicode_ci`.
- [ ]  Todas las tablas tienen `id BIGINT UNSIGNED AUTO_INCREMENT` como llave primaria.
- [ ]  Todas las tablas tienen columnas `created_at` y `updated_at` con defaults.
- [ ]  Todos los valores monetarios se almacenan como `INT UNSIGNED` en centavos.
- [ ]  Todas las llaves foráneas declaran acciones explícitas `ON DELETE` y `ON UPDATE`.
- [ ]  No existen columnas `FLOAT` o `DOUBLE` para almacenar dinero.
- [ ]  El usuario de BD de la aplicación no tiene privilegios `DROP`, `ALTER` ni `CREATE`.
- [ ]  El puerto MySQL no es accesible desde fuera de la red del servidor.
- [ ]  Existen migraciones para todas las tablas y se aplican en orden sin errores.