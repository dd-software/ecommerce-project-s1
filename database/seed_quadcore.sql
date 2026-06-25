============================================================
-- Seed QuadCore — catálogo real de electrónica (reemplaza el demo genérico)
-- Precios en PESOS chilenos (convención del proyecto, sin /100).
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

-- ============================================================
-- CATEGORÍAS
-- ============================================================
INSERT INTO categorias (id, nombre, slug, descripcion, id_padre, activo) VALUES
(1,  'Componentes PC',     'componentes-pc',     'Procesadores, RAM, SSD, fuentes y refrigeración', NULL, 1),
(2,  'Accesorios',         'accesorios',         'Mouse, teclados y periféricos',                   NULL, 1),
(3,  'Cables',             'cables',             'Cables y conectores',                             NULL, 1),
(4,  'Herramientas',       'herramientas',       'Precisión, soldadura y limpieza',                 NULL, 1),
(5,  'Repuestos',          'repuestos',          'Pastas térmicas, botones, bisagras',              NULL, 1),
(6,  'Servicios Técnicos', 'servicios-tecnicos', 'Diagnóstico y reparación',                        NULL, 1),
(7,  'Notebooks',          'notebooks',          'Notebooks gamer, oficina y ultraligeros',          NULL, 1),
(8,  'Computadores',       'computadores',       'PCs armados gamer y de oficina',                  NULL, 1),
(9,  'Consolas',           'consolas',           'PlayStation, Xbox y Nintendo',                    NULL, 1),
(10, 'Celulares',          'celulares',          'Smartphones de todas las marcas',                 NULL, 1),
(11, 'TV y Audio',         'tv-audio',           'Smart TVs, audífonos y parlantes',                NULL, 1),
(12, 'Monitores',          'monitores',          'Monitores gamer y de oficina',                    NULL, 1),
(13, 'Tarjetas de Video',  'tarjetas-video',     'GPUs para gaming y diseño',                       NULL, 1),
(14, 'Almacenamiento',     'almacenamiento',     'SSD, HDD, pendrives y memorias',                  NULL, 1),
(15, 'Redes',              'redes',              'Routers, switches y WiFi',                        NULL, 1),
(16, 'Videojuegos',        'videojuegos',        'Juegos para PS5, Xbox y Switch',                  NULL, 1),
(17, 'Controles y Gaming', 'controles-gaming',   'Controles, headsets y volantes',                  NULL, 1),
(18, 'Bolsos y Fundas',    'bolsos-fundas',      'Mochilas, fundas y estuches',                     NULL, 1),
(19, 'Sillas y Escritorios','sillas-escritorios','Ergonomía gamer y de oficina',                    NULL, 1),
(20, 'Limpieza y Cuidado', 'limpieza-cuidado',   'Limpieza de equipos electrónicos',                NULL, 1);

