# Historias de Usuario - Autenticación

### US-C-001: Crearme una cuenta de cliente
- **Como** invitado
- **Quiero** crearme una cuenta de cliente
- **Para** acceder a los módulos de compras y ver mi historial
- **Criterios de Aceptación**:
  - El correo debe ser único en la base de datos.
  - La contraseña debe poseer al menos 8 caracteres, una mayúscula y un número.

### US-C-002: Iniciar sesión con mis credenciales
- **Como** usuario administrativo
- **Quiero** iniciar sesión con mis credenciales
- **Para** acceder al panel de administración correspondiente a mi rol
- **Criterios de Aceptación**:
  - El JWT emitido debe codificar el rol exacto (Vendedor, Supervisor, Admin).
  - El frontend Bootstrap 5.3 debe adaptar los menús visibles según el rol del JWT decodificado.

