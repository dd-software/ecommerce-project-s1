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
Ecomers/
├── README.md
├── .env.example            # Plantilla de configuración (copiar a .env)
├── .htaccess               # Reescritura: redirige la raíz a public/
├── config/
│   └── app.php             # Carga el .env y define constantes (APP_ENV, JWT_SECRET, ...)
├── database/
│   ├── schema.sql          # Crea la BD uct_ecommerce y las tablas
│   ├── seed.sql            # Datos de ejemplo (productos, categorías, usuarios)
│   ├── setup.sql           # Crea BD + usuario dedicado (despliegue con usuario propio)
│   ├── fix_encoding.sql            # Corrige tildes de productos si se importó mal
│   └── fix_categorias_encoding.sql # Corrige tildes de categorías
├── public/                 # Document root (lo que sirve Apache)
│   ├── .htaccess           # Reglas de reescritura + cabeceras de seguridad (CSP)
│   ├── index.php           # Front controller: registra rutas y despacha
│   ├── index.html          # Tienda (diseño Bootstrap)
│   ├── css/  js/           # Estilos y scripts de la tienda Bootstrap
│   └── store/              # Tienda alternativa (diseño estilo PCFactory)
├── src/                    # Backend (PSR-4, namespace App\)
│   ├── Core/               # Router, Request, Response, Database, JWT, Session, middlewares
│   ├── Auth/  Catalogo/  Carrito/  Checkout/    # Módulos A–D
│   ├── Pagos/  Inventario/  Admin/  Integracion/ # Módulos E–H
│   └── ...
└── specs/                  # Documentación SDD (specs por módulo, contratos API, modelado)
    ├── 00-producto/   01-arquitectura/   02-modelado/   03-contratos-api/
    └── 04-modulos/    (modulo-a … modulo-h)
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

Para desarrollo local basta con **XAMPP** (incluye Apache, MySQL/MariaDB y PHP).

| Componente | Versión Requerida | Notas |
|---|---|---|
| PHP | 8.2+ | Incluido en XAMPP |
| MySQL / MariaDB | 8.x / 10.x | Incluido en XAMPP |
| Apache | 2.4 | Con módulos **`mod_rewrite`** y **`mod_headers`** activos (por defecto en XAMPP) |
| Git | 2.40+ | Para clonar el repositorio |

---

### 💻 Despliegue Local con XAMPP (lo que usamos)

> Apache debe tener habilitados **`mod_rewrite`** y **`mod_headers`** (vienen activos por defecto en XAMPP). El proyecto **no** usa `config/database.php`: la configuración se lee de un archivo **`.env`**.

#### 1. Clonar el repositorio dentro de `htdocs`

```bash
cd C:\xampp\htdocs
git clone https://github.com/dd-software/ecommerce-project-s1.git Ecomers
cd Ecomers
git checkout team-4-los-debians
```

> ⚠️ La carpeta **debe llamarse `Ecomers`**: las URLs y el enrutamiento asumen la ruta `/Ecomers/public/`.

#### 2. Iniciar los servicios

Abrir el **XAMPP Control Panel** y pulsar **Start** en **Apache** y **MySQL**.

#### 3. Crear e importar la base de datos

Desde una terminal (Git Bash o `cmd`) en la raíz del proyecto:

```bash
# Crea la base de datos uct_ecommerce y todas las tablas
"C:\xampp\mysql\bin\mysql.exe" -u root < database/schema.sql

# Carga datos de ejemplo. ¡Usar utf8mb4 para que las tildes no salgan como "?"!
"C:\xampp\mysql\bin\mysql.exe" -u root --default-character-set=utf8mb4 uct_ecommerce < database/seed.sql
```

