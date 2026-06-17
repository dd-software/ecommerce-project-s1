-- ============================================
-- Script de configuración inicial de BD
-- ============================================
-- Ejecutar como root:
-- mysql -u root -p < database/setup.sql

DROP DATABASE IF EXISTS uct_ecommerce;
CREATE DATABASE uct_ecommerce
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'ecommerce_app'@'localhost'
  IDENTIFIED BY 'app_password_here';

GRANT ALL PRIVILEGES ON uct_ecommerce.*
  TO 'ecommerce_app'@'localhost';

FLUSH PRIVILEGES;

USE uct_ecommerce;

-- ============================================
-- Schema: Plataforma Ecommerce UCT
-- Motor: MySQL 8.x / InnoDB
-- Charset: utf8mb4
-- ============================================





-- ============================================
-- Tabla: usuarios
-- ============================================
CREATE TABLE usuarios (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre        VARCHAR(100)     NOT NULL,
    apellido      VARCHAR(100)     NOT NULL,
    email         VARCHAR(255)     NOT NULL,
    password_hash VARCHAR(255)     NOT NULL,
    rol           ENUM('cliente','admin','vendedor','supervisor') NOT NULL DEFAULT 'cliente',
    activo        TINYINT(1)       NOT NULL DEFAULT 1,
    intentos_fallidos INT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta  DATETIME      NULL DEFAULT NULL,
    ultimo_login    DATETIME       NULL DEFAULT NULL,
    created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at    DATETIME         NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email),
    KEY idx_usuarios_rol (rol),
    KEY idx_usuarios_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: categorias
