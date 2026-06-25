# Módulo: Fase 5 - Frontend Vanilla y Experiencia de Usuario (UX) (SDD)

## Análisis
La fase 5 desacopla el backend (API REST) del frontend, utilizando una arquitectura Cliente/Servidor manejada puramente con HTML5, CSS3, y Vanilla JavaScript. Se adopta Bootstrap 5.3 para lograr consistencia visual, complementado con un sistema de notificaciones asíncronas (Toasts) y modales que mejoran drásticamente la UX sin recargar la página.

## Casos de Uso (Flujo E2E Completo)
1. **Acceso Inicial:** El visitante visualiza el catálogo general sin restricciones (`index.html`).
2. **Autenticación:** El visitante se autentica (`login.html`), guardando su sesión (JWT) de manera local.
3. **Compra:** Añade productos al carrito. En `cart.html`, procesa el Checkout que levanta la orden y luego simula el pago automáticamente, desencadenando la lógica backend de deducción de stock.
4. **Gestión (Admin):** El administrador accede a su Dashboard (`admin.html`), evalúa las alertas de inventario (que saltarán si el stock cae tras la compra) y cambia el estado logístico del pedido.

## Tareas
- [x] Crear `public/js/api.js` (Core Fetch Wrapper con inyección de Bearer Token).
- [x] Crear `public/js/ui.js` (Feedback visual mediante Bootstrap Toasts).
- [x] Generar hojas de estilos `style.css` y vistas `.html` (`index`, `login`, `cart`, `admin`).
- [x] Crear lógicas individuales JS por módulo.

## Criterios de Aceptación
- Las vistas de la interfaz deben reaccionar al estado: si hay un token JWT, mostrar menú de usuario; si no, menú de login.
- Todas las peticiones fallidas (ej. 401 Unauthorized, 400 Bad Request) deben emitir Toasts de color rojo (Danger). Las acciones exitosas en verde (Success).
- El sistema no debe poseer renderizado de PHP mezclado con HTML; la separación debe ser absoluta.
