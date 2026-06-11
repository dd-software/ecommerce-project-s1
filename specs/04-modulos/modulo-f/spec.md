# Módulo F - Inventario

## Objetivo y Propósito
Administrar el stock físico y virtual de los productos de la tienda y registrar cada movimiento para auditoría interna.

## Responsabilidades Funcionales
- Mantener actualizado el stock disponible para la venta.
- Registrar todas las entradas y salidas de mercadería indicando motivo y actor responsable.
- Monitorear productos con stock inferior al umbral de seguridad.
- Generar alertas automáticas para reabastecimiento en el dashboard.

## Actores Involucrados
- Administrador
- Supervisor
- Vendedor
- Sistema (Autodecremento al pagar)

## Entidades y Relaciones Relevantes
- MovimientoInventario (id, producto_id, cantidad, tipo_movimiento, motivo, usuario_id, creado_en)

## Reglas de Negocio
- RN-F01: RN-001 No se pueden vender productos con stock insuficiente.
- RN-F02: La cantidad en stock de cualquier producto no puede ser inferior a 0.
- RN-F03: Cada reducción de stock en checkout pagado debe registrarse en la tabla de movimientos como tipo 'EGRESO' con el ID del pedido correspondiente.

## Dependencias y Restricciones
- Depende del stack de Frontend (Bootstrap 5.3, JS Vanilla), Backend (PHP 8 REST), Persistencia (MySQL) e Integración (JWT, JSON).
- Integración segura mediante paso de tokens en cabeceras HTTP de autorización.
