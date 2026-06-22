-- ============================================
-- Schema: Plataforma Ecommerce UCT
-- Motor: MySQL 8.x / InnoDB
-- Charset: utf8mb4
-- ============================================

CREATE DATABASE IF NOT EXISTS uct_ecommerce
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE uct_ecommerce;

-- ============================================
-- Tabla: usuarios
-- ============================================
CREATE TABLE usuarios (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre        VARCHAR(100)     NOT NULL,
    apellido      VARCHAR(100)     NOT NULL,
    email         VARCHAR(255)     NOT NULL,
    password_hash VARCHAR(255)     NOT NULL,
    -- Datos opcionales para precargar el checkout (perfil)
    telefono      VARCHAR(30)      NULL,
    direccion     VARCHAR(255)     NULL,
    comuna        VARCHAR(100)     NULL,
    region        VARCHAR(100)     NULL,
    codigo_postal VARCHAR(20)      NULL,
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
    precio      INT UNSIGNED    NOT NULL COMMENT 'Precio en pesos (sin IVA)',
    precio_anterior INT UNSIGNED NULL COMMENT 'Precio antes de oferta; NULL si no está en oferta',
    stock       INT UNSIGNED    NOT NULL DEFAULT 0,
    stock_minimo INT UNSIGNED   NOT NULL DEFAULT 5,
    marca       VARCHAR(80)     NULL,
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
