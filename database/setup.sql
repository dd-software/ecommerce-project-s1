-- ============================================
-- Script de configuración inicial de BD
-- ============================================
-- Ejecutar como root:
-- mysql -u root -p < database/setup.sql

CREATE DATABASE IF NOT EXISTS uct_ecommerce
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'ecommerce_app'@'localhost'
  IDENTIFIED BY 'app_password_here';

GRANT ALL PRIVILEGES ON uct_ecommerce.*
  TO 'ecommerce_app'@'localhost';

FLUSH PRIVILEGES;

USE uct_ecommerce;

SOURCE database/schema.sql;
SOURCE database/seed.sql;

SELECT '✅ Base de datos configurada correctamente' AS mensaje;
