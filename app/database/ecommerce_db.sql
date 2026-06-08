-- ============================================================
--  ecommerce_db.sql
--  Importar en phpMyAdmin:  Base de datos > Importar > este archivo
--  O en consola MySQL:      mysql -u root -p < ecommerce_db.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ecommerce_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ecommerce_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- ── Tablas ────────────────────────────────────────────────────

DROP TABLE IF EXISTS `historial_pedido`;
DROP TABLE IF EXISTS `detalles_pedido`;
DROP TABLE IF EXISTS `pedidos`;
DROP TABLE IF EXISTS `items_carrito`;
DROP TABLE IF EXISTS `carritos`;
DROP TABLE IF EXISTS `inventario`;
DROP TABLE IF EXISTS `imagenes_producto`;
DROP TABLE IF EXISTS `productos`;
DROP TABLE IF EXISTS `categorias`;
DROP TABLE IF EXISTS `direcciones`;
DROP TABLE IF EXISTS `usuarios`;

CREATE TABLE `usuarios` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `nombre`        VARCHAR(100)  NOT NULL,
  `email`         VARCHAR(150)  NOT NULL UNIQUE,
  `password_hash` VARCHAR(255)  NOT NULL,
  `rol`           ENUM('cliente','administrador') NOT NULL DEFAULT 'cliente',
  `habilitado`    TINYINT(1)    NOT NULL DEFAULT 1,
  `creado_en`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categorias` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `nombre`      VARCHAR(100) NOT NULL,
  `descripcion` TEXT,
  `imagen_url`  VARCHAR(500),
  `activa`      TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `productos` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `categoria_id` INT           NOT NULL,
  `nombre`       VARCHAR(200)  NOT NULL,
  `descripcion`  TEXT,
  `precio`       DECIMAL(10,2) NOT NULL,
  `activo`       TINYINT(1)    NOT NULL DEFAULT 1,
  `creado_en`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `imagenes_producto` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `producto_id`  INT          NOT NULL,
  `url`          VARCHAR(500) NOT NULL,
  `es_principal` TINYINT(1)   NOT NULL DEFAULT 0,
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventario` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `producto_id` INT NOT NULL UNIQUE,
  `stock`       INT NOT NULL DEFAULT 0,
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `carritos` (
  `id`             INT      AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`     INT      NOT NULL UNIQUE,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `items_carrito` (
  `id`              INT           AUTO_INCREMENT PRIMARY KEY,
  `carrito_id`      INT           NOT NULL,
  `producto_id`     INT           NOT NULL,
  `cantidad`        INT           NOT NULL DEFAULT 1,
  `precio_unitario` DECIMAL(10,2) NOT NULL,
  UNIQUE KEY `uq_carrito_producto` (`carrito_id`, `producto_id`),
  FOREIGN KEY (`carrito_id`)  REFERENCES `carritos`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pedidos` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT           NOT NULL,
  `estado`     ENUM('pendiente','pagado','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  `subtotal`   DECIMAL(10,2) NOT NULL,
  `impuesto`   DECIMAL(10,2) NOT NULL,
  `total`      DECIMAL(10,2) NOT NULL,
  `creado_en`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `detalles_pedido` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `pedido_id`       INT           NOT NULL,
  `producto_id`     INT           NOT NULL,
  `nombre_producto` VARCHAR(200)  NOT NULL,
  `cantidad`        INT           NOT NULL,
  `precio_unitario` DECIMAL(10,2) NOT NULL,
  `subtotal`        DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `historial_pedido` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `pedido_id`       INT      NOT NULL,
  `estado_anterior` ENUM('pendiente','pagado','enviado','entregado','cancelado'),
  `estado_nuevo`    ENUM('pendiente','pagado','enviado','entregado','cancelado') NOT NULL,
  `cambiado_en`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nota`            TEXT,
  FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `direcciones` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`    INT          NOT NULL,
  `calle`         VARCHAR(200) NOT NULL,
  `ciudad`        VARCHAR(100) NOT NULL,
  `estado_dir`    VARCHAR(100),
  `codigo_postal` VARCHAR(20),
  `pais`          VARCHAR(100) NOT NULL DEFAULT 'México',
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Datos de prueba ───────────────────────────────────────────

-- Usuarios  (Admin123! y Cliente123! con bcrypt)
INSERT INTO `usuarios` (`nombre`, `email`, `password_hash`, `rol`) VALUES
('Admin Demo',   'admin@ecommerce.com',   '$2y$10$AjDttiDtDL/1anQ6E7aC7.OwZI3mXkL9WVq4ipYRD3cK7zoZj845G', 'administrador'),
('Cliente Demo', 'cliente@ecommerce.com', '$2y$10$1CknKWmphdIyMTMDjoh.guWhnHQqpRtGpqXEOYScI2Kj4APm3u5Y2', 'cliente');

-- Categorías
INSERT INTO `categorias` (`nombre`, `descripcion`, `imagen_url`) VALUES
('Electrónica',    'Dispositivos y accesorios electrónicos.',    'https://picsum.photos/seed/electronica/800/400'),
('Ropa y Moda',    'Ropa, calzado y accesorios de moda.',        'https://picsum.photos/seed/ropa/800/400'),
('Hogar y Cocina', 'Artículos para el hogar y la cocina.',       'https://picsum.photos/seed/hogar/800/400'),
('Deportes',       'Equipos y accesorios deportivos.',            'https://picsum.photos/seed/deportes/800/400'),
('Libros',         'Libros de todos los géneros.',               'https://picsum.photos/seed/libros/800/400');

-- Productos
INSERT INTO `productos` (`categoria_id`, `nombre`, `descripcion`, `precio`) VALUES
(1, 'Smartphone Galaxy S24',       'Pantalla AMOLED 6.7", 256 GB, cámara 200 MP. El flagship de Samsung para 2024.',         899.99),
(1, 'Laptop UltraBook Pro',        'Intel Core i7, 16 GB RAM, SSD 512 GB, 15.6". Ideal para trabajo y estudio.',             649.99),
(1, 'Audífonos Sony WH-1000XM5',  'Cancelación de ruido activa, 30 h de batería, audio Hi-Res.',                             299.99),
(1, 'Tablet iPad Air 11"',         'Chip M2, pantalla Liquid Retina 11", compatible con Apple Pencil.',                       599.99),
(2, 'Camisa Oxford Premium',       '100% algodón, corte slim, disponible en varios colores. Perfecta para la oficina.',       49.99),
(2, 'Vestido Floral Verano',       'Tela ligera de viscosa, diseño floral, ideal para días cálidos.',                         39.99),
(2, 'Tenis Nike Air Max 270',      'Amortiguación Max Air en el talón, upper transpirable, suela de goma.',                   119.99),
(3, 'Sartén Antiadherente Granite','Recubrimiento de granito, mango ergonómico, apta para inducción.',                        34.99),
(3, 'Set Cuchillos Chef 6 pzs',    'Acero inoxidable alemán, mango ergonómico, incluye bloque de madera.',                    69.99),
(3, 'Licuadora Oster 700W',        '10 velocidades, vaso de vidrio 1.5L, función pulse y smoothie.',                          59.99),
(4, 'Balón de Fútbol Adidas',      'Cuero sintético PU, talla 5, cosido a mano, apto para césped y cancha.',                  29.99),
(4, 'Bicicleta Montaña 29"',       '21 velocidades Shimano, frenos de disco, cuadro de aluminio 6061.',                      349.99),
(5, 'El Señor de los Anillos',     'J.R.R. Tolkien. Edición de lujo con ilustraciones originales. Tapa dura.',               34.99),
(5, 'Cien Años de Soledad',        'Gabriel García Márquez. Edición conmemorativa 50 años. Real Academia Española.',          19.99),
(5, 'Atomic Habits',               'James Clear. Guía práctica para construir buenos hábitos. Bestseller mundial.',           16.99);

-- Imágenes principales (una por producto, id 1–15)
INSERT INTO `imagenes_producto` (`producto_id`, `url`, `es_principal`) VALUES
(1,  'https://picsum.photos/seed/galaxy/600/400',    1),
(1,  'https://picsum.photos/seed/galaxy2/600/400',   0),
(2,  'https://picsum.photos/seed/laptop/600/400',    1),
(2,  'https://picsum.photos/seed/laptop2/600/400',   0),
(3,  'https://picsum.photos/seed/sony/600/400',      1),
(3,  'https://picsum.photos/seed/sony2/600/400',     0),
(4,  'https://picsum.photos/seed/ipad/600/400',      1),
(4,  'https://picsum.photos/seed/ipad2/600/400',     0),
(5,  'https://picsum.photos/seed/camisa/600/400',    1),
(5,  'https://picsum.photos/seed/camisa2/600/400',   0),
(6,  'https://picsum.photos/seed/vestido/600/400',   1),
(6,  'https://picsum.photos/seed/vestido2/600/400',  0),
(7,  'https://picsum.photos/seed/nike/600/400',      1),
(7,  'https://picsum.photos/seed/nike2/600/400',     0),
(8,  'https://picsum.photos/seed/sartenl/600/400',   1),
(8,  'https://picsum.photos/seed/sartenl2/600/400',  0),
(9,  'https://picsum.photos/seed/cuchillos/600/400', 1),
(9,  'https://picsum.photos/seed/cuchillos2/600/400',0),
(10, 'https://picsum.photos/seed/licuadora/600/400', 1),
(10, 'https://picsum.photos/seed/licuadora2/600/400',0),
(11, 'https://picsum.photos/seed/balon/600/400',     1),
(11, 'https://picsum.photos/seed/balon2/600/400',    0),
(12, 'https://picsum.photos/seed/bicicleta/600/400', 1),
(12, 'https://picsum.photos/seed/bicicleta2/600/400',0),
(13, 'https://picsum.photos/seed/tolkien/600/400',   1),
(13, 'https://picsum.photos/seed/tolkien2/600/400',  0),
(14, 'https://picsum.photos/seed/gabo/600/400',      1),
(14, 'https://picsum.photos/seed/gabo2/600/400',     0),
(15, 'https://picsum.photos/seed/habits/600/400',    1),
(15, 'https://picsum.photos/seed/habits2/600/400',   0);

-- Inventario
INSERT INTO `inventario` (`producto_id`, `stock`) VALUES
(1, 20), (2, 15), (3, 30), (4, 12),
(5, 50), (6, 40), (7, 25),
(8, 35), (9, 20), (10, 18),
(11, 45), (12, 8),
(13, 30), (14, 40), (15, 35);

-- ============================================================
--  Credenciales de prueba:
--    Admin   : admin@ecommerce.com   / Admin123!
--    Cliente : cliente@ecommerce.com / Cliente123!
-- ============================================================