-- ============================================================
-- PRODUCTOS — Componentes PC
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(1, 'Procesador Intel Core i5-13400F', 'procesador-intel-core-i5-13400f', 'Procesador de 13ª generación, 6 núcleos / 12 hilos, socket LGA1700. Ideal para gaming y productividad.', 229990, 12, 5, 'Intel', NULL, 1, 'Procesador Intel i5-13400F.'),
(1, 'Procesador AMD Ryzen 5 7600X', 'procesador-amd-ryzen-5-7600x', 'Procesador AMD Ryzen 5 7600X, 6 núcleos / 12 hilos, socket AM5, hasta 5.3 GHz.', 229990, 8, 5, 'AMD', NULL, 1, 'Procesador AMD Ryzen 5 7600X.'),
(1, 'Memoria Kingston Fury Beast 16GB DDR5', 'memoria-kingston-fury-beast-16gb-ddr5', 'Memoria RAM Kingston Fury Beast 16GB DDR5 5200MHz, disipador de bajo perfil.', 64990, 20, 8, 'Kingston', NULL, 1, 'RAM Kingston Fury Beast 16GB DDR5.'),
(1, 'Memoria Corsair Vengeance 32GB DDR5', 'memoria-corsair-vengeance-32gb-ddr5', 'Kit Corsair Vengeance 32GB (2x16GB) DDR5 6000MHz, optimizada para AMD EXPO e Intel XMP.', 129990, 15, 6, 'Corsair', NULL, 1, 'RAM Corsair Vengeance 32GB DDR5.'),
(1, 'Fuente EVGA SuperNOVA 750W 80+ Gold', 'fuente-evga-supernova-750w-gold', 'Fuente de poder EVGA SuperNOVA 750W, certificación 80 Plus Gold, modular.', 89990, 10, 4, 'EVGA', NULL, 1, 'Fuente EVGA SuperNOVA 750W Gold.'),
(1, 'Placa Madre ASUS TUF Gaming B650-PLUS', 'placa-madre-asus-tuf-b650-plus', 'Placa madre ASUS TUF Gaming B650-PLUS, socket AM5, PCIe 5.0, DDR5.', 159990, 0, 4, 'ASUS', NULL, 1, 'Placa madre ASUS TUF B650-PLUS.'),
(1, 'SSD Kingston NV2 1TB NVMe M.2', 'ssd-kingston-nv2-1tb-nvme', 'Unidad SSD Kingston NV2 1TB NVMe PCIe 4.0 M.2, lecturas hasta 3500 MB/s.', 49990, 30, 10, 'Kingston', NULL, 1, 'SSD Kingston NV2 1TB NVMe.'),
(1, 'Refrigeración Líquida Corsair iCUE H100i', 'refrigeracion-corsair-icue-h100i', 'Refrigeración líquida AIO Corsair iCUE H100i, radiador 240mm, RGB.', 119990, 7, 3, 'Corsair', NULL, 1, 'Líquida Corsair iCUE H100i 240mm.'),
(1, 'Ventilador 120mm ARGB', 'ventilador-120mm-argb', 'Ventilador de gabinete 120mm ARGB, rodamiento hidráulico, bajo nivel de ruido.', 14990, 25, 10, 'Corsair', NULL, 1, 'Ventilador 120mm ARGB.'),
(1, 'Tarjeta de Video MSI RTX 5070 12G Shadow 2X OC', 'gpu-msi-rtx-5070-shadow-2x-oc', 'GPU MSI GeForce RTX 5070 12GB GDDR7 Shadow 2X OC, ideal para 1440p con DLSS 4.', 699990, 8, 3, 'MSI', NULL, 1, 'MSI RTX 5070 Shadow 2X OC.'),
(1, 'Tarjeta de Video Gigabyte RX 9070 XT Gaming OC 16G', 'gpu-gigabyte-rx-9070-xt-gaming-oc', 'GPU Gigabyte Radeon RX 9070 XT Gaming OC 16GB GDDR6, excelente rendimiento 4K.', 749990, 6, 2, 'Gigabyte', NULL, 1, 'Gigabyte RX 9070 XT Gaming OC.'),
(1, 'Tarjeta de Video Palit RTX 5060 Infinity 2 OC', 'gpu-palit-rtx-5060-infinity-2-oc', 'GPU Palit GeForce RTX 5060 Infinity 2 OC, excelente para 1080p con ray tracing.', 379990, 12, 4, 'Palit', NULL, 1, 'Palit RTX 5060 Infinity 2 OC.'),
(1, 'Tarjeta de Video ASUS Prime RTX 5070 Ti OC Edition', 'gpu-asus-prime-rtx-5070-ti-oc', 'GPU ASUS Prime GeForce RTX 5070 Ti 16GB GDDR7 OC Edition, gaming 1440p/4K.', 899990, 7, 2, 'ASUS', NULL, 1, 'ASUS Prime RTX 5070 Ti OC.'),
(1, 'Tarjeta de Video ASUS Prime RX 9070 XT OC Edition 16GB', 'gpu-asus-prime-rx-9070-xt-oc', 'GPU ASUS Prime Radeon RX 9070 XT OC Edition 16GB GDDR6, rendimiento 4K.', 729990, 6, 2, 'ASUS', NULL, 1, 'ASUS Prime RX 9070 XT OC.'),
(1, 'Tarjeta de Video MSI RTX 5070 Ti 16G Shadow 3X OC', 'gpu-msi-rtx-5070-ti-shadow-3x-oc', 'GPU MSI GeForce RTX 5070 Ti 16GB GDDR7 Shadow 3X OC, máxima potencia gaming.', 949990, 5, 2, 'MSI', NULL, 1, 'MSI RTX 5070 Ti Shadow 3X OC.'),
(1, 'Fuente de Poder Cooler Master Elite Gold 850W', 'fuente-cooler-master-elite-gold-850w', 'Fuente Cooler Master Elite Gold 850W MPX-8505-AFAG, 80+ Gold, full modular.', 109990, 12, 4, 'Cooler Master', NULL, 1, 'Fuente Cooler Master Elite Gold 850W.'),
(1, 'Fuente de Poder Corsair RM750e 2025 750W', 'fuente-corsair-rm750e-2025', 'Fuente Corsair RMe Series RM750e 2025 750W, 80+ Gold, full modular, silenciosa.', 99990, 15, 5, 'Corsair', NULL, 1, 'Fuente Corsair RM750e 2025 750W.'),
(1, 'Fuente de Poder MSI MAG A650BN 650W', 'fuente-msi-mag-a650bn-650w', 'Fuente MSI MAG A650BN 650W, 80+ Bronze, ventilador silencioso, ideal presupuesto.', 54990, 20, 6, 'MSI', NULL, 1, 'Fuente MSI MAG A650BN 650W.'),
(1, 'Fuente de Poder Gigabyte P650SS ICE 650W', 'fuente-gigabyte-p650ss-ice-650w', 'Fuente Gigabyte GP-P650SS ICE 650W, 80+ Silver, diseño compacto, ventilador silencioso.', 49990, 18, 5, 'Gigabyte', NULL, 1, 'Fuente Gigabyte P650SS ICE 650W.'),
(1, 'Placa Madre MSI B550M PRO-VDH WIFI', 'placa-msi-b550m-pro-vdh-wifi', 'Placa madre MSI B550M PRO-VDH WIFI, socket AM4, mATX, WiFi integrado, lista para Ryzen.', 89990, 12, 4, 'MSI', NULL, 1, 'Placa MSI B550M PRO-VDH WIFI.'),
(1, 'Placa Madre MSI PRO B760M-P DDR4', 'placa-msi-pro-b760m-p-ddr4', 'Placa madre MSI PRO B760M-P DDR4, socket LGA1700, mATX, soporte Intel 12/13/14 Gen.', 79990, 15, 5, 'MSI', NULL, 1, 'Placa MSI PRO B760M-P DDR4.'),
(1, 'Placa Madre ASUS ROG Strix B550-F Gaming WiFi II', 'placa-asus-rog-strix-b550-f-gaming-wifi-ii', 'Placa madre ASUS ROG Strix B550-F Gaming WiFi II, socket AM4, ATX, PCIe 4.0, WiFi 6E.', 149990, 8, 3, 'ASUS', NULL, 1, 'Placa ASUS ROG Strix B550-F Gaming WiFi II.'),
(1, 'Placa Madre Gigabyte B550 Gaming X V2', 'placa-gigabyte-b550-gaming-x-v2', 'Placa madre Gigabyte B550 Gaming X V2, socket AM4, ATX, PCIe 4.0, dual M.2.', 99990, 14, 4, 'Gigabyte', NULL, 1, 'Placa Gigabyte B550 Gaming X V2.'),
(1, 'Placa Madre Gigabyte B550 Eagle WiFi6', 'placa-gigabyte-b550-eagle-wifi6', 'Placa madre Gigabyte B550 Eagle WiFi6, socket AM4, ATX, WiFi 6, PCIe 4.0.', 119990, 10, 3, 'Gigabyte', NULL, 1, 'Placa Gigabyte B550 Eagle WiFi6.'),
(1, 'Placa Madre Gigabyte A520M K V2', 'placa-gigabyte-a520m-k-v2', 'Placa madre Gigabyte A520M K V2, socket AM4, mATX, ideal presupuesto para Ryzen.', 54990, 20, 6, 'Gigabyte', NULL, 1, 'Placa Gigabyte A520M K V2.'),
(1, 'Ventilador be quiet! Silent Wings 4 120mm PWM High-Speed', 'ventilador-be-quiet-silent-wings-4-120mm', 'Ventilador be quiet! Silent Wings 4 120mm PWM High-Speed, ultra silencioso, ideal radiadores.', 19990, 25, 8, 'be quiet!', NULL, 1, 'Ventilador be quiet! Silent Wings 4 120mm.'),
(1, 'Lian Li UNI Fan SL Wireless 120 Triple Pack White', 'lian-li-uni-fan-sl-wireless-120-white', 'Kit Lian Li UNI Fan SL Wireless 120mm Triple Pack White, conexión inalámbrica, RGB.', 69990, 10, 3, 'Lian Li', NULL, 1, 'Lian Li UNI Fan SL Wireless 120 White.'),
(1, 'Lian Li UNI Fan SL Infinity Wireless Triple Pack Black', 'lian-li-uni-fan-sl-infinity-wireless-black', 'Kit Lian Li UNI Fan SL Infinity Wireless Triple Pack Black, efecto espejo infinito, RGB.', 74990, 8, 3, 'Lian Li', NULL, 1, 'Lian Li UNI Fan SL Infinity Wireless Black.'),
(1, 'Memoria ADATA XPG Lancer Blade RGB 16GB DDR5 6000MHz', 'ram-adata-xpg-lancer-blade-rgb-16gb-ddr5', 'Memoria ADATA XPG Lancer Blade RGB 16GB DDR5 6000MHz CL48, disipador compacto.', 54990, 22, 8, 'ADATA', NULL, 1, 'RAM ADATA XPG Lancer Blade 16GB DDR5.'),
(1, 'Memoria Kingston Fury Beast 16GB DDR4 3200MHz', 'ram-kingston-fury-beast-16gb-ddr4-3200', 'Memoria Kingston Fury Beast 16GB DDR4 3200MHz CL16, disipador de bajo perfil.', 39990, 30, 10, 'Kingston', NULL, 1, 'RAM Kingston Fury Beast 16GB DDR4.'),
(1, 'Memoria Kingston Fury Beast 32GB DDR5 6000MHz (2x16GB)', 'ram-kingston-fury-beast-32gb-ddr5-6000', 'Kit Kingston Fury Beast 32GB (2x16GB) DDR5 6000MHz CL30, optimizado AMD EXPO.', 119990, 14, 5, 'Kingston', NULL, 1, 'RAM Kingston Fury Beast 32GB DDR5.'),
(1, 'Memoria ADATA XPG Lancer Blade 16GB DDR5 5600MHz', 'ram-adata-xpg-lancer-blade-16gb-ddr5-5600', 'Memoria ADATA XPG Lancer Blade 16GB DDR5 5600MHz CL46, disipador slim.', 49990, 18, 6, 'ADATA', NULL, 1, 'RAM ADATA XPG Lancer Blade 16GB DDR5 5600.'),
(1, 'Memoria Kingston Fury Renegade 16GB DDR5 7200MHz', 'ram-kingston-fury-renegade-16gb-ddr5-7200', 'Memoria Kingston Fury Renegade 16GB DDR5 7200MHz CL38, alto rendimiento gaming.', 64990, 10, 3, 'Kingston', NULL, 1, 'RAM Kingston Fury Renegade 16GB DDR5 7200.'),
(1, 'Memoria ADATA XPG Spectrix D35G 16GB DDR4 3200MHz RGB', 'ram-adata-xpg-spectrix-d35g-16gb-ddr4', 'Memoria ADATA XPG Spectrix D35G 16GB DDR4 3200MHz, iluminación RGB completa.', 44990, 16, 5, 'ADATA', NULL, 1, 'RAM ADATA XPG Spectrix D35G 16GB DDR4.'),
(1, 'Memoria Kingston Fury Beast RGB 8GB DDR4 3200MHz', 'ram-kingston-fury-beast-rgb-8gb-ddr4', 'Memoria Kingston Fury Beast RGB 8GB DDR4 3200MHz, iluminación RGB personalizable.', 24990, 25, 8, 'Kingston', NULL, 1, 'RAM Kingston Fury Beast RGB 8GB DDR4.'),
(1, 'Memoria Crucial 8GB DDR4 3200MHz SO-DIMM', 'ram-crucial-8gb-ddr4-sodimm-3200', 'Memoria Crucial 8GB DDR4 3200MHz SO-DIMM para notebooks y mini PCs.', 19990, 35, 10, 'Crucial', NULL, 1, 'RAM Crucial 8GB DDR4 SO-DIMM.'),
(1, 'Disco Duro Seagate Mobile 1TB 2.5"', 'hdd-seagate-mobile-1tb', 'Disco duro Seagate Mobile 1TB 2.5" ST1000LM035, ideal para notebooks y almacenamiento externo.', 39990, 20, 6, 'Seagate', NULL, 1, 'HDD Seagate Mobile 1TB.'),
(1, 'Disco Duro Seagate Barracuda 1TB 3.5"', 'hdd-seagate-barracuda-1tb', 'Disco duro Seagate Barracuda 1TB 3.5" 7200RPM, ideal para PC de escritorio.', 39990, 25, 8, 'Seagate', NULL, 1, 'HDD Seagate Barracuda 1TB.'),
(1, 'Disco Duro Toshiba N300 NAS 20TB', 'hdd-toshiba-n300-nas-20tb', 'Disco duro Toshiba N300 NAS 20TB, diseñado para sistemas NAS, 7200RPM, alta confiabilidad.', 499990, 4, 2, 'Toshiba', NULL, 1, 'HDD Toshiba N300 NAS 20TB.'),
(1, 'Disco Duro Western Digital Purple Pro 18TB', 'hdd-wd-purple-pro-18tb', 'Disco duro Western Digital Purple Pro 18TB, diseñado para videovigilancia 24/7.', 449990, 5, 2, 'Western Digital', NULL, 1, 'HDD WD Purple Pro 18TB.'),
(1, 'SSD Kingston NV3 2TB NVMe', 'ssd-kingston-nv3-2tb', 'SSD Kingston NV3 2TB NVMe PCIe 4.0 M.2, lecturas hasta 6000 MB/s.', 109990, 18, 6, 'Kingston', NULL, 1, 'SSD Kingston NV3 2TB.'),
(1, 'SSD Western Digital Black SN850X 2TB NVMe', 'ssd-wd-black-sn850x-2tb', 'SSD Western Digital Black SN850X 2TB NVMe PCIe 4.0, lecturas 7300 MB/s, con disipador.', 159990, 12, 4, 'Western Digital', NULL, 1, 'SSD WD Black SN850X 2TB.'),
(1, 'SSD Kingston NV3 500GB NVMe', 'ssd-kingston-nv3-500gb', 'SSD Kingston NV3 500GB NVMe PCIe 4.0 M.2, lecturas hasta 5000 MB/s.', 44990, 30, 10, 'Kingston', NULL, 1, 'SSD Kingston NV3 500GB.'),
(1, 'Gabinete Cougar FV150 RGB White', 'gabinete-cougar-fv150-rgb-white', 'Gabinete Cougar FV150 RGB White, ventana templada, incluye 4 ventiladores ARGB.', 69990, 10, 3, 'Cougar', NULL, 1, 'Gabinete Cougar FV150 RGB White.'),
(1, 'Gabinete Gamemax Storm BK', 'gabinete-gamemax-storm-bk', 'Gabinete Gamemax Storm BK, ventana lateral, diseño agresivo, espacio para refrigeración líquida.', 49990, 15, 5, 'Gamemax', NULL, 1, 'Gabinete Gamemax Storm BK.'),
(1, 'Gabinete Lian Li A3 mATX Wood Edition', 'gabinete-lian-li-a3-matx-wood', 'Gabinete Lian Li A3 mATX Wood Edition, diseño minimalista con detalles de madera.', 99990, 8, 3, 'Lian Li', NULL, 1, 'Gabinete Lian Li A3 mATX Wood.'),
(1, 'Gabinete MSI Pano 130R MLG Edition', 'gabinete-msi-pano-130r-mlg', 'Gabinete MSI Pano 130R MLG Edition, diseño envolvente de cristal templado, ATX.', 119990, 6, 2, 'MSI', NULL, 1, 'Gabinete MSI Pano 130R MLG Edition.');

