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
