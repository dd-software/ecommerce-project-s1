# Módulo A - Catálogo

## Objetivo y Propósito
Permitir a los visitantes y clientes explorar, buscar y filtrar el catálogo de productos genéricos de la tienda en línea.

## Responsabilidades Funcionales
- Presentar una interfaz responsiva con la lista de productos y categorías utilizando Bootstrap 5.3.
- Proveer un motor de búsqueda por texto libre (nombre y descripción del producto).
- Permitir el filtrado dinámico por categoría, rango de precio y disponibilidad de stock.
- Mostrar la ficha técnica individual del producto con imágenes y stock disponible.

## Actores Involucrados
- Invitado (visitante sin sesión)
- Cliente (comprador registrado)
- Vendedor / Administrador (visualización)

## Entidades y Relaciones Relevantes
- Producto (id, nombre, descripcion, precio, imagen_url, categoria_id, estado)
- Categoría (id, nombre, descripcion, estado)
- ImagenProducto (id, producto_id, ruta_imagen, orden)

## Reglas de Negocio
- RN-A01: Solo se deben listar productos activos en la base de datos.
- RN-A02: Si un producto tiene stock = 0, debe mostrar la etiqueta 'Sin Stock' y deshabilitar el botón de compra rápida.

## Dependencias y Restricciones
- Depende del stack de Frontend (Bootstrap 5.3, JS Vanilla), Backend (PHP 8 REST), Persistencia (MySQL) e Integración (JWT, JSON).
- Integración segura mediante paso de tokens en cabeceras HTTP de autorización.
