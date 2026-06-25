# Sprint 3 Review - Plataforma E-commerce UCT

Este documento resume las funcionalidades, mejoras de diseño y características de negocio añadidas e integradas en la plataforma de e-commerce durante el Sprint 3.

---

## 1. Diseño y Estética Visual (Diseño Final)
*   **Tema Cyber-Punk Premium**: Se implementó una interfaz visual moderna basada en una paleta de colores oscuro/gris profundo (`#0a0b0e` y `#141722`) con acentos en rojo neón/carmesí (`#ff2a4b`) que moderniza y destaca la estética general de la tienda.
*   **Micro-animaciones y Tarjetas de Producto**: Se añadieron efectos de transición fluidos al posicionar el cursor sobre los artículos, bordes luminosos y sombras adaptativas para una experiencia interactiva premium.

---

## 2. Nuevas Funcionalidades del Cliente

### 🔍 Buscador Global de Productos
*   Se completó e integró la barra de búsqueda en la barra de navegación superior. Ahora permite buscar cualquier artículo de la tienda filtrando el catálogo en tiempo real según las coincidencias con el título o descripción del producto.

### 💳 Integración de Pago con PayPal
*   Se implementó la opción de pago por **PayPal** en la pasarela de compra (checkout). El sistema permite al cliente seleccionar PayPal y renderiza dinámicamente los botones de pago oficiales de la plataforma para completar transacciones de forma segura.

### ⭐ Reseñas con Calificación en Estrellas
*   Se implementó el sistema de valoraciones en la plataforma. Los usuarios pueden calificar los productos del catálogo mediante una escala de 1 a 5 estrellas y dejar comentarios sobre su experiencia de compra.

### 🔍 Vista de Detalle de Producto Reorganizada
*   Se reestructuró y ordenó la vista de detalle de cada producto. Al hacer clic en un artículo se despliega una presentación limpia con:
    *   Carrusel de imágenes en alta resolución.
    *   Descripción del producto y disponibilidad de stock actual.
    *   Historial de valoraciones y comentarios de otros clientes ordenados cronológicamente.

### 📋 Historial de "Mis Pedidos" con Detalle
*   Se creó una sección dedicada para clientes autenticados donde pueden ver la lista completa de sus compras pasadas.
*   **Vista Detallada**: Al hacer clic en un pedido, se abre un desglose que muestra:
    *   Los productos específicos comprados (con su precio histórico al momento de la compra).
    *   Dirección de despacho y teléfono.
    *   El desglose detallado de valores (subtotal, IVA, costo de envío y total).
    *   El estado actual de la orden (pendiente, pagado, en preparación, enviado, entregado).

---

## 3. Panel de Administración y Gestión

### 📊 Dashboard Administrativo
*   Se añadió un panel de control inicial para los administradores que muestra estadísticas clave del negocio en tiempo real (monto total de ventas, número de pedidos procesados y cantidad de clientes registrados).

### 🖼️ Carga de Imágenes desde el Computador (PC)
*   Se habilitó en el creador/editor de productos la capacidad de **subir archivos de imagen directamente desde el computador del administrador**.
*   El backend procesa y valida los archivos cargados, guardándolos automáticamente en la carpeta de almacenamiento de medios del servidor (`uploads/`).

### 👥 Gestión de Usuarios con Confirmación de Eliminación
*   Se integró una lista de control de usuarios en el Panel Admin.
*   **Confirmación de Seguridad**: Para evitar borrados accidentales, el sistema exige una confirmación interactiva de seguridad antes de proceder con la eliminación definitiva de cualquier cuenta del sistema.
