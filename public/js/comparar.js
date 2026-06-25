// comparar.js — Comparación de productos (client-side, máx 4). Persistencia en localStorage.
// No toca backend: la tabla se arma con los datos del endpoint de detalle existente.
const Compare = {
    KEY: 'qc_compare',
    MAX: 4,
    ids: [],

    init() {
        try { this.ids = JSON.parse(localStorage.getItem(this.KEY) || '[]').map(String); }
        catch { this.ids = []; }

        // Toggle desde cards, detalle y la propia tabla (delegado)
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.qc-compare-toggle');
            if (!btn) return;
            e.preventDefault();
            this.toggle(btn.dataset.id, btn.dataset.name);
        });

        this.renderTray();
    },

    isSelected(id) { return this.ids.includes(String(id)); },

    toggle(id, name) {
        id = String(id);
        const i = this.ids.indexOf(id);
        if (i >= 0) {
            this.ids.splice(i, 1);
        } else {
            if (this.ids.length >= this.MAX) {
                App.showToast(`Puedes comparar hasta ${this.MAX} productos`, 'info');
                return;
            }
            this.ids.push(id);
            App.showToast(`${name || 'Producto'} agregado a comparar`, 'success');
        }
        this.save();
        this.syncToggles();
        this.renderTray();
        if (location.hash.startsWith('#/comparar')) this.openPage();
    },

    clear() {
        this.ids = [];
        this.save();
        this.syncToggles();
        this.renderTray();
        if (location.hash.startsWith('#/comparar')) this.openPage();
    },

    save() { localStorage.setItem(this.KEY, JSON.stringify(this.ids)); },

    // Marca/desmarca los botones visibles según el estado actual
    syncToggles() {
        document.querySelectorAll('.qc-compare-toggle').forEach(btn => {
            btn.classList.toggle('active', this.isSelected(btn.dataset.id));
        });
    },

    // Barra flotante inferior
    renderTray() {
        let tray = document.getElementById('compare-tray');
        if (this.ids.length === 0) { tray?.remove(); return; }
        if (!tray) {
            tray = document.createElement('div');
            tray.id = 'compare-tray';
            document.body.appendChild(tray);
        }
        const enough = this.ids.length >= 2;
        tray.innerHTML = `
            <span class="ct-count"><i class="bi bi-arrow-left-right"></i> ${this.ids.length} para comparar</span>
            <div class="ct-actions">
                <button type="button" class="btn btn-sm btn-link text-muted" id="ct-clear">Limpiar</button>
                <a href="#/comparar" class="btn btn-accent btn-sm" id="ct-go">Comparar</a>
            </div>`;
        tray.querySelector('#ct-clear').addEventListener('click', () => this.clear());
        if (!enough) {
            tray.querySelector('#ct-go').addEventListener('click', (e) => {
                e.preventDefault();
                App.showToast('Agrega al menos 2 productos para comparar', 'info');
            });
        }
    },

    // Página #/comparar
    async openPage() {
        const view = document.getElementById('view-generic');
        if (!view) return;

        if (this.ids.length < 2) {
            view.innerHTML = `
                <div class="gw-page"><header class="gw-head">
                    <p class="gw-kicker"><i class="bi bi-arrow-left-right"></i> Comparar</p>
                    <h1>Comparar productos</h1>
                    <p class="lead">Agrega al menos 2 productos desde el catálogo para verlos lado a lado.</p>
                </header></div>
                <div class="py-4"><a href="#/catalogo" class="btn btn-accent">Ir al catálogo</a></div>`;
            return;
        }

        view.innerHTML = UI.loader('Cargando comparación...');
        try {
            const results = await Promise.all(this.ids.map(id =>
                fetch(`${App.apiBase}/catalogo/${id}`).then(r => r.json())
            ));
            const products = results.filter(r => r.success).map(r => r.data);
            // Sincroniza ids con lo que realmente existe
            this.ids = products.map(p => String(p.id));
            this.save();
            this.renderTray();
            if (products.length < 2) { this.openPage(); return; }
            this.renderTable(view, products);
        } catch (e) {
            view.innerHTML = '<div class="alert alert-danger">Error al cargar la comparación.</div>';
        }
    },

    renderTable(view, products) {
        const esc = (s) => Catalogo.escapeHtml(String(s ?? ''));
        const col = (p) => `
            <th class="cmp-col">
                <button type="button" class="cmp-rm qc-compare-toggle active" data-id="${p.id}" data-name="${esc(p.nombre)}" title="Quitar de comparación" aria-label="Quitar"><i class="bi bi-x-lg"></i></button>
                <a href="#/producto/${p.id}" class="cmp-media">${p.imagen_url ? `<img src="${esc(p.imagen_url)}" alt="${esc(p.nombre)}">` : '<span class="qc-img-ph"><i class="bi bi-cpu"></i></span>'}</a>
                <a href="#/producto/${p.id}" class="cmp-name">${esc(p.nombre)}</a>
            </th>`;
        const r = (label, fn) => `<tr><th class="cmp-attr">${label}</th>${products.map(p => `<td>${fn(p)}</td>`).join('')}</tr>`;
        const precio = (p) => `<span class="cmp-price">${p.precio_formateado || App.formatPrice(p.precio)}</span>${p.precio_anterior_formateado ? `<span class="cmp-old">${esc(p.precio_anterior_formateado)}</span>` : ''}`;
        const disp = (p) => p.sin_stock ? '<span class="cmp-no">Agotado</span>' : `<span class="cmp-yes">${p.stock} disponibles</span>`;
        const add = (p) => p.sin_stock
            ? '<button class="btn btn-secondary btn-sm w-100" disabled>Sin stock</button>'
            : `<button class="btn btn-accent btn-sm w-100 add-to-cart-btn" data-id="${p.id}" data-name="${esc(p.nombre)}"><i class="bi bi-cart-plus"></i> Agregar</button>`;

        view.innerHTML = `
            <div class="gw-page"><header class="gw-head">
                <p class="gw-kicker"><i class="bi bi-arrow-left-right"></i> Comparar</p>
                <h1>Comparar productos</h1>
                <p class="lead">${products.length} productos lado a lado. Quita los que no necesites con la ✕.</p>
            </header></div>
            <div class="compare-wrap">
                <table class="compare-table">
                    <thead><tr><th class="cmp-corner">Producto</th>${products.map(col).join('')}</tr></thead>
                    <tbody>
                        ${r('Precio', precio)}
                        ${r('Marca', p => esc(p.marca || '—'))}
                        ${r('Categoría', p => esc(p.categoria_nombre || '—'))}
                        ${r('Disponibilidad', disp)}
                        ${r('Garantía', () => '12 meses oficial')}
                        ${r('', add)}
                    </tbody>
                </table>
            </div>
            <a href="#/catalogo" class="cart-keep-shopping d-inline-block mt-3"><i class="bi bi-arrow-left"></i> Seguir comprando</a>`;
    }
};
window.Compare = Compare;
