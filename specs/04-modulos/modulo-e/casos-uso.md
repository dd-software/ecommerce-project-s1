# Casos de Uso - Pasarela de Pago

### CU-E-001: Procesar Transacción Financiera
- **Actor**: Cliente, Pasarela de Pago (Servicio Externo Simulado)
- **Precondición**: El pedido existe en estado 'Pendiente'.
- **Flujo Operacional Principal**:
  1. El cliente visualiza la pantalla de pago simulada.
  2. El cliente ingresa los datos de prueba de su tarjeta de crédito.
  3. El sistema envía la solicitud de pago al endpoint de la API REST de simulación.
  4. El sistema recibe aprobación exitosa e inicia la confirmación interna.
- **Excepciones y Flujos Alternativos**:
  - Transacción rechazada: El sistema informa al usuario, cambia el pedido a 'Pago Fallido' e invita a intentar de nuevo.

### CU-E-002: Recibir Webhook de Confirmación
- **Actor**: Cliente, Pasarela de Pago (Servicio Externo Simulado)
- **Precondición**: El webhook de la pasarela se dispara al completarse un pago.
- **Flujo Operacional Principal**:
  1. El backend de e-commerce recibe una petición POST en `/api/pagos/webhook`.
  2. El sistema valida la autenticidad de la petición con una clave secreta configurada.
  3. El sistema recupera el pedido asociado.
  4. El sistema cambia el estado del pedido a 'Pagado'.
  5. El sistema descuenta el stock en el Módulo F de manera persistente.
  6. El sistema responde 200 OK a la pasarela.
- **Excepciones y Flujos Alternativos**:
  - Firma del webhook incorrecta: El sistema responde 403 Forbidden y descarta el evento.

