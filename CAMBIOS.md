# Registro de Cambios — UCT E-Commerce

Documentación de todos los cambios realizados sobre el proyecto original.

---

## Resumen de archivos modificados y creados

| Archivo | Estado | Qué cambió |
|---------|--------|-----------|
| `README.md` | **Creado** | Documentación profesional del proyecto |
| `CODIGO.md` | **Creado** | Documentación técnica del código |
| `database/seed.sql` | **Modificado** | De 2 productos a 16 productos en 6 categorías |
| `database/add_products.sql` | **Creado** | Script para agregar productos a BD existente |
| `src/Models/Product.php` | **Modificado** | +3 métodos nuevos |
| `src/Controllers/CatalogController.php` | **Modificado** | +2 métodos, 1 método actualizado |
| `public/index.php` | **Modificado** | +2 rutas nuevas, refactorización del bloque de catálogo |
| `public/js/catalog.js` | **Modificado** | Reescritura completa con buscador y filtros |
| `public/js/ui.js` | **Modificado** | Conexión de eventos al buscador del header |
| `public/index.html` | **Modificado** | +2 elementos HTML para categorías y título dinámico |

---

## Cambio 1 — `database/seed.sql`

### Qué había antes
```sql
-- 2 categorías
INSERT INTO categories (name, slug, description) VALUES
('Electrónica', 'electronica', 'Dispositivos y gadgets'),
('Ropa', 'ropa', 'Indumentaria y moda');

-- 2 productos, 4 variantes, 4 registros de inventario
```

### Qué hay ahora
```sql
-- Limpieza al inicio para que sea reutilizable (idempotente)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE inventory;
TRUNCATE TABLE product_variants;
-- ... (todas las tablas en orden inverso de dependencias)
SET FOREIGN_KEY_CHECKS = 1;

-- 6 categorías
('Electrónica'), ('Ropa'), ('Calzado'),
('Hogar y Cocina'), ('Deportes y Fitness'), ('Computación y Gaming')

-- 16 productos, 35 variantes, 35 registros de inventario
```

### Por qué se hizo así
El bloque `TRUNCATE` al inicio permite ejecutar el seed sobre una base de datos que ya tiene datos, sin generar errores por IDs duplicados. `SET FOREIGN_KEY_CHECKS = 0` desactiva temporalmente las restricciones de clave foránea para poder vaciar las tablas en cualquier orden.

---

## Cambio 2 — `database/add_products.sql` (archivo nuevo)

Archivo alternativo al seed completo, diseñado para bases de datos que **ya tienen** los 2 productos originales y solo necesitan agregar los nuevos.

### Técnica usada: `INSERT ... SELECT` con `INSERT IGNORE`

```sql
-- En lugar de hardcodear IDs:
INSERT INTO products (category_id, name, slug, description) VALUES (3, 'Laptop', ...)

-- Se consulta el ID por slug para no asumir nada:
INSERT IGNORE INTO products (category_id, name, slug, description)
SELECT id, 'Laptop Pro 15"', 'laptop-pro-15', 'Descripción...'
FROM categories WHERE slug = 'electronica';
```

`INSERT IGNORE` hace que si ya existe un registro con el mismo slug (clave única), MySQL simplemente lo omite sin lanzar un error. Esto hace el script seguro para ejecutarlo múltiples veces.

Lo mismo aplica para las variantes (usando el slug del producto) y el inventario (usando el SKU de la variante).

---

## Cambio 3 — `src/Models/Product.php`

### Métodos agregados

#### `getByCategory(int $categoryId): array`
Filtra productos por categoría. Usa un parámetro posicional `?` para evitar SQL injection.

```php
public function getByCategory(int $categoryId): array {
    $stmt = $this->db->prepare("
        SELECT p.id, p.name, p.slug, p.description, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.deleted_at IS NULL AND p.category_id = ?
        ORDER BY p.name
    ");
    $stmt->execute([$categoryId]);
    return $stmt->fetchAll();
}
```

#### `search(string $query): array`
Busca en tres columnas a la vez usando `LIKE`. El mismo valor `%query%` se pasa tres veces como parámetro posicional porque PDO no permite reutilizar el mismo parámetro nombrado (`:q`) en múltiples posiciones.

```php
public function search(string $query): array {
    $q = '%' . $query . '%';   // Se arma el patrón LIKE en PHP
    $stmt = $this->db->prepare("
        SELECT p.id, p.name, p.slug, p.description, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.deleted_at IS NULL
          AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)
        ORDER BY p.name
    ");
    $stmt->execute([$q, $q, $q]);  // El mismo valor ocupa las 3 posiciones
    return $stmt->fetchAll();
}
```

