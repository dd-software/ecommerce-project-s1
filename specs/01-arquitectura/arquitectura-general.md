# Especificación General de Arquitectura

**Proyecto:** UCT Ecommerce  

**Equipo:** Los Takas — Arquitectura & DevOps  

**Versión:** 1.0  

**Estado:** Sprint 1  

**Última actualización:** 2025  

---

## 1. Propósito y Objetivos

Este documento define la estructura arquitectónica general del sistema UCT Ecommerce. Sirve como **fuente única de verdad** para el diseño global del sistema, responsabilidades por capa, límites entre módulos y patrones de comunicación.

La arquitectura está diseñada para soportar una plataforma de comercio electrónico completa, incluyendo gestión de catálogo de productos, autenticación de usuarios, procesamiento de pedidos y manejo de pagos. El sistema debe ser mantenible, testeable y extensible por múltiples equipos de desarrollo independientes trabajando en paralelo.

**Objetivos principales:**

- Establecer una separación clara de responsabilidades en todas las capas del sistema.
- Permitir el desarrollo y despliegue independiente de cada módulo.
- Definir contratos de integración inequívocos entre frontend, backend y capa de persistencia.
- Soportar generación de código asistida por IA a partir de esta especificación sin requerir contexto adicional.

---

## 2. Patrón Arquitectónico

El sistema sigue una **Arquitectura Hexagonal** (también conocida como *Ports and Adapters*), aplicada de forma consistente en todos los módulos del backend.

### 2.1 Principios base

- La **capa de dominio** contiene toda la lógica de negocio. No tiene dependencias de frameworks, bases de datos ni HTTP.
- La **capa de aplicación** orquesta los casos de uso llamando servicios de dominio y coordinando I/O mediante puertos definidos.
- Los **adaptadores de entrada** (controladores REST, handlers CLI) traducen solicitudes externas a llamadas de aplicación.
- Los **adaptadores de salida** (repositorios de base de datos, servicios de correo, APIs externas) implementan las interfaces (puertos) definidas por la capa de aplicación.

### 2.2 Diagrama de capas

```
┌──────────────────────────────────────────────────────┐
│                    Capa Cliente                      │
│        Navegador (HTML5 + CSS3 + Bootstrap 5.3)       │
│                JavaScript (Vanilla)                   │
└────────────────────┬─────────────────────────────────┘
                     │ HTTP/JSON
┌────────────────────▼─────────────────────────────────┐
│                   Capa de Interfaz                   │
│              Apache HTTP Server 2.4                   │
│        Servido de archivos estáticos + routing PHP    │
└────────────────────┬─────────────────────────────────┘
                     │ Llamadas internas / REST
┌────────────────────▼─────────────────────────────────┐
│                   Capa de Aplicación                 │
│              PHP 8 — Endpoints REST API               │
│  Controladores → Servicios → Lógica Dominio → Repos   │
└────────────────────┬─────────────────────────────────┘
                     │ PDO / SQL
┌────────────────────▼─────────────────────────────────┐
│                  Capa de Persistencia                │
│                  Base de Datos MySQL 8.x              │
│        Esquema relacional, transacciones, índices     │
└──────────────────────────────────────────────────────┘
```

---

## 3. Stack Tecnológico

| Capa | Tecnología | Versión | Responsabilidad |
| --- | --- | --- | --- |
| Interfaz (Frontend) | HTML5 | — | Estructura de páginas y marcado semántico |
| Interfaz (Estilos) | CSS3 | — | Estilos visuales y diseño responsive |
| Interfaz (Framework) | Bootstrap | 5.3 | Librería UI, grilla, componentes |
| Interfaz (Lógica) | JavaScript | ES2020+ | Interactividad del cliente, llamadas AJAX |
| Backend | PHP | 8.x | Lógica de negocio, routing API, sesiones |
| Patrón Backend | REST API | — | Comunicación HTTP stateless |
| Persistencia | MySQL | 8.x | Almacenamiento relacional |
| Seguridad | JWT | — | Tokens stateless de autenticación |
| Intercambio de datos | JSON | — | Serialización request/response |
| Servidor web | Apache | 2.4 | Manejo HTTP, routing con .htaccess |
| Control de versiones | Git | 2.x | Control de código, estrategia de ramas |

