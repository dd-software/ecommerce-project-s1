-- ============================================================
-- Seed QuadCore — catálogo real de electrónica (reemplaza el demo genérico)
-- Precios en PESOS chilenos (convención del proyecto, sin /100).
-- 20 productos · 6 categorías · marcas reales (matchea los mockups).
-- Re-ejecutable: limpia productos/categorías y dependencias.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Limpiar transaccional + catálogo (conserva usuarios, direcciones, cupones)
DELETE FROM items_carrito;
DELETE FROM carritos;
DELETE FROM detalle_pedido;
DELETE FROM pedido_cupon;
DELETE FROM pagos;
DELETE FROM envios;
DELETE FROM pedidos;
DELETE FROM resenas;
DELETE FROM lista_deseos;
DELETE FROM movimientos_inventario;
DELETE FROM imagenes_producto;
DELETE FROM productos;
DELETE FROM categorias;

ALTER TABLE categorias AUTO_INCREMENT = 1;
ALTER TABLE productos  AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- Categorías (ids explícitos 1..6 para referenciarlas directo)
INSERT INTO categorias (id, nombre, slug, descripcion, id_padre, activo) VALUES
(1, 'Componentes PC',     'componentes-pc',     'Procesadores, RAM, SSD, fuentes y refrigeración', NULL, 1),
(2, 'Accesorios',         'accesorios',         'Mouse, teclados y periféricos',                   NULL, 1),
(3, 'Cables',             'cables',             'Cables y conectores',                             NULL, 1),
(4, 'Herramientas',       'herramientas',       'Precisión, soldadura y limpieza',                 NULL, 1),
(5, 'Repuestos',          'repuestos',          'Pastas térmicas, botones, bisagras',              NULL, 1),
(6, 'Servicios Técnicos', 'servicios-tecnicos', 'Diagnóstico y reparación',                        NULL, 1);

