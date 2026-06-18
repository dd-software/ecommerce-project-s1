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

---

## 8. Corrección de Carga Infinita en Catálogo e Integración de Búsqueda
* **Problema:**
  * **Advertencia de PHP en la API:** En `src/Catalogo/CatalogoRepository.php` (método `buscarPorId`), se utilizaba una variable `$p` no definida en lugar de `$producto`. Esto disparaba un warning de PHP en entornos locales con `APP_DEBUG=true` que corrompía la estructura del JSON y hacía fallar al parseador de frontend.
  * **Carga Infinita en Frontend:** Si la API del catálogo retornaba un error (`data.success === false`), el método `loadProducts()` de `catalogo.js` no controlaba este estado en su condicional, omitiendo la limpieza del spinner y dejándolo girando infinitamente.
  * **Desconexión en Búsqueda Global:** La barra de búsqueda en el navbar del sitio redirigía a `/?search=QUERY`, mientras que el catálogo esperaba el parámetro `q` en la URL, provocando que la búsqueda no funcionara.
* **Solución:**
  * Se corrigió la referencia a `$producto` en la consulta del detalle en `CatalogoRepository.php`.
  * Se añadió una estructura `else` al condicional de `data.success` en `catalogo.js` para limpiar el spinner y renderizar un mensaje de error limpio en caso de fallo del backend.
  * Se actualizó la lectura de URL en el catálogo para soportar tanto `q` como `search` de forma transparente.

---

## 9. Solución de Enrutamiento Base de la API en el Frontend (`app.js`)
* **Problema:** 
  Al intentar conectar el frontend con los endpoints del backend, las llamadas AJAX fallaban con error `404 Not Found` debido a que la ruta configurada en la constante `apiBase` apuntaba a `/api` directamente en lugar de pasar a través de `/public/api`. Esto impedía el correcto consumo de servicios al no estar alineados a la estructura del servidor Apache en subdirectorios.