-- ============================================================
-- PRODUCTOS — Accesorios
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(2, 'Mouse Logitech G502 HERO', 'mouse-logitech-g502-hero', 'Mouse gamer Logitech G502 HERO, sensor 25K DPI, 11 botones programables.', 34990, 18, 6, 'Logitech', NULL, 1, 'Mouse Logitech G502 HERO.'),
(2, 'Teclado Mecánico Redragon Kumara', 'teclado-redragon-kumara', 'Teclado mecánico Redragon Kumara K552, switches red, retroiluminación RGB, formato TKL.', 39990, 14, 5, 'Redragon', NULL, 1, 'Teclado Redragon Kumara RGB.'),
(2, 'Audífonos Sony WI-C100 Negro', 'audifonos-sony-wi-c100', 'Audífonos inalámbricos Sony WI-C100 in-ear con banda para el cuello, Bluetooth 5.0, hasta 25 horas de batería, IPX4 resistente a salpicaduras, DSEE y micrófono integrado.', 29990, 20, 6, 'Sony', NULL, 1, 'Audífonos inalámbricos Sony WI-C100 25h IPX4.'),
(2, 'Audífonos Sony WF-C510 True Wireless Negro', 'audifonos-sony-wf-c510-true-wireless', 'Audífonos True Wireless Sony WF-C510, Bluetooth 5.3, hasta 22 horas de batería total, modo sonido ambiente, IPX4, micrófono integrado y carga rápida.', 44990, 18, 6, 'Sony', NULL, 1, 'Audífonos True Wireless Sony WF-C510 22h IPX4.'),
(2, 'Audífonos Xiaomi Redmi Buds 8 Active Negro', 'audifonos-xiaomi-redmi-buds-8-active', 'Audífonos TWS Xiaomi Redmi Buds 8 Active, diseño semi in-ear ergonómico, driver 14.2 mm con diafragma de titanio, reducción de ruido AI, Bluetooth 5.4, hasta 7 horas por carga.', 34990, 25, 8, 'Xiaomi', NULL, 1, 'Audífonos TWS Xiaomi Redmi Buds 8 Active.'),
(2, 'Audífonos Xiaomi Redmi Buds 8 Lite Negro', 'audifonos-xiaomi-redmi-buds-8-lite', 'Audífonos TWS Xiaomi Redmi Buds 8 Lite con cancelación activa de ruido hasta 42 dB, driver 12.4 mm diafragma de titanio, Bluetooth 5.4, hasta 36 horas de batería total con carga rápida.', 24990, 30, 8, 'Xiaomi', NULL, 1, 'Audífonos TWS Xiaomi Redmi Buds 8 Lite con ANC 42dB.'),
(2, 'Audífonos Gamer JBL Quantum 100 V2 Negro', 'audifonos-gamer-jbl-quantum-100-v2', 'Audífonos gamer JBL Quantum 100 V2 over-ear con cable 3.5 mm, tecnología JBL QuantumSOUND Signature, micrófono direccional desmontable con silenciador, almohadillas de espuma viscoelástica, compatibles con PC, PS, Xbox y Switch.', 24990, 22, 6, 'JBL', NULL, 1, 'Headset gamer JBL Quantum 100 V2 over-ear 3.5mm.'),
(2, 'Audífonos Gamer Razer BlackShark V2 X Negro', 'audifonos-gamer-razer-blackshark-v2-x', 'Headset gamer Razer BlackShark V2 X con drivers de 50 mm con diafragma de titanio, micrófono cardioide HyperClear, sonido envolvente 7.1, cancelación pasiva de ruido, conexión 3.5 mm.', 39990, 16, 5, 'Razer', NULL, 1, 'Headset gamer Razer BlackShark V2 X 50mm 7.1.'),
(2, 'Audífonos Gamer Razer Barracuda X Inalámbrico Negro', 'audifonos-gamer-razer-barracuda-x', 'Headset gamer inalámbrico Razer Barracuda X con conexión USB-C 2.4 GHz y Bluetooth 5.2, drivers TriForce de 40 mm, micrófono cardioide desmontable, hasta 50 horas de batería.', 89990, 12, 4, 'Razer', NULL, 1, 'Headset inalámbrico Razer Barracuda X 50h batería.'),
(2, 'Mouse Gamer Corsair IronClaw RGB Alámbrico Negro', 'mouse-gamer-corsair-ironclaw-rgb', 'Mouse gamer alámbrico Corsair IronClaw RGB, sensor óptico hasta 18.000 DPI, diseño ergonómico para manos grandes, 7 botones programables, iluminación RGB por zonas.', 49990, 15, 5, 'Corsair', NULL, 1, 'Mouse gamer Corsair IronClaw RGB 18000 DPI.'),
(2, 'Mouse Vertical Kensington Pro Fit EQ Ergo Inalámbrico Negro', 'mouse-vertical-kensington-pro-fit-eq-ergo', 'Mouse vertical ergonómico inalámbrico Kensington Pro Fit EQ Ergo recargable, diseño vertical reduce tensión en muñeca, receptor USB nano, hasta 4 meses de batería.', 49990, 10, 4, 'Kensington', NULL, 1, 'Mouse vertical ergonómico Kensington Pro Fit EQ Ergo recargable.'),
(2, 'Mouse Gamer Inalámbrico Logitech G703 LightSpeed Negro', 'mouse-gamer-logitech-g703-lightspeed', 'Mouse gamer inalámbrico Logitech G703 LightSpeed, sensor HERO 25K, hasta 25.600 DPI, 6 botones programables, iluminación RGB, compatible con POWERPLAY, hasta 60 horas de batería.', 69990, 14, 5, 'Logitech', NULL, 1, 'Mouse gamer inalámbrico Logitech G703 LightSpeed HERO 25K.'),
(2, 'Mouse Alámbrico Logitech M90 Negro', 'mouse-alambrico-logitech-m90', 'Mouse alámbrico Logitech M90, sensor óptico 1000 DPI, cable USB de 1.8 m, diseño ambidiestro, plug and play sin drivers.', 7990, 40, 12, 'Logitech', NULL, 1, 'Mouse alámbrico Logitech M90 ambidiestro 1000 DPI.'),
(2, 'Teclado Gamer HyperX Alloy Core RGB Negro', 'teclado-gamer-hyperx-alloy-core-rgb', 'Teclado gamer HyperX Alloy Core RGB de membrana, retroiluminación RGB por tecla, resistente a derrames, teclas multimedia dedicadas, cable USB trenzado desmontable.', 34990, 18, 6, 'HyperX', NULL, 1, 'Teclado gamer HyperX Alloy Core RGB membrana anti-derrames.'),
(2, 'Mousepad Trust M Negro', 'mousepad-trust-m', 'Mousepad Trust M de tamaño mediano, superficie textil optimizada para mouse óptico y láser, base antideslizante de goma, bordes cosidos.', 4990, 35, 10, 'Trust', NULL, 1, 'Mousepad Trust M superficie textil base antideslizante.'),
(2, 'Webcam Gear Enfoque Automático 2K', 'webcam-gear-enfoque-automatico-2k', 'Webcam Gear 2K con enfoque automático, micrófono integrado con reducción de ruido, campo visual 90°, compatible con Windows, macOS y Linux, conexión USB.', 29990, 14, 5, 'Gear', NULL, 1, 'Webcam Gear 2K enfoque automático micrófono integrado.'),
(2, 'Webcam Trust Teza UHD 4K', 'webcam-trust-teza-uhd-4k', 'Webcam Trust Teza UHD 4K con micrófono estéreo integrado, enfoque automático, campo visual 90°, compatible con Windows y macOS, ideal para streaming y videollamadas profesionales.', 49990, 10, 4, 'Trust', NULL, 1, 'Webcam Trust Teza 4K UHD estéreo enfoque automático.'),
(2, 'Webcam Logitech C920 Full HD', 'webcam-logitech-c920', 'Webcam Logitech C920 Full HD 1080p con micrófono estéreo.', 49990, 18, 5, 'Logitech', NULL, 1, 'Webcam Logitech C920.'),
(2, 'Micrófono HyperX QuadCast', 'microfono-hyperx-quadcast', 'Micrófono USB HyperX QuadCast con filtro antipop y luz RGB.', 99990, 10, 3, 'HyperX', NULL, 1, 'Micrófono HyperX QuadCast.'),
(2, 'Mousepad Gamer XL', 'mousepad-gamer-xl', 'Mousepad gamer XL 80x30cm, base antideslizante, superficie suave.', 12990, 30, 8, 'QuadCore', NULL, 1, 'Mousepad gamer XL.');