#### `getCategories(): array`
Devuelve todas las categorías activas ordenadas alfabéticamente.

```php
public function getCategories(): array {
    $stmt = $this->db->query("
        SELECT id, name, slug, description
        FROM categories
        WHERE deleted_at IS NULL
        ORDER BY name
    ");
    return $stmt->fetchAll();
}
```

### Qué se mejoró en `getAll()`
Se agregó `ORDER BY p.name` para que los productos siempre lleguen en orden alfabético, en lugar de en el orden de inserción.

---

## Cambio 4 — `src/Controllers/CatalogController.php`

### Método `list()` — actualizado

**Antes:** devolvía todos los productos siempre.

**Ahora:** acepta un parámetro opcional `category_id` en la query string para filtrar:

```php
public function list(): void {
    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
    $products = $categoryId
        ? $this->productModel->getByCategory($categoryId)
        : $this->productModel->getAll();
    Response::json(['data' => $products]);
}
```

El cast `(int)` convierte el valor recibido a entero, eliminando cualquier carácter no numérico que pueda venir en la URL.

### Método `search()` — nuevo

```php
public function search(): void {
    $query = trim($_GET['q'] ?? '');   // trim() elimina espacios al inicio y final
    if (mb_strlen($query) < 2) {       // mb_strlen para contar caracteres Unicode correctamente
        Response::json(['data' => []]);
        return;
    }
    $products = $this->productModel->search($query);
    Response::json(['data' => $products]);
}
```

Se usa `mb_strlen` en vez de `strlen` porque `strlen` cuenta bytes, no caracteres. Una letra con tilde (ej: `á`) ocupa 2 bytes en UTF-8 pero es 1 carácter.

### Método `categories()` — nuevo

```php
public function categories(): void {
    $categories = $this->productModel->getCategories();
    Response::json(['data' => $categories]);
}
```

---

## Cambio 5 — `public/index.php`

### Antes
```php
use Src\Controllers\CatalogController;
$router->add('GET', '/api/catalog/products',      [new CatalogController(), 'list']);
$router->add('GET', '/api/catalog/products/{id}', [new CatalogController(), 'detail']);
```
Se creaban **dos instancias** separadas de `CatalogController` (y por tanto dos conexiones PDO).

### Ahora
```php
use Src\Controllers\CatalogController;
$catalog = new CatalogController();
$router->add('GET', '/api/catalog/products',        [$catalog, 'list']);
$router->add('GET', '/api/catalog/products/search', [$catalog, 'search']);
$router->add('GET', '/api/catalog/categories',      [$catalog, 'categories']);
$router->add('GET', '/api/catalog/products/{id}',   [$catalog, 'detail']);
```

Se usa **una sola instancia** para las cuatro rutas.

### Por qué el orden de las rutas importa

La ruta `/api/catalog/products/search` debe registrarse **antes** que `/api/catalog/products/{id}`. El router evalúa las rutas en orden secuencial: si `{id}` estuviera primero, la URL `/search` sería capturada como si `id = "search"`, el controlador buscaría el producto con ese ID numérico (que sería 0 tras el cast a entero) y devolvería un 404.

```
URL: /api/catalog/products/search

Ruta 1: /api/catalog/products         → no coincide (falta el segmento /search)
Ruta 2: /api/catalog/products/search  → COINCIDE ✓ → ejecuta search()
Ruta 3: /api/catalog/products/{id}    → nunca se evalúa (ya hubo coincidencia)
```

---

## Cambio 6 — `public/js/catalog.js`

El archivo fue reescrito completamente. Estos son los cambios respecto al original:

### Función `renderProducts()` — extraída del código original

**Antes:** la lógica de renderizar HTML estaba dentro de `loadProducts()`, mezclada con la llamada HTTP.

**Ahora:** es una función independiente que cualquier otra función puede llamar (búsqueda, filtro por categoría, carga inicial):

```javascript
function renderProducts(products, heading) {
    const headingEl = document.getElementById('products-heading');
    if (heading && headingEl) headingEl.textContent = heading;

    if (!products || products.length === 0) {
        container.innerHTML = '<p class="text-muted col-12 mt-3">No se encontraron productos.</p>';
        return;
    }
    container.innerHTML = products.map(product => `...`).join('');
}
```

### Variable `allProducts` — caché en memoria

```javascript
let allProducts = [];

async function loadProducts() {
    const res   = await api.request('/catalog/products');
    allProducts = res.data;  // Se guarda en memoria
    renderProducts(allProducts, 'Todos los Productos');
}
```

