-- ============================================================
-- TAREA LEONARDO #1 — Imágenes de productos
-- ============================================================
-- Rellena la URL de cada producto entre las comillas.
-- Fuente recomendada: https://unsplash.com  (el CSP ya permite images.unsplash.com)
--   1. Buscá la imagen, click derecho → "Copiar dirección de la imagen".
--   2. Tiene que terminar en una imagen real (.jpg / .png / o un enlace de images.unsplash.com).
--   3. Pegala entre las comillas de su producto. NO cambies el slug ni el resto.
--
-- Para aplicar y ver el cambio:
--   mysql -h127.0.0.1 -u ecommerce_app -p uct_ecommerce < database/seed_imagenes.sql
--   (refrescá el navegador con Ctrl+Shift+R)
--
-- Si una URL queda vacía, el producto simplemente muestra el placeholder gris.
-- Es solo data: imposible romper la app con este archivo.
-- ============================================================

-- Componentes PC
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1555617981-dac3880eac6e?w=600' WHERE slug = 'procesador-intel-core-i5-13400f';  -- ejemplo ya puesto
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1645563301273-3ced54692158?q=80&w=870&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'procesador-amd-ryzen-5-7600x';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1676554565685-3aeb5d7ad1b7?q=80&w=1074&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'memoria-kingston-fury-beast-16gb-ddr5';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1666430163009-31c44d29ec85?q=80&w=687&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'memoria-corsair-vengeance-32gb-ddr5';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1675893857450-783969c8922f?q=80&w=1074&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'fuente-evga-supernova-750w-gold';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1712701815718-29f5fe510c0e?q=80&w=1074&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'placa-madre-asus-tuf-b650-plus';
UPDATE productos SET imagen_url = 'https://plus.unsplash.com/premium_photo-1721133221361-4f2b2af3b6fe?q=80&w=870&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'ssd-kingston-nv2-1tb-nvme';
UPDATE productos SET imagen_url = 'https://plus.unsplash.com/premium_photo-1721133263972-3b12e3ba6420?q=80&w=885&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'refrigeracion-corsair-icue-h100i';

-- Accesorios
UPDATE productos SET imagen_url = 'https://plus.unsplash.com/premium_photo-1721133230729-f76c4b036a58?q=80&w=870&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'ventilador-120mm-argb';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1563297007-0686b7003af7?q=80&w=917&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'mouse-logitech-g502-hero';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1639506060078-83c565d0e51a?q=80&w=870&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'teclado-redragon-kumara';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1760377821967-8bb2f250e041?q=80&w=870&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'cable-hdmi-2-1-belkin-8k-2m';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1578016980868-197203ff4b02?q=80&w=870&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'cable-sata-iii-pack-x3';

-- Herramientas
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1770386582823-3a7094e35b22?q=80&w=1074&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'kit-destornilladores-precision-32';
UPDATE productos SET imagen_url = 'https://plus.unsplash.com/premium_photo-1664195539700-c30fdcb402d2?q=80&w=718&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'estacion-soldadura-60w';

-- Repuestos
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1640223321014-8659ca225cba?q=80&w=735&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'pasta-termica-arctic-mx-4-4g';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1699528136776-51ddd829363e?q=80&w=870&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'kit-limpieza-aire-comprimido';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1537606845186-9921373c310f?q=80&w=870&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'boton-power-reset-universal';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1556644304-99ca9f48ae96?q=80&w=870&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'bisagras-plasticas-notebook';

-- Servicios Técnicos
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1604754742629-3e5728249d73?q=80&w=870&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' WHERE slug = 'diagnostico-tecnico-profesional';

-- ── Catálogo ampliado (notebooks, computadores, consolas, celulares, TV, monitores) ──
-- URLs verificadas con curl (HTTP 200 + image/*). Dominios permitidos por el CSP.
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600' WHERE slug = 'notebook-asus-vivobook-15-i5';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=600' WHERE slug = 'notebook-lenovo-ideapad-gaming-3';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=600' WHERE slug = 'notebook-hp-pavilion-14';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=600' WHERE slug = 'macbook-air-m2-13';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1587202372634-32705e3bf49c?w=600' WHERE slug = 'pc-gamer-quadcore-ryzen5-rtx4060';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1547082299-de196ea013d6?w=600' WHERE slug = 'pc-oficina-quadcore-i3';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1591488320449-011701bb6704?w=600' WHERE slug = 'pc-gamer-pro-quadcore-i7-rtx4070';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=600' WHERE slug = 'playstation-5-slim';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1621259182978-fbf93132d53d?w=600' WHERE slug = 'xbox-series-x';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1578303512597-81e6cc155b3e?w=600' WHERE slug = 'nintendo-switch-oled';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=600' WHERE slug = 'iphone-15-128gb';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=600' WHERE slug = 'samsung-galaxy-s24-256gb';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=600' WHERE slug = 'xiaomi-redmi-note-13-pro';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600' WHERE slug = 'motorola-moto-g54-5g';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?w=600' WHERE slug = 'smart-tv-samsung-55-crystal-4k';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600' WHERE slug = 'audifonos-sony-wh-1000xm5';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=600' WHERE slug = 'parlante-jbl-charge-5';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=600' WHERE slug = 'monitor-lg-ultragear-27-165hz';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1517059224940-d4af9eec41b7?w=600' WHERE slug = 'monitor-samsung-essential-24';
UPDATE productos SET imagen_url = 'https://images.unsplash.com/photo-1555680202-c86f0e12f086?w=600' WHERE slug = 'monitor-aoc-gaming-27-144hz';
