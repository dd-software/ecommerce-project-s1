-- ============================================
-- Datos Semilla - Plataforma Ecommerce UCT
-- ============================================

USE uct_ecommerce;

-- ============================================
-- Usuarios (passwords: "Password123!")
-- hash generado con password_hash('Password123!', PASSWORD_BCRYPT, ['cost' => 12])
-- ============================================
INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, activo) VALUES
('Admin', 'Sistema', 'admin@uct.cl', '$2y$12$LJ3m4ys3Gql.ZhkBARVOge7JxSJgXPKCFs7r0dVIQGIgGndOMrHaa', 'admin', 1),
('Juan', 'Pérez', 'juan@email.com', '$2y$12$LJ3m4ys3Gql.ZhkBARVOge7JxSJgXPKCFs7r0dVIQGIgGndOMrHaa', 'cliente', 1),
('María', 'González', 'maria@email.com', '$2y$12$LJ3m4ys3Gql.ZhkBARVOge7JxSJgXPKCFs7r0dVIQGIgGndOMrHaa', 'cliente', 1),
('Pedro', 'Vendedor', 'pedro@uct.cl', '$2y$12$LJ3m4ys3Gql.ZhkBARVOge7JxSJgXPKCFs7r0dVIQGIgGndOMrHaa', 'vendedor', 1),
('Ana', 'Supervisor', 'ana@uct.cl', '$2y$12$LJ3m4ys3Gql.ZhkBARVOge7JxSJgXPKCFs7r0dVIQGIgGndOMrHaa', 'supervisor', 1);

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
(2, 'Notebook HP ProBook 450 G9', 'notebook-hp-probook-450-g9', 'Notebook HP ProBook 450 G9, Intel Core i5-1235U, 8GB RAM, 256GB SSD, Pantalla 15.6" FHD', 599990, 25, 5, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400', 1, 'Notebook HP para productividad empresarial.'),
(2, 'MacBook Air M3', 'macbook-air-m3', 'MacBook Air con chip M3, 8GB RAM, 256GB SSD, Pantalla Liquid Retina 13.6"', 999990, 15, 3, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=400', 1, 'MacBook Air ultradelgado con chip M3.'),
(2, 'Monitor Dell 27" 4K', 'monitor-dell-27-4k', 'Monitor Dell UltraSharp U2723QE, 27 pulgadas, resolución 4K UHD, USB-C Hub', 389990, 10, 3, 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=400', 1, 'Monitor profesional Dell 4K.'),
(2, 'Teclado Mecánico Logitech MX', 'teclado-mecanico-logitech-mx', 'Teclado mecánico inalámbrico Logitech MX Mechanical, switches táctiles, retroiluminado', 89990, 40, 10, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=400', 1, 'Teclado mecánico premium Logitech.'),

-- Celulares
(3, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'Apple iPhone 15 Pro Max, 256GB, Titanio Natural, Pantalla 6.7" Super Retina XDR', 1299990, 8, 2, 'https://images.unsplash.com/photo-1592750475338-74b7b2108593?w=400', 1, 'El iPhone más avanzado de Apple.'),
(3, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Samsung Galaxy S24 Ultra, 512GB, Titanium Gray, S Pen incluido, Galaxy AI', 1199990, 12, 2, 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=400', 1, 'Samsung Galaxy S24 Ultra con Galaxy AI.'),
(3, 'Cargador Inalámbrico 15W', 'cargador-inalambrico-15w', 'Cargador inalámbrico rápido 15W compatible Qi, diseño delgado', 19990, 100, 20, 'https://images.unsplash.com/photo-1621929747188-0b4dc1b27ee1?w=400', 1, 'Cargador inalámbrico rápido.'),

-- Audio
(4, 'Audífonos Sony WH-1000XM5', 'audifonos-sony-wh-1000xm5', 'Audífonos inalámbricos Sony con cancelación de ruido activa líder en la industria', 299990, 18, 5, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400', 1, 'Audífonos premium con ANC Sony.'),
(4, 'Parlante JBL Flip 6', 'parlante-jbl-flip-6', 'Parlante Bluetooth portátil JBL Flip 6, resistente al agua IP67, 12h batería', 79990, 35, 8, 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=400', 1, 'Parlante portátil JBL resistente al agua.'),

-- Ropa Hombre
(6, 'Polera Algodón Premium', 'polera-algodon-premium', 'Polera 100% algodón peinado, disponible en varios colores, corte regular', 15990, 150, 30, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400', 1, 'Polera de algodón premium.'),
(6, 'Jeans Slim Fit', 'jeans-slim-fit', 'Jeans slim fit denim elástico, lavado oscuro, 5 bolsillos', 35990, 80, 15, 'https://images.unsplash.com/photo-1542272454315-4c01d7abdf4a?w=400', 1, 'Jeans slim fit de denim premium.'),

-- Ropa Mujer
(7, 'Vestido Verano Floral', 'vestido-verano-floral', 'Vestido de verano estampado floral, tejido liviano, corte A', 24990, 60, 12, 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=400', 1, 'Vestido veraniego estampado floral.'),
(7, 'Chaqueta Denim', 'chaqueta-denim', 'Chaqueta de denim oversize, lavado claro, botones metálicos', 42990, 45, 10, 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=400', 1, 'Chaqueta de denim oversize.'),

-- Deportes
(8, 'Zapatillas Running Pro', 'zapatillas-running-pro', 'Zapatillas ideales para maratones con amortiguación premium, suela Vibram', 89990, 20, 5, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400', 1, 'Zapatillas running con amortiguación premium.'),
(8, 'Colchoneta Yoga 6mm', 'colchoneta-yoga-6mm', 'Colchoneta de yoga 6mm, material TPE ecológico, antideslizante, incluye correa', 24990, 70, 15, 'https://images.unsplash.com/photo-1601925260368-ae2f83cf8b7f?w=400', 1, 'Colchoneta yoga ecológica 6mm.'),

-- Hogar
(9, 'Lámpara LED Escritorio', 'lampara-led-escritorio', 'Lámpara LED con brazo ajustable, 3 modos de luz, puerto USB carga', 29990, 55, 10, 'https://images.unsplash.com/photo-1507473885765-e6ed057ab6fe?w=400', 1, 'Lámpara LED escritorio ajustable.'),
(9, 'Set Toallas Algodón', 'set-toallas-algodon', 'Set de 6 toallas 100% algodón egipcio, 2 baño, 2 mano, 2 rostro', 39990, 40, 10, 'https://images.unsplash.com/photo-1616627547584-bf28cee262db?w=400', 1, 'Set toallas algodón egipcio.'),

-- Libros
(10, 'Clean Code - Robert Martin', 'clean-code-robert-martin', 'Clean Code: A Handbook of Agile Software Craftsmanship - Edición tapa blanda', 34990, 25, 5, 'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=400', 1, 'Libro Clean Code de Robert C. Martin.'),
(10, 'El Principito', 'el-principito', 'El Principito de Antoine de Saint-Exupéry - Edición ilustrada de colección', 12990, 50, 10, 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400', 1, 'El Principito edición ilustrada.'),
-- Producto con stock bajo para probar alertas
(3, 'Cable USB-C Premium 2m', 'cable-usb-c-premium-2m', 'Cable USB-C a USB-C trenzado, carga rápida 100W, transferencia 10Gbps', 9990, 3, 5, 'https://images.unsplash.com/photo-1611472173362-3f53dbd65d80?w=400', 1, 'Cable USB-C premium de 2 metros.');

-- ============================================
-- Imágenes de producto (principal)
-- NOTA: el campo imagen_url en productos ya funciona como principal
-- ============================================

-- ============================================
-- Cupones
-- ============================================
INSERT INTO cupones (codigo, tipo_descuento, valor, monto_minimo, usos_maximos, usos_actuales, fecha_inicio, fecha_fin, activo) VALUES
('BIENVENIDO10', 'porcentaje', 10, 20000, 500, 0, '2025-01-01 00:00:00', '2027-12-31 23:59:59', 1),
('DESCUENTO5000', 'monto_fijo', 5000, 30000, 200, 0, '2025-01-01 00:00:00', '2027-12-31 23:59:59', 1),
('VERANO2026', 'porcentaje', 15, 40000, 100, 0, '2026-01-01 00:00:00', '2026-03-31 23:59:59', 1);