-- ============================================
CREATE TABLE categorias (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre     VARCHAR(150)     NOT NULL,
    slug       VARCHAR(150)     NOT NULL,
    descripcion TEXT            NULL,
    id_padre   BIGINT UNSIGNED  NULL DEFAULT NULL,
    activo     TINYINT(1)       NOT NULL DEFAULT 1,
    created_at DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categorias_slug (slug),
    KEY idx_categorias_padre (id_padre),
    CONSTRAINT fk_categorias_padre FOREIGN KEY (id_padre) REFERENCES categorias (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: productos
-- ============================================
CREATE TABLE productos (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_categoria BIGINT UNSIGNED NOT NULL,
    nombre      VARCHAR(255)    NOT NULL,
    slug        VARCHAR(255)    NOT NULL,
    descripcion TEXT            NULL,
    precio      INT UNSIGNED    NOT NULL COMMENT 'Precio en centavos (sin IVA)',
    stock       INT UNSIGNED    NOT NULL DEFAULT 0,
    stock_minimo INT UNSIGNED   NOT NULL DEFAULT 5,
    imagen_url  VARCHAR(500)    NULL,
    activo      TINYINT(1)      NOT NULL DEFAULT 1,
    meta_descripcion VARCHAR(300) NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME        NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_productos_slug (slug),
    KEY idx_productos_categoria (id_categoria),
    KEY idx_productos_activo (activo),
    KEY idx_productos_precio (precio),
    KEY idx_productos_stock (stock),
    CONSTRAINT fk_productos_categoria FOREIGN KEY (id_categoria) REFERENCES categorias (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: imagenes_producto
-- ============================================
CREATE TABLE imagenes_producto (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_producto BIGINT UNSIGNED NOT NULL,
    url_imagen  VARCHAR(500)    NOT NULL,
    principal   TINYINT(1)      NOT NULL DEFAULT 0,
    orden       INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_imagenes_producto (id_producto),
    CONSTRAINT fk_imagenes_producto FOREIGN KEY (id_producto) REFERENCES productos (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: direcciones
-- ============================================
CREATE TABLE direcciones (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario    BIGINT UNSIGNED NOT NULL,
    calle         VARCHAR(255)    NOT NULL,
    numero        VARCHAR(20)     NOT NULL,
    departamento  VARCHAR(50)     NULL,
    comuna        VARCHAR(100)    NOT NULL,
    ciudad        VARCHAR(100)    NOT NULL,
    region        VARCHAR(100)    NOT NULL,
    codigo_postal VARCHAR(20)     NULL,
    telefono      VARCHAR(20)     NULL,
    principal     TINYINT(1)      NOT NULL DEFAULT 0,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_direcciones_usuario (id_usuario),
    CONSTRAINT fk_direcciones_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: carritos
-- ============================================
CREATE TABLE carritos (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario  BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL para visitantes',
    session_id  VARCHAR(128)    NULL DEFAULT NULL COMMENT 'Session ID para visitantes',
    activo      TINYINT(1)      NOT NULL DEFAULT 1,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_carritos_usuario (id_usuario),
    KEY idx_carritos_session (session_id),
    CONSTRAINT fk_carritos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: items_carrito
-- ============================================
CREATE TABLE items_carrito (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_carrito      BIGINT UNSIGNED NOT NULL,
    id_producto     BIGINT UNSIGNED NOT NULL,
    cantidad        INT UNSIGNED    NOT NULL DEFAULT 1,
    precio_unitario INT UNSIGNED    NOT NULL COMMENT 'Precio al momento de agregar (centavos)',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_items_carrito (id_carrito, id_producto),
    KEY idx_items_carrito (id_carrito),
    CONSTRAINT fk_items_carrito FOREIGN KEY (id_carrito) REFERENCES carritos (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_items_producto FOREIGN KEY (id_producto) REFERENCES productos (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: pedidos
-- ============================================
CREATE TABLE pedidos (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario          BIGINT UNSIGNED NOT NULL,
    id_direccion        BIGINT UNSIGNED NULL,
    subtotal            INT UNSIGNED    NOT NULL COMMENT 'Subtotal en centavos',
    iva                 INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'IVA en centavos',
    costo_envio         INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Costo de envío en centavos',
    total               INT UNSIGNED    NOT NULL COMMENT 'Total en centavos',
    estado              ENUM('pendiente','pagado','en_preparacion','enviado','entregado','cancelado')
                        NOT NULL DEFAULT 'pendiente',
    direccion_envio     TEXT            NULL,
    telefono_contacto   VARCHAR(20)     NULL,
    notas               TEXT            NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pedidos_usuario (id_usuario),
    KEY idx_pedidos_estado (estado),
    KEY idx_pedidos_fecha (created_at),
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: detalle_pedido
-- ============================================
CREATE TABLE detalle_pedido (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_pedido       BIGINT UNSIGNED NOT NULL,
    id_producto     BIGINT UNSIGNED NOT NULL,
    nombre_producto VARCHAR(255)    NOT NULL COMMENT 'Snapshot del nombre al momento de compra',
    sku             VARCHAR(100)    NULL,
    cantidad        INT UNSIGNED    NOT NULL,
    precio_unitario INT UNSIGNED    NOT NULL COMMENT 'Precio en centavos al momento de compra',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_detalle_pedido (id_pedido),
    CONSTRAINT fk_detalle_pedido FOREIGN KEY (id_pedido) REFERENCES pedidos (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detalle_producto FOREIGN KEY (id_producto) REFERENCES productos (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: estados_pedido (historial de estados)
-- ============================================
CREATE TABLE estados_pedido (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_pedido           BIGINT UNSIGNED NOT NULL,
    estado              VARCHAR(30)     NOT NULL,
    fecha_cambio        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_responsable BIGINT UNSIGNED NULL,
    comentario          TEXT            NULL,
    PRIMARY KEY (id),
    KEY idx_estados_pedido (id_pedido),
    KEY idx_estados_fecha (fecha_cambio),
    CONSTRAINT fk_estados_pedido FOREIGN KEY (id_pedido) REFERENCES pedidos (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: pagos
-- ============================================
CREATE TABLE pagos (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_pedido           BIGINT UNSIGNED NOT NULL,
    metodo_pago         VARCHAR(50)     NOT NULL COMMENT 'webpay, tarjeta, transferencia',
    referencia_externa  VARCHAR(255)    NULL COMMENT 'ID de transacción externa',
    monto               INT UNSIGNED    NOT NULL COMMENT 'Monto en centavos',
    estado              ENUM('pendiente','aprobado','rechazado','reembolsado') NOT NULL DEFAULT 'pendiente',
    respuesta_pasarela  TEXT            NULL,
    fecha_pago          DATETIME        NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pagos_pedido (id_pedido),
    KEY idx_pagos_estado (estado),
    CONSTRAINT fk_pagos_pedido FOREIGN KEY (id_pedido) REFERENCES pedidos (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: movimientos_inventario
-- ============================================
CREATE TABLE movimientos_inventario (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_producto     BIGINT UNSIGNED NOT NULL,
    id_pedido       BIGINT UNSIGNED NULL,
    cantidad        INT             NOT NULL COMMENT 'Positivo=entrada, Negativo=salida',
    tipo            ENUM('entrada','egreso','ajuste','devolucion') NOT NULL,
    motivo          VARCHAR(255)    NULL,
    stock_anterior  INT UNSIGNED    NOT NULL,
    stock_nuevo     INT UNSIGNED    NOT NULL,
    id_usuario      BIGINT UNSIGNED NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_movimientos_producto (id_producto),
    KEY idx_movimientos_pedido (id_pedido),
    KEY idx_movimientos_fecha (created_at),
    CONSTRAINT fk_movimientos_producto FOREIGN KEY (id_producto) REFERENCES productos (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: cupones
-- ============================================
CREATE TABLE cupones (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo          VARCHAR(50)     NOT NULL,
    tipo_descuento  ENUM('porcentaje','monto_fijo') NOT NULL,
    valor           INT UNSIGNED    NOT NULL COMMENT 'Porcentaje o monto en centavos',
    monto_minimo    INT UNSIGNED    NULL COMMENT 'Monto mínimo de compra en centavos',
    usos_maximos    INT UNSIGNED    NULL,
    usos_actuales   INT UNSIGNED    NOT NULL DEFAULT 0,
    fecha_inicio    DATETIME        NOT NULL,
    fecha_fin       DATETIME        NOT NULL,
    activo          TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cupones_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: pedido_cupon (N:M)
-- ============================================
CREATE TABLE pedido_cupon (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_pedido   BIGINT UNSIGNED NOT NULL,
    id_cupon    BIGINT UNSIGNED NOT NULL,
    descuento   INT UNSIGNED    NOT NULL COMMENT 'Monto descontado en centavos',
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pedido_cupon (id_pedido, id_cupon),
    CONSTRAINT fk_pc_pedido FOREIGN KEY (id_pedido) REFERENCES pedidos (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pc_cupon FOREIGN KEY (id_cupon) REFERENCES cupones (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: envios
-- ============================================
CREATE TABLE envios (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_pedido           BIGINT UNSIGNED NOT NULL,
    metodo_envio        VARCHAR(100)    NOT NULL DEFAULT 'estandar',
    costo_envio         INT UNSIGNED    NOT NULL DEFAULT 0,
    codigo_seguimiento  VARCHAR(100)    NULL,
    empresa_transporte  VARCHAR(100)    NULL,
    estado              ENUM('pendiente','en_preparacion','en_camino','entregado','rechazado') NOT NULL DEFAULT 'pendiente',
    fecha_despacho      DATETIME        NULL,
    fecha_estimada      DATE            NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_envios_pedido (id_pedido),
    KEY idx_envios_estado (estado),
    CONSTRAINT fk_envios_pedido FOREIGN KEY (id_pedido) REFERENCES pedidos (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: reseñas
-- ============================================
CREATE TABLE resenas (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_producto     BIGINT UNSIGNED NOT NULL,
    id_usuario      BIGINT UNSIGNED NOT NULL,
    calificacion    TINYINT UNSIGNED NOT NULL COMMENT '1 a 5 estrellas',
    comentario      TEXT            NULL,
    aprobada        TINYINT(1)      NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_resena_usuario_producto (id_producto, id_usuario),
    KEY idx_resenas_producto (id_producto),
    CONSTRAINT fk_resenas_producto FOREIGN KEY (id_producto) REFERENCES productos (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_resenas_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: lista_deseos
-- ============================================
CREATE TABLE lista_deseos (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario  BIGINT UNSIGNED NOT NULL,
    id_producto BIGINT UNSIGNED NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_deseos (id_usuario, id_producto),
    CONSTRAINT fk_deseos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_deseos_producto FOREIGN KEY (id_producto) REFERENCES productos (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: auditoria_sistema
-- ============================================
CREATE TABLE auditoria_sistema (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entidad             VARCHAR(100)    NOT NULL,
    id_entidad          BIGINT UNSIGNED NULL,
    accion              VARCHAR(50)     NOT NULL COMMENT 'CREATE, UPDATE, DELETE, LOGIN, LOGOUT',
    id_usuario          BIGINT UNSIGNED NULL,
    detalle             TEXT            NULL,
    ip_origen           VARCHAR(45)     NULL,
    fecha_evento        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_auditoria_entidad (entidad, id_entidad),
    KEY idx_auditoria_usuario (id_usuario),
    KEY idx_auditoria_fecha (fecha_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: notificaciones (cola de emails)
-- ============================================
CREATE TABLE notificaciones (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    destinatario    VARCHAR(255)    NOT NULL,
    asunto          VARCHAR(255)    NOT NULL,
    cuerpo          TEXT            NOT NULL,
    tipo            VARCHAR(50)     NOT NULL DEFAULT 'email' COMMENT 'email, sms, sistema',
    estado          ENUM('pendiente','enviado','fallido') NOT NULL DEFAULT 'pendiente',
    reintentos      INT UNSIGNED    NOT NULL DEFAULT 0,
    error           TEXT            NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    enviado_en      DATETIME        NULL,
    PRIMARY KEY (id),
    KEY idx_notificaciones_estado (estado),
    KEY idx_notificaciones_destinatario (destinatario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: impuestos
-- ============================================
CREATE TABLE impuestos (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(100)    NOT NULL,
    porcentaje  DECIMAL(5,2)    NOT NULL COMMENT 'Ej: 19.00 para IVA 19%',
    activo      TINYINT(1)      NOT NULL DEFAULT 1,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insertar impuesto IVA por defecto
-- ============================================
INSERT INTO impuestos (nombre, porcentaje, activo) VALUES ('IVA', 19.00, 1);

-- ============================================
-- Datos Semilla - Plataforma Ecommerce UCT
-- ============================================



-- ============================================
-- Usuarios (passwords: Password123!)
-- hash generado con password_hash('Password123!', PASSWORD_BCRYPT, ['cost' => 12])
-- ============================================
INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, activo) VALUES
('Admin', 'Sistema', 'admin@uct.cl', '$2y$12$LJ3m4ys3Gql.ZhkBARVOge7JxSJgXPKCFs7r0dVIQGIgGndOMrHaa', 'admin', 1),
('Juan', 'Pérez', 'juan@email.com', '$2y$12$LJ3m4ys3Gql.ZhkBARVOge7JxSJgXPKCFs7r0dVIQGIgGndOMrHaa', 'cliente', 1),
('María', 'González', 'maria@email.com', '$2y$12$LJ3m4ys3Gql.ZhkBARVOge7JxSJgXPKCFs7r0dVIQGIgGndOMrHaa', 'cliente', 1),
('Pedro', 'Vendedor', 'pedro@uct.cl', '$2y$12$LJ3m4ys3Gql.ZhkBARVOge7JxSJgXPKCFs7r0dVIQGIgGndOMrHaa', 'vendedor', 1),
('Ana', 'Supervisor', 'ana@uct.cl', '$2y$12$LJ3m4ys3Gql.ZhkBARVOge7JxSJgXPKCFs7r0dVIQGIgGndOMrHaa', 'supervisor', 1),
('Admin Test', 'Readme', 'admin@test.com', '$2y$10$IWzDmgvQg9zQVMVPXY7ERuqvg0XdMLKYpgtzISJ8NcLE9xew140PC', 'admin', 1),
('Cliente Test', 'Readme', 'cliente@test.com', '$2y$10$H7dar56srklve7yg.hXdPep6jdR/ibaAKT2YsB2BtFoTy0OWloyaa', 'cliente', 1);

-- ============================================
-- Direcciones
-- ============================================
INSERT INTO direcciones (id_usuario, calle, numero, comuna, ciudad, region, codigo_postal, telefono, principal) VALUES
(2, 'Av. Siempreviva', '742', 'Springfield', 'Temuco', 'La Araucanía', '4780000', '+56912345678', 1),
(3, 'Calle Los Robles', '1234', 'Centro', 'Temuco', 'La Araucanía', '4780001', '+56987654321', 1);

-- ============================================
-- Categorías
-- ============================================
INSERT INTO categorias (nombre, slug, descripcion, id_padre, activo) VALUES
('Tecnología', 'tecnologia', 'Productos tecnológicos y gadgets', NULL, 1),
('Computadores', 'computadores', 'Notebooks, PC y accesorios de computación', 1, 1),
('Celulares', 'celulares', 'Smartphones y accesorios móviles', 1, 1),
('Audio', 'audio', 'Audífonos, parlantes y equipos de sonido', 1, 1),
('Ropa', 'ropa', 'Vestuario y accesorios de moda', NULL, 1),
('Hombre', 'hombre', 'Ropa masculina', 5, 1),
('Mujer', 'mujer', 'Ropa femenina', 5, 1),
('Deportes', 'deportes', 'Artículos deportivos y outdoor', NULL, 1),
('Hogar', 'hogar', 'Artículos para el hogar y decoración', NULL, 1),
('Libros', 'libros', 'Libros y material de lectura', NULL, 1);

-- ============================================
-- Productos (precios en centavos)
-- ============================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, imagen_url, activo, meta_descripcion) VALUES
-- Tecnología - Computadores
(2, 'Notebook HP ProBook 450 G9', 'notebook-hp-probook-450-g9', 'Notebook HP ProBook 450 G9, Intel Core i5-1235U, 8GB RAM, 256GB SSD, Pantalla 15.6 pulg FHD', 59999000, 25, 5, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400', 1, 'Notebook HP para productividad empresarial.'),
(2, 'MacBook Air M3', 'macbook-air-m3', 'MacBook Air con chip M3, 8GB RAM, 256GB SSD, Pantalla Liquid Retina 13.6 pulg', 99999000, 15, 3, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=400', 1, 'MacBook Air ultradelgado con chip M3.'),
(2, 'Monitor Dell 27 pulg 4K', 'monitor-dell-27-4k', 'Monitor Dell UltraSharp U2723QE, 27 pulgadas, resolución 4K UHD, USB-C Hub', 38999000, 10, 3, 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=400', 1, 'Monitor profesional Dell 4K.'),
(2, 'Teclado Mecánico Logitech MX', 'teclado-mecanico-logitech-mx', 'Teclado mecánico inalámbrico Logitech MX Mechanical, switches táctiles, retroiluminado', 8999000, 40, 10, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=400', 1, 'Teclado mecánico premium Logitech.'),
-- Celulares
(3, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'Apple iPhone 15 Pro Max, 256GB, Titanio Natural, Pantalla 6.7 pulg Super Retina XDR', 129999000, 8, 2, 'https://images.unsplash.com/photo-1592750475338-74b7b2108593?w=400', 1, 'El iPhone más avanzado de Apple.'),
(3, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Samsung Galaxy S24 Ultra, 512GB, Titanium Gray, S Pen incluido, Galaxy AI', 119999000, 12, 2, 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=400', 1, 'Samsung Galaxy S24 Ultra con Galaxy AI.'),
(3, 'Cargador Inalámbrico 15W', 'cargador-inalambrico-15w', 'Cargador inalámbrico rápido 15W compatible Qi, diseño delgado', 1999000, 100, 20, 'https://images.unsplash.com/photo-1621929747188-0b4dc1b27ee1?w=400', 1, 'Cargador inalámbrico rápido.'),
-- Audio
(4, 'Audífonos Sony WH-1000XM5', 'audifonos-sony-wh-1000xm5', 'Audífonos inalámbricos Sony con cancelación de ruido activa líder en la industria', 29999000, 18, 5, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400', 1, 'Audífonos premium con ANC Sony.'),
(4, 'Parlante JBL Flip 6', 'parlante-jbl-flip-6', 'Parlante Bluetooth portátil JBL Flip 6, resistente al agua IP67, 12h batería', 7999000, 35, 8, 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=400', 1, 'Parlante portátil JBL resistente al agua.'),
-- Ropa Hombre
(6, 'Polera Algodón Premium', 'polera-algodon-premium', 'Polera 100% algodón peinado, disponible en varios colores, corte regular', 1599000, 150, 30, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400', 1, 'Polera de algodón premium.'),
(6, 'Jeans Slim Fit', 'jeans-slim-fit', 'Jeans slim fit denim elástico, lavado oscuro, 5 bolsillos', 3599000, 80, 15, 'https://images.unsplash.com/photo-1542272454315-4c01d7abdf4a?w=400', 1, 'Jeans slim fit de denim premium.'),
-- Ropa Mujer
(7, 'Vestido Verano Floral', 'vestido-verano-floral', 'Vestido de verano estampado floral, tejido liviano, corte A', 2499000, 60, 12, 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=400', 1, 'Vestido veraniego estampado floral.'),
(7, 'Chaqueta Denim', 'chaqueta-denim', 'Chaqueta de denim oversize, lavado claro, botones metálicos', 4299000, 45, 10, 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=400', 1, 'Chaqueta de denim oversize.'),
-- Deportes
(8, 'Zapatillas Running Pro', 'zapatillas-running-pro', 'Zapatillas ideales para maratones con amortiguación premium, suela Vibram', 8999000, 20, 5, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400', 1, 'Zapatillas running con amortiguación premium.'),
(8, 'Colchoneta Yoga 6mm', 'colchoneta-yoga-6mm', 'Colchoneta de yoga 6mm, material TPE ecológico, antideslizante, incluye correa', 2499000, 70, 15, 'https://images.unsplash.com/photo-1601925260368-ae2f83cf8b7f?w=400', 1, 'Colchoneta yoga ecológica 6mm.'),
-- Hogar
(9, 'Lámpara LED Escritorio', 'lampara-led-escritorio', 'Lámpara LED con brazo ajustable, 3 modos de luz, puerto USB carga', 2999000, 55, 10, 'https://images.unsplash.com/photo-1507473885765-e6ed057ab6fe?w=400', 1, 'Lámpara LED escritorio ajustable.'),
(9, 'Set Toallas Algodón', 'set-toallas-algodon', 'Set de 6 toallas 100% algodón egipcio, 2 baño, 2 mano, 2 rostro', 3999000, 40, 10, 'https://images.unsplash.com/photo-1616627547584-bf28cee262db?w=400', 1, 'Set toallas algodón egipcio.'),
-- Libros
(10, 'Clean Code - Robert Martin', 'clean-code-robert-martin', 'Clean Code: A Handbook of Agile Software Craftsmanship - Edición tapa blanda', 3499000, 25, 5, 'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=400', 1, 'Libro Clean Code de Robert C. Martin.'),
(10, 'El Principito', 'el-principito', 'El Principito de Antoine de Saint-Exupéry - Edición ilustrada de colección', 1299000, 50, 10, 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400', 1, 'El Principito edición ilustrada.'),
-- Producto con stock bajo para probar alertas
(3, 'Cable USB-C Premium 2m', 'cable-usb-c-premium-2m', 'Cable USB-C a USB-C trenzado, carga rápida 100W, transferencia 10Gbps', 999000, 3, 5, 'https://images.unsplash.com/photo-1611472173362-3f53dbd65d80?w=400', 1, 'Cable USB-C premium de 2 metros.');

-- ============================================
-- Imágenes de producto (principal)
-- NOTA: el campo imagen_url en productos ya funciona como principal
-- ============================================

-- ============================================
-- Cupones
-- ============================================
INSERT INTO cupones (codigo, tipo_descuento, valor, monto_minimo, usos_maximos, usos_actuales, fecha_inicio, fecha_fin, activo) VALUES
('BIENVENIDO10', 'porcentaje', 10, 2000000, 500, 0, '2025-01-01 00:00:00', '2027-12-31 23:59:59', 1),
('DESCUENTO5000', 'monto_fijo', 500000, 3000000, 200, 0, '2025-01-01 00:00:00', '2027-12-31 23:59:59', 1),
('VERANO2026', 'porcentaje', 15, 4000000, 100, 0, '2026-01-01 00:00:00', '2026-03-31 23:59:59', 1);


SELECT '✅ Base de datos configurada correctamente' AS mensaje;
