# Visión del Producto - Ecommerce UCT (SDD Academic Edition v1.0)

## 1. Definición del Problema
Las pequeñas y medianas tiendas locales carecen de una plataforma de ventas en línea que sea simple, autoadministrable, responsiva y que no requiera conocimientos técnicos avanzados para su gestión. El sistema actual busca resolver la brecha de digitalización mediante un software modular y eficiente basado en arquitectura Web clásica (PHP/MySQL).

## 2. Objetivos del Sistema
- **Exploración de Catálogo:** Permitir la visualización fluida de productos filtrados por categorías y disponibilidad en tiempo real.
- **Conversión de Compra:** Facilitar un flujo de checkout intuitivo, seguro y automatizado en tres pasos: carrito, datos de envío/pago y confirmación.
- **Gestión Logística:** Proveer trazabilidad completa de los estados de un pedido (Pendiente, Pagado, Despachado, Entregado, Cancelado).
- **Control de Inventario:** Automatizar el descuento de stock físico y alertar en caso de quiebre de inventario.
- **Auditoría y Reportes:** Ofrecer métricas clave de ventas y comportamiento para la toma de decisiones del administrador.

## 3. Actores del Sistema
El sistema interactúa con tres actores principales, definidos por sus matrices de permisos y capacidades operacionales:

| Actor | Descripción | Responsabilidades en el Sistema |
| :--- | :--- | :--- |
| **Visitante** | Usuario no autenticado en la plataforma. | - Examinar el catálogo general de productos.<br>- Filtrar y buscar artículos.<br>- Añadir elementos al carrito de compras temporal (sesión). |
| **Cliente** | Usuario registrado y autenticado. | - Gestionar su perfil y direcciones.<br>- Transformar el carrito en un pedido.<br>- Consultar el historial y estado de sus compras. |
| **Administrador** | Usuario con privilegios elevados de gestión. | - Gestionar el catálogo (CRUD de productos y categorías).<br>- Modificar stock y precios.<br>- Cambiar estados de los pedidos y acceder al dashboard de reportes. |

## 4. Matriz de Capacidades por Rol (Casos de Uso de Alto Nivel)
1. **Módulo Catálogo:**
   - `CU-01` Buscar y filtrar productos (Visitante, Cliente, Administrador)
   - `CU-02` Ver detalle técnico de producto (Visitante, Cliente, Administrador)
2. **Módulo Ventas/Checkout:**
   - `CU-03` Gestionar carrito de compras (Visitante, Cliente)
   - `CU-04` Procesar orden y pago (Cliente)
3. **Módulo Administración:**
   - `CU-05` Mantenedor (CRUD) de Productos (Administrador)
   - `CU-06` Actualizar estados de pedidos (Administrador)
   - `CU-07` Visualizar reportes de venta (Administrador)