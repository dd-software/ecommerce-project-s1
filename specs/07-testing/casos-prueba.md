# Especificación de Casos de Prueba (Testing Specs)

Los siguientes casos de prueba están diseñados bajo la metodología **Black-Box Testing** para verificar la integridad de los requerimientos y reglas de negocio descritas. Sirven de base conceptual para la posterior generación de scripts de pruebas unitarias o de aceptación.

---

### CP-001: Compra Exitosa de Producto (Flujo Feliz)
- **ID:** CP-001
- **Módulo Asociado:** Checkout y Ventas
- **Requerimiento Funcional:** Generar pedidos, Agregar productos al carrito.
- **Regla de Negocio Vinculada:** RN-001, RN-003, RN-005.
- **Precondiciones:**
  1. El usuario está autenticado como `Cliente`.
  2. El producto "Polera Institucional UCT" existe en la base de datos con un precio de `$12.990` y `stock = 5`.

#### Pasos de Ejecución:
1. El cliente navega al catálogo y selecciona el producto "Polera Institucional UCT".
2. Hace clic en "Agregar al carrito" con cantidad = 2.
3. Se dirige al módulo de carrito (`/carrito.php`) y presiona "Proceder al Pago".
4. Ingresa datos válidos de despacho, simula confirmación de pago y presiona "Confirmar Pedido".

#### Resultados Esperados:
- El sistema muestra una vista de éxito con el ID del pedido generado.
- La base de datos registra el nuevo registro en la tabla `pedidos` con estado inicial `Pagado`.
- La tabla `productos` refleja de inmediato la actualización de stock: el valor disminuyó de 5 a 3 ($5 - 2 = 3$).
- Se inserta un registro en `order_history` vinculando el estado `Pagado` con la marca de tiempo exacta.

---

### CP-002: Autenticación Exitosa en el Sistema
- **ID:** CP-002
- **Módulo Asociado:** Autenticación (Auth)
- **Requerimiento Funcional:** Registrar usuarios / Iniciar Sesión.
- **Regla de Negocio Vinculada:** RN-004.
- **Precondiciones:**
  1. Existe un usuario en la base de datos con el correo `vicente@uct.cl`.
  2. Su contraseña encriptada (con `password_hash`) corresponde a `Uct_2026_pass`.
  3. El estado del usuario en la columna `activo` es igual a `1`.

#### Pasos de Ejecución:
1. El usuario accede a la vista de login (`/login.php`).
2. Digita el correo `vicente@uct.cl` en el campo correspondiente.
3. Digita la contraseña `Uct_2026_pass` en el campo de texto de contraseña.
4. Presiona el botón "Iniciar Sesión".

#### Resultados Esperados:
- El sistema valida las credenciales y genera un identificador de sesión seguro (`$_SESSION['user_id']`).
- Se redirige automáticamente al index del catálogo o home personalizado de usuario.
- La barra de navegación (Navbar) actualiza dinámicamente sus componentes mostrando el nombre del usuario y ocultando los botones "Iniciar Sesión" y "Registrarse".

---

### CP-003: Intento de Compra sin Stock Suficiente (Flujo Alterno / Negativo)
- **ID:** CP-003
- **Módulo Asociado:** Carrito / Checkout
- **Regla de Negocio Vinculada:** RN-001.
- **Precondiciones:**
  1. El producto "Tazón UCT" posee un `stock = 1` en la tabla de existencias.
  2. El cliente ya tiene añadido 1 "Tazón UCT" en su sesión de carrito.

#### Pasos de Ejecución:
1. El cliente incrementa manualmente la cantidad del "Tazón UCT" a 2 unidades dentro de la interfaz del carrito.
2. O bien, intenta procesar la petición vía POST modificando los parámetros hacia el backend.

#### Resultados Esperados:
- El sistema bloquea la acción mediante PHP.
- Se renderiza una alerta en el frontend indicando: *"Lo sentimos, no hay stock suficiente para procesar la cantidad solicitada"*.
- El botón de confirmación final se deshabilita para evitar peticiones corruptas.