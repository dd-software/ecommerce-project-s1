USE uct_ecommerce;

INSERT IGNORE INTO
    categories (name, slug, description)
VALUES (
        'Calzado',
        'calzado',
        'Zapatos, zapatillas y botas'
    ),
    (
        'Hogar y Cocina',
        'hogar-cocina',
        'Muebles, decoración y electrodomésticos'
    ),
    (
        'Deportes y Fitness',
        'deportes-fitness',
        'Equipamiento deportivo y fitness'
    ),
    (
        'Computación y Gaming',
        'computacion-gaming',
        'Hardware, periféricos y videojuegos'
    );

INSERT IGNORE INTO
    products (
        category_id,
        name,
        slug,
        description
    )
SELECT id, 'Laptop Pro 15"', 'laptop-pro-15', 'Laptop profesional con Intel Core i7 de 13ª gen, 512GB SSD y pantalla Full HD'
FROM categories
WHERE
    slug = 'electronica';

INSERT IGNORE INTO
    products (
        category_id,
        name,
        slug,
        description
    )
SELECT id, 'Auriculares Bluetooth Pro', 'auriculares-bluetooth-pro', 'Auriculares inalámbricos con cancelación de ruido activa y 30h de batería'
FROM categories
WHERE
    slug = 'electronica';

INSERT IGNORE INTO
    products (
        category_id,
        name,
        slug,
        description
    )
SELECT id, 'Smart TV 50" 4K', 'smart-tv-50-4k', 'Televisión 4K HDR con Android TV integrado y sonido Dolby Atmos'
FROM categories
WHERE
    slug = 'electronica';

INSERT IGNORE INTO
    products (
        category_id,
        name,
        slug,
        description
    )
SELECT id, 'Polera Deportiva', 'polera-deportiva', 'Polera transpirable para deporte con tejido DryFit que aleja la humedad'
FROM categories
WHERE
    slug = 'ropa';

INSERT IGNORE INTO
    products (
        category_id,
        name,
        slug,
        description
    )
SELECT id, 'Chaqueta Impermeable', 'chaqueta-impermeable', 'Chaqueta cortaviento impermeable, ideal para trekking y uso en ciudad'
FROM categories
WHERE
    slug = 'ropa';

INSERT IGNORE INTO
    products (
        category_id,
        name,
        slug,
        description
    )
SELECT id, 'Zapatillas Running Ultra', 'zapatillas-running-ultra', 'Zapatillas de running con amortiguación reactiva y suela antideslizante'
FROM categories
WHERE
    slug = 'calzado';

INSERT IGNORE INTO
    products (
        category_id,
        name,
        slug,
        description
    )
SELECT id, 'Botines de Cuero Clásico', 'botines-cuero-clasico', 'Botines de cuero genuino con suela de goma, elegantes y duraderos'
FROM categories
WHERE
    slug = 'calzado';

INSERT IGNORE INTO
    products (
        category_id,
        name,
        slug,
        description
    )
SELECT id, 'Cafetera Express', 'cafetera-express', 'Cafetera espresso con bomba de 15 bares y espumador de leche integrado'
FROM categories
WHERE
    slug = 'hogar-cocina';

INSERT IGNORE INTO
    products (
        category_id,
        name,
        slug,
        description
    )
SELECT id, 'Silla Ergonómica Oficina', 'silla-ergonomica-oficina', 'Silla de oficina con soporte lumbar ajustable, reposabrazos 4D y tapiz en malla'
FROM categories
WHERE
    slug = 'hogar-cocina';

INSERT IGNORE INTO
    products (
        category_id,
        name,
        slug,
        description
    )
SELECT id, 'Bicicleta MTB Rodado 29"', 'bicicleta-mtb-rodado-29', 'Bicicleta de montaña con cuadro de aluminio, suspensión delantera y 21 velocidades'
FROM categories
WHERE
    slug = 'deportes-fitness';

INSERT IGNORE INTO
    products (
        category_id,
        name,
        slug,
        description
    )
SELECT id, 'Guantes de Boxeo', 'guantes-de-boxeo', 'Guantes de cuero sintético con relleno de espuma de alta densidad'
FROM categories
WHERE
    slug = 'deportes-fitness';

INSERT IGNORE INTO
    products (
        category_id,
        name,
        slug,
        description
    )
SELECT id, 'Teclado Mecánico RGB', 'teclado-mecanico-rgb', 'Teclado mecánico con retroiluminación RGB por tecla y switches intercambiables'
FROM categories
WHERE
    slug = 'computacion-gaming';

