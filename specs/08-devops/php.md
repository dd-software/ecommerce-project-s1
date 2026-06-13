# Especificación de Runtime PHP

**Proyecto:** UCT Ecommerce  

**Equipo:** Los Takas — Arquitectura & DevOps  

**Versión:** 1.0  

**Estado:** Sprint 1  

**Última actualización:** 2025  

---

## 1. Propósito y Objetivos

Este documento especifica el entorno de ejecución (*runtime*) de PHP para el backend de UCT Ecommerce. Define la versión requerida de PHP, dependencias de extensiones, directivas de configuración, estándares de codificación y restricciones de despliegue que aplican a todo el código PHP escrito para este proyecto.

Sirve como referencia autorizada para cualquier herramienta o integrante del equipo que genere, revise o despliegue código PHP.

---

## 2. Versión del Runtime

| Propiedad | Valor |
| --- | --- |
| Runtime | PHP |
| Versión requerida | 8.x (mínimo 8.1) |
| Versión recomendada | 8.2 o 8.3 |
| Línea base EOL | PHP 8.0 no es aceptable |

Todo el código PHP debe ser sintácticamente válido para PHP 8.1+. Se permiten funcionalidades de PHP 8.1 (enums, propiedades `readonly`, fibers) y PHP 8.2 (clases `readonly`, `true`/`false` como tipos independientes).

---

## 3. Extensiones PHP Requeridas

Las siguientes extensiones deben estar habilitadas en la instalación de PHP:

| Extensión | Propósito | Requerida |
| --- | --- | --- |
| `pdo` | Interfaz base de PHP Data Objects | Sí |
| `pdo_mysql` | Driver PDO para conectividad MySQL | Sí |
| `json` | Codificación/decodificación JSON | Sí |
| `mbstring` | Strings multibyte (UTF-8) | Sí |
| `openssl` | Firma JWT (HS256 vía HMAC-SHA256) | Sí |
| `hash` | Funciones de hashing criptográfico | Sí |
| `fileinfo` | Detección MIME para subida de archivos | Sí |
| `intl` | Internacionalización (si se agrega multi-moneda) | Recomendado |
| `opcache` | Caché de bytecode para rendimiento en producción | Sí (producción) |

Para verificar que las extensiones están cargadas:

```bash
php -m | grep -E "pdo|pdo_mysql|json|mbstring|openssl|hash|fileinfo|opcache"
```

---

## 4. Configuración PHP (`php.ini`)

### 4.1 Entorno de Desarrollo

```
; Reporte de errores — mostrar todos los errores en desarrollo
error_reporting = E_ALL
display_errors = On
display_startup_errors = On
log_errors = On
error_log = /var/log/php/php_errors.log

; Límites de ejecución
max_execution_time = 60
memory_limit = 256M

; Subida de archivos
file_uploads = On
upload_max_filesize = 4M
post_max_size = 8M
max_file_uploads = 5

; Sesión
session.use_strict_mode = 1
session.cookie_httponly = 1
session.cookie_secure = 0  ; 0 en dev (sin HTTPS), 1 en producción

; Zona horaria
date.timezone = America/Santiago
```

### 4.2 Entorno de Producción

```
; Reporte de errores — solo registrar, nunca mostrar
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/php_errors.log

; OPcache
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 60
opcache.validate_timestamps = 0  ; deshabilitar en producción para rendimiento

; Límites de ejecución
max_execution_time = 30
memory_limit = 128M

; Seguridad
expose_php = Off
session.use_strict_mode = 1
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Strict
```

---

## 5. Gestión de Dependencias

El proyecto usa **Composer** para la gestión de dependencias PHP.

### 5.1 Paquetes Requeridos

| Paquete | Restricción de versión | Propósito |
| --- | --- | --- |
| `firebase/php-jwt` | `^6.0` | Emisión y validación de JWT |
| `vlucas/phpdotenv` | `^5.0` | Carga del archivo `.env` |
| `respect/validation` | `^2.0` | Reglas de validación de entrada |

### 5.2 Estructura de `composer.json`

```json
{
  "name": "uct/ecommerce-backend",
  "description": "UCT Ecommerce PHP Backend",
  "require": {
    "php": ">=8.1",
    "firebase/php-jwt": "^6.0",
    "vlucas/phpdotenv": "^5.0",
    "respect/validation": "^2.0"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.0"
  },
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Tests\\": "tests/"
    }
  }
}
```

### 5.3 Autoloading

