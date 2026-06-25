-- Reset de contraseña: columnas para el token de recuperación.
-- Correr en la BD YA existente (el server). En local, setup.sql ya las crea.
--   mysql -u root ecommerce < database/migracion_reset_password.sql
ALTER TABLE usuarios
    ADD COLUMN reset_token_hash CHAR(64) NULL DEFAULT NULL AFTER ultimo_login,
    ADD COLUMN reset_expira     DATETIME NULL DEFAULT NULL AFTER reset_token_hash;