INSERT IGNORE INTO
    products (
        category_id,
        name,
        slug,
        description
    )
SELECT id, 'Mouse Gamer Pro', 'mouse-gamer-pro', 'Mouse gaming con sensor óptico de 16000 DPI, 6 botones programables y RGB'
FROM categories
WHERE
    slug = 'computacion-gaming';

INSERT IGNORE INTO
    products (
        category_id,
        name,
        slug,
        description
    )
SELECT id, 'Monitor Curvo 27" QHD', 'monitor-curvo-27-qhd', 'Monitor gaming 27" curvo 1500R, resolución QHD 2560x1440, 165Hz y 1ms'
FROM categories
WHERE
    slug = 'computacion-gaming';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'LAP-PRO-8-256', '{"ram": "8GB", "almacenamiento": "256GB SSD"}', 899.99
FROM products
WHERE
    slug = 'laptop-pro-15';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'LAP-PRO-16-512', '{"ram": "16GB", "almacenamiento": "512GB SSD"}', 1199.99
FROM products
WHERE
    slug = 'laptop-pro-15';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'AUR-BLU-BK', '{"color": "Negro"}', 89.99
FROM products
WHERE
    slug = 'auriculares-bluetooth-pro';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'AUR-BLU-WH', '{"color": "Blanco"}', 89.99
FROM products
WHERE
    slug = 'auriculares-bluetooth-pro';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'TV-50-4K-BK', '{"color": "Negro", "pulgadas": "50"}', 649.99
FROM products
WHERE
    slug = 'smart-tv-50-4k';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'POL-DEP-S-RJ', '{"talla": "S", "color": "Rojo"}', 24.99
FROM products
WHERE
    slug = 'polera-deportiva';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'POL-DEP-M-RJ', '{"talla": "M", "color": "Rojo"}', 24.99
FROM products
WHERE
    slug = 'polera-deportiva';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'POL-DEP-L-NG', '{"talla": "L", "color": "Negro"}', 24.99
FROM products
WHERE
    slug = 'polera-deportiva';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'CHA-IMP-M-AZ', '{"talla": "M", "color": "Azul Marino"}', 79.99
FROM products
WHERE
    slug = 'chaqueta-impermeable';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'CHA-IMP-L-VD', '{"talla": "L", "color": "Verde Militar"}', 79.99
FROM products
WHERE
    slug = 'chaqueta-impermeable';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'ZAP-RUN-39', '{"talla": "39", "color": "Naranja/Negro"}', 119.99
FROM products
WHERE
    slug = 'zapatillas-running-ultra';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'ZAP-RUN-40', '{"talla": "40", "color": "Naranja/Negro"}', 119.99
FROM products
WHERE
    slug = 'zapatillas-running-ultra';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'ZAP-RUN-41', '{"talla": "41", "color": "Azul/Blanco"}', 119.99
FROM products
WHERE
    slug = 'zapatillas-running-ultra';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'ZAP-RUN-42', '{"talla": "42", "color": "Azul/Blanco"}', 119.99
FROM products
WHERE
    slug = 'zapatillas-running-ultra';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'ZAP-RUN-43', '{"talla": "43", "color": "Negro"}', 119.99
FROM products
WHERE
    slug = 'zapatillas-running-ultra';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'BOT-CU-40-NG', '{"talla": "40", "color": "Negro"}', 149.99
FROM products
WHERE
    slug = 'botines-cuero-clasico';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'BOT-CU-42-NG', '{"talla": "42", "color": "Negro"}', 149.99
FROM products
WHERE
    slug = 'botines-cuero-clasico';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'BOT-CU-44-CF', '{"talla": "44", "color": "Café"}', 149.99
FROM products
WHERE
    slug = 'botines-cuero-clasico';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'CAF-EXP-4T', '{"capacidad": "4 tazas"}', 129.99
FROM products
WHERE
    slug = 'cafetera-express';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'CAF-EXP-8T', '{"capacidad": "8 tazas"}', 199.99
FROM products
WHERE
    slug = 'cafetera-express';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'SIL-ERG-NG', '{"color": "Negro"}', 299.99
FROM products
WHERE
    slug = 'silla-ergonomica-oficina';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'SIL-ERG-GR', '{"color": "Gris"}', 299.99
FROM products
WHERE
    slug = 'silla-ergonomica-oficina';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'BIC-MTB-S-RJ', '{"talla": "S (1.55-1.70m)", "color": "Rojo"}', 599.99
FROM products
WHERE
    slug = 'bicicleta-mtb-rodado-29';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'BIC-MTB-M-AZ', '{"talla": "M (1.70-1.85m)", "color": "Azul"}', 649.99
