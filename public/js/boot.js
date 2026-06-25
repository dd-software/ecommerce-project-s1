// boot.js - Arranque de la SPA. Externalizado del index.html para cumplir la CSP
// (script-src 'self' sin 'unsafe-inline'): un <script> inline acá quedaría bloqueado.
document.addEventListener('DOMContentLoaded', () => {
    App.init();
    Catalogo.loadCategories();   // rellena categorías (checkboxes) en el sidebar
    Catalogo.loadMarcas();       // rellena marcas (checkboxes) en el sidebar
    Catalogo.initFilters();      // listeners de filtros/orden + add-to-cart (una sola vez)
    Carrito.init();              // mini-carrito (drawer) + total del header + listeners
    Compare.init();              // comparación de productos (localStorage + barra flotante)
    Info.loadCategoriasMenu();   // llena el menú "Categorías" del header
    Router.init();               // pinta la primera vista según el hash
});
