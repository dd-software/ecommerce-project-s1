# Ecommerce UCT — SDD Academic Edition v1.0

> Repositorio documental maestro del proyecto integrador **Diseño y Desarrollo de Software + IA**.
> Plataforma de ecommerce construida con metodología **Specification Driven Development (SDD)**, arquitectura modular A-H y equipos especializados coordinados en Jira.

---

## Tabla de Contenidos

- [Visión del Producto](#visión-del-producto)
- [Stack Tecnológico](#stack-tecnológico)
- [Metodología](#metodología)
- [Arquitectura Modular](#arquitectura-modular)
- [Modelo de Datos](#modelo-de-datos)
- [Reglas de Negocio](#reglas-de-negocio)
- [Contratos API](#contratos-api)
- [Estructura del Repositorio](#estructura-del-repositorio)
- [Equipo y Gestión](#equipo-y-gestión)

---

## Visión del Producto

**Problema que resuelve:** Las pequeñas tiendas necesitan una plataforma de ventas online simple, administrable y sin dependencia de grandes plataformas externas.

### Usuarios del sistema

| Rol | Descripción |
|---|---|
| **Visitante** | Navega el catálogo sin autenticación |
| **Cliente** | Se registra, compra y gestiona sus pedidos |
| **Administrador** | Gestiona inventario, pedidos y reportes desde el dashboard |

### Capacidades del sistema

- Exploración de catálogo con categorías jerárquicas y variantes de producto (talla, color, SKU)
- Flujo completo de compra: carrito → checkout → pago → confirmación
- Gestión de pedidos con trazabilidad de estados
- Administración de inventario con control de stock mínimo
- Wishlist, reseñas y sistema de cupones/descuentos
- Documentos tributarios (boleta/factura) y gestión de devoluciones
- Panel de administración con reportes y auditoría del sistema

---

## Stack Tecnológico

| Capa | Tecnología |
|---|---|
| **Frontend** | HTML5, CSS3, Bootstrap 5.3, JavaScript |
| **Backend** | PHP 8, REST API |
| **Base de datos** | MySQL |
| **Autenticación** | JWT |
| **Intercambio de datos** | JSON |

---

## Metodología

Este proyecto aplica **Specification Driven Development (SDD)**: toda funcionalidad existe primero como especificación formal antes de escribir una sola línea de código.

### Flujo de trabajo

```
Spec → Revisión → Aprobación → Implementación → Testing → PR → Merge
```

- **Scrum** para gestión ágil de sprints (tablero en **Jira**)
- **GitHub Flow** para control de versiones: rama por feature, PR obligatorio, revisión de pares
- Cada módulo tiene su propio equipo responsable con especificaciones independientes

---

## Arquitectura Modular

El sistema se divide en **8 módulos** (A–H), cada uno con equipo y documentación propios:

| Módulo | Nombre | Responsabilidad |
|---|---|---|
| **A** | Catálogo | Listado, búsqueda y detalle de productos. Categorías y variantes |
| **B** | Carrito | Gestión del carrito de compras y cálculo de totales |
| **C** | Autenticación | Registro, login, JWT, roles y permisos (RBAC) |
| **D** | Checkout | Proceso de compra, selección de dirección y método de envío |
| **E** | Pasarela de Pago | Procesamiento de pagos, reintentos y estados de transacción |
| **F** | Inventario | Control de stock por variante, alertas de stock mínimo |
| **G** | Administración | Dashboard, gestión de pedidos, usuarios y reportes |
| **H** | Integración | Coordinación entre módulos, contratos compartidos y eventos |

Cada módulo contiene:

```
modulo-X/
├── spec.md                # Especificación funcional
├── user-stories.md        # Historias de usuario
├── casos-uso.md           # Casos de uso detallados
├── criterios-aceptacion.md
├── api-contract.md        # Contrato de API del módulo
├── tareas.md              # Backlog de tareas
├── checklist.md           # Checklist de completitud
├── testing.md             # Estrategia de testing
└── riesgos.md             # Riesgos identificados
```

---

## Modelo de Datos

El modelo cubre **14 dominios funcionales** con enfoque en escalabilidad, trazabilidad e integridad:

### Dominios

```
Usuarios / Roles / Direcciones
Catálogo / Productos / Variantes / Imágenes
Inventario
Carrito / Items
Pedidos / DetallePedido / EstadosPedido
Pagos
Envíos / MetodosEnvio / EstadosEnvio
Cupones / Descuentos
Impuestos / DocumentosTributarios
Wishlist
Reseñas
Devoluciones / Reembolsos
Auditoría
```

### Relaciones principales

```
Usuario 1:N Direccion
Usuario N:M Rol
Usuario 1:N Pedido
Usuario 1:1 Carrito
Categoria 1:N Producto
Producto 1:N VarianteProducto
VarianteProducto 1:1 Inventario
Carrito 1:N ItemCarrito
Pedido 1:N DetallePedido
Pedido 1:N EstadoPedido
Pedido 1:N Pago
Pedido N:M Cupon
Pedido 1:1 DocumentoTributario
Pedido 1:1 Envio
Pedido 1:N Devolucion
Devolucion 1:N Reembolso
```

> Ver diagrama ERD completo en [`specs/02-modelado/modelo-dominio.md`](specs/02-modelado/modelo-dominio.md)

### Principios de diseño del modelo

- **Soft Delete** en registros históricos (nunca eliminación física)
- **Snapshot inmutable** en DetallePedido (precio y nombre al momento de compra)
- **Roles por tabla** en lugar de ENUM, para escalabilidad RBAC
- **Pago 1:N** por pedido, para soportar reintentos y múltiples pasarelas
- **Stock en variante**, no en producto raíz

---

## Reglas de Negocio

| Código | Regla |
|---|---|
| RN-001 | No se pueden vender productos sin stock disponible |
| RN-002 | Solo los administradores pueden acceder al dashboard |
| RN-003 | El stock se descuenta únicamente tras confirmación de pago |
| RN-004 | Los usuarios deshabilitados no pueden iniciar sesión |
| RN-005 | Todo pedido debe tener trazabilidad completa de estados |

**Estados del pedido:** `pendiente_pago` → `pagado` → `en_preparacion` → `enviado` → `entregado` / `cancelado`

**Estados del pago:** `pendiente` → `aprobado` / `rechazado`

---

## Contratos API

Los contratos OpenAPI 3.0 de cada módulo se encuentran en [`specs/03-contratos-api/`](specs/03-contratos-api/):

| Archivo | Módulo |
|---|---|
| `auth.yaml` | Autenticación y sesiones |
| `catalogo.yaml` | Productos y categorías |
| `carrito.yaml` | Carrito de compras |
| `checkout.yaml` | Proceso de compra |
| `pagos.yaml` | Transacciones |
| `inventario.yaml` | Stock y variantes |
| `admin.yaml` | Panel de administración |

---

## Estructura del Repositorio

```
ecommerce-project-s1-team-1-heavyduty/
├── README.md
└── specs/
    ├── 00-producto/
    │   ├── vision-producto.md
    │   └── reglas-negocio.md
    ├── 01-arquitectura/
    │   └── arquitectura-general.md
    ├── 02-modelado/
    │   └── modelo-dominio.md
    ├── 03-contratos-api/
    │   ├── auth.yaml
    │   ├── catalogo.yaml
    │   ├── carrito.yaml
    │   ├── checkout.yaml
    │   ├── pagos.yaml
    │   ├── inventario.yaml
    │   └── admin.yaml
    └── 04-modulos/
        ├── modulo-a/   ← Catálogo
        ├── modulo-b/   ← Carrito
        ├── modulo-c/   ← Autenticación
        ├── modulo-d/   ← Checkout
        ├── modulo-e/   ← Pasarela de Pago
        ├── modulo-f/   ← Inventario
        ├── modulo-g/   ← Administración
        └── modulo-h/   ← Integración
```

---

## Equipo y Gestión

- **Metodología ágil:** Scrum con sprints gestionados en **Jira**
- **Control de versiones:** GitHub Flow — rama por feature, PR obligatorio con revisión de pares
- **Documentación:** Toda especificación debe estar aprobada antes de iniciar implementación (SDD)
- **Equipo:** Sección 1, Team 1 — Heavy Duty

---

---

## 🚀 Guía de Despliegue

### Requisitos del Sistema

| Componente | Versión Requerida |
|---|---|
| PHP | 8.x |
| MySQL | 8.x |
| Apache | 2.4 |
| Git | 2.40+ |

---

### 💻 Despliegue Local (Desarrollo)

#### Opción 1: XAMPP (Windows / Linux / macOS)

1. **Descargar e instalar XAMPP** desde [apachefriends.org](https://www.apachefriends.org)
2. **Clonar el repositorio** en la carpeta `htdocs`:
   ```bash
   cd C:\xampp\htdocs         # Windows
   cd /opt/lampp/htdocs        # Linux
   cd /Applications/XAMPP/htdocs  # macOS

   git clone https://github.com/dd-software/ecommerce-project-s1.git ecommerce
   cd ecommerce
   git checkout feature/full-platform
   ```
3. **Configurar base de datos:**
   - Abrir XAMPP Control Panel y arrancar **Apache** y **MySQL**
   - Abrir phpMyAdmin: http://localhost/phpmyadmin
   - Crear base de datos: `ecommerce_uct` (utf8mb4_general_ci)
   - Importar `database/schema.sql` desde la pestaña Importar
   - Importar `database/seed.sql` para datos de prueba
4. **Configurar la conexión:**
   - Copiar `config/database.example.php` → `config/database.php`
   - Editar credenciales (por defecto XAMPP: `root` / sin contraseña):
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'ecommerce_uct');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```
5. **Abrir en el navegador:** http://localhost/ecommerce/public

#### Opción 2: WAMP (Windows)

1. **Descargar e instalar WAMP** desde [wampserver.com](https://www.wampserver.com)
2. **Clonar el repositorio** en la carpeta `www`:
   ```bash
   cd C:\wamp64\www
   git clone https://github.com/dd-software/ecommerce-project-s1.git ecommerce
   cd ecommerce
   git checkout feature/full-platform
   ```
3. **Configurar base de datos:**
   - Arrancar WAMP (icono verde en la bandeja)
   - Abrir phpMyAdmin: http://localhost/phpmyadmin
   - Crear base de datos `ecommerce_uct` (utf8mb4_general_ci)
   - Importar `database/schema.sql` y `database/seed.sql`
4. **Configurar la conexión** en `config/database.php` (WAMP default: `root` / sin contraseña)
5. **Abrir en el navegador:** http://localhost/ecommerce/public

#### Opción 3: Servidor de desarrollo PHP embebido

```bash
git clone https://github.com/dd-software/ecommerce-project-s1.git
cd ecommerce-project-s1
git checkout feature/full-platform

# Iniciar servidor PHP embebido
php -S localhost:8080 -t public/
```

> ⚠️ El servidor embebido de PHP es útil para desarrollo rápido, pero no soporta `.htaccess`. Usa Apache o Nginx para todas las funcionalidades.

---

### 🌐 Despliegue en Producción (VPS Linux)

#### 1. Requisitos del Servidor

| Recurso | Mínimo | Recomendado |
|---|---|---|
| CPU | 1 vCPU | 2 vCPU |
| RAM | 1 GB | 4 GB |
| Disco | 20 GB SSD | 40 GB SSD |
| SO | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |

**Proveedores sugeridos:** Hetzner, DigitalOcean, Vultr, AWS Lightsail

#### 2. Instalación del Stack LAMP

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Apache
sudo apt install apache2 -y
sudo systemctl enable apache2

# Instalar PHP 8.x y extensiones necesarias
sudo apt install php8.3 php8.3-cli php8.3-common php8.3-mysql \
  php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip -y

# Instalar MySQL 8
sudo apt install mysql-server -y
sudo systemctl enable mysql
sudo mysql_secure_installation  # Seguir las instrucciones interactivas

# Habilitar mod_rewrite de Apache
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### 3. Configurar Base de Datos

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE ecommerce_uct CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ecommerce_user'@'localhost' IDENTIFIED BY 'contraseña_segura_aqui';
GRANT ALL PRIVILEGES ON ecommerce_uct.* TO 'ecommerce_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Importar schema y datos
mysql -u ecommerce_user -p ecommerce_uct < database/schema.sql
mysql -u ecommerce_user -p ecommerce_uct < database/seed.sql
```

#### 4. Desplegar la Aplicación

```bash
# Clonar el proyecto
cd /var/www
sudo git clone https://github.com/dd-software/ecommerce-project-s1.git ecommerce
cd ecommerce
sudo git checkout feature/full-platform

# Configurar permisos
sudo chown -R www-data:www-data /var/www/ecommerce
sudo chmod -R 755 /var/www/ecommerce
sudo chmod -R 775 /var/www/ecommerce/storage
```

#### 5. Configurar Apache Virtual Host

Crear archivo de configuración:

```bash
sudo nano /etc/apache2/sites-available/ecommerce.conf
```

```apache
<VirtualHost *:80>
    ServerName tudominio.com
    ServerAdmin admin@tudominio.com
    DocumentRoot /var/www/ecommerce/public

    <Directory /var/www/ecommerce/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/ecommerce-error.log
    CustomLog ${APACHE_LOG_DIR}/ecommerce-access.log combined
</VirtualHost>
```

```bash
# Habilitar el sitio y reiniciar Apache
sudo a2ensite ecommerce.conf
sudo systemctl reload apache2
```

#### 6. Configurar Variables de Entorno

```bash
cd /var/www/ecommerce
cp config/database.example.php config/database.php

# Editar con las credenciales de producción
sudo nano config/database.php
```

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_uct');
define('DB_USER', 'ecommerce_user');
define('DB_PASS', 'contraseña_segura_aqui');

define('APP_ENV', 'production');
define('APP_DEBUG', false);
define('APP_URL', 'https://tudominio.com');
define('JWT_SECRET', 'clave-secreta-jwt-de-al-menos-32-caracteres');
define('JWT_EXPIRY', 3600);
```

#### 7. HTTPS con Certbot (SSL Gratuito)

```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d tudominio.com
```

#### 8. Verificar el Despliegue

- 🌐 Abrir `https://tudominio.com` — deberías ver la landing page del catálogo
- 🔐 Probar login con el usuario seed: `cliente@test.com` / `password123`
- ⚙️ Acceder al admin: `admin@test.com` / `admin123` → `/admin`

---

### 🔐 Usuarios de Prueba (Seed Data)

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | admin@test.com | admin123 |
| Cliente | cliente@test.com | password123 |

---

*Ecommerce UCT — SDD Academic Edition v1.0 · Proyecto Integrador DDS + IA*
