# Casos de Uso - Checkout

### CU-D-001: Iniciar Formulario Checkout
- **Actor**: Cliente (Autenticado)
- **Precondición**: El carrito tiene al menos un producto con stock verificado.
- **Flujo Operacional Principal**:
  1. El cliente presiona el botón 'Proceder al Pago' desde la vista de carrito.
  2. El sistema solicita dirección, datos de contacto y facturación.
  3. El cliente completa el formulario y presiona 'Confirmar Pedido'.
- **Excepciones y Flujos Alternativos**:
  - El carrito está vacío: Se redirige al catálogo con alerta.

### CU-D-002: Generar Registro de Pedido
- **Actor**: Cliente (Autenticado)
- **Precondición**: El cliente completó los datos obligatorios de envío.
- **Flujo Operacional Principal**:
  1. El sistema inicia una transacción de base de datos MySQL.
  2. El sistema verifica nuevamente el stock de cada producto en el carrito.
  3. El sistema inserta el registro en la tabla `pedidos` y su desglose en `detalles_pedido`.
  4. El sistema cambia el estado de los ítems a 'Pendiente' de pago y limpia el carrito.
  5. El sistema confirma la transacción y redirige a la pasarela de pago (Módulo E).
- **Excepciones y Flujos Alternativos**:
  - Sin stock de último momento: El sistema cancela la transacción de base de datos y alerta al usuario.

