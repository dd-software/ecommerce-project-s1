<?php
/**
 * Script de instalación — ejecutar UNA VEZ:
 *   php database/setup.php
 *
 * Crea la base de datos, las tablas y carga datos de prueba.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_db');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    // Conexión sin base de datos para crearla si no existe
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    echo "✓ Base de datos lista.\n";
} catch (PDOException $e) {
    exit("✗ Error de conexión: " . $e->getMessage() . "\n");
}

// ─── ESQUEMA ────────────────────────────────────────────────────────────────

$schema = "
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS historial_pedido;
DROP TABLE IF EXISTS detalles_pedido;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS items_carrito;
DROP TABLE IF EXISTS carritos;
DROP TABLE IF EXISTS inventario;
DROP TABLE IF EXISTS imagenes_producto;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS direcciones;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100)  NOT NULL,
  email         VARCHAR(150)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  rol           ENUM('cliente','administrador') NOT NULL DEFAULT 'cliente',
  habilitado    TINYINT(1)    NOT NULL DEFAULT 1,
  creado_en     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categorias (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(100) NOT NULL,
  descripcion TEXT,
  imagen_url  VARCHAR(500),
  activa      TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE productos (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT          NOT NULL,
  nombre       VARCHAR(200) NOT NULL,
  descripcion  TEXT,
  precio       DECIMAL(10,2) NOT NULL,
  activo       TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB;

CREATE TABLE imagenes_producto (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  producto_id  INT          NOT NULL,
  url          VARCHAR(500) NOT NULL,
  es_principal TINYINT(1)   NOT NULL DEFAULT 0,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE inventario (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT NOT NULL UNIQUE,
  stock       INT NOT NULL DEFAULT 0,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE carritos (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id   INT      NOT NULL UNIQUE,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE items_carrito (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  carrito_id      INT           NOT NULL,
  producto_id     INT           NOT NULL,
  cantidad        INT           NOT NULL DEFAULT 1,
  precio_unitario DECIMAL(10,2) NOT NULL,
  UNIQUE KEY uq_carrito_producto (carrito_id, producto_id),
  FOREIGN KEY (carrito_id)  REFERENCES carritos(id)  ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB;

CREATE TABLE pedidos (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT           NOT NULL,
  estado      ENUM('pendiente','pagado','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  subtotal    DECIMAL(10,2) NOT NULL,
  impuesto    DECIMAL(10,2) NOT NULL,
  total       DECIMAL(10,2) NOT NULL,
  creado_en   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE detalles_pedido (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id        INT           NOT NULL,
  producto_id      INT           NOT NULL,
  nombre_producto  VARCHAR(200)  NOT NULL,
  cantidad         INT           NOT NULL,
  precio_unitario  DECIMAL(10,2) NOT NULL,
  subtotal         DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE historial_pedido (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id       INT      NOT NULL,
  estado_anterior ENUM('pendiente','pagado','enviado','entregado','cancelado'),
  estado_nuevo    ENUM('pendiente','pagado','enviado','entregado','cancelado') NOT NULL,
  cambiado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  nota            TEXT,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE direcciones (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id     INT          NOT NULL,
  calle          VARCHAR(200) NOT NULL,
  ciudad         VARCHAR(100) NOT NULL,
  estado_dir     VARCHAR(100),
  codigo_postal  VARCHAR(20),
  pais           VARCHAR(100) NOT NULL DEFAULT 'México',
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;
";

foreach (array_filter(array_map('trim', explode(';', $schema))) as $sql) {
    if ($sql) $pdo->exec($sql);
}
echo "✓ Tablas creadas.\n";

// ─── SEED DATA ───────────────────────────────────────────────────────────────

// Usuarios
$users = [
    ['Admin Demo',   'admin@ecommerce.com',   'Admin123!',   'administrador'],
    ['Cliente Demo', 'cliente@ecommerce.com', 'Cliente123!', 'cliente'],
];
$stmtU = $pdo->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, ?)");
foreach ($users as [$nombre, $email, $pass, $rol]) {
    $stmtU->execute([$nombre, $email, password_hash($pass, PASSWORD_BCRYPT), $rol]);
}
echo "✓ Usuarios creados.\n";

// Categorías
$cats = [
    ['Electrónica',    'Dispositivos y accesorios electrónicos.',    'https://picsum.photos/seed/electronica/800/400'],
    ['Ropa y Moda',    'Ropa, calzado y accesorios de moda.',        'https://picsum.photos/seed/ropa/800/400'],
    ['Hogar y Cocina', 'Artículos para el hogar y la cocina.',       'https://picsum.photos/seed/hogar/800/400'],
    ['Deportes',       'Equipos y accesorios deportivos.',            'https://picsum.photos/seed/deportes/800/400'],
    ['Libros',         'Libros de todos los géneros.',               'https://picsum.photos/seed/libros/800/400'],
];
$stmtC = $pdo->prepare("INSERT INTO categorias (nombre, descripcion, imagen_url) VALUES (?, ?, ?)");
foreach ($cats as $cat) $stmtC->execute($cat);
echo "✓ Categorías creadas.\n";

// Productos
$products = [
    // [cat_id, nombre, descripcion, precio, imagen_seed, stock]
    [1, 'Smartphone Galaxy S24', 'Pantalla AMOLED 6.7", 256 GB, cámara 200 MP. El flagship de Samsung para 2024.', 899.99, 'galaxy', 20],
    [1, 'Laptop UltraBook Pro',  'Intel Core i7, 16 GB RAM, SSD 512 GB, 15.6". Ideal para trabajo y estudio.',   649.99, 'laptop', 15],
    [1, 'Audífonos Sony WH-1000XM5', 'Cancelación de ruido activa, 30 h de batería, audio Hi-Res.',              299.99, 'sony',   30],
    [1, 'Tablet iPad Air 11"',   'Chip M2, pantalla Liquid Retina 11", compatible con Apple Pencil.',            599.99, 'ipad',   12],

    [2, 'Camisa Oxford Premium', '100% algodón, corte slim, disponible en varios colores. Perfecta para la oficina.', 49.99, 'camisa', 50],
    [2, 'Vestido Floral Verano', 'Tela ligera de viscosa, diseño floral, ideal para días cálidos.',               39.99, 'vestido', 40],
    [2, 'Tenis Nike Air Max 270','Amortiguación Max Air en el talón, upper transpirable, suela de goma.',          119.99, 'nike',   25],

    [3, 'Sartén Antiadherente Granite', 'Recubrimiento de granito, mango ergonómico, apta para inducción.',      34.99, 'sartenl', 35],
    [3, 'Set Cuchillos Chef 6 pzs',     'Acero inoxidable alemán, mango ergonómico, incluye bloque de madera.',  69.99, 'cuchillos', 20],
    [3, 'Licuadora Oster 700W',         '10 velocidades, vaso de vidrio 1.5L, función pulse y smoothie.',        59.99, 'licuadora', 18],

    [4, 'Balón de Fútbol Adidas',       'Cuero sintético PU, talla 5, cosido a mano, apto para césped y cancha.', 29.99, 'balon',   45],
    [4, 'Bicicleta Montaña 29"',        '21 velocidades Shimano, frenos de disco, cuadro de aluminio 6061.',     349.99, 'bicicleta', 8],

    [5, 'El Señor de los Anillos',     'J.R.R. Tolkien. Edición de lujo con ilustraciones originales. Tapa dura.', 34.99, 'tolkien', 30],
    [5, 'Cien Años de Soledad',        'Gabriel García Márquez. Edición conmemorativa 50 años. Real Academia Española.', 19.99, 'gabo', 40],
    [5, 'Atomic Habits',               'James Clear. Guía práctica para construir buenos hábitos. Bestseller mundial.', 16.99, 'habits', 35],
];

$stmtP = $pdo->prepare("INSERT INTO productos (categoria_id, nombre, descripcion, precio) VALUES (?, ?, ?, ?)");
$stmtI = $pdo->prepare("INSERT INTO imagenes_producto (producto_id, url, es_principal) VALUES (?, ?, 1)");
$stmtInv = $pdo->prepare("INSERT INTO inventario (producto_id, stock) VALUES (?, ?)");

foreach ($products as [$catId, $nombre, $desc, $precio, $seed, $stock]) {
    $stmtP->execute([$catId, $nombre, $desc, $precio]);
    $pid = (int)$pdo->lastInsertId();
    $stmtI->execute([$pid, "https://picsum.photos/seed/{$seed}/600/400"]);
    // Segunda imagen
    $pdo->prepare("INSERT INTO imagenes_producto (producto_id, url, es_principal) VALUES (?, ?, 0)")
        ->execute([$pid, "https://picsum.photos/seed/{$seed}2/600/400"]);
    $stmtInv->execute([$pid, $stock]);
}
echo "✓ Productos e inventario creados.\n";

echo "\n=== Instalación completada ===\n";
echo "Credenciales de prueba:\n";
echo "  Admin : admin@ecommerce.com   / Admin123!\n";
echo "  Client: cliente@ecommerce.com / Cliente123!\n";
echo "\nInicia el servidor:\n";
echo "  php -S localhost:8000 router.php\n";
echo "  Abre: http://localhost:8000\n";
