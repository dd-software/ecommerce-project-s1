# Casos de Uso - Inventario

### CU-F-001: Registrar Ajuste de Stock
- **Actor**: Administrador, Supervisor, Vendedor, Sistema (Autodecremento al pagar)
- **Precondición**: El usuario tiene rol Administrador, Supervisor o Vendedor.
- **Flujo Operacional Principal**:
  1. El actor accede al gestor de inventario en el dashboard (Módulo G).
  2. El actor selecciona un producto e ingresa una cantidad y motivo (ej. 'Ingreso de proveedor', 'Ajuste por merma').
  3. El sistema actualiza la tabla `productos` y guarda el registro en `movimientos_inventario`.
  4. El sistema notifica el guardado exitoso.
- **Excepciones y Flujos Alternativos**:
  - El ajuste deja el stock final en negativo: El sistema bloquea la transacción y muestra error.

### CU-F-002: Consultar Alertas de Bajo Stock
- **Actor**: Administrador, Supervisor, Vendedor, Sistema (Autodecremento al pagar)
- **Precondición**: El usuario cuenta con rol administrativo en sesión activa.
- **Flujo Operacional Principal**:
  1. El actor ingresa al panel de alertas de inventario.
  2. El sistema consulta los productos con stock inferior al umbral mínimo configurado (ej. 5 unidades).
  3. El sistema lista los productos con indicativo visual llamativo en Bootstrap (badge rojo).
- **Excepciones y Flujos Alternativos**:
  - Todos los productos tienen stock correcto: Se muestra 'No hay alertas pendientes'.