---

## 4. Módulos del Sistema

El sistema se descompone en los siguientes módulos funcionales. Cada módulo mapea a un directorio en el backend y a un conjunto correspondiente de tablas en la base de datos.

| Módulo | Responsabilidad | Actores principales |
| --- | --- | --- |
| Auth | Registro, login, emisión y validación de JWT | Invitado, Usuario registrado, Admin |
| Products | Catálogo, categorías, inventario, búsqueda | Admin, Cliente |
| Cart | Carrito de compras por sesión o persistente | Cliente |
| Orders | Creación de pedidos, estados, historial | Cliente, Admin |
| Payments | Intentos de pago, registro de transacciones, webhooks | Cliente, Pasarela de pago |
| Users | Perfil de usuario, libreta de direcciones | Usuario registrado, Admin |
| Admin Panel | Backoffice: CRUD productos, gestión pedidos | Admin |

---

## 5. Patrones de Comunicación

### 5.1 Cliente ↔ Backend

Toda comunicación entre navegador y backend usa **HTTP/1.1** con **JSON** como formato de datos.

- **Solicitudes síncronas:** AJAX estándar (API `fetch`) para operaciones de lectura.
- **Envíos de formularios:** solicitudes `POST` con `application/json`.
- **Autenticación:** token JWT en el header `Authorization: Bearer <token>` en cada request protegido.

### 5.2 Backend ↔ Base de datos

PHP se comunica con MySQL mediante **PDO (PHP Data Objects)** usando exclusivamente *prepared statements*. La interpolación directa de strings en SQL está estrictamente prohibida para prevenir SQL injection.

### 5.3 Gestión de sesión

- Los JWT se emiten al iniciar sesión y se almacenan en el cliente en `localStorage` (o `sessionStorage` para auth solo por sesión).
- Expiración del access token: **2 horas**.
- La estrategia de refresh token se define en la especificación del módulo Auth.

---

## 6. Estructura de Directorios

```
/
├── public/                   # Document root de Apache
│   ├── index.php              # Front controller — enruta todas las requests
│   ├── assets/
│   │   ├── css/               # Hojas de estilo compiladas
│   │   ├── js/                # Módulos JavaScript del frontend
│   │   └── images/
├── src/
│   ├── Controllers/           # Adaptadores de entrada — HTTP
│   ├── Services/              # Capa de aplicación — casos de uso
│   ├── Domain/                # Núcleo de negocio — entidades/reglas
│   ├── Repositories/          # Adaptadores de salida — acceso a BD
│   ├── Middleware/            # JWT, CORS, rate limiting
│   └── Config/                # Conexión BD, configuración env
├── views/                     # Plantillas HTML server-side (si aplica)
├── tests/                     # Tests unitarios e integración
├── .htaccess                  # Reglas rewrite de Apache
└── composer.json              # Manifiesto de dependencias PHP
```

---

## 7. Preocupaciones Transversales (*Cross-Cutting Concerns*)

### 7.1 Autenticación y autorización

- Todos los endpoints excepto `POST /api/auth/login` y `POST /api/auth/register` requieren un JWT válido.
- Control de acceso basado en roles (RBAC): `customer`, `admin`.
- La validación de JWT se ejecuta en middleware antes de llegar al controlador.

### 7.2 Manejo de errores

Todas las respuestas del API usan un sobre (envelope) consistente:

```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "El producto solicitado no existe.",
    "field": null
  }
}
```

Los códigos HTTP deben usarse semánticamente:

