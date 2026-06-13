# Reglas de Negocio (Business Rules Spec)

Este documento establece las restricciones lógicas operacionales, restricciones y validaciones cruzadas que gobiernan la base de datos y el backend de la aplicación. Cualquier desarrollo automatizado debe implementar estas validaciones a nivel de backend (PHP) y base de datos (Constraints/Triggers).

### RN-001: Control Estricto de Disponibilidad (Stock)
- **Declaración:** No se permite la comercialización ni reserva de productos cuyo stock disponible sea igual o menor a cero ($Stock \le 0$).
- **Validación del Backend:** Antes de renderizar el botón "Agregar al carrito" o procesar la API del checkout, se debe verificar el campo `stock` en la tabla `productos`. Si el stock es insuficiente para la cantidad solicitada ($Cantidad > Stock$), el sistema debe retornar un código de error `HTTP 409 Conflict`.

### RN-002: Restricción de Acceso al Dashboard de Gestión
- **Declaración:** Solo los usuarios con el rol explícito de `Administrador` pueden interactuar con rutas bajo el prefijo `/admin/` o consumir endpoints del panel de control.
- **Validación del Backend:** Middleware de autenticación y autorización. Si un `Cliente` o `Visitante` intenta acceder, el sistema destruirá la petición devolviendo un error `HTTP 403 Forbidden` y redirigirá al index principal.

### RN-003: Sincronización de Inventario Post-Pago
- **Declaración:** El stock físico de un producto decrementará de forma atómica únicamente tras la confirmación exitosa de la pasarela de pago o confirmación del pedido.
- **Validación del Backend:** El proceso debe encapsularse en una **Transacción SQL (ACID)**. Si la actualización falla por concurrencia, se aplica un `ROLLBACK` completo para evitar inconsistencia en la base de datos.
```$$Stock_{final} = Stock_{inicial} - Cantidad_{solicitada}$$```

### RN-004: Restricción de Autenticación para Usuarios Deshabilitados
- **Declaración:** Los usuarios cuyos registros contengan el estado `activo = 0` (deshabilitados) no podrán generar sesiones válidas en el sistema.
- **Validación del Backend:** Durante el proceso de Login, tras verificar la correspondencia del Hash de la contraseña (`password_verify()`), se evaluará la bandera `is_active`. Si es falso, se denegará el acceso devolviendo un mensaje estandarizado para seguridad: *"Credenciales incorrectas o cuenta suspendida"*.

### RN-005: Trazabilidad Obligatoria de Estados de Pedidos
- **Declaración:** Todo pedido (`order`) generado debe registrar de forma cronológica e inmutable cada cambio en su ciclo de vida en una tabla de auditoría (`order_history`).
- **Estados Válidos:** `[Pendiente -> Pagado -> Despachado -> Entregado]`.
- **Restricción de Flujo:** No se permiten saltos de estados ilógicos (ej. de `Pendiente` directo a `Entregado` sin pasar por `Pagado`).