USE uct_ecommerce;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE inventory;
TRUNCATE TABLE cart_items;
TRUNCATE TABLE order_items;
TRUNCATE TABLE orders;
TRUNCATE TABLE product_variants;
TRUNCATE TABLE products;
TRUNCATE TABLE categories;
TRUNCATE TABLE users;
TRUNCATE TABLE roles;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO roles (name, description) VALUES
('admin',    'Administrador del sistema'),
('customer', 'Cliente estándar');

INSERT INTO users (role_id, first_name, last_name, email, password_hash, is_active) VALUES
(1, 'Admin', 'Sistema', 'admin@uct.cl',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
(2, 'Juan',  'Perez',   'cliente@uct.cl',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE);

INSERT INTO categories (name, slug, description) VALUES
('Electrónica',          'electronica',        'Smartphones, televisores y gadgets'),
('Ropa',                 'ropa',               'Indumentaria y moda'),
('Calzado',              'calzado',            'Zapatos, zapatillas y botas'),
('Hogar y Cocina',       'hogar-cocina',       'Muebles, decoración y electrodomésticos'),
('Deportes y Fitness',   'deportes-fitness',   'Equipamiento deportivo y fitness'),
('Computación y Gaming', 'computacion-gaming', 'Hardware, periféricos y videojuegos');

INSERT INTO products (category_id, name, slug, description) VALUES
(1, 'Smartphone X',             'smartphone-x',             'Último smartphone con pantalla AMOLED 6.7" y triple cámara 108MP'),
(2, 'Camiseta Básica',          'camiseta-basica',          'Camiseta de algodón 100% Pima, suave y duradera'),
(1, 'Laptop Pro 15"',           'laptop-pro-15',            'Laptop profesional con Intel Core i7 de 13ª gen, 512GB SSD y pantalla Full HD'),
(1, 'Auriculares Bluetooth Pro','auriculares-bluetooth-pro','Auriculares inalámbricos con cancelación de ruido activa y 30h de batería'),
(1, 'Smart TV 50" 4K',          'smart-tv-50-4k',           'Televisión 4K HDR con Android TV integrado y sonido Dolby Atmos'),
(2, 'Polera Deportiva',         'polera-deportiva',         'Polera transpirable para deporte con tejido DryFit que aleja la humedad'),
(2, 'Chaqueta Impermeable',     'chaqueta-impermeable',     'Chaqueta cortaviento impermeable, ideal para trekking y uso en ciudad'),
(3, 'Zapatillas Running Ultra', 'zapatillas-running-ultra', 'Zapatillas de running con amortiguación reactiva y suela antideslizante'),
(3, 'Botines de Cuero Clásico', 'botines-cuero-clasico',    'Botines de cuero genuino con suela de goma, elegantes y duraderos'),
(4, 'Cafetera Express',         'cafetera-express',         'Cafetera espresso con bomba de 15 bares y espumador de leche integrado'),
(4, 'Silla Ergonómica Oficina', 'silla-ergonomica-oficina', 'Silla de oficina con soporte lumbar ajustable, reposabrazos 4D y tapiz en malla'),
(5, 'Bicicleta MTB Rodado 29"', 'bicicleta-mtb-rodado-29', 'Bicicleta de montaña con cuadro de aluminio, suspensión delantera y 21 velocidades'),
(5, 'Guantes de Boxeo',         'guantes-de-boxeo',         'Guantes de cuero sintético con relleno de espuma de alta densidad'),
(6, 'Teclado Mecánico RGB',     'teclado-mecanico-rgb',     'Teclado mecánico con retroiluminación RGB por tecla y switches intercambiables'),
(6, 'Mouse Gamer Pro',          'mouse-gamer-pro',          'Mouse gaming con sensor óptico de 16000 DPI, 6 botones programables y RGB'),
(6, 'Monitor Curvo 27" QHD',    'monitor-curvo-27-qhd',     'Monitor gaming 27" curvo 1500R, resolución QHD 2560×1440, 165Hz y 1ms');

INSERT INTO product_variants (product_id, sku, attributes, price) VALUES
(1,  'SMART-X-128',     '{"almacenamiento": "128GB", "color": "Negro"}',                 599.99),
(1,  'SMART-X-256',     '{"almacenamiento": "256GB", "color": "Blanco"}',                699.99),

(2,  'CAM-BAS-M-BL',    '{"talla": "M", "color": "Azul"}',                               19.99),
(2,  'CAM-BAS-L-BL',    '{"talla": "L", "color": "Azul"}',                               19.99),
(3,  'LAP-PRO-8-256',   '{"ram": "8GB", "almacenamiento": "256GB SSD"}',                899.99),
(3,  'LAP-PRO-16-512',  '{"ram": "16GB", "almacenamiento": "512GB SSD"}',              1199.99),
(4,  'AUR-BLU-BK',      '{"color": "Negro"}',                                             89.99),
(4,  'AUR-BLU-WH',      '{"color": "Blanco"}',                                            89.99),
(5,  'TV-50-4K-BK',     '{"color": "Negro", "pulgadas": "50"}',                          649.99),
(6,  'POL-DEP-S-RJ',    '{"talla": "S", "color": "Rojo"}',                               24.99),
(6,  'POL-DEP-M-RJ',    '{"talla": "M", "color": "Rojo"}',                               24.99),
(6,  'POL-DEP-L-NG',    '{"talla": "L", "color": "Negro"}',                              24.99),
(7,  'CHA-IMP-M-AZ',    '{"talla": "M", "color": "Azul Marino"}',                        79.99),
(7,  'CHA-IMP-L-VD',    '{"talla": "L", "color": "Verde Militar"}',                      79.99),
(8,  'ZAP-RUN-39',      '{"talla": "39", "color": "Naranja/Negro"}',                    119.99),
(8,  'ZAP-RUN-40',      '{"talla": "40", "color": "Naranja/Negro"}',                    119.99),
(8,  'ZAP-RUN-41',      '{"talla": "41", "color": "Azul/Blanco"}',                      119.99),
(8,  'ZAP-RUN-42',      '{"talla": "42", "color": "Azul/Blanco"}',                      119.99),
(8,  'ZAP-RUN-43',      '{"talla": "43", "color": "Negro"}',                            119.99),
(9,  'BOT-CU-40-NG',    '{"talla": "40", "color": "Negro"}',                            149.99),
(9,  'BOT-CU-42-NG',    '{"talla": "42", "color": "Negro"}',                            149.99),
(9,  'BOT-CU-44-CF',    '{"talla": "44", "color": "Café"}',                             149.99),
(10, 'CAF-EXP-4T',      '{"capacidad": "4 tazas"}',                                     129.99),
(10, 'CAF-EXP-8T',      '{"capacidad": "8 tazas"}',                                     199.99),
(11, 'SIL-ERG-NG',      '{"color": "Negro"}',                                            299.99),
(11, 'SIL-ERG-GR',      '{"color": "Gris"}',                                             299.99),
(12, 'BIC-MTB-S-RJ',    '{"talla": "S (1.55-1.70m)", "color": "Rojo"}',                599.99),
(12, 'BIC-MTB-M-AZ',    '{"talla": "M (1.70-1.85m)", "color": "Azul"}',                649.99),
(13, 'GUA-BOX-10',      '{"peso": "10 oz"}',                                              49.99),
(13, 'GUA-BOX-12',      '{"peso": "12 oz"}',                                              59.99),
(14, 'TEC-MEC-AZ',      '{"switches": "Azul (Táctil/Clicky)"}',                         149.99),
(14, 'TEC-MEC-RJ',      '{"switches": "Rojo (Lineal/Silencioso)"}',                     149.99),
(15, 'MOU-GAM-W',       '{"conectividad": "Inalámbrico", "color": "Negro"}',              89.99),
(15, 'MOU-GAM-C',       '{"conectividad": "Cable USB", "color": "Blanco"}',               79.99),
(16, 'MON-CUR-27',      '{"resolución": "QHD 2560x1440", "refresco": "165Hz"}',         449.99);

INSERT INTO inventory (variant_id, stock, min_stock_alert) VALUES
(1,  50,  10),
(2,  20,   5),   
(3,  100, 15),   
(4,    2,  5),   
(5,  15,   5),   
(6,   8,   3),   
(7,  60,  10), 
(8,  45,  10),  
(9,  25,   5),  
(10, 80,  20),   
(11, 120, 20),  
(12, 65,  15),   
(13, 30,  10),   
(14, 25,  10),   
(15, 40,   8),  
(16, 55,   8),  
(17, 70,   8),  
(18, 50,   8),  
(19, 35,   8),  
(20, 20,   5),  
(21, 18,   5),   
(22, 10,   5),   
(23, 30,   8),   
(24, 15,   5),   
(25, 12,   4),   
(26,  8,   3),   
(27,  5,   2),   
(28,  7,   2),   
(29, 40,  10),  
(30, 35,  10),   
(31, 25,   6),   
(32, 20,   6),   
(33, 30,   8),  
(34, 45,   8),   
(35, 10,   3);
