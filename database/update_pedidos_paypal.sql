ALTER TABLE pedidos
ADD COLUMN paypal_order_id VARCHAR(100) NULL AFTER id_usuario,
ADD COLUMN paypal_capture_id VARCHAR(100) NULL AFTER paypal_order_id,
ADD COLUMN metodo_pago VARCHAR(50) NULL AFTER estado;
