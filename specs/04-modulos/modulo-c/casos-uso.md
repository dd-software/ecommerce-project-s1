# Casos de Uso - Autenticación

### CU-C-001: Iniciar Sesión
- **Actor**: Invitado, Cliente, Vendedor, Supervisor, Administrador, Dev
- **Precondición**: El usuario está previamente registrado y activo.
- **Flujo Operacional Principal**:
  1. El usuario ingresa email y contraseña en el formulario.
  2. El sistema valida los datos en PHP 8 contra MySQL usando `password_verify`.
  3. Si es válido, genera un JWT que contiene rol y datos del usuario.
  4. El sistema retorna el JWT al cliente, quien lo guarda en LocalStorage.
- **Excepciones y Flujos Alternativos**:
  - Credenciales inválidas o usuario inactivo: Retorna código HTTP 401 Unauthorized.

### CU-C-002: Autorizar Endpoint Protegido
- **Actor**: Invitado, Cliente, Vendedor, Supervisor, Administrador, Dev
- **Precondición**: El cliente realiza una petición http adjuntando el JWT en la cabecera 'Authorization: Bearer <token>'.
- **Flujo Operacional Principal**:
  1. El middleware del backend intercepta la petición.
  2. El sistema decodifica y verifica la firma del JWT.
  3. El sistema comprueba que el rol del usuario tenga permiso para el recurso solicitado.
  4. El sistema permite el procesamiento del endpoint.
- **Excepciones y Flujos Alternativos**:
  - JWT vencido, alterado o rol sin permisos: Retorna HTTP 403 Forbidden o HTTP 401 Unauthorized.

