USE uct_ecommerce;
INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, activo)
VALUES ('Administrador', 'Sistema', 'admin@gmail.com', '$2y$10$z6G2FPnwbfyM2J8EfqDjHO04MW68VTkVubcNDVPmb0aQhA.Q16z7i', 'admin', 1)
ON DUPLICATE KEY UPDATE password_hash = '$2y$10$z6G2FPnwbfyM2J8EfqDjHO04MW68VTkVubcNDVPmb0aQhA.Q16z7i', rol = 'admin', activo = 1;
