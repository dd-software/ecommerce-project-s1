# Casos de Uso - Administración

### CU-G-001: Crear un Nuevo Producto
- **Actor**: Administrador, Supervisor, Vendedor
- **Precondición**: El usuario posee credenciales de Administrador o Vendedor y sesión activa.
- **Flujo Operacional Principal**:
  1. El actor accede a la pestaña 'Productos' del Dashboard.
  2. El actor presiona 'Agregar Producto' y completa el formulario (Nombre, descripción, precio, categoría, stock inicial, imágenes).
  3. El sistema valida la integridad de los datos en PHP 8.
  4. El sistema guarda el producto y almacena las imágenes en el servidor.
  5. El sistema actualiza la lista y confirma la creación.
- **Excepciones y Flujos Alternativos**:
  - Falta de campos obligatorios o formato inválido: Se cancela el guardado y se resalta el error en Bootstrap.

### CU-G-002: Cambiar Estado de un Pedido
- **Actor**: Administrador, Supervisor, Vendedor
- **Precondición**: El usuario es Administrador o Supervisor.
- **Flujo Operacional Principal**:
  1. El actor ingresa al gestor de pedidos.
  2. El actor selecciona un pedido y hace clic en 'Actualizar Estado'.
  3. El actor selecciona el nuevo estado (ej. 'Despachado').
  4. El sistema guarda el cambio en la tabla `pedidos` en MySQL.
  5. El sistema dispara el evento de notificación (Módulo H).
- **Excepciones y Flujos Alternativos**:
  - Transición de estado inválida: El sistema bloquea el cambio (ej. no se puede pasar de 'Pendiente' a 'Despachado' sin pago confirmado).

