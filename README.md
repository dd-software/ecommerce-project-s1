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

> ✅ **Inicio rápido verificado (Linux + MySQL nativo).** Estos son los pasos exactos con los que la app quedó corriendo end-to-end: registro → login → catálogo → carrito → checkout → pago → panel admin. Si solo quieres levantarla, usa esta opción.
>
> ℹ️ Nota: el proyecto carga la configuración desde un archivo **`.env`** (ver `.env.example`), **no** desde `config/database.php`. La base de datos se llama **`uct_ecommerce`** y se crea/puebla con **`database/setup.sql`** (encadena esquema + usuarios/cupones + catálogo QuadCore + imágenes). Abajo hay ejemplos para **Linux, Windows y macOS**.

#### Opción recomendada: MySQL nativo + servidor embebido de PHP

**Requisitos:** PHP 8.1+ con `pdo_mysql` y `mbstring`, y **MySQL 8 server** (no solo el cliente: verifica con `which mysqld`).

```bash
# 0. Instalar el servidor MySQL si solo tienes el cliente, y arrancarlo
sudo apt install -y mysql-server
sudo systemctl enable --now mysql

# 1. Crear BD + usuario. OJO: se crea para 'localhost' Y '127.0.0.1'.
#    El código conecta por TCP a 127.0.0.1; un usuario solo '@localhost'
#    (socket) daría "access denied".
sudo mysql -e "CREATE DATABASE IF NOT EXISTS uct_ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'ecommerce_app'@'localhost' IDENTIFIED BY 'TU_PASS';"
sudo mysql -e "CREATE USER IF NOT EXISTS 'ecommerce_app'@'127.0.0.1' IDENTIFIED BY 'TU_PASS';"
sudo mysql -e "GRANT ALL PRIVILEGES ON uct_ecommerce.* TO 'ecommerce_app'@'localhost'; GRANT ALL PRIVILEGES ON uct_ecommerce.* TO 'ecommerce_app'@'127.0.0.1'; FLUSH PRIVILEGES;"

# 2. Cargar TODO de una (esquema + usuarios/cupones + catálogo QuadCore + imágenes).
#    setup.sql ya hace CREATE DATABASE + CREATE USER (localhost y 127.0.0.1) + los 4 seeds,
#    así que en la práctica los pasos 1 y 2 se reducen a:  sudo mysql < database/setup.sql
sudo mysql uct_ecommerce < database/schema.sql
sudo mysql uct_ecommerce < database/seed.sql
sudo mysql uct_ecommerce < database/seed_quadcore.sql   # catálogo real (95 productos, 20 categorías)
sudo mysql uct_ecommerce < database/seed_imagenes.sql   # imágenes de los productos

# 3. Configurar entorno
cp .env.example .env
#   Editar .env:
#     DB_PASS=TU_PASS  (la misma de arriba)
#     JWT_SECRET=...    (>=32 chars aleatorios)  ->  openssl rand -base64 48

# 4. Servir. Se pasa router.php para que /api/* funcione sin .htaccess.
php -S localhost:8000 -t public router.php
#   Abrir http://localhost:8000
```

> ⚠️ El servidor embebido de PHP **no lee `.htaccess`**, por eso pasamos `router.php`: replica la regla de Apache (los archivos reales —css/js/imágenes— se sirven directo y todo lo demás va a `public/index.php`). Sin él, las rutas `/api/*` devuelven 404.

#### 🪟 Windows (XAMPP)

1. Instalar [XAMPP](https://www.apachefriends.org) (trae PHP + MySQL + phpMyAdmin) y [Git para Windows](https://git-scm.com/download/win).
2. En el **XAMPP Control Panel**, arrancar **MySQL**.
3. Clonar y configurar (en Git Bash o la Shell de XAMPP):
   ```bash
   git clone https://github.com/dd-software/ecommerce-project-s1.git
   cd ecommerce-project-s1
   git checkout team-1-heavyduty
   cp .env.example .env
   ```
   En `.env`, usar el root de XAMPP (**sin contraseña**):
   `DB_HOST=127.0.0.1`, `DB_USER=root`, `DB_PASS=`, `DB_NAME=uct_ecommerce`, y un `JWT_SECRET` de 32+ caracteres.
4. Crear y poblar la BD (parado en la carpeta del proyecto):
   ```bash
   mysql -u root < database/setup.sql
   # si "mysql" no se reconoce:  C:\xampp\mysql\bin\mysql.exe -u root < database/setup.sql
   ```
   > Alternativa gráfica (phpMyAdmin): creá la BD `uct_ecommerce` e importá **en orden** `schema.sql` → `seed.sql` → `seed_quadcore.sql` → `seed_imagenes.sql`. (phpMyAdmin no soporta `SOURCE`, por eso ahí no sirve `setup.sql`.)
5. Levantar:
   ```bash
   php -S localhost:8000 -t public router.php
   # si "php" no se reconoce:  C:\xampp\php\php.exe -S localhost:8000 -t public router.php
   ```
   Abrir **http://localhost:8000**

#### 🍎 macOS (Homebrew)

```bash
# Requisitos
brew install php mysql git
brew services start mysql

# Proyecto
git clone https://github.com/dd-software/ecommerce-project-s1.git
cd ecommerce-project-s1
git checkout team-1-heavyduty
cp .env.example .env          # editar DB_* y JWT_SECRET

# BD + datos (setup.sql crea BD, usuario y carga los 4 seeds)
mysql -u root < database/setup.sql

# Servir
php -S localhost:8000 -t public router.php   # → http://localhost:8000
```

> El usuario `ecommerce_app` queda creado con la clave `app_password_here` (definida en `setup.sql` / `.env.example`). Para una clave propia, cambiala en ambos lados. En local también podés usar `root` en el `.env`.

> ⚠️ El servidor embebido de PHP **no** lee `.htaccess`; por eso siempre se sirve con `router.php` (replica el ruteo de Apache). Para producción usá Apache/Nginx con el `.htaccess` incluido.

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
CREATE DATABASE uct_ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ecommerce_app'@'localhost' IDENTIFIED BY 'contraseña_segura_aqui';
GRANT ALL PRIVILEGES ON uct_ecommerce.* TO 'ecommerce_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Importar esquema + TODOS los datos (catálogo real QuadCore + imágenes)
mysql -u ecommerce_app -p uct_ecommerce < database/schema.sql
mysql -u ecommerce_app -p uct_ecommerce < database/seed.sql
mysql -u ecommerce_app -p uct_ecommerce < database/seed_quadcore.sql
mysql -u ecommerce_app -p uct_ecommerce < database/seed_imagenes.sql
```

#### 4. Desplegar la Aplicación

```bash
# Clonar el proyecto
cd /var/www
sudo git clone https://github.com/dd-software/ecommerce-project-s1.git ecommerce
cd ecommerce
sudo git checkout team-1-heavyduty

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

#### 6. Configurar Variables de Entorno (`.env`)

La configuración va en un archivo **`.env`** en la raíz del proyecto (no en `config/database.php`).

```bash
cd /var/www/ecommerce
cp .env.example .env
sudo nano .env
```

```ini
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=uct_ecommerce
DB_USER=ecommerce_app
DB_PASS=contraseña_segura_aqui
JWT_SECRET=clave-secreta-aleatoria-de-32-o-mas-caracteres
JWT_EXPIRY=3600
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com
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
