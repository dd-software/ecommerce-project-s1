/**
 * router.js - Router de vistas de la SPA (hash-based)
 * Mapea #/ruta -> muestra una .qc-view y dispara su render.
 * Carrito y checkout son overlays (offcanvas/modal), no se rutean.
 */

const Router = {
    // Vistas aún sin construir: muestran un stub "en construcción".
    // ponytail: stubs hasta que cada vista tenga su tarea (perfil/pedidos/favoritos/contacto).
    stubs: {},

    // Páginas informativas de Leo (paginas.js) → render en view-generic
    paginas: ['despacho', 'garantia', 'medios-pago', 'nosotros'],

    init() {
        // Links con data-view -> setean el hash
        document.addEventListener('click', (e) => {
            const link = e.target.closest('[data-view]');
            if (!link) return;
            e.preventDefault();
            const view = link.dataset.view;
            const cat = link.dataset.cat;
            let hash = '#/' + (view === 'home' ? '' : view);
            if (cat) hash += '?cat=' + encodeURIComponent(cat);
            if (location.hash === hash) this.render();   // re-render si ya estamos ahí
            else location.hash = hash;
        });

        window.addEventListener('hashchange', () => this.render());
        this.render();
    },

    parse() {
        const raw = location.hash.replace(/^#\/?/, '');
        const [path, query] = raw.split('?');
        return { path: path || 'home', params: new URLSearchParams(query || '') };
    },

    show(viewId) {
        document.querySelectorAll('.qc-view').forEach(v => v.classList.remove('active'));
        const el = document.getElementById(viewId);
        if (el) el.classList.add('active');
        window.scrollTo(0, 0);
    },

    crumbs(items) {
        const box = document.getElementById('breadcrumbs');
        if (!box) return;
        box.innerHTML = items.map((it, i) => {
            const last = i === items.length - 1;
            const sep = i > 0 ? '<span class="sep">/</span>' : '';
            return sep + (last || !it[1]
                ? `<span class="current">${it[0]}</span>`
                : `<a href="${it[1]}">${it[0]}</a>`);
        }).join('');
    },

    render() {
        const { path, params } = this.parse();
        const seg = path.split('/');

        // Detalle de producto: #/producto/:id
        if (seg[0] === 'producto' && seg[1]) {
            this.show('view-detalle');
            this.crumbs([['Inicio', '#/'], ['Catálogo', '#/catalogo'], ['Detalle']]);
            Catalogo.loadDetail(seg[1]);
            return;
        }

        // Detalle de pedido: #/pedido/:id
        if (seg[0] === 'pedido' && seg[1]) {
            this.show('view-generic');
            this.crumbs([['Inicio', '#/'], ['Mis Pedidos', '#/pedidos'], ['Detalle']]);
            Pedidos.openDetail(seg[1]);
            return;
        }

        // Mis Pedidos / perfil
        if (path === 'pedidos') {
            this.show('view-generic');
            this.crumbs([['Inicio', '#/'], ['Mis Pedidos']]);
            Pedidos.openPage();
            return;
        }
        if (path === 'perfil') {
            this.show('view-generic');
            this.crumbs([['Inicio', '#/'], ['Mi perfil']]);
            Perfil.openPage();
            return;
        }
        if (path === 'admin') {
            this.show('view-generic');
            this.crumbs([['Inicio', '#/'], ['Panel admin']]);
            Admin.openPage();
            return;
        }

        // Páginas institucionales (info-pages.js)
        if (path === 'empresas') {
            this.show('view-generic');
            this.crumbs([['Inicio', '#/'], ['Venta Empresas']]);
            Info.empresas();
            return;
        }
        if (path === 'tiendas') {
            this.show('view-generic');
            this.crumbs([['Inicio', '#/'], ['Tiendas']]);
            Info.tiendas();
            return;
        }

        // Contacto (contacto.js — tarea de Leo)
        if (path === 'contacto') {
            this.show('view-generic');
            this.crumbs([['Inicio', '#/'], ['Contacto']]);
            Contacto.render();
            return;
        }

        // Páginas informativas del footer (paginas.js — tarea de Leo)
        if (this.paginas.includes(path)) {
            this.show('view-generic');
            this.crumbs([['Inicio', '#/'], [path]]);
            Paginas.render(path);
            return;
        }

        // Carrito (página completa) · checkout · confirmación
        if (path === 'carrito') {
            this.show('view-generic');
            this.crumbs([['Inicio', '#/'], ['Carrito']]);
            Carrito.openPage();
            return;
        }
        if (path === 'checkout') {
            this.show('view-generic');
            this.crumbs([['Inicio', '#/'], ['Carrito', '#/carrito'], ['Checkout']]);
            Checkout.openPage();
            return;
        }
        if (path === 'confirmacion') {
            this.show('view-generic');
            this.crumbs([['Inicio', '#/'], ['Confirmación']]);
            Checkout.openConfirmacion();
            return;
        }

        // Favoritos: grid de productos guardados (vista real, reutiliza view-generic)
        if (path === 'favoritos') {
            this.show('view-generic');
            this.crumbs([['Inicio', '#/'], ['Mis favoritos']]);
            Catalogo.loadFavoritos();
            return;
        }

        // Stubs en construcción
        if (this.stubs[path]) {
            const [title, icon, msg] = this.stubs[path];
            this.show('view-generic');
            this.crumbs([['Inicio', '#/'], [title]]);
            document.getElementById('view-generic').innerHTML = `
                <div class="empty-state">
                    <i class="bi ${icon}"></i>
                    <h5>${title}</h5>
                    <p class="text-muted">${msg}</p>
                    <a href="#/catalogo" class="btn btn-outline-uct btn-sm mt-2">Ver catálogo</a>
                </div>`;
            return;
        }

        // Home: hero + destacados
        if (path === 'home') {
            this.show('view-home');
            this.crumbs([['Inicio']]);
            Catalogo.loadFeatured();
            return;
        }

        // Catálogo (ruta explícita)
        if (path === 'catalogo') {
            this.show('view-catalogo');
            const cat = params.get('cat');
            const q = params.get('q');
            Catalogo.filters.categoria = cat || null;
            Catalogo.filters.q = q || null;
            const searchInput = document.getElementById('search-input');
            if (searchInput) searchInput.value = q || '';
            Catalogo.currentPage = 1;
            Catalogo.loadProducts();
            Catalogo.loadPriceDistribution();
            this.crumbs(q
                ? [['Inicio', '#/'], ['Catálogo', '#/catalogo'], [`Búsqueda: "${q}"`]]
                : [['Inicio', '#/'], ['Catálogo']]);
            return;
        }

        // 404 - Página no encontrada (para cualquier otra ruta)
        this.show('view-generic');
        this.crumbs([['Inicio', '#/'], ['Página no encontrada']]);
        document.getElementById('view-generic').innerHTML = `
            <div class="empty-state">
            <i class="bi bi-exclamation-triangle"></i>
            <h5>Página no encontrada</h5>
            <p class="text-muted">La ruta que buscas no existe en nuestro sitio.</p>
            <a href="#/" class="btn btn-outline-uct btn-sm mt-2">Volver al inicio</a>
        </div>`;
    }
};
