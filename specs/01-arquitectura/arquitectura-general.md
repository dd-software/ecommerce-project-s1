# Especificación General de Arquitectura

**Proyecto:** UCT Ecommerce  
**Equipo:** Los Takas — Arquitectura & DevOps  
**Versión:** 1.1  
**Estado:** Sprint 2  
**Última actualización:** 2026  

---

## Tabla de Contenidos

1. [Propósito y Objetivos](#1-propósito-y-objetivos)
2. [Patrón Arquitectónico](#2-patrón-arquitectónico)
3. [Stack Tecnológico](#3-stack-tecnológico)
4. [Entorno de Desarrollo Local (XAMPP / WampServer)](#4-entorno-de-desarrollo-local-xampp--wampserver)
5. [Módulos del Sistema](#5-módulos-del-sistema)
6. [Patrones de Comunicación](#6-patrones-de-comunicación)
7. [Estructura de Directorios](#7-estructura-de-directorios)
8. [Flujo de Datos — Ciclo de Vida de una Request](#8-flujo-de-datos--ciclo-de-vida-de-una-request)
9. [Preocupaciones Transversales](#9-preocupaciones-transversales)
10. [Restricciones de Seguridad](#10-restricciones-de-seguridad)
11. [Decisiones Arquitectónicas (ADR)](#11-decisiones-arquitectónicas-adr)
12. [Glosario del Dominio](#12-glosario-del-dominio)
13. [Dependencias de Integración](#13-dependencias-de-integración)
14. [Criterios de Aceptación](#14-criterios-de-aceptación)
15. [Fuera de Alcance](#15-fuera-de-alcance)

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

### 2.3 Mapa de dependencias entre módulos

```
Auth ◄─────────────────── Todos los módulos protegidos
                          (valida JWT via middleware)

Products ◄──────────────── Orders  (verifica stock)
         ◄──────────────── Inventory (gestiona stock)

Orders ◄────────────────── Payments (actualiza estado)
       ◄────────────────── Cart     (genera el pedido)

Users ◄─────────────────── Auth    (crea/autentica usuario)
      ◄─────────────────── Orders  (asocia pedido a usuario)
```

> **Regla:** Un módulo solo puede comunicarse con otro a través de su interfaz de servicio pública. Ningún módulo accede directamente a las tablas de base de datos de otro módulo.

---

## 3. Stack Tecnológico

| Capa | Tecnología | Versión | Responsabilidad |
|------|------------|---------|-----------------|
| Interfaz (Frontend) | HTML5 | — | Estructura de páginas y marcado semántico |
| Interfaz (Estilos) | CSS3 | — | Estilos visuales y diseño responsive |
| Interfaz (Framework) | Bootstrap | 5.3 | Librería UI, grilla, componentes |
| Interfaz (Lógica) | JavaScript | ES2020+ | Interactividad del cliente, llamadas AJAX |
| Backend | PHP | 8.2+ | Lógica de negocio, routing API, sesiones |
| Patrón Backend | REST API | — | Comunicación HTTP stateless |
| Persistencia | MySQL | 8.x | Almacenamiento relacional |
| Seguridad | JWT (HS256) | — | Tokens stateless de autenticación |
| Intercambio de datos | JSON | — | Serialización request/response |
| Servidor web | Apache | 2.4 | Manejo HTTP, routing con .htaccess |
| Control de versiones | Git | 2.x | Control de código, estrategia de ramas |
| Entorno local (dev) | XAMPP / WampServer | ≥ 8.2 | Stack AMP local para desarrollo |

---

## 4. Entorno de Desarrollo Local (XAMPP / WampServer)

El entorno de desarrollo local se gestiona a través de **XAMPP** (recomendado para Windows y macOS) o **WampServer** (alternativa Windows). Ambas soluciones proveen el stack Apache + MySQL + PHP en una sola instalación sin requerir configuración de servidor independiente.

| Herramienta | SO soportado | Versión requerida | Observaciones |
|-------------|--------------|-------------------|---------------|
| XAMPP | Windows / macOS / Linux | ≥ 8.2 (paquete PHP 8.2+) | **Recomendado** para el equipo |
| WampServer | Windows | ≥ 3.3.x (PHP 8.2+) | Alternativa viable en Windows |

### 4.1 URLs por entorno

| Entorno | Herramienta | Frontend | API REST |
|---------|-------------|----------|----------|
| **Desarrollo** | XAMPP / WampServer | `http://uct-ecommerce.local` | `http://uct-ecommerce.local/api/v1` |
| **Staging** | VPS Linux + Apache | `https://staging.ecommerce.uct.cl` | `https://staging.ecommerce.uct.cl/api/v1` |
| **Producción** | VPS Linux + Apache | `https://ecommerce.uct.cl` | `https://ecommerce.uct.cl/api/v1` |

### 4.2 Configuración con XAMPP

#### Paso 1 — Clonar el repositorio

```
# Windows
C:\xampp\htdocs\uct-ecommerce\

# macOS
/Applications/XAMPP/htdocs/uct-ecommerce/

# Linux
/opt/lampp/htdocs/uct-ecommerce/
```

#### Paso 2 — Habilitar mod_rewrite

En `httpd.conf` (XAMPP), descomentar la línea:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

> ⚠️ **Crítico:** En XAMPP para Windows, `mod_rewrite` viene **deshabilitado** por defecto. Sin este módulo, el `.htaccess` del proyecto es ignorado silenciosamente y todas las rutas del API retornarán 404.

#### Paso 3 — Configurar Virtual Host

Editar `C:\xampp\apache\conf\extra\httpd-vhosts.conf` (o equivalente en macOS/Linux):

```apache
<VirtualHost *:80>
    ServerName uct-ecommerce.local
    DocumentRoot "C:/xampp/htdocs/uct-ecommerce/public"

    <Directory "C:/xampp/htdocs/uct-ecommerce/public">
        Options -Indexes -FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  "logs/uct-ecommerce-error.log"
    CustomLog "logs/uct-ecommerce-access.log" combined
</VirtualHost>
```

También descomentar en `httpd.conf`:

```apache
Include conf/extra/httpd-vhosts.conf
```

#### Paso 4 — Configurar archivo hosts

Agregar al archivo `hosts` del sistema operativo:

```
127.0.0.1   uct-ecommerce.local
```

- **Windows:** `C:\Windows\System32\drivers\etc\hosts` (editar como Administrador)
- **macOS / Linux:** `/etc/hosts` (editar con `sudo`)

#### Paso 5 — Crear base de datos en phpMyAdmin

1. Abrir `http://localhost/phpmyadmin`
2. Crear base de datos `uct_ecommerce`
   - Charset: `utf8mb4`
   - Collation: `utf8mb4_unicode_ci`
3. Crear usuario dedicado: `uct_app` con contraseña segura
4. Otorgar solo privilegios `SELECT, INSERT, UPDATE, DELETE` sobre `uct_ecommerce`
5. Ejecutar las migraciones de `database/migrations/` en orden cronológico

#### Paso 6 — Configurar variables de entorno

```bash
cp .env.example .env
# Editar .env con las credenciales de la BD local
```

#### Paso 7 — Instalar dependencias PHP

```bash
cd C:\xampp\htdocs\uct-ecommerce
composer install
```

#### Paso 8 — Reiniciar Apache

Desde el panel de control de XAMPP, detener y reiniciar Apache.

Verificar acceso: `http://uct-ecommerce.local` debe cargar la aplicación.

### 4.3 Configuración con WampServer

1. Instalar WampServer 3.3+ con PHP 8.2.
2. Copiar el proyecto en `C:\wamp64\www\uct-ecommerce\`.
3. Click izquierdo en el ícono de WampServer en la barra de tareas → *Your VirtualHosts* → *Add a VirtualHost*.
4. Configurar:
   - **Virtual Host Name:** `uct-ecommerce.local`
   - **Document Root:** `C:\wamp64\www\uct-ecommerce\public`
5. WampServer actualiza automáticamente el archivo `hosts`.
6. Reiniciar todos los servicios.

### 4.4 Checklist de variables de entorno requeridas (`.env`)

| Variable | Ejemplo (dev local) | Descripción |
|----------|---------------------|-------------|
| `DB_HOST` | `127.0.0.1` | Host MySQL |
| `DB_PORT` | `3306` | Puerto MySQL |
| `DB_NAME` | `uct_ecommerce` | Nombre de la base de datos |
| `DB_USER` | `uct_app` | Usuario de BD (sin privilegios DDL) |
| `DB_PASS` | `s3cr3t_local` | Password de BD |
| `JWT_SECRET` | *(mínimo 32 chars aleatorios)* | Clave de firma JWT |
| `JWT_EXPIRY` | `7200` | TTL del token en segundos |
| `APP_ENV` | `development` | Entorno actual |
| `APP_DEBUG` | `true` | Solo `true` en desarrollo |

Generar un `JWT_SECRET` seguro:

```bash
openssl rand -base64 32
```

---

## 5. Módulos del Sistema

El sistema se descompone en los siguientes módulos funcionales. Cada módulo mapea a un directorio en el backend y a un conjunto correspondiente de tablas en la base de datos.

| Módulo | Responsabilidad | Actores principales |
|--------|-----------------|---------------------|
| Auth | Registro, login, emisión y validación de JWT | Invitado, Usuario registrado, Admin |
| Products | Catálogo, categorías, búsqueda | Admin, Cliente |
| Inventory | Stock, movimientos, alertas de stock bajo | Admin, Supervisor, Vendedor |
| Cart | Carrito de compras por sesión o persistente | Cliente |
| Orders | Creación de pedidos, estados, historial | Cliente, Admin |
| Payments | Intentos de pago, registro de transacciones, webhooks | Cliente, Pasarela de pago |
| Users | Perfil de usuario, libreta de direcciones | Usuario registrado, Admin |
| Admin Panel | Backoffice: CRUD productos, gestión pedidos | Admin |

### 5.1 Roles del sistema (RBAC)

| Rol | Descripción | Módulos con acceso especial |
|-----|-------------|----------------------------|
| `customer` | Usuario registrado que compra | Cart, Orders, Users (propio) |
| `admin` | Operador de backoffice con acceso total | Todos los módulos |
| `supervisor` | Operador con acceso a inventario y reportes | Inventory, Orders (lectura) |
| `vendedor` | Operador de punto de venta | Inventory (ajustes), Orders (lectura) |

---

## 6. Patrones de Comunicación

### 6.1 Cliente ↔ Backend

Toda comunicación entre navegador y backend usa **HTTP/1.1** con **JSON** como formato de datos.

- **Solicitudes síncronas:** AJAX estándar (API `fetch`) para operaciones de lectura.
- **Envíos de formularios:** solicitudes `POST` con `application/json`.
- **Autenticación:** token JWT en el header `Authorization: Bearer <token>` en cada request protegido.

### 6.2 Backend ↔ Base de datos

PHP se comunica con MySQL mediante **PDO (PHP Data Objects)** usando exclusivamente *prepared statements* con parámetros enlazados. La interpolación directa de strings en SQL está **estrictamente prohibida**, sin excepciones, incluyendo valores numéricos como `LIMIT` y `OFFSET` (usar `bindValue()` con `PDO::PARAM_INT`).

### 6.3 Gestión de sesión

- Los JWT se emiten al iniciar sesión y se almacenan en el cliente en `localStorage` (clave: `uct_auth_token`).
- Expiración del access token: **2 horas** (7200 segundos).
- La estrategia de refresh token se define en la especificación del módulo Auth.

### 6.4 Flujo de autenticación JWT

```
Cliente                        Backend                     MySQL
  │                               │                           │
  │  POST /api/v1/auth/login      │                           │
  │  { email, password }          │                           │
  ├──────────────────────────────►│                           │
  │                               │  SELECT * FROM users      │
  │                               │  WHERE email = ?          │
  │                               ├──────────────────────────►│
  │                               │◄──────────────────────────┤
  │                               │  password_verify()        │
  │                               │  JWT::encode(payload)     │
  │◄──────────────────────────────┤                           │
  │  { token: "eyJ..." }          │                           │
  │                               │                           │
  │  localStorage.setItem(token)  │                           │
  │                               │                           │
  │  GET /api/v1/orders           │                           │
  │  Authorization: Bearer eyJ... │                           │
  ├──────────────────────────────►│                           │
  │                         JwtMiddleware::validate()         │
  │                         ✓ Token válido → continúa         │
  │                         ✗ Token inválido → 401            │
  │◄──────────────────────────────┤                           │
  │  { success: true, data: [...] }                           │
```

---

## 7. Estructura de Directorios

```
/
├── public/                    # Document root de Apache
│   ├── index.php               # Front controller — enruta todas las requests
│   └── assets/
│       ├── css/                # Hojas de estilo compiladas
│       ├── js/                 # Módulos JavaScript del frontend
│       └── images/
├── src/
│   ├── Controllers/            # Adaptadores de entrada — HTTP
│   ├── Services/               # Capa de aplicación — casos de uso
│   ├── Domain/                 # Núcleo de negocio — entidades/reglas
│   ├── Repositories/           # Adaptadores de salida — acceso a BD
│   ├── Exceptions/             # Jerarquía de excepciones custom
│   ├── Middleware/             # JWT, CORS, rate limiting
│   └── Config/                 # Conexión BD, configuración env
├── views/                      # Plantillas HTML server-side (si aplica)
├── tests/
│   ├── Unit/                   # Tests de Services y Domain
│   └── Integration/            # Tests de Controllers y Repositories
├── database/
│   └── migrations/             # Archivos SQL en orden cronológico
├── scripts/
│   ├── setup-local.sh          # Setup automático en Linux/macOS
│   └── setup-local.ps1         # Setup automático en Windows (PowerShell)
├── docs/                       # Especificaciones de cada módulo
├── .htaccess                   # Reglas rewrite de Apache
├── .env.example                # Variables de entorno (sin valores reales)
├── composer.json
├── composer.lock               # Commiteado — asegura versiones idénticas
└── README.md                   # Índice de documentación y quickstart
```

---

## 8. Flujo de Datos — Ciclo de Vida de una Request Típica

```
Navegador
  │
  │  1. GET /api/v1/products?category=shoes
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
  │  4. Valida parámetros de entrada
  ▼
ProductService::listByCategory(category: "shoes")
  │
  │  5. Aplica reglas de negocio, consulta repositorio
  ▼
ProductRepository::findByCategory(category: "shoes")
  │
  │  6. Ejecuta SQL preparado contra MySQL
  ▼
MySQL retorna result set
  │
  │  7. Repository mapea filas a objetos de dominio
  ▼
Service arma estructura de respuesta
  │
  │  8. Controller codifica JSON
  ▼
Navegador recibe: { "success": true, "data": [...] }
```

---

## 9. Preocupaciones Transversales (*Cross-Cutting Concerns*)

### 9.1 Autenticación y autorización

- Todos los endpoints excepto `POST /api/v1/auth/login` y `POST /api/v1/auth/register` requieren un JWT válido.
- Control de acceso basado en roles (RBAC): `customer`, `admin`, `supervisor`, `vendedor`.
- La validación de JWT se ejecuta en middleware antes de llegar al controlador.
- Ningún módulo decodifica JWT por su cuenta — todos usan el contexto inyectado por el middleware.

### 9.2 Jerarquía de excepciones

Todas las excepciones del dominio extienden de una base común:

```
\Exception
└── App\Exceptions\AppException             ← base de todas las app exceptions
    ├── App\Exceptions\ValidationException  ← 400/422 — datos inválidos
    ├── App\Exceptions\AuthException        ← 401 — autenticación fallida
    ├── App\Exceptions\ForbiddenException   ← 403 — permisos insuficientes
    ├── App\Exceptions\NotFoundException    ← 404 — recurso no encontrado
    └── App\Exceptions\BusinessException    ← 409/422 — regla de negocio violada
```

El front controller captura estas excepciones y las convierte al envelope JSON de error.

### 9.3 Manejo de errores

Todas las respuestas del API usan un envelope consistente:

```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "El producto solicitado no existe.",
    "field": null
  },
  "meta": {
    "request_id": "req_abc123"
  }
}
```

Códigos HTTP semánticos:

| Código | Uso |
|--------|-----|
| `200 OK` | Lectura/actualización exitosa |
| `201 Created` | Creación exitosa |
| `400 Bad Request` | Error de validación de entrada |
| `401 Unauthorized` | JWT inválido o ausente |
| `403 Forbidden` | Rol insuficiente |
| `404 Not Found` | Recurso inexistente |
| `409 Conflict` | Conflicto de estado (ej. stock insuficiente) |
| `422 Unprocessable Entity` | Violación de regla de negocio |
| `500 Internal Server Error` | Excepción no manejada |

### 9.4 Validación de entrada

Toda entrada recibida desde el cliente debe validarse antes de llegar a la capa de servicios:

- Validar campos requeridos.
- Acotar largo de strings.
- Validar numéricos como enteros/decimales positivos.
- Emails deben cumplir el formato RFC 5322.
- Parámetros de paginación: `page` ≥ 1, `per_page` entre 1 y 100.

### 9.5 CORS

- En **desarrollo local**, `Access-Control-Allow-Origin: http://uct-ecommerce.local`.
- En **producción**, `Access-Control-Allow-Origin` debe ser el dominio exacto del frontend. El wildcard `*` está **prohibido** cuando se usa el header `Authorization`.

### 9.6 Logging

Logs a nivel de aplicación se escriben en `/var/log/app/app.log`. Cada entrada incluye: timestamp, request ID, método HTTP, URI, status code de respuesta y duración en ms.

---

## 10. Restricciones de Seguridad

- Todas las consultas a BD usan PDO prepared statements (sin concatenación de SQL, incluyendo LIMIT/OFFSET).
- Contraseñas con `password_hash()` usando `PASSWORD_BCRYPT` con costo ≥ 12.
- La clave secreta JWT se guarda en variables de entorno, nunca hardcodeada.
- Subida de archivos (imágenes de producto): validar MIME y restringir a `image/jpeg`, `image/png`, `image/webp`. Tamaño máx: 2 MB.
- Configuración sensible (credenciales BD, JWT secret) se carga desde `.env` y queda fuera del control de versiones.
- `in_array()` siempre debe usarse con tercer argumento `true` para comparación estricta.

---

## 11. Decisiones Arquitectónicas (ADR)

Los ADR documentan el *por qué* detrás de las decisiones importantes. Deben consultarse antes de proponer cambios estructurales.

### ADR-001 — Arquitectura Hexagonal sobre MVC clásico

**Decisión:** Se usa Arquitectura Hexagonal en lugar de MVC tradicional.  
**Razón:** MVC clásico tiende a generar "fat controllers" o "fat models" a medida que crece la lógica de negocio. La arquitectura hexagonal fuerza que la lógica de negocio quede aislada en la capa de dominio/servicios, independiente de HTTP y de la base de datos. Esto facilita el testing unitario (se puede testear el Service sin levantar Apache ni MySQL) y permite cambiar el adaptador de persistencia sin tocar la lógica de negocio.

### ADR-002 — JWT sobre sesiones PHP

**Decisión:** Se usa JWT (HS256) en lugar de sesiones PHP (`$_SESSION`).  
**Razón:** Las sesiones PHP son stateful y requieren almacenamiento del lado del servidor (archivos o BD). JWT es stateless, lo que simplifica el escalado horizontal y elimina la necesidad de sincronizar sesiones entre servidores. El costo es que los tokens no pueden invalidarse antes de su expiración sin un mecanismo de blacklist — tradeoff aceptable para este proyecto.

### ADR-003 — MySQL sobre PostgreSQL

**Decisión:** Se usa MySQL 8.x como motor de base de datos.  
**Razón:** El entorno XAMPP/WampServer incluye MySQL por defecto, lo que simplifica el setup del entorno de desarrollo sin dependencias adicionales. MySQL 8.x provee todas las funcionalidades necesarias para este proyecto (transacciones ACID, JSON columns, full-text search). PostgreSQL sería una elección igualmente válida en un entorno de producción dedicado.

### ADR-004 — Monolito modular sobre microservicios

**Decisión:** El sistema se construye como un monolito modular.  
**Razón:** Para un equipo académico pequeño trabajando en Sprint 1, los microservicios introducen complejidad operacional innecesaria (service discovery, comunicación inter-servicio, deployment independiente). El monolito modular con límites bien definidos permite refactorizar hacia microservicios en el futuro si fuera necesario.

---

## 12. Glosario del Dominio

| Término | Definición |
|---------|------------|
| **Producto** | Artículo del catálogo disponible para la venta. Tiene precio, stock y categoría. |
| **Categoría** | Agrupación jerárquica de productos (puede tener subcategorías). |
| **Carrito** | Colección temporal de productos seleccionados por un cliente antes de comprar. |
| **Pedido** | Intención de compra confirmada por el cliente. Tiene ítems, total y estado. |
| **Ítem de pedido** | Una línea dentro de un pedido: un producto, cantidad y precio al momento de compra. |
| **Pago** | Intento de cobro asociado a un pedido. Registra el resultado de la pasarela de pago. |
| **Stock** | Cantidad de unidades físicas disponibles de un producto. |
| **Movimiento de inventario** | Registro de cualquier cambio en el stock: egreso por venta, entrada por reposición, ajuste manual. |
| **JWT** | JSON Web Token — credencial de sesión del usuario, firmada con HS256. |
| **Envelope** | Sobre estándar que envuelve todas las respuestas del API (`success`, `data`/`error`, `meta`). |
| **Centavos** | Unidad mínima monetaria usada internamente. $89.90 se almacena y transmite como `8990`. |
| **Slug** | Versión URL-amigable del nombre de un recurso (ej. `zapatillas-running-2025`). |

---

## 13. Dependencias de Integración

| Este módulo depende de | Provee a |
|------------------------|----------|
| MySQL (persistencia) | Todos los módulos backend |
| Apache (serving) | Frontend y backend |
| Librería JWT (auth) | Auth → todas las rutas protegidas |
| Bootstrap CDN o local (estilos) | Todas las páginas frontend |

---

## 14. Criterios de Aceptación

- [ ] Todos los endpoints HTTP retornan JSON con el envelope success/error definido.
- [ ] Ningún endpoint retorna HTTP 200 cuando existe error en el body.
- [ ] Validación JWT aplicada en todas las rutas marcadas como protegidas.
- [ ] Todas las queries SQL usan prepared statements (sin excepciones, incluyendo LIMIT/OFFSET).
- [ ] La estructura de directorios coincide exactamente con esta especificación.
- [ ] Bootstrap 5.3 está cargado y no existen frameworks CSS en conflicto.
- [ ] La app corre correctamente en Apache con la configuración `.htaccess`.
- [ ] La app corre correctamente en XAMPP (Windows/macOS) siguiendo la guía de la §4.
- [ ] Todas las variables de entorno se cargan desde `.env` y no se hardcodean.
- [ ] `mod_rewrite` está habilitado y verificado en el entorno local.
- [ ] La base de datos `uct_ecommerce` fue creada con charset `utf8mb4` en phpMyAdmin.

---

## 15. Fuera de Alcance (*Out of Scope*)

- Funcionalidades en tiempo real (WebSockets, Server-Sent Events).
- Apps móviles nativas.
- Microservicios o despliegue con contenedores (se define aparte en especificaciones DevOps).
- Integración con SDK de pasarela de pago de terceros (se define en la spec del módulo Payments).

---

## CHANGELOG

| Versión | Fecha | Cambio | Autor |
|---------|-------|--------|-------|
| 1.0 | 2025 | Versión inicial | Los Takas |
| 1.1 | 2025 | Agregada §4 (XAMPP/WampServer), §11 (ADR), §12 (Glosario), mapa de dependencias de módulos, roles RBAC completos, jerarquía de excepciones | Los Takas |