- `200 OK` — lectura/actualización exitosa
- `201 Created` — creación exitosa
- `400 Bad Request` — error de validación
- `401 Unauthorized` — JWT inválido o ausente
- `403 Forbidden` — rol insuficiente
- `404 Not Found` — recurso inexistente
- `422 Unprocessable Entity` — violación de regla de negocio
- `500 Internal Server Error` — excepción no manejada

### 7.3 Validación de entrada

Toda entrada recibida desde el cliente debe validarse antes de llegar a la capa de servicios:

- Validar campos requeridos.
- Acotar largo de strings.
- Validar numéricos como enteros/decimales positivos.
- Emails deben cumplir el formato RFC 5322.

### 7.4 CORS

Apache se configura para permitir solicitudes **solo** desde el origen del frontend. En producción, `Access-Control-Allow-Origin` no debe ser `*`.

### 7.5 Logging

Logs a nivel de aplicación se escriben en `/var/log/app/app.log`. Cada entrada incluye: timestamp, request ID, método HTTP, URI, status code de respuesta y duración en ms.

---

## 8. Flujo de Datos — Ciclo de Vida de una Request Típica

```
Navegador
  │
  │  1. GET /api/products?category=shoes
  ▼
Apache (.htaccess reescribe hacia /public/index.php)
  │
  │  2. Front controller resuelve la ruta
  ▼
Middleware (validación JWT, rate limit)
  │
  │  3. Request pasa middleware
  ▼
ProductController::index()
  │
  │  4. Llama al service con parámetros validados
  ▼
ProductService::listByCategory(category: "shoes")
  │
  │  5. Consulta repositorio
  ▼
ProductRepository::findByCategory(category: "shoes")
  │
  │  6. Ejecuta SQL preparado contra MySQL
  ▼
MySQL retorna result set
  │
  │  7. Repo mapea filas a objetos de dominio
  ▼
Service arma estructura de respuesta
  │
  │  8. Controller codifica JSON
  ▼
Navegador recibe: { "success": true, "data": [...] }
```

---

## 9. Restricciones de Seguridad

- Todas las consultas a BD usan PDO prepared statements (sin concatenación de SQL).
- Contraseñas con `password_hash()` usando `PASSWORD_BCRYPT` con costo ≥ 12.
- La clave secreta JWT se guarda en variables de entorno, nunca hardcodeada.
- Subida de archivos (imágenes de producto): validar MIME y restringir a `image/jpeg`, `image/png`, `image/webp`. Tamaño máx: 2 MB.
- Configuración sensible (credenciales BD, JWT secret) se carga desde `.env` y queda fuera del control de versiones.

---

## 10. Dependencias de Integración

| Este módulo depende de | Provee a |
| --- | --- |
| MySQL (persistencia) | Todos los módulos backend |
| Apache (serving) | Frontend y backend |
| Librería JWT (auth) | Auth → todas las rutas protegidas |
| Bootstrap CDN o local (estilos) | Todas las páginas frontend |

---

## 11. Criterios de Aceptación

- [ ]  Todos los endpoints HTTP retornan JSON con el envelope success/error definido.
- [ ]  Ningún endpoint retorna HTTP 200 cuando existe error en el body.
- [ ]  Validación JWT aplicada en todas las rutas marcadas como protegidas.
- [ ]  Todas las queries SQL usan prepared statements (sin excepciones).
- [ ]  La estructura de directorios coincide exactamente con esta especificación.
- [ ]  Bootstrap 5.3 está cargado y no existen frameworks CSS en conflicto.
- [ ]  La app corre correctamente en Apache con la configuración `.htaccess`.
- [ ]  Todas las variables de entorno se cargan desde `.env` y no se hardcodean.

---

## 12. Fuera de Alcance (*Out of Scope*)

- Funcionalidades en tiempo real (WebSockets, Server-Sent Events).
- Apps móviles nativas.
- Microservicios o despliegue con contenedores (se define aparte en especificaciones DevOps).
- Integración con SDK de pasarela de pago de terceros (se define en la spec del módulo Payments).