-- ============================================================
-- PRODUCTOS — Cables
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(3, 'Cable HDMI 2.1 Belkin 8K 2m', 'cable-hdmi-2-1-belkin-8k-2m', 'Cable HDMI 2.1 Belkin, soporte 8K@60Hz / 4K@120Hz, 2 metros, blindado.', 8990, 50, 15, 'Belkin', NULL, 1, 'Cable HDMI 2.1 Belkin 8K 2m.'),
(3, 'Cable SATA III Pack x3', 'cable-sata-iii-pack-x3', 'Pack de 3 cables SATA III 6Gbps, conectores rectos con seguro metálico.', 4990, 60, 20, 'QuadCore', NULL, 1, 'Pack 3 cables SATA III.'),
(3, 'Cable DisplayPort 1.4 2m', 'cable-displayport-14-2m', 'Cable DisplayPort 1.4, 2 metros, soporta 4K 144Hz.', 9990, 30, 8, 'QuadCore', NULL, 1, 'Cable DisplayPort 1.4 2m.'),
(3, 'Cable USB-C a USB-C 100W 2m', 'cable-usbc-100w-2m', 'Cable USB-C a USB-C 2m, carga rápida hasta 100W y datos.', 8990, 35, 10, 'QuadCore', NULL, 1, 'Cable USB-C 100W 2m.'),
(3, 'Cable de Red Cat6 3m', 'cable-red-cat6-3m', 'Cable de red Ethernet Cat6 3 metros, hasta 1 Gbps.', 4990, 50, 15, 'QuadCore', NULL, 1, 'Cable de red Cat6 3m.'),
(3, 'Cable Programación USB para Baofeng UV-5R', 'cable-programacion-usb-baofeng-uv5r', 'Cable USB de programación para radio Baofeng UV-5R, incluye driver y software CD.', 9990, 25, 8, 'Genérica', NULL, 1, 'Cable programación USB Baofeng UV-5R.'),
(3, 'Cable Transferencia USB 2.0 Alta Velocidad Laplink', 'cable-transferencia-usb-laplink', 'Cable Laplink de transferencia de datos USB 2.0 de alta velocidad entre PC y PC.', 24990, 15, 5, 'Laplink', NULL, 1, 'Cable transferencia USB Laplink.'),
(3, 'Cable HDMI 2.1 Fibra Óptica 8K 10m Optixel', 'cable-hdmi-fibra-optica-8k-10m-optixel', 'Cable HDMI 2.1 de fibra óptica Optixel, 10 metros, soporte 8K@60Hz / 4K@120Hz.', 69990, 10, 3, 'Optixel', NULL, 1, 'Cable HDMI fibra óptica 8K 10m Optixel.');

-- ============================================================
-- PRODUCTOS — Herramientas
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(4, 'Kit Destornilladores Precisión 32 pzs', 'kit-destornilladores-precision-32', 'Kit de precisión 32 piezas para electrónica y notebooks, puntas magnéticas.', 14990, 22, 8, 'Arctic', NULL, 1, 'Kit destornilladores precisión 32 pzs.'),
(4, 'Estación de Soldadura 60W', 'estacion-soldadura-60w', 'Estación de soldadura 60W con control de temperatura ajustable y soporte.', 44990, 9, 3, 'QuadCore', NULL, 1, 'Estación de soldadura 60W.'),
(4, 'Multímetro Digital', 'multimetro-digital', 'Multímetro digital con medición de voltaje, corriente y continuidad.', 19990, 16, 5, 'QuadCore', NULL, 1, 'Multímetro digital.'),
(4, 'Tester de Cables de Red RJ45', 'tester-cables-red-rj45', 'Tester de cables de red RJ45/RJ11, verifica continuidad y conexiones.', 14990, 14, 4, 'QuadCore', NULL, 1, 'Tester cables de red.');

-- ============================================================
-- PRODUCTOS — Repuestos
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(5, 'Pasta Térmica Arctic MX-4 4g', 'pasta-termica-arctic-mx-4-4g', 'Pasta térmica Arctic MX-4 de 4 gramos, alta conductividad, sin metales.', 7990, 40, 15, 'Arctic', NULL, 1, 'Pasta térmica Arctic MX-4 4g.'),
(5, 'Kit Limpieza Aire Comprimido', 'kit-limpieza-aire-comprimido', 'Aire comprimido para limpieza de componentes y teclados, 400ml.', 9990, 35, 12, 'QuadCore', NULL, 1, 'Kit limpieza aire comprimido.'),
(5, 'Botón Power/Reset Universal', 'boton-power-reset-universal', 'Repuesto botón Power/Reset universal para gabinetes con cableado incluido.', 3990, 45, 15, 'QuadCore', NULL, 1, 'Botón Power/Reset universal.'),
(5, 'Bisagras Plásticas Notebook (par)', 'bisagras-plasticas-notebook', 'Par de bisagras plásticas de repuesto para notebooks, alta resistencia.', 6990, 28, 10, 'QuadCore', NULL, 1, 'Bisagras plásticas notebook (par).');

-- ============================================================
-- PRODUCTOS — Servicios Técnicos
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(6, 'Diagnóstico Técnico Profesional', 'diagnostico-tecnico-profesional', 'Servicio de diagnóstico técnico profesional de equipos, con informe detallado.', 19990, 99, 1, 'QuadCore', NULL, 1, 'Diagnóstico técnico profesional.');

-- ============================================================
-- PRODUCTOS — Notebooks
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(7, 'Notebook ASUS VivoBook 15 i5', 'notebook-asus-vivobook-15-i5', 'Notebook ASUS VivoBook 15.6" FHD, Intel Core i5, 8GB RAM, 512GB SSD, Windows 11.', 549990, 14, 4, 'ASUS', NULL, 1, 'Notebook ASUS VivoBook 15 i5.'),
(7, 'Notebook Lenovo IdeaPad Gaming 3', 'notebook-lenovo-ideapad-gaming-3', 'Notebook gamer Lenovo IdeaPad Gaming 3, Ryzen 7, 16GB RAM, RTX 3050, 512GB SSD.', 729990, 9, 3, 'Lenovo', NULL, 1, 'Lenovo IdeaPad Gaming 3 RTX 3050.'),
(7, 'Notebook HP Pavilion 14', 'notebook-hp-pavilion-14', 'Notebook HP Pavilion 14" FHD, Intel Core i5, 16GB RAM, 512GB SSD.', 489990, 11, 4, 'HP', NULL, 1, 'Notebook HP Pavilion 14 i5.'),
(7, 'MacBook Air M2 13"', 'macbook-air-m2-13', 'Apple MacBook Air 13" chip M2, 8GB RAM, 256GB SSD, macOS.', 1199990, 6, 2, 'Apple', NULL, 1, 'MacBook Air M2 13 pulgadas.'),
(7, 'Notebook Acer Nitro 5 RTX 4050', 'notebook-acer-nitro-5-rtx4050', 'Notebook gamer Acer Nitro 5, i5, 16GB, RTX 4050, 512GB SSD, 144Hz.', 799990, 8, 3, 'Acer', NULL, 1, 'Acer Nitro 5 RTX 4050.'),
(7, 'Notebook Dell Inspiron 15', 'notebook-dell-inspiron-15', 'Notebook Dell Inspiron 15.6" FHD, i7, 16GB RAM, 512GB SSD.', 629990, 10, 3, 'Dell', NULL, 1, 'Dell Inspiron 15 i7.'),
(7, 'Notebook ASUS TUF Gaming F15', 'notebook-asus-tuf-f15', 'Notebook ASUS TUF Gaming F15, i7, 16GB, RTX 4060, 1TB SSD, 144Hz.', 699990, 7, 2, 'ASUS', NULL, 1, 'ASUS TUF Gaming F15.'),
(7, 'Notebook Lenovo ThinkPad E14', 'notebook-lenovo-thinkpad-e14', 'Notebook Lenovo ThinkPad E14, i5, 16GB, 512GB SSD, ideal profesional.', 589990, 9, 3, 'Lenovo', NULL, 1, 'Lenovo ThinkPad E14.');

-- ============================================================
-- PRODUCTOS — Computadores
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(8, 'PC Gamer QuadCore Ryzen 5 RTX 4060', 'pc-gamer-quadcore-ryzen5-rtx4060', 'PC gamer armado: Ryzen 5 7600, 16GB DDR5, RTX 4060, SSD 1TB NVMe. Listo para jugar.', 899990, 7, 2, 'QuadCore', NULL, 1, 'PC Gamer Ryzen 5 RTX 4060.'),
(8, 'PC Oficina QuadCore Intel i3', 'pc-oficina-quadcore-i3', 'PC de oficina: Intel Core i3, 8GB RAM, SSD 480GB, ideal para trabajo y estudio.', 369990, 20, 5, 'QuadCore', NULL, 1, 'PC Oficina Intel i3.'),
(8, 'PC Gamer Pro QuadCore i7 RTX 4070', 'pc-gamer-pro-quadcore-i7-rtx4070', 'PC gamer alta gama: Intel Core i7, 32GB DDR5, RTX 4070, SSD 2TB NVMe.', 1499990, 4, 2, 'QuadCore', NULL, 1, 'PC Gamer Pro i7 RTX 4070.');