> Alternativa con **phpMyAdmin** (http://localhost/phpmyadmin): pestaña *Importar* → `database/schema.sql`, luego `database/seed.sql`, con cotejamiento **utf8mb4**.
>
> Si las tildes ya quedaron como `?`, corrígelas con:
> ```bash
> "C:\xampp\mysql\bin\mysql.exe" -u root --default-character-set=utf8mb4 uct_ecommerce < database/fix_encoding.sql
> "C:\xampp\mysql\bin\mysql.exe" -u root --default-character-set=utf8mb4 uct_ecommerce < database/fix_categorias_encoding.sql
> ```

#### 4. Configurar el entorno (`.env`)

Copiar `.env.example` → `.env` y ajustarlo para XAMPP (usuario `root` **sin contraseña**):

```ini
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=uct_ecommerce
DB_USER=root
DB_PASS=
JWT_SECRET=pon_aqui_una_cadena_aleatoria_de_minimo_32_caracteres
JWT_EXPIRY=7200
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/Ecomers/public
```

> ⚠️ El `.env.example` trae `DB_USER=ecommerce_app` por defecto. En XAMPP usa **`root`** y deja **`DB_PASS` vacío**, o la app fallará con *"Access denied for user 'ecommerce_app'"*.

#### 5. Abrir en el navegador

| URL | Qué es |
|---|---|
| `http://localhost/Ecomers/public/` | Entrada (redirige a la tienda) |
| `http://localhost/Ecomers/public/index.html` | Tienda — diseño Bootstrap |
| `http://localhost/Ecomers/public/store/` | Tienda — diseño estilo PCFactory |
| `http://localhost/Ecomers/public/api/health` | Healthcheck de la API REST |

La API REST vive bajo `http://localhost/Ecomers/public/api/...`

#### Servidor PHP embebido (opcional, solo para probar la API)

```bash
php -S localhost:8080 -t public/
```

> ⚠️ El servidor embebido **no soporta `.htaccess`**, así que el enrutamiento y las cabeceras de seguridad no funcionan igual. Para la experiencia completa usa Apache (XAMPP).

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
-- El nombre real de la base de datos es uct_ecommerce.
-- database/setup.sql ya crea la BD y un usuario dedicado 'ecommerce_app'.
CREATE DATABASE uct_ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ecommerce_app'@'localhost' IDENTIFIED BY 'contraseña_segura_aqui';
GRANT ALL PRIVILEGES ON uct_ecommerce.* TO 'ecommerce_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Importar schema y datos (utf8mb4 para conservar las tildes)
mysql -u ecommerce_app -p uct_ecommerce --default-character-set=utf8mb4 < database/schema.sql
mysql -u ecommerce_app -p uct_ecommerce --default-character-set=utf8mb4 < database/seed.sql
```

#### 4. Desplegar la Aplicación

```bash
# Clonar el proyecto
cd /var/www
sudo git clone https://github.com/dd-software/ecommerce-project-s1.git ecommerce
cd ecommerce
sudo git checkout team-4-los-debians

# Configurar permisos
sudo chown -R www-data:www-data /var/www/ecommerce
sudo chmod -R 755 /var/www/ecommerce
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

La configuración se lee de un archivo **`.env`** (cargado por `config/app.php`). No existe `config/database.php`.

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
JWT_SECRET=clave-secreta-jwt-de-al-menos-32-caracteres
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

- 🌐 Abrir `https://tudominio.com/` — deberías ver la tienda
- 🩺 Comprobar la API: `https://tudominio.com/api/health` debe responder `{"success":true,...}`
- 🔐 Iniciar sesión con un usuario seed (ver tabla abajo)

---

### 🔐 Usuarios de Prueba (datos de `seed.sql`)

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | `admin@uct.cl` | `admin123` |
| Cliente | `juan@email.com` | `password123` |
| Cliente | `maria@email.com` | `password123` |
| Vendedor | `pedro@uct.cl` | `password123` |
| Supervisor | `ana@uct.cl` | `password123` |

> Estas credenciales funcionan tras importar `database/seed.sql`. También puedes crear una cuenta nueva desde el botón **"Crear cuenta"** de la tienda.

---

### 🛠️ Problemas comunes

| Síntoma | Causa / Solución |
|---|---|
| **500 Internal Server Error** o *"exceeded the limit of 10 internal redirects"* | Falta el archivo **`public/.htaccess`**. Debe existir (contiene las reglas de reescritura y las cabeceras de seguridad). |
| *"Access denied for user 'ecommerce_app'"* | El `.env` no apunta a `root`. En XAMPP: `DB_USER=root` y `DB_PASS=` vacío. |
| Tildes/ñ aparecen como `?` | Importaste sin `utf8mb4`. Reimporta `seed.sql` con `--default-character-set=utf8mb4` o corre `database/fix_encoding.sql` y `database/fix_categorias_encoding.sql`. |
| **404** en `http://localhost/Ecomers/` | Falta el `/public/`. Entra por `http://localhost/Ecomers/public/`. |
| La página carga pero no aparecen productos | MySQL no está iniciado, la BD no se importó, o el `.env` tiene credenciales/DB incorrectas. |

---

*Ecommerce UCT — SDD Academic Edition v1.0 · Proyecto Integrador DDS + IA*
