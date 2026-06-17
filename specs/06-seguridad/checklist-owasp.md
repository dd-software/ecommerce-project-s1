# Especificaciones de Seguridad — Plataforma E-Commerce

**Proyecto:** Plataforma E-Commerce para Empresa Única  
**Tecnologías:** PHP 8, MySQL, Bootstrap 5.3, JavaScript  
**Versión:** 2.0  
**Fecha:** Junio 2026

---

# 1. Objetivos de Seguridad

Garantizar la confidencialidad, integridad y disponibilidad de la información del sistema mediante controles alineados con:

- OWASP Top 10
- OWASP ASVS
- Buenas prácticas PHP/MySQL
- Principio de mínimo privilegio
- Seguridad por diseño (Security by Design)

---

# 2. Roles y Control de Acceso

## Visitante

Puede:

- Ver catálogo
- Buscar productos
- Revisar detalles de productos

No puede:

- Comprar productos
- Acceder a pedidos
- Acceder al panel administrativo

---

## Cliente

Puede:

- Iniciar sesión
- Gestionar carrito
- Realizar compras
- Consultar historial de pedidos
- Gestionar sus direcciones

No puede:

- Acceder al Dashboard Administrativo
- Gestionar productos
- Gestionar usuarios

---

## Administrador

Puede:

- Acceder al Dashboard
- Gestionar productos
- Gestionar inventario
- Gestionar usuarios
- Revisar pedidos
- Revisar reportes

---

## Regla RN-002

Todo acceso al Dashboard debe validarse en el backend.

Nunca confiar únicamente en restricciones del frontend.

---

# 3. Gestión de Usuarios y Contraseñas

## Requisitos de contraseña

- Mínimo 8 caracteres
- Al menos una letra mayúscula
- Al menos un número
- Al menos un carácter especial

---

## Hash de Contraseñas

Se utilizará:

```php
password_hash($password, PASSWORD_BCRYPT);
```

Validación:

```php
password_verify($password, $hash);
```

Prohibido:

- MD5
- SHA1
- Contraseñas en texto plano

---

## Bloqueo por Intentos Fallidos

Después de:

- 5 intentos fallidos consecutivos

El sistema bloqueará temporalmente la cuenta durante:

- 15 minutos

---

## Usuarios Deshabilitados

Regla RN-004:

Un usuario deshabilitado no podrá iniciar sesión aunque sus credenciales sean válidas.

---

# 4. Gestión Segura de Sesiones

## Requisitos

- Session ID regenerado después del login
- Expiración automática por inactividad
- Cookies seguras

Configuración recomendada:

```php
session_set_cookie_params([
    'httponly' => true,
    'secure' => true,
    'samesite' => 'Strict'
]);
```

---

## Cierre de Sesión

Al cerrar sesión:

- Destruir sesión
- Eliminar cookies
- Invalidar identificador de sesión

---

# 5. Seguridad de Base de Datos

## Prepared Statements (Obligatorio)

Todas las consultas deben utilizar consultas preparadas.

Ejemplo:

```php
$stmt = $conn->prepare(
"SELECT * FROM usuarios WHERE email = ?"
);

$stmt->bind_param("s", $email);
$stmt->execute();
```

---

## Prohibido

```php
$sql = "SELECT * FROM usuarios WHERE email='$email'";
```

Riesgo:

- SQL Injection

---

## Mínimos privilegios

La cuenta MySQL de producción:

- No debe utilizar root
- Debe tener únicamente permisos necesarios

---

# 6. Protección del Catálogo y Carrito

## Regla RN-001

No vender productos sin stock.

Validaciones obligatorias:

- Al agregar al carrito
- Al actualizar cantidades
- Antes de confirmar compra

---

## Concurrencia

Antes de confirmar una compra:

- Revalidar stock en base de datos
- Ejecutar operación dentro de transacción

Objetivo:

Evitar sobreventa cuando varios usuarios compran simultáneamente.

---

# 7. Seguridad del Proceso de Compra

## Regla RN-003

El stock solo se descuenta cuando:

- El pago es confirmado

Nunca:

- Al agregar al carrito
- Al iniciar checkout

---

## Validaciones de Pedido

Verificar:

- Usuario autenticado
- Carrito válido
- Stock disponible
- Monto correcto
- Dirección válida

---

# 8. Seguridad de Pagos

## Principio General

El sistema NO almacenará:

- Número de tarjeta
- CVV
- Fecha de expiración

---

## Pasarela Externa

Todo procesamiento de pago será realizado por:

