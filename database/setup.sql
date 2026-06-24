-- ============================================
-- Script de configuración inicial de BD
-- ============================================
-- Ejecutar como root:
-- mysql -u root -p < database/setup.sql

-- ⚠️ RESET: borra y recrea la BD desde cero, dejándola al día con el esquema y
-- los seeds (catálogo, imágenes, usuarios demo). Se pierde la data de PRUEBA local
-- (pedidos, carritos, favoritos, perfiles editados). Solo para DESARROLLO.
DROP DATABASE IF EXISTS uct_ecommerce;

CREATE DATABASE uct_ecommerce
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Se crea para 'localhost' (socket) Y '127.0.0.1' (TCP): el código conecta por
-- TCP a 127.0.0.1, y un usuario solo '@localhost' daría "access denied".
CREATE USER IF NOT EXISTS 'ecommerce_app'@'localhost'   IDENTIFIED BY 'app_password_here';
CREATE USER IF NOT EXISTS 'ecommerce_app'@'127.0.0.1'   IDENTIFIED BY 'app_password_here';

GRANT ALL PRIVILEGES ON uct_ecommerce.* TO 'ecommerce_app'@'localhost';
GRANT ALL PRIVILEGES ON uct_ecommerce.* TO 'ecommerce_app'@'127.0.0.1';

FLUSH PRIVILEGES;

USE uct_ecommerce;

-- NOTA: el comentario va en su propia línea. MySQL toma TODO lo que sigue a
-- SOURCE (hasta el fin de línea) como nombre de archivo, así que un comentario
-- al lado rompía la carga ("Failed to open file '...seed.sql -- ...'").
SOURCE database/schema.sql;
-- usuarios, direcciones, cupones (+ catálogo demo)
SOURCE database/seed.sql;
-- reemplaza el catálogo demo por el real QuadCore
SOURCE database/seed_quadcore.sql;
-- imágenes de productos (trabajo de Leonardo)
SOURCE database/seed_imagenes.sql;

SELECT '✅ Base de datos configurada correctamente' AS mensaje;