-- ============================================================
-- PRODUCTOS — Consolas
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(9, 'Lenovo Legion Go S 8ARP1 512GB', 'lenovo-legion-go-s-512gb', 'Consola portátil Lenovo Legion Go S 8ARP1, 512GB SSD, pantalla 8" WQXGA, Windows 11, procesador AMD Ryzen Z2 Go.', 649990, 6, 2, 'Lenovo', NULL, 1, 'Lenovo Legion Go S 512GB.'),
(9, 'Nintendo Switch OLED White + Membresía 12 Meses + Super Mario Bros Wonder', 'nintendo-switch-oled-bundle-mario-wonder', 'Consola Nintendo Switch OLED edición blanca, incluye membresía Nintendo Switch Online 12 meses y juego Super Mario Bros Wonder.', 429990, 10, 3, 'Nintendo', NULL, 1, 'Nintendo Switch OLED bundle Mario Wonder.'),
(9, 'Nintendo Switch 2 Black', 'nintendo-switch-2-black', 'Consola Nintendo Switch 2, pantalla 8.4" LCD, 256GB SSD, retrocompatible con Switch, nuevo procesador.', 479990, 8, 3, 'Nintendo', NULL, 1, 'Nintendo Switch 2 Black.'),
(9, 'Nintendo Switch 2 Black + Mario Kart World', 'nintendo-switch-2-mario-kart-world', 'Consola Nintendo Switch 2 Black con bundle de Mario Kart World incluido, listo para jugar.', 549990, 6, 2, 'Nintendo', NULL, 1, 'Nintendo Switch 2 Mario Kart World.'),
(9, 'Nintendo Switch Joy-Con Neon + Super Mario Bros Wonder', 'nintendo-switch-joycon-neon-mario-wonder', 'Bundle Nintendo Switch Joy-Con color Neon, incluye Super Mario Bros Wonder y accesorios.', 429990, 8, 3, 'Nintendo', NULL, 1, 'Nintendo Switch Joy-Con Neon Mario Wonder.'),
(9, 'PlayStation 5 Slim', 'playstation-5-slim', 'Consola Sony PlayStation 5 Slim edición disco, 1TB, control DualSense incluido.', 599990, 10, 3, 'Sony', NULL, 1, 'PlayStation 5 Slim 1TB.'),
(9, 'Xbox Series X', 'xbox-series-x', 'Consola Microsoft Xbox Series X, 1TB SSD, 4K nativo, control inalámbrico.', 649990, 8, 3, 'Microsoft', NULL, 1, 'Xbox Series X 1TB.'),
(9, 'Nintendo Switch OLED', 'nintendo-switch-oled', 'Consola Nintendo Switch modelo OLED, pantalla 7" vibrante, 64GB.', 379990, 13, 4, 'Nintendo', NULL, 1, 'Nintendo Switch OLED.'),
(9, 'PlayStation 5 Edición God of War Ragnarök', 'ps5-god-of-war-ragnarok', 'Consola PS5 bundle God of War Ragnarök, incluye el juego en formato digital.', 649990, 6, 2, 'Sony', NULL, 1, 'PS5 God of War Ragnarök.'),
(9, 'PlayStation 5 Digital Edition', 'ps5-digital-edition', 'Consola PS5 Digital Edition (sin lector), 1TB, control DualSense.', 549990, 9, 3, 'Sony', NULL, 1, 'PS5 Digital Edition.'),
(9, 'Xbox Series S 512GB', 'xbox-series-s-512gb', 'Consola Xbox Series S 512GB, digital, compacta, 1440p.', 379990, 12, 4, 'Microsoft', NULL, 1, 'Xbox Series S 512GB.'),
(9, 'Nintendo Switch OLED Edición Zelda', 'switch-oled-zelda', 'Nintendo Switch OLED edición especial The Legend of Zelda: TOTK.', 419990, 7, 2, 'Nintendo', NULL, 1, 'Switch OLED edición Zelda.'),
(9, 'Steam Deck OLED 512GB', 'steam-deck-oled-512gb', 'Consola portátil Valve Steam Deck OLED 512GB, ejecuta tu librería de Steam.', 749990, 5, 2, 'Valve', NULL, 1, 'Steam Deck OLED 512GB.'),
(9, 'ASUS ROG Ally Z1 Extreme', 'asus-rog-ally-z1', 'Consola portátil ASUS ROG Ally Z1 Extreme, Windows 11, pantalla 120Hz.', 829990, 4, 2, 'ASUS', NULL, 1, 'ROG Ally Z1 Extreme.');

-- ============================================================
-- PRODUCTOS — Celulares
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(10, 'iPhone 15 128GB', 'iphone-15-128gb', 'Apple iPhone 15 128GB, chip A16 Bionic, cámara 48MP, USB-C.', 899990, 12, 4, 'Apple', NULL, 1, 'iPhone 15 128GB.'),
(10, 'Samsung Galaxy S24 256GB', 'samsung-galaxy-s24-256gb', 'Samsung Galaxy S24 256GB, pantalla AMOLED 120Hz, Galaxy AI.', 849990, 9, 3, 'Samsung', NULL, 1, 'Samsung Galaxy S24 256GB.'),
(10, 'Xiaomi Redmi Note 13 Pro', 'xiaomi-redmi-note-13-pro', 'Xiaomi Redmi Note 13 Pro, 256GB, cámara 200MP, carga rápida 67W.', 229990, 22, 6, 'Xiaomi', NULL, 1, 'Xiaomi Redmi Note 13 Pro.'),
(10, 'Motorola Moto G54 5G', 'motorola-moto-g54-5g', 'Motorola Moto G54 5G, 128GB, pantalla 120Hz, batería 5000mAh.', 169990, 18, 5, 'Motorola', NULL, 1, 'Motorola Moto G54 5G.'),
(10, 'iPhone 15 Pro 256GB', 'iphone-15-pro-256gb', 'Apple iPhone 15 Pro 256GB, titanio, chip A17 Pro, cámara 48MP.', 1299990, 7, 2, 'Apple', NULL, 1, 'iPhone 15 Pro 256GB.'),
(10, 'Samsung Galaxy A55 128GB', 'samsung-galaxy-a55-128gb', 'Samsung Galaxy A55 5G 128GB, pantalla Super AMOLED 120Hz.', 349990, 15, 5, 'Samsung', NULL, 1, 'Samsung Galaxy A55.'),
(10, 'Google Pixel 8 128GB', 'google-pixel-8-128gb', 'Google Pixel 8 128GB, cámara con IA, Android puro y actualizado.', 699990, 8, 3, 'Google', NULL, 1, 'Google Pixel 8.'),
(10, 'Huawei Pura 80 256GB Frosted Black', 'huawei-pura-80-256gb', 'Huawei Pura 80 256GB / 12GB RAM, pantalla OLED, cámara avanzada, color Frosted Black.', 699990, 8, 3, 'Huawei', NULL, 1, 'Huawei Pura 80 256GB Frosted Black.'),
(10, 'Samsung Galaxy S25 256GB Navy', 'samsung-galaxy-s25-256gb-navy', 'Samsung Galaxy S25 S931 256GB / 12GB RAM, pantalla Dynamic AMOLED, color Navy.', 849990, 10, 3, 'Samsung', NULL, 1, 'Samsung Galaxy S25 256GB Navy.'),
(10, 'Samsung Galaxy A17 5G 256GB Black', 'samsung-galaxy-a17-5g-256gb-black', 'Samsung Galaxy A17 5G A176 256GB / 8GB RAM, pantalla Super AMOLED, batería larga duración.', 229990, 15, 5, 'Samsung', NULL, 1, 'Samsung Galaxy A17 5G 256GB Black.'),
(10, 'Motorola Edge 60 Pro 512GB Pantone Shadow', 'motorola-edge-60-pro-512gb-shadow', 'Motorola Edge 60 Pro 512GB / 12GB RAM, pantalla pOLED 165Hz, carga TurboPower 125W.', 599990, 10, 3, 'Motorola', NULL, 1, 'Motorola Edge 60 Pro 512GB.'),
(10, 'Samsung Galaxy S26 256GB Black', 'samsung-galaxy-s26-256gb-black', 'Samsung Galaxy S26 S947B 256GB / 12GB RAM, nuevo procesador, cámara mejorada con IA.', 949990, 8, 3, 'Samsung', NULL, 1, 'Samsung Galaxy S26 256GB Black.'),
(10, 'Xiaomi Poco M8 256GB Silver', 'xiaomi-poco-m8-256gb-silver', 'Xiaomi Poco M8 256GB / 8GB RAM, pantalla AMOLED 120Hz, procesador MediaTek, color Silver.', 179990, 20, 6, 'Xiaomi', NULL, 1, 'Xiaomi Poco M8 256GB Silver.'),
(10, 'Samsung Galaxy S25 FE 256GB Jet Black', 'samsung-galaxy-s25-fe-256gb-black', 'Samsung Galaxy S25 FE S731 256GB / 8GB RAM, pantalla Dynamic AMOLED, triple cámara.', 549990, 12, 4, 'Samsung', NULL, 1, 'Samsung Galaxy S25 FE Jet Black.'),
(10, 'ZTE Nubia Air 256GB Titanium Black', 'zte-nubia-air-256gb-black', 'ZTE Nubia Air 256GB / 8GB RAM, pantalla AMOLED, diseño ultradelgado, color Titanium Black.', 299990, 10, 3, 'ZTE', NULL, 1, 'ZTE Nubia Air 256GB Titanium Black.'),
(10, 'iPhone 14 128GB', 'iphone-14-128gb', 'Apple iPhone 14 128GB, chip A15 Bionic, cámara dual 12MP.', 749990, 10, 3, 'Apple', NULL, 1, 'iPhone 14 128GB.'),
(10, 'Samsung Galaxy S23 FE 256GB', 'samsung-galaxy-s23-fe', 'Samsung Galaxy S23 FE 256GB, pantalla AMOLED 120Hz, triple cámara.', 549990, 11, 3, 'Samsung', NULL, 1, 'Galaxy S23 FE.'),
(10, 'Motorola Edge 40 256GB', 'motorola-edge-40', 'Motorola Edge 40 256GB, pantalla pOLED 144Hz, carga 68W.', 399990, 13, 4, 'Motorola', NULL, 1, 'Motorola Edge 40.');

