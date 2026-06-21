/**
 * router.js - Router de vistas de la SPA (hash-based)
 * Mapea #/ruta -> muestra una .qc-view y dispara su render.
 * Carrito y checkout son overlays (offcanvas/modal), no se rutean.
 */

const Router = {
    // Vistas aún sin construir: muestran un stub "en construcción".
    // ponytail: stubs hasta que cada vista tenga su tarea (perfil/pedidos/favoritos/contacto).
    stubs: {
        contacto:  ['Contáctanos', 'bi-envelope', 'Formulario de contacto en construcción.'],
        perfil:    ['Mi perfil', 'bi-person', 'Tu perfil estará disponible pronto.'],
        pedidos:   ['Mis compras', 'bi-bag', 'El historial de pedidos estará disponible pronto.'],
        favoritos: ['Mis favoritos', 'bi-heart', 'Tu lista de favoritos estará disponible pronto.'],
    },

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

        // Catálogo: filtros + grid (default para rutas desconocidas)
        this.show('view-catalogo');
        const cat = params.get('cat');
        // ponytail: solo filtra si la categoría es un ID numérico; los slugs del nav
        // (repuestos/accesorios…) no matchean las categorías reales del seed.
        Catalogo.filters.categoria = (cat && /^\d+$/.test(cat)) ? cat : null;
        Catalogo.currentPage = 1;
        Catalogo.loadProducts();
        Catalogo.loadPriceDistribution();
        this.crumbs([['Inicio', '#/'], ['Catálogo']]);
    }
};