-- Productos (precio en pesos; stock; marca)
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
-- Componentes PC (9)
(1, 'Procesador Intel Core i5-13400F', 'procesador-intel-core-i5-13400f', 'Procesador de 13ª generación, 6 núcleos / 12 hilos, socket LGA1700. Ideal para gaming y productividad.', 229990, 12, 5, 'Intel', NULL, 1, 'Procesador Intel i5-13400F.'),
(1, 'Procesador AMD Ryzen 5 7600X', 'procesador-amd-ryzen-5-7600x', 'Procesador AMD Ryzen 5 7600X, 6 núcleos / 12 hilos, socket AM5, hasta 5.3 GHz.', 229990, 8, 5, 'AMD', NULL, 1, 'Procesador AMD Ryzen 5 7600X.'),
(1, 'Memoria Kingston Fury Beast 16GB DDR5', 'memoria-kingston-fury-beast-16gb-ddr5', 'Memoria RAM Kingston Fury Beast 16GB DDR5 5200MHz, disipador de bajo perfil.', 64990, 20, 8, 'Kingston', NULL, 1, 'RAM Kingston Fury Beast 16GB DDR5.'),
(1, 'Memoria Corsair Vengeance 32GB DDR5', 'memoria-corsair-vengeance-32gb-ddr5', 'Kit Corsair Vengeance 32GB (2x16GB) DDR5 6000MHz, optimizada para AMD EXPO e Intel XMP.', 129990, 15, 6, 'Corsair', NULL, 1, 'RAM Corsair Vengeance 32GB DDR5.'),
(1, 'Fuente EVGA SuperNOVA 750W 80+ Gold', 'fuente-evga-supernova-750w-gold', 'Fuente de poder EVGA SuperNOVA 750W, certificación 80 Plus Gold, modular.', 89990, 10, 4, 'EVGA', NULL, 1, 'Fuente EVGA SuperNOVA 750W Gold.'),
(1, 'Placa Madre ASUS TUF Gaming B650-PLUS', 'placa-madre-asus-tuf-b650-plus', 'Placa madre ASUS TUF Gaming B650-PLUS, socket AM5, PCIe 5.0, DDR5.', 159990, 0, 4, 'ASUS', NULL, 1, 'Placa madre ASUS TUF B650-PLUS.'),
(1, 'SSD Kingston NV2 1TB NVMe M.2', 'ssd-kingston-nv2-1tb-nvme', 'Unidad SSD Kingston NV2 1TB NVMe PCIe 4.0 M.2, lecturas hasta 3500 MB/s.', 49990, 30, 10, 'Kingston', NULL, 1, 'SSD Kingston NV2 1TB NVMe.'),
(1, 'Refrigeración Líquida Corsair iCUE H100i', 'refrigeracion-corsair-icue-h100i', 'Refrigeración líquida AIO Corsair iCUE H100i, radiador 240mm, RGB.', 119990, 7, 3, 'Corsair', NULL, 1, 'Líquida Corsair iCUE H100i 240mm.'),
(1, 'Ventilador 120mm ARGB', 'ventilador-120mm-argb', 'Ventilador de gabinete 120mm ARGB, rodamiento hidráulico, bajo nivel de ruido.', 14990, 25, 10, 'Corsair', NULL, 1, 'Ventilador 120mm ARGB.'),
-- Accesorios (2)
(2, 'Mouse Logitech G502 HERO', 'mouse-logitech-g502-hero', 'Mouse gamer Logitech G502 HERO, sensor 25K DPI, 11 botones programables.', 34990, 18, 6, 'Logitech', NULL, 1, 'Mouse Logitech G502 HERO.'),
(2, 'Teclado Mecánico Redragon Kumara', 'teclado-redragon-kumara', 'Teclado mecánico Redragon Kumara K552, switches red, retroiluminación RGB, formato TKL.', 39990, 14, 5, 'Redragon', NULL, 1, 'Teclado Redragon Kumara RGB.'),
-- Cables (2)
(3, 'Cable HDMI 2.1 Belkin 8K 2m', 'cable-hdmi-2-1-belkin-8k-2m', 'Cable HDMI 2.1 Belkin, soporte 8K@60Hz / 4K@120Hz, 2 metros, blindado.', 8990, 50, 15, 'Belkin', NULL, 1, 'Cable HDMI 2.1 Belkin 8K 2m.'),
(3, 'Cable SATA III Pack x3', 'cable-sata-iii-pack-x3', 'Pack de 3 cables SATA III 6Gbps, conectores rectos con seguro metálico.', 4990, 60, 20, 'QuadCore', NULL, 1, 'Pack 3 cables SATA III.'),
-- Herramientas (2)
(4, 'Kit Destornilladores Precisión 32 pzs', 'kit-destornilladores-precision-32', 'Kit de precisión 32 piezas para electrónica y notebooks, puntas magnéticas.', 14990, 22, 8, 'Arctic', NULL, 1, 'Kit destornilladores precisión 32 pzs.'),
(4, 'Estación de Soldadura 60W', 'estacion-soldadura-60w', 'Estación de soldadura 60W con control de temperatura ajustable y soporte.', 44990, 9, 3, 'QuadCore', NULL, 1, 'Estación de soldadura 60W.'),
-- Repuestos (4)
(5, 'Pasta Térmica Arctic MX-4 4g', 'pasta-termica-arctic-mx-4-4g', 'Pasta térmica Arctic MX-4 de 4 gramos, alta conductividad, sin metales.', 7990, 40, 15, 'Arctic', NULL, 1, 'Pasta térmica Arctic MX-4 4g.'),
(5, 'Kit Limpieza Aire Comprimido', 'kit-limpieza-aire-comprimido', 'Aire comprimido para limpieza de componentes y teclados, 400ml.', 9990, 35, 12, 'QuadCore', NULL, 1, 'Kit limpieza aire comprimido.'),
(5, 'Botón Power/Reset Universal', 'boton-power-reset-universal', 'Repuesto botón Power/Reset universal para gabinetes con cableado incluido.', 3990, 45, 15, 'QuadCore', NULL, 1, 'Botón Power/Reset universal.'),
(5, 'Bisagras Plásticas Notebook (par)', 'bisagras-plasticas-notebook', 'Par de bisagras plásticas de repuesto para notebooks, alta resistencia.', 6990, 28, 10, 'QuadCore', NULL, 1, 'Bisagras plásticas notebook (par).'),
-- Servicios Técnicos (1)
(6, 'Diagnóstico Técnico Profesional', 'diagnostico-tecnico-profesional', 'Servicio de diagnóstico técnico profesional de equipos, con informe detallado.', 19990, 99, 1, 'QuadCore', NULL, 1, 'Diagnóstico técnico profesional.');

-- ── Reseñas de ejemplo (aprobadas) para mostrar ratings en la demo ──
-- usuarios: 2=Juan (cliente), 4=Ana (supervisor). Unique por (producto, usuario).
INSERT INTO resenas (id_producto, id_usuario, calificacion, comentario, aprobada) VALUES
(1, 2, 5, 'Excelente procesador, muy rápido para gaming y edición.', 1),
(1, 4, 4, 'Muy buena relación precio/rendimiento.', 1),
(2, 2, 5, 'La placa funcionó perfecta, fácil de instalar.', 1),
(3, 4, 4, 'Memoria veloz, sin problemas de compatibilidad.', 1),
(5, 2, 5, 'La pasta térmica bajó varios grados la temperatura.', 1);

