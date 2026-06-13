# Casos de Uso - Carrito

### CU-B-001: Agregar Ítem al Carrito
- **Actor**: Invitado, Cliente
- **Precondición**: El producto tiene stock disponible.
- **Flujo Operacional Principal**:
  1. El usuario hace clic en 'Agregar al carrito' desde la ficha de producto.
  2. El sistema verifica el stock actual.
  3. El sistema añade el ítem al almacenamiento (Local o DB) y actualiza el contador en la barra de navegación.
- **Excepciones y Flujos Alternativos**:
  - Stock insuficiente: El sistema alerta al usuario y no permite la acción.

### CU-B-002: Sincronizar Carrito de Invitado a Cliente
- **Actor**: Invitado, Cliente
- **Precondición**: El usuario invitado tiene ítems en su LocalStorage.
- **Flujo Operacional Principal**:
  1. El usuario inicia sesión exitosamente (Módulo C).
  2. El sistema lee el LocalStorage del navegador.
  3. El sistema guarda esos ítems en la tabla `carrito_items` asociados a la cuenta del cliente en la DB MySQL.
  4. El sistema limpia el LocalStorage.
- **Excepciones y Flujos Alternativos**:
  - Conflicto de stock posterior: Si un ítem ya no cuenta con stock, se elimina y se notifica al usuario.

