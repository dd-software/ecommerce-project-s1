# Módulo: Fase 2 - Autenticación (Módulo C) y API Core (SDD)

## Análisis
La fase 2 introduce la base de la API REST mediante la implementación de un enrutador (Router) PHP nativo y componentes base (Response, JWTHandler) para asegurar respuestas JSON consistentes y manejar JSON Web Tokens (JWT) de forma segura. 
Se implementa el Módulo C, cubriendo la autenticación (Login) y el registro de clientes. Se aplican las reglas obligatorias de la rúbrica como tipado estricto, estatus HTTP, PHPDoc exhaustivo, y la regla de negocio explícita `RN-004` (Usuarios deshabilitados no pueden entrar).

## Casos de Uso
1. **Inicio de Sesión:** Un usuario inicia sesión proporcionando email y contraseña. Recibe un token JWT y datos de sesión si las credenciales son válidas y está activo.
2. **Registro:** Un usuario visitante puede registrarse proporcionando datos y contraseña, la cual se almacena encriptada (Bcrypt).
3. **Manejo Centralizado de Errores:** Cualquier ruta no encontrada o excepción no controlada será capturada emitiendo un JSON con su HTTP Status Code correspondiente (`400`, `401`, `404`, `500`).

## Tareas
- [x] Crear `src/Core/Router.php` como motor de rutas REST.
- [x] Crear `src/Core/Response.php` para estandarizar las respuestas JSON.
- [x] Crear `src/Core/JWTHandler.php` para generación y validación de tokens sin dependencias externas.
- [x] Crear modelo `src/Models/User.php` para interacción con DB usando PDO.
- [x] Crear controlador `src/Controllers/AuthController.php` con la lógica del Módulo C.
- [x] Configurar el punto de entrada `public/index.php` (AutoLoader PSR-4 simplificado, variables .env y manejo de excepciones).
- [x] Generar `.env` desde su plantilla y crear estructura de directorios interna.

## Criterios de Aceptación
- Todos los endpoints deben retornar JSON estándar y códigos HTTP apropiados (`200`, `201`, `400`, `401`, `404`, `500`).
- El Router debe rechazar con HTTP `404` rutas que no coincidan.
- El Login debe implementar `password_verify` y chequear la bandera `is_active` del usuario.
- El JWT generado contendrá el `role` del usuario para futuros usos de RBAC.