-- ── Ofertas de ejemplo (precio_anterior > precio → se muestra el % de descuento) ──
UPDATE productos SET precio_anterior = 279990 WHERE slug = 'procesador-intel-core-i5-13400f';   -- ~18%
UPDATE productos SET precio_anterior = 269990 WHERE slug = 'procesador-amd-ryzen-5-7600x';       -- ~26%
UPDATE productos SET precio_anterior = 74990  WHERE slug = 'memoria-kingston-fury-beast-16gb-ddr5'; -- ~27%
UPDATE productos SET precio_anterior = 99990  WHERE slug = 'fuente-evga-supernova-750w-gold';     -- ~10%
UPDATE productos SET precio_anterior = 189990 WHERE slug = 'placa-madre-asus-tuf-b650-plus';      -- ~16%
UPDATE productos SET precio_anterior = 44990  WHERE slug = 'mouse-logitech-g502-hero';            -- ~22%
UPDATE productos SET precio_anterior = 149990 WHERE slug = 'refrigeracion-corsair-icue-h100i';    -- ~20%

-- ============================================================
-- Catálogo ampliado (retail variado: PCs, notebooks, consolas, celulares, TV, monitores)
-- imagen_url NULL → placeholder gris; las imágenes las cura Leonardo (seed_imagenes.sql)
-- ============================================================
INSERT INTO categorias (id, nombre, slug, descripcion, id_padre, activo) VALUES
(7,  'Notebooks',    'notebooks',    'Notebooks gamer, oficina y ultraligeros',  NULL, 1),
(8,  'Computadores', 'computadores', 'PCs armados gamer y de oficina',           NULL, 1),
(9,  'Consolas',     'consolas',     'PlayStation, Xbox y Nintendo',             NULL, 1),
(10, 'Celulares',    'celulares',    'Smartphones de todas las marcas',          NULL, 1),
(11, 'TV y Audio',   'tv-audio',     'Smart TVs, audífonos y parlantes',         NULL, 1),
(12, 'Monitores',    'monitores',    'Monitores gamer y de oficina',             NULL, 1);

INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
-- Notebooks
(7, 'Notebook ASUS VivoBook 15 i5', 'notebook-asus-vivobook-15-i5', 'Notebook ASUS VivoBook 15.6" FHD, Intel Core i5, 8GB RAM, 512GB SSD, Windows 11.', 549990, 14, 4, 'ASUS', NULL, 1, 'Notebook ASUS VivoBook 15 i5.'),
(7, 'Notebook Lenovo IdeaPad Gaming 3', 'notebook-lenovo-ideapad-gaming-3', 'Notebook gamer Lenovo IdeaPad Gaming 3, Ryzen 7, 16GB RAM, RTX 3050, 512GB SSD.', 729990, 9, 3, 'Lenovo', NULL, 1, 'Lenovo IdeaPad Gaming 3 RTX 3050.'),
(7, 'Notebook HP Pavilion 14', 'notebook-hp-pavilion-14', 'Notebook HP Pavilion 14" FHD, Intel Core i5, 16GB RAM, 512GB SSD.', 489990, 11, 4, 'HP', NULL, 1, 'Notebook HP Pavilion 14 i5.'),
(7, 'MacBook Air M2 13"', 'macbook-air-m2-13', 'Apple MacBook Air 13" chip M2, 8GB RAM, 256GB SSD, macOS.', 1199990, 6, 2, 'Apple', NULL, 1, 'MacBook Air M2 13 pulgadas.'),
-- Computadores
(8, 'PC Gamer QuadCore Ryzen 5 RTX 4060', 'pc-gamer-quadcore-ryzen5-rtx4060', 'PC gamer armado: Ryzen 5 7600, 16GB DDR5, RTX 4060, SSD 1TB NVMe. Listo para jugar.', 899990, 7, 2, 'QuadCore', NULL, 1, 'PC Gamer Ryzen 5 RTX 4060.'),
(8, 'PC Oficina QuadCore Intel i3', 'pc-oficina-quadcore-i3', 'PC de oficina: Intel Core i3, 8GB RAM, SSD 480GB, ideal para trabajo y estudio.', 369990, 20, 5, 'QuadCore', NULL, 1, 'PC Oficina Intel i3.'),
(8, 'PC Gamer Pro QuadCore i7 RTX 4070', 'pc-gamer-pro-quadcore-i7-rtx4070', 'PC gamer alta gama: Intel Core i7, 32GB DDR5, RTX 4070, SSD 2TB NVMe.', 1499990, 4, 2, 'QuadCore', NULL, 1, 'PC Gamer Pro i7 RTX 4070.'),
-- Consolas
(9, 'PlayStation 5 Slim', 'playstation-5-slim', 'Consola Sony PlayStation 5 Slim edición disco, 1TB, control DualSense incluido.', 599990, 10, 3, 'Sony', NULL, 1, 'PlayStation 5 Slim 1TB.'),
(9, 'Xbox Series X', 'xbox-series-x', 'Consola Microsoft Xbox Series X, 1TB SSD, 4K nativo, control inalámbrico.', 649990, 8, 3, 'Microsoft', NULL, 1, 'Xbox Series X 1TB.'),
(9, 'Nintendo Switch OLED', 'nintendo-switch-oled', 'Consola Nintendo Switch modelo OLED, pantalla 7" vibrante, 64GB.', 379990, 13, 4, 'Nintendo', NULL, 1, 'Nintendo Switch OLED.'),
-- Celulares
(10, 'iPhone 15 128GB', 'iphone-15-128gb', 'Apple iPhone 15 128GB, chip A16 Bionic, cámara 48MP, USB-C.', 899990, 12, 4, 'Apple', NULL, 1, 'iPhone 15 128GB.'),
(10, 'Samsung Galaxy S24 256GB', 'samsung-galaxy-s24-256gb', 'Samsung Galaxy S24 256GB, pantalla AMOLED 120Hz, Galaxy AI.', 849990, 9, 3, 'Samsung', NULL, 1, 'Samsung Galaxy S24 256GB.'),
(10, 'Xiaomi Redmi Note 13 Pro', 'xiaomi-redmi-note-13-pro', 'Xiaomi Redmi Note 13 Pro, 256GB, cámara 200MP, carga rápida 67W.', 229990, 22, 6, 'Xiaomi', NULL, 1, 'Xiaomi Redmi Note 13 Pro.'),
(10, 'Motorola Moto G54 5G', 'motorola-moto-g54-5g', 'Motorola Moto G54 5G, 128GB, pantalla 120Hz, batería 5000mAh.', 169990, 18, 5, 'Motorola', NULL, 1, 'Motorola Moto G54 5G.'),
-- TV y Audio
(11, 'Smart TV Samsung 55" Crystal 4K', 'smart-tv-samsung-55-crystal-4k', 'Smart TV Samsung 55" Crystal UHD 4K, Tizen, HDR, control por voz.', 399990, 8, 3, 'Samsung', NULL, 1, 'Smart TV Samsung 55 4K.'),
(11, 'Audífonos Sony WH-1000XM5', 'audifonos-sony-wh-1000xm5', 'Audífonos Sony WH-1000XM5 con cancelación de ruido líder, 30h de batería.', 299990, 11, 4, 'Sony', NULL, 1, 'Sony WH-1000XM5 noise cancelling.'),
(11, 'Parlante JBL Charge 5', 'parlante-jbl-charge-5', 'Parlante portátil JBL Charge 5, resistente al agua IP67, 20h de batería.', 119990, 16, 5, 'JBL', NULL, 1, 'Parlante JBL Charge 5.'),
-- Monitores
(12, 'Monitor Gamer LG UltraGear 27" 165Hz', 'monitor-lg-ultragear-27-165hz', 'Monitor gamer LG UltraGear 27" QHD, 165Hz, 1ms, IPS, FreeSync.', 219990, 10, 3, 'LG', NULL, 1, 'Monitor LG UltraGear 27 165Hz.'),
(12, 'Monitor Samsung Essential 24"', 'monitor-samsung-essential-24', 'Monitor Samsung 24" FHD, panel IPS, bordes ultrafinos, ideal oficina.', 99990, 19, 5, 'Samsung', NULL, 1, 'Monitor Samsung 24 FHD.'),
(12, 'Monitor AOC Gaming 27" 144Hz', 'monitor-aoc-gaming-27-144hz', 'Monitor gamer AOC 27" FHD, 144Hz, 1ms, FreeSync Premium.', 159990, 12, 4, 'AOC', NULL, 1, 'Monitor AOC 27 144Hz.');

-- Ofertas del catálogo ampliado (precio_anterior > precio)
UPDATE productos SET precio_anterior = 849990  WHERE slug = 'notebook-lenovo-ideapad-gaming-3';
UPDATE productos SET precio_anterior = 1299990 WHERE slug = 'macbook-air-m2-13';
UPDATE productos SET precio_anterior = 1049990 WHERE slug = 'pc-gamer-quadcore-ryzen5-rtx4060';
UPDATE productos SET precio_anterior = 649990  WHERE slug = 'playstation-5-slim';
UPDATE productos SET precio_anterior = 999990  WHERE slug = 'iphone-15-128gb';
UPDATE productos SET precio_anterior = 269990  WHERE slug = 'xiaomi-redmi-note-13-pro';
UPDATE productos SET precio_anterior = 499990  WHERE slug = 'smart-tv-samsung-55-crystal-4k';
UPDATE productos SET precio_anterior = 269990  WHERE slug = 'monitor-lg-ultragear-27-165hz';