-- ============================================================
-- PRODUCTOS — TV y Audio
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(11, 'Smart TV Samsung 55" Crystal 4K', 'smart-tv-samsung-55-crystal-4k', 'Smart TV Samsung 55" Crystal UHD 4K, Tizen, HDR, control por voz.', 399990, 8, 3, 'Samsung', NULL, 1, 'Smart TV Samsung 55 4K.'),
(11, 'Audífonos Sony WH-1000XM5', 'audifonos-sony-wh-1000xm5', 'Audífonos Sony WH-1000XM5 con cancelación de ruido líder, 30h de batería.', 299990, 11, 4, 'Sony', NULL, 1, 'Sony WH-1000XM5 noise cancelling.'),
(11, 'Parlante JBL Charge 5', 'parlante-jbl-charge-5', 'Parlante portátil JBL Charge 5, resistente al agua IP67, 20h de batería.', 119990, 16, 5, 'JBL', NULL, 1, 'Parlante JBL Charge 5.'),
(11, 'Smart TV LG 50" 4K UHD', 'smart-tv-lg-50-4k', 'Smart TV LG 50" 4K UHD, webOS, ThinQ AI, HDR.', 349990, 8, 3, 'LG', NULL, 1, 'Smart TV LG 50 4K.'),
(11, 'Soundbar Samsung B550 2.1', 'soundbar-samsung-b550', 'Barra de sonido Samsung B550 2.1ch con subwoofer inalámbrico.', 199990, 10, 3, 'Samsung', NULL, 1, 'Soundbar Samsung B550.'),
(11, 'Audífonos Bose QuietComfort', 'audifonos-bose-quietcomfort', 'Audífonos Bose QuietComfort con cancelación de ruido y gran confort.', 249990, 9, 3, 'Bose', NULL, 1, 'Bose QuietComfort.'),
(11, 'AirPods Pro 2da Generación', 'airpods-pro-2', 'Apple AirPods Pro 2da gen, cancelación activa de ruido, estuche USB-C.', 229990, 14, 4, 'Apple', NULL, 1, 'AirPods Pro 2.');

-- ============================================================
-- PRODUCTOS — Monitores
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(12, 'Monitor Gamer LG UltraGear 27" 165Hz', 'monitor-lg-ultragear-27-165hz', 'Monitor gamer LG UltraGear 27" QHD, 165Hz, 1ms, IPS, FreeSync.', 219990, 10, 3, 'LG', NULL, 1, 'Monitor LG UltraGear 27 165Hz.'),
(12, 'Monitor Samsung Essential 24"', 'monitor-samsung-essential-24', 'Monitor Samsung 24" FHD, panel IPS, bordes ultrafinos, ideal oficina.', 99990, 19, 5, 'Samsung', NULL, 1, 'Monitor Samsung 24 FHD.'),
(12, 'Monitor AOC Gaming 27" 144Hz', 'monitor-aoc-gaming-27-144hz', 'Monitor gamer AOC 27" FHD, 144Hz, 1ms, FreeSync Premium.', 159990, 12, 4, 'AOC', NULL, 1, 'Monitor AOC 27 144Hz.'),
(12, 'Monitor TCL 25G64 25" FHD 180Hz', 'monitor-tcl-25g64', 'Monitor gamer TCL 25G64 25" FHD, panel VA, 180Hz, 1ms, FreeSync, ideal esports.', 129990, 12, 4, 'TCL', NULL, 1, 'Monitor TCL 25G64 180Hz.'),
(12, 'Monitor MSI G242L E14 24" FHD 180Hz', 'monitor-msi-g242l-e14', 'Monitor gamer MSI G242L E14 24" FHD, panel IPS, 180Hz, 1ms, FreeSync Premium.', 149990, 10, 3, 'MSI', NULL, 1, 'Monitor MSI G242L E14 180Hz.'),
(12, 'Monitor ASUS TUF VG27AQ5A 27" QHD 180Hz', 'monitor-asus-tuf-vg27aq5a', 'Monitor gamer ASUS TUF VG27AQ5A 27" QHD, panel IPS, 180Hz, 1ms, G-Sync Compatible.', 249990, 8, 3, 'ASUS', NULL, 1, 'Monitor ASUS TUF VG27AQ5A QHD.'),
(12, 'Monitor Xiaomi A27Qi 2026 27" QHD 100Hz', 'monitor-xiaomi-a27qi-2026', 'Monitor Xiaomi A27Qi 2026 27" QHD, panel IPS, 100Hz, diseño ultra delgado.', 169990, 15, 5, 'Xiaomi', NULL, 1, 'Monitor Xiaomi A27Qi 2026 QHD.'),
(12, 'Monitor Samsung Odyssey G5 G50SF 27" QHD 180Hz', 'monitor-samsung-odyssey-g5-g50sf', 'Monitor gamer Samsung Odyssey G5 G50SF 27" QHD, panel IPS, 180Hz, 1ms, FreeSync.', 219990, 10, 3, 'Samsung', NULL, 1, 'Monitor Samsung Odyssey G5 G50SF.'),
(12, 'Monitor ASUS ROG Strix OLED XG27AQDMES 27" QHD 240Hz', 'monitor-asus-rog-oled-xg27aqdmes', 'Monitor gamer ASUS ROG Strix OLED XG27AQDMES 27" QHD, panel OLED, 240Hz, 0.03ms.', 899990, 4, 2, 'ASUS', NULL, 1, 'Monitor ASUS ROG OLED 240Hz.'),
(12, 'Monitor LG UltraGear 24GS60F-B 24" FHD 180Hz', 'monitor-lg-ultragear-24gs60f', 'Monitor gamer LG UltraGear 24GS60F-B 24" FHD, panel IPS, 180Hz, 1ms, FreeSync.', 139990, 14, 4, 'LG', NULL, 1, 'Monitor LG UltraGear 24GS60F.'),
(12, 'Monitor BenQ Zowie XL2566X 25" FHD 400Hz', 'monitor-benq-zowie-xl2566x', 'Monitor gamer BenQ Zowie XL2566X 25" FHD, panel TN, 400Hz, DyAc+ para esports profesional.', 599990, 5, 2, 'BenQ', NULL, 1, 'Monitor BenQ Zowie XL2566X 400Hz.'),
(12, 'Monitor Xiaomi G27Qi 2026 27" QHD 180Hz', 'monitor-xiaomi-g27qi-2026', 'Monitor gamer Xiaomi G27Qi 2026 27" QHD, panel IPS, 180Hz, 1ms, FreeSync Premium.', 199990, 12, 4, 'Xiaomi', NULL, 1, 'Monitor Xiaomi G27Qi 2026 QHD 180Hz.'),
(12, 'Monitor LG UltraFine 32" 4K', 'monitor-lg-ultrafine-32-4k', 'Monitor LG UltraFine 32" UHD 4K IPS, HDR10, ideal diseño y multimedia.', 299990, 9, 3, 'LG', NULL, 1, 'Monitor LG 32 4K.'),
(12, 'Monitor Samsung Odyssey G5 27"', 'monitor-samsung-odyssey-g5-27', 'Monitor gamer Samsung Odyssey G5 27" QHD 165Hz curvo.', 279990, 10, 3, 'Samsung', NULL, 1, 'Samsung Odyssey G5 27.'),
(12, 'Monitor Gamer Ultrawide 34" 144Hz', 'monitor-ultrawide-34-144hz', 'Monitor ultrawide 34" QHD 144Hz, inmersión total para juegos y trabajo.', 499990, 6, 2, 'LG', NULL, 1, 'Monitor ultrawide 34 144Hz.');

