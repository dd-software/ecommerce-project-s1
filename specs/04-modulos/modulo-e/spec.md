# Módulo E - Pasarela de Pago

## Objetivo y Propósito
Integrar, procesar y simular las transacciones financieras para la confirmación o rechazo de los pedidos creados.

## Responsabilidades Funcionales
- Ofrecer un canal simulado de procesamiento de tarjetas de crédito/débito.
- Recibir notificaciones asíncronas de la pasarela (Webhooks) para confirmación de pago.
- Actualizar el estado del pedido a 'Pagado' en caso de transacción exitosa.
- Actualizar el estado del pedido a 'Pago Fallido' en caso de rechazo del procesador.

## Actores Involucrados
- Cliente
- Pasarela de Pago (Servicio Externo Simulado)

## Entidades y Relaciones Relevantes
- Pago (id, pedido_id, transaccion_id, monto, metodo_pago, estado, respuesta_pasarela, creado_en)

## Reglas de Negocio
- RN-E01: RN-003 El stock de los productos se descuenta de forma definitiva en la base de datos MySQL tras confirmar la aprobación del pago.
- RN-E02: Un pedido solo cambia de estado 'Pendiente' a 'Pagado' mediante confirmación exitosa con firma verificada de la pasarela.

## Dependencias y Restricciones
- Depende del stack de Frontend (Bootstrap 5.3, JS Vanilla), Backend (PHP 8 REST), Persistencia (MySQL) e Integración (JWT, JSON).
- Integración segura mediante paso de tokens en cabeceras HTTP de autorización.
