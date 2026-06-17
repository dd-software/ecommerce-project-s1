# Módulo D - Checkout

## Objetivo y Propósito
Recopilar los datos de despacho, facturación y generar formalmente el pedido de compra en estado pendiente.

## Responsabilidades Funcionales
- Guiar al cliente en el proceso de finalización de compra.
- Recopilar dirección de envío, teléfono y datos de facturación.
- Validar stock en tiempo real antes del cierre del pedido.
- Registrar el pedido en MySQL en estado 'Pendiente' de pago.

## Actores Involucrados
- Cliente (Autenticado)

## Entidades y Relaciones Relevantes
- Pedido (id, usuario_id, total, estado, direccion_despacho, telefono, creado_en)
- DetallePedido (id, pedido_id, producto_id, cantidad, precio_unitario)

## Reglas de Negocio
- RN-D01: El carrito de compras no debe estar vacío al iniciar el checkout.
- RN-D02: El checkout debe realizarse únicamente por clientes que tengan una sesión activa con JWT válido.
- RN-D03: RN-005 Todo pedido debe tener trazabilidad de estados (Pendiente, Pagado, Despachado, Entregado, Cancelado).

## Dependencias y Restricciones
- Depende del stack de Frontend (Bootstrap 5.3, JS Vanilla), Backend (PHP 8 REST), Persistencia (MySQL) e Integración (JWT, JSON).
- Integración segura mediante paso de tokens en cabeceras HTTP de autorización.