-- ============================================================
-- PRODUCTOS — Tarjetas de Video
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(13, 'Tarjeta de Video NVIDIA RTX 4060 8GB', 'gpu-rtx-4060-8gb', 'GPU NVIDIA GeForce RTX 4060 8GB GDDR6, DLSS 3, ideal 1080p.', 379990, 9, 3, 'NVIDIA', NULL, 1, 'RTX 4060 8GB.'),
(13, 'Tarjeta de Video NVIDIA RTX 4070 12GB', 'gpu-rtx-4070-12gb', 'GPU RTX 4070 12GB GDDR6X, excelente para 1440p con ray tracing.', 619990, 6, 2, 'NVIDIA', NULL, 1, 'RTX 4070 12GB.'),
(13, 'Tarjeta de Video RTX 4070 Ti Super', 'gpu-rtx-4070-ti-super', 'GPU RTX 4070 Ti Super 16GB, gaming 1440p/4K de alto rendimiento.', 899990, 4, 2, 'NVIDIA', NULL, 1, 'RTX 4070 Ti Super.'),
(13, 'Tarjeta de Video AMD RX 7800 XT 16GB', 'gpu-rx-7800-xt-16gb', 'GPU AMD Radeon RX 7800 XT 16GB GDDR6, gran relación precio/rendimiento.', 559990, 7, 2, 'AMD', NULL, 1, 'RX 7800 XT 16GB.'),
(13, 'Tarjeta de Video RTX 4060 Ti 16GB', 'gpu-rtx-4060-ti-16gb', 'GPU NVIDIA RTX 4060 Ti 16GB GDDR6, gaming 1080p/1440p con DLSS 3.', 479990, 8, 3, 'NVIDIA', NULL, 1, 'RTX 4060 Ti 16GB.'),
(13, 'Tarjeta de Video AMD RX 7600 8GB', 'gpu-rx-7600-8gb', 'GPU AMD Radeon RX 7600 8GB GDDR6, excelente para 1080p.', 329990, 11, 3, 'AMD', NULL, 1, 'RX 7600 8GB.');

-- ============================================================
-- PRODUCTOS — Almacenamiento
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(14, 'SSD Samsung 980 Pro 1TB NVMe', 'ssd-samsung-980-pro-1tb', 'SSD Samsung 980 Pro 1TB PCIe 4.0, lecturas hasta 7000 MB/s.', 89990, 22, 6, 'Samsung', NULL, 1, 'SSD Samsung 980 Pro 1TB.'),
(14, 'SSD Crucial MX500 2TB SATA', 'ssd-crucial-mx500-2tb', 'SSD Crucial MX500 2TB SATA 2.5", confiable para todo equipo.', 119990, 14, 4, 'Crucial', NULL, 1, 'SSD Crucial MX500 2TB.'),
(14, 'Disco Duro Seagate Barracuda 2TB', 'hdd-seagate-barracuda-2tb', 'HDD Seagate Barracuda 2TB 7200RPM para almacenamiento masivo.', 49990, 18, 5, 'Seagate', NULL, 1, 'HDD Seagate 2TB.'),
(14, 'Pendrive Kingston DataTraveler 128GB', 'pendrive-kingston-128gb', 'Pendrive Kingston USB 3.2 128GB, compacto y veloz.', 12990, 40, 10, 'Kingston', NULL, 1, 'Pendrive Kingston 128GB.'),
(14, 'MicroSD SanDisk Extreme 256GB', 'microsd-sandisk-256gb', 'Tarjeta microSD SanDisk Extreme 256GB A2 U3, ideal cámaras y consolas.', 29990, 30, 8, 'SanDisk', NULL, 1, 'MicroSD SanDisk 256GB.'),
(14, 'Disco Externo WD Elements 4TB', 'disco-externo-wd-4tb', 'Disco externo WD Elements 4TB USB 3.0, respaldo portátil.', 99990, 12, 4, 'Western Digital', NULL, 1, 'Disco externo WD 4TB.');

-- ============================================================
-- PRODUCTOS — Redes
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(15, 'Router TP-Link Archer AX1500 WiFi6', 'router-tplink-ax1500', 'Router TP-Link Archer AX1500 WiFi 6, doble banda, gigabit.', 49990, 16, 5, 'TP-Link', NULL, 1, 'Router TP-Link AX1500.'),
(15, 'Repetidor WiFi TP-Link RE315', 'repetidor-wifi-tplink-re315', 'Extensor de rango TP-Link RE315 AC1200, amplía tu señal WiFi.', 24990, 20, 6, 'TP-Link', NULL, 1, 'Repetidor TP-Link RE315.'),
(15, 'Switch TP-Link 8 Puertos Gigabit', 'switch-tplink-8-puertos', 'Switch TP-Link 8 puertos gigabit, plug and play, metálico.', 27990, 13, 4, 'TP-Link', NULL, 1, 'Switch 8 puertos gigabit.'),
(15, 'Tarjeta WiFi PCIe AX WiFi6', 'tarjeta-wifi-pcie-ax', 'Tarjeta de red PCIe WiFi 6 + Bluetooth 5.2 para PC de escritorio.', 22990, 17, 5, 'TP-Link', NULL, 1, 'Tarjeta WiFi PCIe AX.');

-- ============================================================
-- PRODUCTOS — Videojuegos
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(16, 'God of War Ragnarök PS5', 'juego-god-of-war-ragnarok-ps5', 'Videojuego God of War Ragnarök para PlayStation 5, edición física.', 49990, 25, 6, 'Sony', NULL, 1, 'God of War Ragnarök PS5.'),
(16, 'Mario Kart 8 Deluxe Switch', 'juego-mario-kart-8-switch', 'Videojuego Mario Kart 8 Deluxe para Nintendo Switch.', 44990, 28, 8, 'Nintendo', NULL, 1, 'Mario Kart 8 Deluxe.'),
(16, 'EA Sports FC 24 PS5', 'juego-ea-fc-24-ps5', 'Videojuego EA Sports FC 24 para PlayStation 5.', 39990, 30, 8, 'EA', NULL, 1, 'EA Sports FC 24 PS5.'),
(16, 'The Legend of Zelda TOTK Switch', 'juego-zelda-totk-switch', 'Videojuego The Legend of Zelda: Tears of the Kingdom para Switch.', 49990, 20, 6, 'Nintendo', NULL, 1, 'Zelda Tears of the Kingdom.'),
(16, 'Marvels Spider-Man 2 PS5', 'juego-spiderman-2-ps5', 'Videojuego Marvel''s Spider-Man 2 para PlayStation 5.', 54990, 18, 5, 'Sony', NULL, 1, 'Spider-Man 2 PS5.');

-- ============================================================
-- PRODUCTOS — Controles y Gaming
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(17, 'Control DualSense PS5', 'control-dualsense-ps5', 'Control inalámbrico Sony DualSense para PS5, retroalimentación háptica.', 64990, 20, 6, 'Sony', NULL, 1, 'Control DualSense PS5.'),
(17, 'Control Inalámbrico Xbox Series', 'control-xbox-series', 'Control inalámbrico Xbox Series, compatible con Xbox y PC.', 59990, 18, 6, 'Microsoft', NULL, 1, 'Control Xbox Series.'),
(17, 'Nintendo Switch Pro Controller', 'control-switch-pro', 'Control Nintendo Switch Pro, ergonómico, batería de larga duración.', 69990, 14, 4, 'Nintendo', NULL, 1, 'Switch Pro Controller.'),
(17, 'Control PowerA Enhanced (Alternativo)', 'control-powera-enhanced', 'Control alámbrico PowerA Enhanced para Switch/Xbox, opción económica.', 29990, 22, 6, 'PowerA', NULL, 1, 'Control PowerA Enhanced.'),
(17, 'Headset Gamer HyperX Cloud II', 'headset-hyperx-cloud-2', 'Audífonos gamer HyperX Cloud II con sonido envolvente 7.1 y micrófono.', 69990, 16, 5, 'HyperX', NULL, 1, 'Headset HyperX Cloud II.'),
(17, 'Volante Logitech G29 Driving Force', 'volante-logitech-g29', 'Volante Logitech G29 con pedales, force feedback, PS/PC.', 299990, 6, 2, 'Logitech', NULL, 1, 'Volante Logitech G29.');

-- ============================================================
-- PRODUCTOS — Bolsos y Fundas
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(18, 'Mochila Notebook QuadCore 15.6"', 'mochila-notebook-quadcore', 'Mochila para notebook hasta 15.6", acolchada, resistente al agua.', 29990, 25, 6, 'QuadCore', NULL, 1, 'Mochila notebook 15.6.'),
(18, 'Funda Notebook Neopreno 15"', 'funda-notebook-neopreno-15', 'Funda de neopreno para notebook 15", protección contra golpes.', 14990, 30, 8, 'QuadCore', NULL, 1, 'Funda notebook neopreno 15.'),
(18, 'Bolso de Transporte para Consola', 'bolso-consola', 'Bolso acolchado para transportar tu consola y accesorios.', 24990, 14, 4, 'QuadCore', NULL, 1, 'Bolso para consola.'),
(18, 'Mochila Notebook Hardley Impermeable 25L Gris', 'mochila-hardley-impermeable-25l-gris', 'Mochila para notebook Hardley 25L, impermeable, antirrobo, compartimento acolchado hasta 15.6".', 39990, 15, 5, 'Hardley', NULL, 1, 'Mochila notebook Hardley impermeable 25L gris.');

-- ============================================================
-- PRODUCTOS — Sillas y Escritorios
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(19, 'Silla Gamer QuadCore Pro', 'silla-gamer-quadcore-pro', 'Silla gamer ergonómica reclinable con soporte lumbar y apoyabrazos 2D.', 159990, 10, 3, 'QuadCore', NULL, 1, 'Silla gamer QuadCore Pro.'),
(19, 'Silla Ergonómica de Oficina', 'silla-ergonomica-oficina', 'Silla de oficina ergonómica con malla transpirable y altura ajustable.', 119990, 12, 4, 'QuadCore', NULL, 1, 'Silla ergonómica oficina.'),
(19, 'Escritorio Gamer 120cm', 'escritorio-gamer-120', 'Escritorio gamer 120cm con superficie amplia y pasacables.', 99990, 8, 3, 'QuadCore', NULL, 1, 'Escritorio gamer 120cm.'),
(19, 'Soporte de Monitor con Brazo', 'soporte-monitor-brazo', 'Soporte de monitor con brazo articulado, libera espacio en tu escritorio.', 34990, 15, 5, 'QuadCore', NULL, 1, 'Soporte monitor con brazo.');