Todas las clases siguen autoloading **PSR-4**. El namespace raíz `App\` mapea al directorio `src/`.

```
App\Controllers\ProductController   →  src/Controllers/ProductController.php
App\Services\OrderService           →  src/Services/OrderService.php
App\Repositories\UserRepository     →  src/Repositories/UserRepository.php
App\Domain\Product                  →  src/Domain/Product.php
App\Middleware\JwtMiddleware        →  src/Middleware/JwtMiddleware.php
```

---

## 6. Estándares de Codificación

Todo el código PHP debe cumplir **PSR-12** (*Extended Coding Style*).

### 6.1 Reglas Clave

- Los archivos usan solo la etiqueta de apertura `<?php` (sin `?>` en archivos PHP puros).
- Codificación UTF-8 sin BOM.
- Saltos de línea Unix (`\n`).
- Indentación de 4 espacios (sin tabs).
- Llaves de clases y métodos en la misma línea que la declaración.
- `declare(strict_types=1);` debe ir al inicio de cada archivo PHP después de `<?php`.

### 6.2 Seguridad de Tipos (*Type Safety*)

```php
<?php

declare(strict_types=1);

namespace App\Services;

class ProductService
{
    public function __construct(
        private readonly ProductRepository $repository
    ) {
    }

    public function findById(int $id): ?Product
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException("El ID del producto debe ser un entero positivo.");
        }

        return $this->repository->findById($id);
    }
}
```

Todos los métodos `public` y `protected` deben declarar tipo de retorno. Todos los parámetros deben declarar tipos. `mixed` se permite solo cuando sea realmente inevitable.

### 6.3 Patrones Prohibidos

```php
// PROHIBIDO — riesgo de SQL injection
$pdo->query("SELECT * FROM users WHERE id = " . $_GET['id']);

// PROHIBIDO — eval() siempre está prohibido
eval($userInput);

// PROHIBIDO — sin strict types
<?php
// falta declare(strict_types=1)

// PROHIBIDO — suprimir errores con @
$result = @file_get_contents($url);

// PROHIBIDO — extract() sobre input de usuario
extract($_POST);
```

---

## 7. Variables de Entorno

Toda la configuración se carga desde un archivo `.env` en la raíz del proyecto. El archivo `.env` nunca debe commitearse al repositorio.

Se debe mantener un `.env.example` con valores placeholder.

| Variable | Tipo | Descripción |
| --- | --- | --- |
| `DB_HOST` | string | Hostname MySQL (p. ej. `127.0.0.1`) |
| `DB_PORT` | integer | Puerto MySQL (default: `3306`) |
| `DB_NAME` | string | Nombre de base de datos |
| `DB_USER` | string | Usuario de base de datos |
| `DB_PASS` | string | Password de base de datos |
| `JWT_SECRET` | string | String aleatorio mínimo 32 caracteres para firmar JWT |
| `JWT_EXPIRY` | integer | TTL del token en segundos (default: `7200`) |
| `APP_ENV` | string | `development` o `production` |
| `APP_DEBUG` | boolean | `true` solo en desarrollo |

Patrón de carga:

```php
<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'JWT_SECRET']);
```

---

## 8. Requisitos de Seguridad

1. **`declare(strict_types=1)`** debe estar presente en cada archivo `.php`.
2. Todas las consultas a base de datos deben usar PDO prepared statements con parámetros enlazados.
3. Todo string aportado por el usuario que se renderiza en HTML debe escaparse con `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`.
4. Passwords deben hashearse con `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])`.
5. La verificación de passwords debe usar `password_verify()`.
6. `JWT_SECRET` debe tener al menos 32 caracteres aleatorios (generado con `openssl rand -base64 32`).
7. `expose_php = Off` en `php.ini` de producción.
8. Ningún input de usuario puede llegar a `eval()`, `system()`, `exec()`, `shell_exec()`, `passthru()` o `popen()`.

---

## 9. Testing

### 9.1 Framework

Se usa **PHPUnit 10** para tests unitarios y de integración.

### 9.2 Estructura de Directorios

```
tests/
├── Unit/
│   ├── Services/
│   └── Domain/
└── Integration/
    ├── Controllers/
    └── Repositories/
```

### 9.3 Ejecución de Tests

```bash
# Ejecutar todos los tests
./vendor/bin/phpunit

# Ejecutar con reporte de cobertura
./vendor/bin/phpunit --coverage-html coverage/
```

### 9.4 Cobertura Mínima

La cobertura de tests unitarios debe ser ≥ 80% para todas las clases de servicios y dominio.

---

## 10. Criterios de Aceptación

- [ ]  La versión de PHP es 8.1 o superior en todos los entornos.
- [ ]  Todas las extensiones requeridas (`pdo`, `pdo_mysql`, `mbstring`, `openssl`, `json`) están habilitadas.
- [ ]  Todos los archivos PHP comienzan con `declare(strict_types=1)`.
- [ ]  Todos los métodos públicos tienen declaraciones de tipos en parámetros y retornos.
- [ ]  No existe interpolación directa de strings SQL en ninguna parte del codebase.
- [ ]  Las contraseñas se almacenan con `password_hash()` usando `PASSWORD_BCRYPT` y costo ≥ 12.
- [ ]  `display_errors = Off` en `php.ini` de producción.
- [ ]  `.env` está en `.gitignore` y nunca se commitea.
- [ ]  La suite de PHPUnit corre sin errores.
- [ ]  OPcache está habilitado en el entorno de producción.