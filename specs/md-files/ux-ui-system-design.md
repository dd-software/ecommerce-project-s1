# SDD: Sistema de Diseño y Componentes UI

Este documento consolida las directrices visuales globales (Design System) y las especificaciones de comportamiento para la biblioteca de componentes de la interfaz de usuario.

---

## PARTE I: Sistema de Diseño (Design System)

### 1. Objetivos y propósito
Establecer un marco visual y de interacción estandarizado para todo el ecosistema frontend del e-commerce. Su propósito es garantizar coherencia estética, accesibilidad (WCAG 2.1 AA) y eficiencia en el desarrollo mediante la reutilización de patrones.

### 2. Responsabilidades funcionales
- Proveer la paleta de colores corporativos, tipografía y espaciados base.
- Definir el comportamiento responsivo (*Mobile-First*).
- Estandarizar la apariencia de los elementos nativos de HTML.

### 3. Actores involucrados
- Desarrolladores Frontend (consumidores del sistema).
- Usuarios Finales (clientes y administradores que interactúan con la UI).

### 4. Entradas y salidas
- **Entradas:** Clases de utilidad, variables CSS/Sass, estructura DOM.
- **Salidas:** Interfaces renderizadas y estilizadas en el navegador del usuario.

### 5. Reglas de negocio
- Se debe utilizar estrictamente la paleta institucional de la Universidad Católica de Temuco:
  - Primario: `#003366`
  - Secundario (Acento): `#F2A900`
  - Fondo Claro: `#F8F9FA`
  - Texto Oscuro: `#212529`

### 6. Flujos operacionales
- Las variables nativas de CSS deben cargarse en el `:root` al inicializar la aplicación antes de la renderización de los componentes visuales.

### 7. Casos de uso
- Renderizado de botones de acción primaria y secundaria.
- Adaptación de la cuadrícula (Grid) según el dispositivo (Móvil, Tablet, Desktop).

### 8. Entidades y relaciones relevantes
- Depende de las variables estructurales definidas en el framework base.

### 9. Restricciones, validaciones y dependencias
- **Dependencia:** Bootstrap v5.3 (rama 5.3.x).
- **Restricción:** Prohibido el uso de estilos en línea (`style="..."`). Toda modificación debe realizarse por clases utilitarias o variables globales.

### 10. Criterios de aceptación
- Todo texto sobre fondo primario o secundario debe superar un ratio de contraste de 4.5:1.
- El diseño no debe presentar desbordamiento horizontal (`overflow-x`) en resoluciones desde 320px en adelante.

### 11. Consideraciones de integración con otros módulos
- Este sistema es transversal; todos los componentes detallados en la Parte II deben importar este sistema de diseño como dependencia principal.

---

## PARTE II: Catálogo de Componentes UI

### 1. Objetivos y propósito
Proveer una biblioteca de componentes de interfaz de usuario encapsulados, modulares y reutilizables (CardProducto, TablaAdmin, Navbar, Footer, Toast) para agilizar el desarrollo de las vistas del e-commerce.

### 2. Responsabilidades funcionales
- **CardProducto:** Renderizar información individual de ítems y permitir su selección.
- **TablaAdmin:** Desplegar datos tabulares con soporte para operaciones CRUD y paginación.
- **Navbar:** Proveer navegación global, acceso a módulos y reflejar el estado de sesión.
- **Footer:** Mostrar enlaces legales, redes sociales e información institucional.
- **Toast:** Entregar feedback asíncrono no bloqueante al usuario tras una acción.

### 3. Actores involucrados
- Usuarios No Autenticados, Clientes Registrados y Administradores.

### 4. Entradas y salidas
- **CardProducto:** In(`id`, `nombre`, `precio`, `imagenUrl`, `stockDisponible`) -> Out(Evento `onAgregarAlCarrito`).
- **TablaAdmin:** In(`columnas`, `datos`, `paginacion`) -> Out(Eventos `onEditar`, `onEliminar`, `onCambioPagina`).
- **Navbar:** In(`usuarioAutenticado`, `rol`, `conteoCarrito`) -> Out(Navegación y ruteo).
- **Toast:** In(`mensaje`, `tipo`, `duracion`) -> Out(Visualización temporal y evento de cierre).

### 5. Reglas de negocio
- **CardProducto:** Deshabilitar visual y funcionalmente el botón de compra si `stockDisponible == 0`.
- **Navbar:** Mostrar el enlace al "Panel Admin" exclusivamente si el rol en sesión es `ADMINISTRADOR`.
- **Toast:** Utilizar semántica de color estricta (Éxito = Verde `#198754`, Error = Rojo `#DC3545`).

### 6. Flujos operacionales
- **Flujo de Toast:** Renderiza en `top-0 end-0` (z-index: 1050), inicia temporizador (`duracion` por defecto 3000ms), aplica animación de desvanecimiento (fade-out) y se desmonta del DOM.

### 7. Casos de uso
- Un cliente navega por el catálogo y añade un producto al carrito interactuando con la `CardProducto`.
- El sistema notifica la adición exitosa mediante la aparición de un `Toast`.
- El administrador elimina un producto descontinuado interactuando con las acciones de la `TablaAdmin`.

### 8. Entidades y relaciones relevantes
- Los componentes interactúan activamente con el gestor de estado global de la aplicación (Ej. Contexto de Carrito, Contexto de Sesión).

### 9. Restricciones, validaciones y dependencias
- Dependencia estricta de las directrices establecidas en la Parte I (Sistema de Diseño).
- **TablaAdmin:** Validar que el arreglo de `datos` sea mayor a cero; de lo contrario, renderizar un *Empty State* (estado vacío) amigable.

### 10. Criterios de aceptación
- `Navbar` debe mantener su posición visible mediante `sticky-top` al realizar scroll vertical en la página.
- El evento `onEliminar` en `TablaAdmin` debe emitirse únicamente tras una confirmación explícita (modal o alerta) del usuario para prevenir borrados accidentales.

### 11. Consideraciones de integración con otros módulos
- La `CardProducto` requiere integración directa con el servicio de API del módulo `carrito` para procesar la acción de compra en el backend.