FROM products
WHERE
    slug = 'bicicleta-mtb-rodado-29';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'GUA-BOX-10', '{"peso": "10 oz"}', 49.99
FROM products
WHERE
    slug = 'guantes-de-boxeo';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'GUA-BOX-12', '{"peso": "12 oz"}', 59.99
FROM products
WHERE
    slug = 'guantes-de-boxeo';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'TEC-MEC-AZ', '{"switches": "Azul (Táctil/Clicky)"}', 149.99
FROM products
WHERE
    slug = 'teclado-mecanico-rgb';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'TEC-MEC-RJ', '{"switches": "Rojo (Lineal/Silencioso)"}', 149.99
FROM products
WHERE
    slug = 'teclado-mecanico-rgb';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'MOU-GAM-W', '{"conectividad": "Inalámbrico", "color": "Negro"}', 89.99
FROM products
WHERE
    slug = 'mouse-gamer-pro';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'MOU-GAM-C', '{"conectividad": "Cable USB", "color": "Blanco"}', 79.99
FROM products
WHERE
    slug = 'mouse-gamer-pro';

INSERT IGNORE INTO
    product_variants (
        product_id,
        sku,
        attributes,
        price
    )
SELECT id, 'MON-CUR-27', '{"resolución": "QHD 2560x1440", "refresco": "165Hz"}', 449.99
FROM products
WHERE
    slug = 'monitor-curvo-27-qhd';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 15, 5
FROM product_variants
WHERE
    sku = 'LAP-PRO-8-256';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 8, 3
FROM product_variants
WHERE
    sku = 'LAP-PRO-16-512';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 60, 10
FROM product_variants
WHERE
    sku = 'AUR-BLU-BK';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 45, 10
FROM product_variants
WHERE
    sku = 'AUR-BLU-WH';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 25, 5
FROM product_variants
WHERE
    sku = 'TV-50-4K-BK';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 80, 20
FROM product_variants
WHERE
    sku = 'POL-DEP-S-RJ';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 120, 20
FROM product_variants
WHERE
    sku = 'POL-DEP-M-RJ';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 65, 15
FROM product_variants
WHERE
    sku = 'POL-DEP-L-NG';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 30, 10
FROM product_variants
WHERE
    sku = 'CHA-IMP-M-AZ';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 25, 10
FROM product_variants
WHERE
    sku = 'CHA-IMP-L-VD';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 40, 8
FROM product_variants
WHERE
    sku = 'ZAP-RUN-39';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 55, 8
FROM product_variants
WHERE
    sku = 'ZAP-RUN-40';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 70, 8
FROM product_variants
WHERE
    sku = 'ZAP-RUN-41';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 50, 8
FROM product_variants
WHERE
    sku = 'ZAP-RUN-42';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 35, 8
FROM product_variants
WHERE
    sku = 'ZAP-RUN-43';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 20, 5
FROM product_variants
WHERE
    sku = 'BOT-CU-40-NG';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 18, 5
FROM product_variants
WHERE
    sku = 'BOT-CU-42-NG';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 10, 5
FROM product_variants
WHERE
    sku = 'BOT-CU-44-CF';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 30, 8
FROM product_variants
WHERE
    sku = 'CAF-EXP-4T';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 15, 5
FROM product_variants
WHERE
    sku = 'CAF-EXP-8T';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 12, 4
FROM product_variants
WHERE
    sku = 'SIL-ERG-NG';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 8, 3
FROM product_variants
WHERE
    sku = 'SIL-ERG-GR';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 5, 2
FROM product_variants
WHERE
    sku = 'BIC-MTB-S-RJ';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 7, 2
FROM product_variants
WHERE
    sku = 'BIC-MTB-M-AZ';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 40, 10
FROM product_variants
WHERE
    sku = 'GUA-BOX-10';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 35, 10
FROM product_variants
WHERE
    sku = 'GUA-BOX-12';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 25, 6
FROM product_variants
WHERE
    sku = 'TEC-MEC-AZ';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 20, 6
FROM product_variants
WHERE
    sku = 'TEC-MEC-RJ';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 30, 8
FROM product_variants
WHERE
    sku = 'MOU-GAM-W';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 45, 8
FROM product_variants
WHERE
    sku = 'MOU-GAM-C';

INSERT IGNORE INTO
    inventory (
        variant_id,
        stock,
        min_stock_alert
    )
SELECT id, 10, 3
FROM product_variants
WHERE
    sku = 'MON-CUR-27';