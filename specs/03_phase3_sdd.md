# Módulo: Fase 3 - Catálogo, Inventario y Carrito (SDD)

## Análisis
La fase 3 introduce tres módulos vitales que interactúan fuertemente entre sí: Catálogo (A), Inventario (F) y Carrito de Compras (B).
- **Catálogo:** Solo lectura para usuarios anónimos o registrados. Lista productos y variantes.
- **Inventario:** Solo administradores. Visualiza alertas de stock mínimo.
- **Carrito:** Requiere autenticación JWT. Almacena ítems temporalmente con `cart_items` en DB.

## Casos de Uso
1. **Ver Productos:** Un usuario visualiza la lista de productos o el detalle de un producto específico con todas sus variantes (incluyendo el stock).
2. **Alertas de Inventario:** Un administrador revisa cuáles variantes tienen stock menor o igual a su umbral mínimo de alerta.
3. **Gestión del Carrito:** Un usuario autenticado agrega variantes, visualiza su contenido y remueve ítems.

## Tareas
- [x] Actualizar `schema.sql` agregando la tabla `cart_items`.
- [x] Crear `src/Core/AuthMiddleware.php` para validar el JWT y autorizar roles.
- [x] Crear `Product` Model y `CatalogController` con Endpoints GET.
- [x] Crear `Inventory` Model y `InventoryController` (restringido a `admin`).
- [x] Crear `Cart` Model (UPSERT logic) y `CartController` restringido por sesión JWT.
- [x] Integrar todas las rutas nuevas en `public/index.php`.

## Criterios de Aceptación
- La ruta del carrito requerirá token Bearer. En caso de error o expiración, devolverá `401 Unauthorized`.
- La ruta de alertas de stock requerirá un JWT y el rol `admin`. En caso contrario, devolverá `403 Forbidden`.
- El catálogo retornará el objeto JSON anidado del producto junto a sus variantes.