- MercadoPago
- WebPay
- Stripe
- Otro proveedor certificado

---

## Verificación de Pagos

Todo pago confirmado debe:

- Validarse desde backend
- Verificarse mediante Webhook
- Registrarse en auditoría

---

# 9. Protección Contra OWASP Top 10

## Validación de Entradas

Todos los datos recibidos desde:

- Formularios
- URLs
- APIs
- Cookies

Deben validarse antes de procesarse.

---

## Protección XSS

Escapar toda salida HTML:

```php
htmlspecialchars(
    $dato,
    ENT_QUOTES,
    'UTF-8'
);
```

Aplicar también:

- Content Security Policy (CSP)

---

## Protección CSRF

Todos los formularios que modifiquen información deben incluir:

```php
$_SESSION['csrf_token']
```

Validar token antes de:

- Crear
- Editar
- Eliminar

---

## Protección SQL Injection

Mediante:

- Prepared Statements
- Validación de tipos
- Sanitización

---

## Protección de Archivos

Si se implementa subida de imágenes:

Validar:

- Extensión
- MIME Type
- Tamaño máximo

No permitir:

- PHP
- EXE
- JS
- Scripts ejecutables

---

# 10. CRUD Administrativo Seguro

Todas las operaciones del Dashboard deben:

- Verificar sesión activa
- Verificar rol Administrador
- Validar CSRF
- Registrar auditoría

Aplica para:

- Crear producto
- Editar producto
- Eliminar producto
- Gestionar stock
- Gestionar usuarios

---

# 11. Trazabilidad y Auditoría

## Regla RN-005

Todo pedido debe registrar:

- Fecha
- Estado anterior
- Estado nuevo
- Usuario responsable

Estados mínimos:

- Pendiente
- Pagado
- En preparación
- Enviado
- Entregado
- Cancelado

---

## Logs de Seguridad

Registrar:

- Logins exitosos
- Logins fallidos
- Cambios de contraseña
- Cambios de rol
- Eliminación de productos
- Accesos administrativos

---

## No Registrar

- Contraseñas
- Tokens completos
- Datos bancarios
- Datos sensibles sin anonimizar

---

# 12. Configuración Segura del Servidor

## HTTPS Obligatorio

TLS 1.2 mínimo

TLS 1.3 recomendado

---

## Headers de Seguridad

```http
Content-Security-Policy: default-src 'self'
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Strict-Transport-Security: max-age=31536000
Referrer-Policy: strict-origin
```

---

# 13. Variables de Entorno

Nunca almacenar en Git:

- Contraseñas
- API Keys
- Secret Keys

Agregar al .gitignore:

```text
.env
.env.local
.env.production
```

---

# 14. Seguridad del Repositorio

Requerimientos:

- Pull Request obligatorio
- Revisión antes de merge
- Protección de rama main
- Commits descriptivos
- No subir credenciales

---

# 15. Checklist OWASP

## Validación de entradas

- [ ] Todos los formularios validan datos
- [ ] Se valida longitud y formato
- [ ] Se validan datos en backend

---

## Protección XSS

- [ ] Uso de htmlspecialchars()
- [ ] CSP configurada
- [ ] Salidas HTML escapadas

---

## Protección CSRF

- [ ] Token CSRF implementado
- [ ] Validación en formularios POST
- [ ] Protección en CRUD administrativo

---

## Prepared Statements

- [ ] Todas las consultas usan prepare()
- [ ] No existen consultas concatenadas
- [ ] Inputs parametrizados

---

## Gestión segura de sesiones

- [ ] Cookies HttpOnly
- [ ] Cookies Secure
- [ ] Regeneración de Session ID
- [ ] Expiración de sesión

---

## Hash de contraseñas

- [ ] password_hash()
- [ ] password_verify()
- [ ] Sin contraseñas en texto plano

---

## Control de acceso basado en roles

- [ ] Validación backend de roles
- [ ] Dashboard restringido a Administradores
- [ ] Usuarios deshabilitados bloqueados
- [ ] Principio de mínimo privilegio

---

# 16. Criterios de Aceptación de Seguridad

El sistema será considerado seguro para entrega académica cuando:

- Se cumplan las reglas RN-001 a RN-005
- El Dashboard esté protegido por RBAC
- Se utilicen Prepared Statements
- Exista protección XSS y CSRF
- Las contraseñas estén hasheadas con bcrypt
- Las sesiones sean seguras
- El repositorio no contenga secretos
- El Checklist OWASP esté completamente validado