Cuando el usuario hace clic en "Todos" en el filtro de categorías, se reutiliza `allProducts` sin volver a llamar al servidor.

### `window.filterByCategory()` — filtro por categoría

```javascript
window.filterByCategory = async function(categoryId, btn) {
    // 1. Quitar resaltado de todos los botones
    document.querySelectorAll('#category-filter button').forEach(b => {
        b.className = 'btn btn-outline-secondary btn-sm';
    });
    // 2. Resaltar el botón clickeado
    btn.className = 'btn btn-primary btn-sm';

    // 3. Limpiar el buscador
    const searchInput = document.querySelector('.search-input');
    if (searchInput) searchInput.value = '';

    // 4. Mostrar spinner mientras carga
    container.innerHTML = '<div class="col-12 text-center">...</div>';

    // 5. Si es "Todos", usar caché; si es categoría, llamar al servidor
    if (!categoryId) {
        renderProducts(allProducts, 'Todos los Productos');
        return;
    }
    const res = await api.request(`/catalog/products?category_id=${categoryId}`);
    renderProducts(res.data, btn.textContent.trim());
};
```

Se define como `window.filterByCategory` (propiedad global) porque se invoca desde el atributo `onclick` en el HTML generado dinámicamente. Las funciones locales no son accesibles desde el HTML.

### `window.performSearch()` — buscador

```javascript
window.performSearch = async function(query) {
    // Si la búsqueda está vacía, volver al estado inicial
    if (!query) {
        const firstBtn = document.querySelector('#category-filter button');
        if (firstBtn) window.filterByCategory(null, firstBtn);
        else loadProducts();
        return;
    }
    // Quitar resaltado de categorías (la búsqueda es transversal)
    document.querySelectorAll('#category-filter button').forEach(b => {
        b.className = 'btn btn-outline-secondary btn-sm';
    });
    // Llamar al endpoint de búsqueda
    const res = await api.request(`/catalog/products/search?q=${encodeURIComponent(query)}`);
    renderProducts(res.data, `Resultados para "${query}"`);
};
```

`encodeURIComponent()` convierte caracteres especiales en la URL: un espacio se convierte en `%20`, una `ñ` en `%C3%B1`, etc. Sin esto, la URL quedaría malformada.

---

## Cambio 7 — `public/js/ui.js`

Se agregaron 8 líneas al final de `renderHeader()`, después de asignar `headerContainer.innerHTML`:

```javascript
// Conectar buscador
const searchInput = headerContainer.querySelector('.search-input');
const searchBtn   = headerContainer.querySelector('.search-btn');

const doSearch = () => {
    const q = (searchInput?.value ?? '').trim();
    if (typeof window.performSearch === 'function') window.performSearch(q);
};

if (searchBtn)   searchBtn.addEventListener('click', doSearch);
if (searchInput) searchInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') doSearch();
});
```

### Por qué va aquí y no en `catalog.js`

El buscador está en el **header**, que es renderizado por `ui.js`. Los event listeners deben agregarse **después** de que el HTML del header exista en el DOM. Como `ui.js` es quien crea ese HTML, es quien debe agregar los listeners.

La función `window.performSearch` puede o no existir (solo existe en `index.html` donde se carga `catalog.js`). Por eso se verifica con `typeof window.performSearch === 'function'` antes de llamarla — en otras páginas como `cart.html` el buscador simplemente no hace nada.

### El operador `?.` (optional chaining)

```javascript
const q = (searchInput?.value ?? '').trim();
```

`searchInput?.value` equivale a: "si `searchInput` existe, dame `.value`; si es `null`, dame `undefined` en vez de lanzar un error". El `?? ''` convierte ese `undefined` en cadena vacía.

---

## Cambio 8 — `public/index.html`

### Antes
```html
<h3 class="mb-4 fw-bold">Ofertas Destacadas</h3>
<div class="row g-4" id="product-list">
```

### Ahora
```html
<h3 class="mb-4 fw-bold" id="products-heading">Todos los Productos</h3>
<div class="d-flex flex-wrap gap-2 mb-4" id="category-filter"></div>
<div class="row g-4" id="product-list">
```

Dos cambios:

1. Se agregó `id="products-heading"` al `<h3>` para que JavaScript pueda actualizarlo dinámicamente con el texto "Resultados para X" o el nombre de la categoría activa.

2. Se agregó el `<div id="category-filter">` vacío. `loadCategories()` en `catalog.js` lo llena con botones al cargar la página. Usar `d-flex flex-wrap gap-2` de Bootstrap hace que los botones se acomoden en filas automáticamente si no caben en una sola línea.
