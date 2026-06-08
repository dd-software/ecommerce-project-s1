# Casos de Uso - Catálogo

### CU-A-001: Buscar y Filtrar Productos
- **Actor**: Invitado (visitante sin sesión), Cliente (comprador registrado), Vendedor / Administrador (visualización)
- **Precondición**: El catálogo tiene productos registrados y activos.
- **Flujo Operacional Principal**:
  1. El usuario accede a la página principal del catálogo.
  2. El sistema muestra la lista de productos paginada (12 por página).
  3. El usuario escribe una palabra clave y selecciona una categoría.
  4. El sistema filtra los productos de manera asíncrona y refresca la vista.
- **Excepciones y Flujos Alternativos**:
  - No hay resultados: El sistema muestra 'No se encontraron productos con los criterios ingresados'.

### CU-A-002: Visualizar Detalle del Producto
- **Actor**: Invitado (visitante sin sesión), Cliente (comprador registrado), Vendedor / Administrador (visualización)
- **Precondición**: El producto existe y está activo.
- **Flujo Operacional Principal**:
  1. El usuario hace clic sobre la tarjeta del producto.
  2. El sistema redirige o abre un modal con el detalle completo.
  3. El sistema renderiza imágenes, descripción, precio y stock actual.
- **Excepciones y Flujos Alternativos**:
  - Producto inactivo o inexistente: El sistema redirige a una página de error 404.