-- ============================================================
-- Catálogo XL: más categorías y productos (variedad tipo retail)
-- ============================================================
INSERT INTO categorias (id, nombre, slug, descripcion, id_padre, activo) VALUES
(13, 'Tarjetas de Video',    'tarjetas-video',     'GPUs para gaming y diseño',                NULL, 1),
(14, 'Almacenamiento',       'almacenamiento',     'SSD, HDD, pendrives y memorias',           NULL, 1),
(15, 'Redes',                'redes',              'Routers, switches y WiFi',                 NULL, 1),
(16, 'Videojuegos',          'videojuegos',        'Juegos para PS5, Xbox y Switch',           NULL, 1),
(17, 'Controles y Gaming',   'controles-gaming',   'Controles, headsets y volantes',           NULL, 1),
(18, 'Bolsos y Fundas',      'bolsos-fundas',      'Mochilas, fundas y estuches',              NULL, 1),
(19, 'Sillas y Escritorios', 'sillas-escritorios', 'Ergonomía gamer y de oficina',             NULL, 1),
(20, 'Limpieza y Cuidado',   'limpieza-cuidado',   'Limpieza de equipos electrónicos',         NULL, 1);

INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
-- Tarjetas de Video
(13, 'Tarjeta de Video NVIDIA RTX 4060 8GB', 'gpu-rtx-4060-8gb', 'GPU NVIDIA GeForce RTX 4060 8GB GDDR6, DLSS 3, ideal 1080p.', 379990, 9, 3, 'NVIDIA', NULL, 1, 'RTX 4060 8GB.'),
(13, 'Tarjeta de Video NVIDIA RTX 4070 12GB', 'gpu-rtx-4070-12gb', 'GPU RTX 4070 12GB GDDR6X, excelente para 1440p con ray tracing.', 619990, 6, 2, 'NVIDIA', NULL, 1, 'RTX 4070 12GB.'),
(13, 'Tarjeta de Video RTX 4070 Ti Super', 'gpu-rtx-4070-ti-super', 'GPU RTX 4070 Ti Super 16GB, gaming 1440p/4K de alto rendimiento.', 899990, 4, 2, 'NVIDIA', NULL, 1, 'RTX 4070 Ti Super.'),
(13, 'Tarjeta de Video AMD RX 7800 XT 16GB', 'gpu-rx-7800-xt-16gb', 'GPU AMD Radeon RX 7800 XT 16GB GDDR6, gran relación precio/rendimiento.', 559990, 7, 2, 'AMD', NULL, 1, 'RX 7800 XT 16GB.'),
-- Almacenamiento
(14, 'SSD Samsung 980 Pro 1TB NVMe', 'ssd-samsung-980-pro-1tb', 'SSD Samsung 980 Pro 1TB PCIe 4.0, lecturas hasta 7000 MB/s.', 89990, 22, 6, 'Samsung', NULL, 1, 'SSD Samsung 980 Pro 1TB.'),
(14, 'SSD Crucial MX500 2TB SATA', 'ssd-crucial-mx500-2tb', 'SSD Crucial MX500 2TB SATA 2.5", confiable para todo equipo.', 119990, 14, 4, 'Crucial', NULL, 1, 'SSD Crucial MX500 2TB.'),
(14, 'Disco Duro Seagate Barracuda 2TB', 'hdd-seagate-barracuda-2tb', 'HDD Seagate Barracuda 2TB 7200RPM para almacenamiento masivo.', 49990, 18, 5, 'Seagate', NULL, 1, 'HDD Seagate 2TB.'),
(14, 'Pendrive Kingston DataTraveler 128GB', 'pendrive-kingston-128gb', 'Pendrive Kingston USB 3.2 128GB, compacto y veloz.', 12990, 40, 10, 'Kingston', NULL, 1, 'Pendrive Kingston 128GB.'),
(14, 'MicroSD SanDisk Extreme 256GB', 'microsd-sandisk-256gb', 'Tarjeta microSD SanDisk Extreme 256GB A2 U3, ideal cámaras y consolas.', 29990, 30, 8, 'SanDisk', NULL, 1, 'MicroSD SanDisk 256GB.'),
(14, 'Disco Externo WD Elements 4TB', 'disco-externo-wd-4tb', 'Disco externo WD Elements 4TB USB 3.0, respaldo portátil.', 99990, 12, 4, 'Western Digital', NULL, 1, 'Disco externo WD 4TB.'),
-- Redes
(15, 'Router TP-Link Archer AX1500 WiFi6', 'router-tplink-ax1500', 'Router TP-Link Archer AX1500 WiFi 6, doble banda, gigabit.', 49990, 16, 5, 'TP-Link', NULL, 1, 'Router TP-Link AX1500.'),
(15, 'Repetidor WiFi TP-Link RE315', 'repetidor-wifi-tplink-re315', 'Extensor de rango TP-Link RE315 AC1200, amplía tu señal WiFi.', 24990, 20, 6, 'TP-Link', NULL, 1, 'Repetidor TP-Link RE315.'),
(15, 'Switch TP-Link 8 Puertos Gigabit', 'switch-tplink-8-puertos', 'Switch TP-Link 8 puertos gigabit, plug and play, metálico.', 27990, 13, 4, 'TP-Link', NULL, 1, 'Switch 8 puertos gigabit.'),
(15, 'Tarjeta WiFi PCIe AX WiFi6', 'tarjeta-wifi-pcie-ax', 'Tarjeta de red PCIe WiFi 6 + Bluetooth 5.2 para PC de escritorio.', 22990, 17, 5, 'TP-Link', NULL, 1, 'Tarjeta WiFi PCIe AX.'),
-- Notebooks (extra)
(7, 'Notebook Acer Nitro 5 RTX 4050', 'notebook-acer-nitro-5-rtx4050', 'Notebook gamer Acer Nitro 5, i5, 16GB, RTX 4050, 512GB SSD, 144Hz.', 799990, 8, 3, 'Acer', NULL, 1, 'Acer Nitro 5 RTX 4050.'),
(7, 'Notebook Dell Inspiron 15', 'notebook-dell-inspiron-15', 'Notebook Dell Inspiron 15.6" FHD, i7, 16GB RAM, 512GB SSD.', 629990, 10, 3, 'Dell', NULL, 1, 'Dell Inspiron 15 i7.'),
-- Celulares (extra)
(10, 'iPhone 15 Pro 256GB', 'iphone-15-pro-256gb', 'Apple iPhone 15 Pro 256GB, titanio, chip A17 Pro, cámara 48MP.', 1299990, 7, 2, 'Apple', NULL, 1, 'iPhone 15 Pro 256GB.'),
(10, 'Samsung Galaxy A55 128GB', 'samsung-galaxy-a55-128gb', 'Samsung Galaxy A55 5G 128GB, pantalla Super AMOLED 120Hz.', 349990, 15, 5, 'Samsung', NULL, 1, 'Samsung Galaxy A55.'),
(10, 'Google Pixel 8 128GB', 'google-pixel-8-128gb', 'Google Pixel 8 128GB, cámara con IA, Android puro y actualizado.', 699990, 8, 3, 'Google', NULL, 1, 'Google Pixel 8.'),
-- Consolas (ediciones y alternativas)
(9, 'PlayStation 5 Edición God of War Ragnarök', 'ps5-god-of-war-ragnarok', 'Consola PS5 bundle God of War Ragnarök, incluye el juego en formato digital.', 649990, 6, 2, 'Sony', NULL, 1, 'PS5 God of War Ragnarök.'),
(9, 'PlayStation 5 Digital Edition', 'ps5-digital-edition', 'Consola PS5 Digital Edition (sin lector), 1TB, control DualSense.', 549990, 9, 3, 'Sony', NULL, 1, 'PS5 Digital Edition.'),
(9, 'Xbox Series S 512GB', 'xbox-series-s-512gb', 'Consola Xbox Series S 512GB, digital, compacta, 1440p.', 379990, 12, 4, 'Microsoft', NULL, 1, 'Xbox Series S 512GB.'),
(9, 'Nintendo Switch OLED Edición Zelda', 'switch-oled-zelda', 'Nintendo Switch OLED edición especial The Legend of Zelda: TOTK.', 419990, 7, 2, 'Nintendo', NULL, 1, 'Switch OLED edición Zelda.'),
(9, 'Steam Deck OLED 512GB', 'steam-deck-oled-512gb', 'Consola portátil Valve Steam Deck OLED 512GB, ejecuta tu librería de Steam.', 749990, 5, 2, 'Valve', NULL, 1, 'Steam Deck OLED 512GB.'),
(9, 'ASUS ROG Ally Z1 Extreme', 'asus-rog-ally-z1', 'Consola portátil ASUS ROG Ally Z1 Extreme, Windows 11, pantalla 120Hz.', 829990, 4, 2, 'ASUS', NULL, 1, 'ROG Ally Z1 Extreme.'),
-- Videojuegos
(16, 'God of War Ragnarök PS5', 'juego-god-of-war-ragnarok-ps5', 'Videojuego God of War Ragnarök para PlayStation 5, edición física.', 49990, 25, 6, 'Sony', NULL, 1, 'God of War Ragnarök PS5.'),
(16, 'Mario Kart 8 Deluxe Switch', 'juego-mario-kart-8-switch', 'Videojuego Mario Kart 8 Deluxe para Nintendo Switch.', 44990, 28, 8, 'Nintendo', NULL, 1, 'Mario Kart 8 Deluxe.'),
(16, 'EA Sports FC 24 PS5', 'juego-ea-fc-24-ps5', 'Videojuego EA Sports FC 24 para PlayStation 5.', 39990, 30, 8, 'EA', NULL, 1, 'EA Sports FC 24 PS5.'),
(16, 'The Legend of Zelda TOTK Switch', 'juego-zelda-totk-switch', 'Videojuego The Legend of Zelda: Tears of the Kingdom para Switch.', 49990, 20, 6, 'Nintendo', NULL, 1, 'Zelda Tears of the Kingdom.'),
(16, 'Marvels Spider-Man 2 PS5', 'juego-spiderman-2-ps5', 'Videojuego Marvel''s Spider-Man 2 para PlayStation 5.', 54990, 18, 5, 'Sony', NULL, 1, 'Spider-Man 2 PS5.'),
-- Controles y Gaming
(17, 'Control DualSense PS5', 'control-dualsense-ps5', 'Control inalámbrico Sony DualSense para PS5, retroalimentación háptica.', 64990, 20, 6, 'Sony', NULL, 1, 'Control DualSense PS5.'),
(17, 'Control Inalámbrico Xbox Series', 'control-xbox-series', 'Control inalámbrico Xbox Series, compatible con Xbox y PC.', 59990, 18, 6, 'Microsoft', NULL, 1, 'Control Xbox Series.'),
(17, 'Nintendo Switch Pro Controller', 'control-switch-pro', 'Control Nintendo Switch Pro, ergonómico, batería de larga duración.', 69990, 14, 4, 'Nintendo', NULL, 1, 'Switch Pro Controller.'),
(17, 'Control PowerA Enhanced (Alternativo)', 'control-powera-enhanced', 'Control alámbrico PowerA Enhanced para Switch/Xbox, opción económica.', 29990, 22, 6, 'PowerA', NULL, 1, 'Control PowerA Enhanced.'),
(17, 'Headset Gamer HyperX Cloud II', 'headset-hyperx-cloud-2', 'Audífonos gamer HyperX Cloud II con sonido envolvente 7.1 y micrófono.', 69990, 16, 5, 'HyperX', NULL, 1, 'Headset HyperX Cloud II.'),
(17, 'Volante Logitech G29 Driving Force', 'volante-logitech-g29', 'Volante Logitech G29 con pedales, force feedback, PS/PC.', 299990, 6, 2, 'Logitech', NULL, 1, 'Volante Logitech G29.'),
-- Bolsos y Fundas
(18, 'Mochila Notebook QuadCore 15.6"', 'mochila-notebook-quadcore', 'Mochila para notebook hasta 15.6", acolchada, resistente al agua.', 29990, 25, 6, 'QuadCore', NULL, 1, 'Mochila notebook 15.6.'),
(18, 'Funda Notebook Neopreno 15"', 'funda-notebook-neopreno-15', 'Funda de neopreno para notebook 15", protección contra golpes.', 14990, 30, 8, 'QuadCore', NULL, 1, 'Funda notebook neopreno 15.'),
(18, 'Bolso de Transporte para Consola', 'bolso-consola', 'Bolso acolchado para transportar tu consola y accesorios.', 24990, 14, 4, 'QuadCore', NULL, 1, 'Bolso para consola.'),
-- Sillas y Escritorios
(19, 'Silla Gamer QuadCore Pro', 'silla-gamer-quadcore-pro', 'Silla gamer ergonómica reclinable con soporte lumbar y apoyabrazos 2D.', 159990, 10, 3, 'QuadCore', NULL, 1, 'Silla gamer QuadCore Pro.'),
(19, 'Silla Ergonómica de Oficina', 'silla-ergonomica-oficina', 'Silla de oficina ergonómica con malla transpirable y altura ajustable.', 119990, 12, 4, 'QuadCore', NULL, 1, 'Silla ergonómica oficina.'),
(19, 'Escritorio Gamer 120cm', 'escritorio-gamer-120', 'Escritorio gamer 120cm con superficie amplia y pasacables.', 99990, 8, 3, 'QuadCore', NULL, 1, 'Escritorio gamer 120cm.'),
(19, 'Soporte de Monitor con Brazo', 'soporte-monitor-brazo', 'Soporte de monitor con brazo articulado, libera espacio en tu escritorio.', 34990, 15, 5, 'QuadCore', NULL, 1, 'Soporte monitor con brazo.'),
-- Limpieza y Cuidado
(20, 'Aire Comprimido 400ml', 'aire-comprimido-400ml', 'Aire comprimido 400ml para limpiar teclados, ventiladores y componentes.', 9990, 40, 12, 'QuadCore', NULL, 1, 'Aire comprimido 400ml.'),
(20, 'Kit Limpieza de Pantallas', 'kit-limpieza-pantallas', 'Kit limpiador de pantallas: spray + paño microfibra, sin residuos.', 8990, 35, 10, 'QuadCore', NULL, 1, 'Kit limpieza pantallas.'),
(20, 'Pack 5 Paños de Microfibra', 'pack-panos-microfibra', 'Pack de 5 paños de microfibra premium para equipos y lentes.', 5990, 50, 15, 'QuadCore', NULL, 1, 'Pack 5 paños microfibra.'),
(20, 'Alcohol Isopropílico 250ml', 'alcohol-isopropilico-250ml', 'Alcohol isopropílico 99% 250ml para limpieza de electrónica.', 6990, 45, 12, 'QuadCore', NULL, 1, 'Alcohol isopropílico 250ml.'),
-- Accesorios (extra)
(2, 'Webcam Logitech C920 Full HD', 'webcam-logitech-c920', 'Webcam Logitech C920 Full HD 1080p con micrófono estéreo.', 49990, 18, 5, 'Logitech', NULL, 1, 'Webcam Logitech C920.'),
(2, 'Micrófono HyperX QuadCast', 'microfono-hyperx-quadcast', 'Micrófono USB HyperX QuadCast con filtro antipop y luz RGB.', 99990, 10, 3, 'HyperX', NULL, 1, 'Micrófono HyperX QuadCast.'),
(2, 'Mousepad Gamer XL', 'mousepad-gamer-xl', 'Mousepad gamer XL 80x30cm, base antideslizante, superficie suave.', 12990, 30, 8, 'QuadCore', NULL, 1, 'Mousepad gamer XL.'),
-- Herramientas (extra)
(4, 'Multímetro Digital', 'multimetro-digital', 'Multímetro digital con medición de voltaje, corriente y continuidad.', 19990, 16, 5, 'QuadCore', NULL, 1, 'Multímetro digital.'),
(4, 'Tester de Cables de Red RJ45', 'tester-cables-red-rj45', 'Tester de cables de red RJ45/RJ11, verifica continuidad y conexiones.', 14990, 14, 4, 'QuadCore', NULL, 1, 'Tester cables de red.'),
-- Cables (extra)
(3, 'Cable DisplayPort 1.4 2m', 'cable-displayport-14-2m', 'Cable DisplayPort 1.4, 2 metros, soporta 4K 144Hz.', 9990, 30, 8, 'QuadCore', NULL, 1, 'Cable DisplayPort 1.4 2m.'),
(3, 'Cable USB-C a USB-C 100W 2m', 'cable-usbc-100w-2m', 'Cable USB-C a USB-C 2m, carga rápida hasta 100W y datos.', 8990, 35, 10, 'QuadCore', NULL, 1, 'Cable USB-C 100W 2m.'),
(3, 'Cable de Red Cat6 3m', 'cable-red-cat6-3m', 'Cable de red Ethernet Cat6 3 metros, hasta 1 Gbps.', 4990, 50, 15, 'QuadCore', NULL, 1, 'Cable de red Cat6 3m.');

-- Ofertas del catálogo XL
UPDATE productos SET precio_anterior = 449990  WHERE slug = 'gpu-rtx-4060-8gb';
UPDATE productos SET precio_anterior = 729990  WHERE slug = 'gpu-rtx-4070-12gb';
UPDATE productos SET precio_anterior = 109990  WHERE slug = 'ssd-samsung-980-pro-1tb';
UPDATE productos SET precio_anterior = 899990  WHERE slug = 'notebook-acer-nitro-5-rtx4050';
UPDATE productos SET precio_anterior = 1449990 WHERE slug = 'iphone-15-pro-256gb';
UPDATE productos SET precio_anterior = 729990  WHERE slug = 'ps5-god-of-war-ragnarok';
UPDATE productos SET precio_anterior = 59990   WHERE slug = 'juego-god-of-war-ragnarok-ps5';
UPDATE productos SET precio_anterior = 199990  WHERE slug = 'silla-gamer-quadcore-pro';
UPDATE productos SET precio_anterior = 84990   WHERE slug = 'headset-hyperx-cloud-2';
UPDATE productos SET precio_anterior = 64990   WHERE slug = 'router-tplink-ax1500';