-- ============================================================
-- PRODUCTOS — Limpieza y Cuidado
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(20, 'Aire Comprimido 400ml', 'aire-comprimido-400ml', 'Aire comprimido 400ml para limpiar teclados, ventiladores y componentes.', 9990, 40, 12, 'QuadCore', NULL, 1, 'Aire comprimido 400ml.'),
(20, 'Kit Limpieza de Pantallas', 'kit-limpieza-pantallas', 'Kit limpiador de pantallas: spray + paño microfibra, sin residuos.', 8990, 35, 10, 'QuadCore', NULL, 1, 'Kit limpieza pantallas.'),
(20, 'Pack 5 Paños de Microfibra', 'pack-panos-microfibra', 'Pack de 5 paños de microfibra premium para equipos y lentes.', 5990, 50, 15, 'QuadCore', NULL, 1, 'Pack 5 paños microfibra.'),
(20, 'Alcohol Isopropílico 250ml', 'alcohol-isopropilico-250ml', 'Alcohol isopropílico 99% 250ml para limpieza de electrónica.', 6990, 45, 12, 'QuadCore', NULL, 1, 'Alcohol isopropílico 250ml.');

-- ============================================================
-- PRODUCTOS — Rango medio adicional
-- ============================================================
INSERT INTO productos (id_categoria, nombre, slug, descripcion, precio, stock, stock_minimo, marca, imagen_url, activo, meta_descripcion) VALUES
(1,  'Procesador Intel Core i7-13700K', 'cpu-intel-i7-13700k', 'Procesador Intel Core i7-13700K, 16 núcleos, socket LGA1700, ideal gaming y creación.', 389990, 12, 4, 'Intel', NULL, 1, 'Intel Core i7-13700K.'),
(1,  'Procesador AMD Ryzen 7 7700X', 'cpu-amd-ryzen7-7700x', 'Procesador AMD Ryzen 7 7700X, 8 núcleos / 16 hilos, socket AM5.', 349990, 13, 4, 'AMD', NULL, 1, 'AMD Ryzen 7 7700X.'),
(1,  'Placa Madre ASUS ROG Strix Z790', 'placa-asus-rog-z790', 'Placa madre ASUS ROG Strix Z790, LGA1700, DDR5, PCIe 5.0, WiFi.', 289990, 9, 3, 'ASUS', NULL, 1, 'Placa ASUS ROG Z790.'),
(1,  'Memoria Corsair Dominator 64GB DDR5', 'ram-corsair-dominator-64gb', 'Kit Corsair Dominator 64GB (2x32GB) DDR5 6000MHz RGB.', 249990, 10, 3, 'Corsair', NULL, 1, 'RAM Corsair Dominator 64GB.'),
(1,  'Fuente Corsair RM850x 850W Gold', 'fuente-corsair-rm850x', 'Fuente Corsair RM850x 850W 80+ Gold, full modular, silenciosa.', 129990, 14, 4, 'Corsair', NULL, 1, 'Fuente Corsair RM850x 850W.'),
(2,  'Teclado Mecánico Logitech G Pro', 'teclado-logitech-g-pro', 'Teclado mecánico gamer Logitech G Pro, switches GX, RGB, compacto.', 119990, 12, 4, 'Logitech', NULL, 1, 'Teclado Logitech G Pro.');

-- ============================================================
-- RESEÑAS DE EJEMPLO
-- ============================================================
INSERT INTO resenas (id_producto, id_usuario, calificacion, comentario, aprobada) VALUES
(1, 2, 5, 'Excelente procesador, muy rápido para gaming y edición.', 1),
(1, 4, 4, 'Muy buena relación precio/rendimiento.', 1),
(2, 2, 5, 'La placa funcionó perfecta, fácil de instalar.', 1),
(3, 4, 4, 'Memoria veloz, sin problemas de compatibilidad.', 1),
(5, 2, 5, 'La pasta térmica bajó varios grados la temperatura.', 1);

-- ============================================================
-- OFERTAS (precio_anterior > precio → muestra % de descuento)
-- ============================================================
UPDATE productos SET precio_anterior = 279990 WHERE slug = 'procesador-intel-core-i5-13400f';
UPDATE productos SET precio_anterior = 269990 WHERE slug = 'procesador-amd-ryzen-5-7600x';
UPDATE productos SET precio_anterior = 74990  WHERE slug = 'memoria-kingston-fury-beast-16gb-ddr5';
UPDATE productos SET precio_anterior = 99990  WHERE slug = 'fuente-evga-supernova-750w-gold';
UPDATE productos SET precio_anterior = 189990 WHERE slug = 'placa-madre-asus-tuf-b650-plus';
UPDATE productos SET precio_anterior = 44990  WHERE slug = 'mouse-logitech-g502-hero';
UPDATE productos SET precio_anterior = 149990 WHERE slug = 'refrigeracion-corsair-icue-h100i';
UPDATE productos SET precio_anterior = 849990 WHERE slug = 'notebook-lenovo-ideapad-gaming-3';
UPDATE productos SET precio_anterior = 1299990 WHERE slug = 'macbook-air-m2-13';
UPDATE productos SET precio_anterior = 1049990 WHERE slug = 'pc-gamer-quadcore-ryzen5-rtx4060';
UPDATE productos SET precio_anterior = 649990 WHERE slug = 'playstation-5-slim';
UPDATE productos SET precio_anterior = 999990 WHERE slug = 'iphone-15-128gb';
UPDATE productos SET precio_anterior = 269990 WHERE slug = 'xiaomi-redmi-note-13-pro';
UPDATE productos SET precio_anterior = 499990 WHERE slug = 'smart-tv-samsung-55-crystal-4k';
UPDATE productos SET precio_anterior = 269990 WHERE slug = 'monitor-lg-ultragear-27-165hz';
UPDATE productos SET precio_anterior = 449990 WHERE slug = 'gpu-rtx-4060-8gb';
UPDATE productos SET precio_anterior = 729990 WHERE slug = 'gpu-rtx-4070-12gb';
UPDATE productos SET precio_anterior = 109990 WHERE slug = 'ssd-samsung-980-pro-1tb';
UPDATE productos SET precio_anterior = 899990 WHERE slug = 'notebook-acer-nitro-5-rtx4050';
UPDATE productos SET precio_anterior = 1449990 WHERE slug = 'iphone-15-pro-256gb';
UPDATE productos SET precio_anterior = 729990 WHERE slug = 'ps5-god-of-war-ragnarok';
UPDATE productos SET precio_anterior = 59990  WHERE slug = 'juego-god-of-war-ragnarok-ps5';
UPDATE productos SET precio_anterior = 199990 WHERE slug = 'silla-gamer-quadcore-pro';
UPDATE productos SET precio_anterior = 84990  WHERE slug = 'headset-hyperx-cloud-2';
UPDATE productos SET precio_anterior = 64990  WHERE slug = 'router-tplink-ax1500';
UPDATE productos SET precio_anterior = 29990  WHERE slug = 'audifonos-xiaomi-redmi-buds-8-lite';
UPDATE productos SET precio_anterior = 44990  WHERE slug = 'audifonos-xiaomi-redmi-buds-8-active';
UPDATE productos SET precio_anterior = 39990  WHERE slug = 'audifonos-sony-wi-c100';
UPDATE productos SET precio_anterior = 59990  WHERE slug = 'audifonos-sony-wf-c510-true-wireless';
UPDATE productos SET precio_anterior = 34990  WHERE slug = 'audifonos-gamer-jbl-quantum-100-v2';
UPDATE productos SET precio_anterior = 54990  WHERE slug = 'audifonos-gamer-razer-blackshark-v2-x';
UPDATE productos SET precio_anterior = 119990 WHERE slug = 'audifonos-gamer-razer-barracuda-x';
UPDATE productos SET precio_anterior = 69990  WHERE slug = 'mouse-gamer-corsair-ironclaw-rgb';
UPDATE productos SET precio_anterior = 89990  WHERE slug = 'mouse-gamer-logitech-g703-lightspeed';
UPDATE productos SET precio_anterior = 44990  WHERE slug = 'teclado-gamer-hyperx-alloy-core-rgb';
UPDATE productos SET precio_anterior = 64990  WHERE slug = 'webcam-trust-teza-uhd-4k';
UPDATE productos SET precio_anterior = 449990 WHERE slug = 'cpu-intel-i7-13700k';
UPDATE productos SET precio_anterior = 569990 WHERE slug = 'gpu-rtx-4060-ti-16gb';
UPDATE productos SET precio_anterior = 359990 WHERE slug = 'monitor-lg-ultrafine-32-4k';
UPDATE productos SET precio_anterior = 829990 WHERE slug = 'notebook-asus-tuf-f15';
UPDATE productos SET precio_anterior = 449990 WHERE slug = 'samsung-galaxy-s23-fe';
UPDATE productos SET precio_anterior = 299990 WHERE slug = 'audifonos-bose-quietcomfort';

-- ============================================================
-- BOOST DE STOCK GENERAL
-- ============================================================
UPDATE productos SET stock = stock + 15 WHERE stock > 0; --