* **Solución:**
  Se modificó la línea 7 del archivo [`public/js/app.js`](file:///c:/xampp/htdocs/ecommerce-project-s1/public/js/app.js) para concatenar `/public/api` en lugar de `/api` (posteriormente refinado utilizando la API `URL` y `document.baseURI` para mayor dinamismo y robustez).

---

## 10. Error 500 Intermitente de Base de Datos al Agregar Productos al Carrito
* **Problema:** Mientras probábamos la inserción de productos en el carrito, la API comenzó a retornar errores `500 Internal Server Error` de forma intermitente. Esto ocurría porque los procesos persistentes de Apache en XAMPP compartían variables de entorno globales; el validador `getenv()` en [`config/app.php`](file:///c:/xampp/htdocs/ecommerce-project-s1/config/app.php) leía credenciales obsoletas de solicitudes previas, bloqueando la carga del archivo `.env` correcto y rechazando el acceso a la base de datos.
* **Solución:** Se modificó la validación para verificar únicamente la súperglobal `$_ENV` (`!array_key_exists($clave, $_ENV)`) en lugar de `getenv()`, asegurando una lectura limpia de credenciales en cada petición.

---

## 11. Integración de Carrito Dinámico, Formato de Precios y Flujo de Checkout en Modales
* **Problema:**
  * **Actualización del Carrito:** Al agregar productos al carrito de compras desde el catálogo, el listado detallado dentro del panel lateral (`cartOffcanvas`) no se regeneraba dinámicamente, dando la falsa impresión de que no se agregaban elementos.
  * **Precios y Totales ausentes o incorrectos:** Los montos de Subtotal, IVA y Total se visualizaban en blanco o como valores inválidos porque el backend retornaba montos crudos en centavos sin formatear, y el frontend usaba claves inconsistentes.
  * **Redireccionamiento Inexistente:** Al presionar "Proceder al Pago", el sistema intentaba redirigir a los archivos inexistentes `/checkout.html` y `/login.html?redirect=checkout.html`, rompiendo el flujo.
  * **Punto Verde Desalineado:** El indicador verde con el conteo de ítems del carrito flotaba desalineado y alejado del texto "Carrito" en pantallas móviles, posicionándose a la extrema derecha porque el enlace ocupaba todo el ancho del menú móvil.
* **Solución:**
  * **Carga en tiempo real:** Se vinculó el evento `show.bs.offcanvas` en [`public/js/carrito.js`](file:///c:/xampp/htdocs/ecommerce-project-s1/public/js/carrito.js) para recargar y renderizar los ítems dinámicamente cada vez que se despliega el menú lateral.
  * **Cálculo y Formato de Cifras:** Se modificó [`src/Carrito/CarritoService.php`](file:///c:/xampp/htdocs/ecommerce-project-s1/src/Carrito/CarritoService.php) para dividir los montos en centavos por 100 y retornar claves formateadas listas para el frontend (`subtotal_formateado`, `iva_formateado`, `total_formateado`), además de refactorizar la estructura de renderizado HTML a un estilo tipo Flexbox mucho más cómodo.
  * **Checkout e Inicio de Sesión In-place:** Se reemplazó el redireccionamiento HTML externo por el modal `#checkoutModal`. Si el usuario no está logueado, se bloquea la compra temporalmente y se levanta automáticamente el modal de inicio de sesión (`#loginModal`).
  * **Alineación del Punto Verde:** Se envolvió el ícono y la palabra "Carrito" dentro de un contenedor inline relativo (`<span class="position-relative d-inline-block">`) en [`public/index.html`](file:///c:/xampp/htdocs/ecommerce-project-s1/public/index.html) para que el indicador verde se posicione estrictamente adyacente al texto en todas las resoluciones.

---

## 12. Bucle de Redirección Inesperado al Intentar Probar el Checkout del Carrito
* **Problema:** Al intentar probar el flujo del checkout tras remover la redirección a `/login.html`, el navegador seguía redirigiendo automáticamente a esa ruta inexistente. Esto se debía a que el navegador mantenía guardada en su caché local una copia obsoleta de los archivos HTML y JS, ignorando los cambios realizados en el código local de `checkout.js`.
* **Solución:** Se agregaron parámetros de versión (`?v=2`) a las llamadas de scripts en [`public/index.html`](file:///c:/xampp/htdocs/ecommerce-project-s1/public/index.html) y se añadieron directivas en el archivo [`.htaccess`](file:///c:/xampp/htdocs/ecommerce-project-s1/public/.htaccess) para indicarle al navegador que nunca almacene en caché las páginas HTML, garantizando que el usuario siempre reciba los últimos scripts de frontend.

---

## 13. Reorganización de la Landing Page por Categorías y Soporte de Jerarquía de Productos
* **Problema:**
  * **Visualización Mezclada:** Por defecto, la landing page mostraba un grid genérico con todos los productos mezclados sin ningún criterio (celulares, libros, ropa de verano), lo cual hacía ver al sitio desorganizado y poco profesional.
  * **Filtros por Categoría Padre Incompletos:** Al intentar filtrar por una categoría principal (como *Tecnología*), la API del catálogo no asociaba jerárquicamente a sus subcategorías (*Computadores*, *Celulares*, *Audio*), mostrando 0 productos al usuario al seleccionar la categoría padre.
* **Solución:**
  * **Secciones Separadas por Categorías:** Se reestructuró [`public/index.html`](file:///c:/xampp/htdocs/ecommerce-project-s1/public/index.html) y [`public/js/catalogo.js`](file:///c:/xampp/htdocs/ecommerce-project-s1/public/js/catalogo.js) para dividir la Landing Page por departamentos de forma nativa (*Tecnología*, *Deportes/Actividad Física*, *Moda y Ropa*, *Libros*), cargando una fila organizada de hasta 4 productos para cada categoría.
  * **Píldoras de Acceso Rápido y Alternancia:** Se incorporaron botones/píldoras de categoría al inicio. Al hacer clic en una píldora o en "Ver Todo", la página oculta la Landing Page categorizada y despliega una vista de filtrado tradicional con la opción de "Volver al Inicio" en tiempo real sin recargar la página.
  * **Soporte Jerárquico en Backend:** Se optimizó la consulta SQL en [`src/Catalogo/CatalogoRepository.php`](file:///c:/xampp/htdocs/ecommerce-project-s1/src/Catalogo/CatalogoRepository.php) para resolver la herencia de categorías, listando productos de la categoría seleccionada o de cualquiera de sus categorías hijas:
    ```sql
    (p.id_categoria = :categoria_id OR p.id_categoria IN (SELECT id FROM categorias WHERE id_padre = :categoria_id_parent))
    ```






