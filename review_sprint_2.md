# Reporte de Cambios y Correcciones — Sprint 2

Este documento detalla todos los problemas identificados y corregidos en la plataforma durante el **Sprint 2** para lograr su correcto funcionamiento en servidores locales como XAMPP/WAMP.

---

## 1. Corrección de Rutas en `public/setup.php`
* **Problema:** El script de diagnóstico `public/setup.php` intentaba cargar la configuración (`config/app.php`) y el autoloader (`src/Core/Autoloader.php`) usando `__DIR__`, buscando los archivos dentro del directorio `/public` en lugar de la raíz del proyecto. Esto arrojaba errores críticos de archivos no encontrados.
* **Solución:** Se corrigieron los paths de inclusión añadiendo el selector de directorio padre (`../`):
  ```php
  require_once __DIR__ . '/../config/app.php';
  require_once __DIR__ . '/../src/Core/Autoloader.php';
  ```

---

## 2. Enrutamiento Compatible con Subdirectorios (XAMPP/WAMP)
* **Problema:** El router compara la URI de la petición HTTP directamente con los patrones definidos (como `/api/catalogo`). Al desplegar el proyecto en un subdirectorio local (ej: `http://localhost/ecommerce-project-s1/public/api/catalogo`), la URI capturada era `/ecommerce-project-s1/public/api/catalogo`, lo que provocaba que ninguna ruta coincidiera y arrojara un error **404 ROUTE_NOT_FOUND**.
* **Solución:** Se modificó el constructor de la clase `App\Core\Request` ([`src/Core/Request.php`](file:///c:/xampp/htdocs/ecommerce-project-s1/src/Core/Request.php)) para que detecte dinámicamente si el script se está ejecutando desde un subdirectorio y remueva ese prefijo de la URI antes de pasarla al enrutador:
  ```php
  $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
  $basePath = str_replace('\\', '/', dirname($scriptName));
  if ($basePath === '/' || $basePath === '\\') {
      $basePath = '';
  }
  
  $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
  if ($basePath !== '' && str_starts_with($path, $basePath)) {
      $path = substr($path, strlen($basePath));
  }
  $this->uri = '/' . ltrim($path, '/');
  ```

---

## 3. Corrección de Semillas, Precios e Importación de Base de Datos
* **Problema:**
  * **Precios Incorrectos:** En `database/seed.sql`, los precios de los productos estaban en pesos chilenos en lugar de centavos. Como el backend divide los precios por 100 para mostrarlos, los productos se veían 100 veces más baratos (ej: `$6.000` en vez de `$599.990` CLP).
  * **Error de Sintaxis en phpMyAdmin (`SOURCE`):** El script `database/setup.sql` utilizaba el comando `SOURCE database/schema.sql;` para enlazar los archivos. Sin embargo, `SOURCE` es un comando de la interfaz de línea de comandos (CLI) de MySQL y no es una sentencia SQL estándar, por lo que al importar el archivo en phpMyAdmin arrojaba el error: `You have an error in your SQL syntax... near 'SOURCE database/schema.sql'`.
* **Solución:**
  * Se multiplicaron todos los precios de los productos y valores de cupones por 100 en el script semilla para que correspondan a centavos.
  * Se modificó `database/setup.sql` agregando `DROP DATABASE IF EXISTS uct_ecommerce` al inicio para limpiar ejecuciones anteriores.
  * **Consolidación en setup.sql:** Se eliminaron las sentencias `SOURCE` de `database/setup.sql` y se compilaron los contenidos completos de `database/schema.sql` y `database/seed.sql` directamente dentro de este archivo único. De esta forma, el archivo `setup.sql` es ahora **completamente autocontenido** y puede importarse vía phpMyAdmin sin ningún error de sintaxis.

---

## 4. Sincronización de Credenciales de Prueba con README
* **Problema:** El archivo `README.md` documentaba las credenciales del Administrador como `admin@test.com` (`admin123`) y del Cliente como `cliente@test.com` (`password123`). No obstante, en la semilla (`seed.sql`) estas cuentas no existían, lo que causaba confusión al intentar iniciar sesión.
* **Solución:** Se agregaron ambas cuentas de prueba a la tabla `usuarios` en `database/seed.sql` con sus correspondientes contraseñas encriptadas mediante `password_hash()` de PHP, permitiendo probar con cualquier set de credenciales.

---

## 5. Creación del Panel de Administración (`public/admin.html`)
* **Problema:** Aunque la lógica del panel administrativo estaba completamente escrita en el archivo JavaScript `public/js/admin.js`, la página HTML del dashboard (`admin.html`) **no existía en el repositorio**, imposibilitando el acceso de los administradores y rompiendo las redirecciones de la aplicación.
* **Solución:** Se creó el archivo `public/admin.html` con un diseño moderno y responsivo alineado a la paleta de colores institucional del proyecto (`#003366` UCT Primary, `#F2A900` UCT Accent):
  * **Sidebar:** Barra de navegación lateral que invoca las secciones dinámicas (Dashboard, Productos, Pedidos, Usuarios, Reportes) mediante la clase `.admin-nav-link` y el atributo `data-section`.
  * **Contenedor Principal:** `<div id="admin-content"></div>` donde `admin.js` renderiza las tablas CRUD y gráficos de control.
  * **Cuentas de Acceso:** Barra superior que muestra el nombre del usuario y el botón de cerrar sesión.

---

## 6. Ajustes de Redireccionamientos en el Frontend
* **Problema:** Múltiples redireccionamientos usaban rutas absolutas al dominio raíz (ej: `window.location.href = '/'` o `href="/"` en el menú). En XAMPP, esto causaba que el usuario fuera redirigido a `http://localhost/` en lugar de la carpeta del proyecto. Adicionalmente, el panel admin intentaba redirigir a un archivo `login.html` inexistente en caso de no estar autenticado.
* **Solución:**
  * Se modificaron los enlaces de `index.html` y las redirecciones de cierre de sesión en `app.js` para usar rutas relativas o calcular la base dinámica:
    ```javascript
    const base = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    window.location.href = base + '/';
    ```
  * Se modificó `public/js/admin.js` para que, en lugar de buscar un `login.html` ausente, redirija al usuario a la página de inicio con un parámetro de consulta:
    ```javascript
    window.location.href = base + '/index.html?showLogin=true';
    ```
  * Se agregó un script en `public/index.html` para abrir automáticamente el modal de inicio de sesión si se detecta dicho parámetro:
    ```javascript
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('showLogin') === 'true') {
        const modalEl = document.getElementById('loginModal');
        if (modalEl) {
            const loginModal = new bootstrap.Modal(modalEl);
            loginModal.show();
        }
    }
    ```

---

## 7. Solución al Conflicto de Ruta Raíz y DirectoryIndex
* **Problema:** En muchos servidores locales XAMPP, el archivo de índice por defecto (`DirectoryIndex`) está configurado para priorizar `index.php` sobre `index.html`. Por lo tanto, al ingresar a `http://localhost/ecommerce-project-s1/public/`, Apache cargaba `index.php`. Al ejecutarse, el Router no encontraba ninguna ruta API que coincidiera con la raíz `/` y respondía con el error: `ROUTE_NOT_FOUND (La ruta solicitada no existe: GET /)`.
* **Solución:**
  * Se añadió una regla `DirectoryIndex index.html` al inicio del archivo [.htaccess](file:///c:/xampp/htdocs/ecommerce-project-s1/public/.htaccess) en la carpeta `/public` para forzar a Apache a servir la landing page estática directamente.
  * Como respaldo redundante, se registró la ruta GET `/` en el Front Controller ([`public/index.php`](file:///c:/xampp/htdocs/ecommerce-project-s1/public/index.php)) para cargar y retornar el contenido de `index.html` en caso de que la petición sea derivada a PHP:
    ```php
    $router->get('/', function($request, $response) {
        $htmlPath = __DIR__ . '/index.html';
        if (file_exists($htmlPath)) {
            header('Content-Type: text/html; charset=utf-8');
            readfile($htmlPath);
        } else {
            $response->error('NOT_FOUND', 'Página no encontrada.', 404);
        }
    });
    ```

