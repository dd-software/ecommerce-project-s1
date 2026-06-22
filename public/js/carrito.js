/**
 * carrito.js - Carrito de compras
 * Dos vistas sobre el mismo dato:
 *   - Mini-carrito (offcanvas lateral): vistazo rápido, botón "Ver carrito".
 *   - Página #/carrito: "Mi Carrito" completa (tabla + resumen + recomendados).
 * Los controles (+/−, eliminar) usan clases + data-id, así funcionan en ambas.
 */

const Carrito = {
    cart: null,

    init() {
        this.loadCart();
        this.initEventListeners();
    },

    /** Carga el carrito desde la API y re-pinta todas las vistas montadas */
    async loadCart() {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/carrito`);
            const data = await resp.json();
            if (data.success) {
                this.cart = data.data;
                App.cartCount = this.totalUnidades;
                App.updateCartBadge();
                this.render();
            }
        } catch (e) {
            const oc = document.getElementById('cart-items');
            if (oc) oc.innerHTML = '<div class="alert alert-danger">Error al cargar el carrito.</div>';
        }
    },

    /** Aplica un carrito ya recibido (ej. la respuesta de agregar) sin segundo fetch */
    applyCart(cart) {
        if (!cart) return;
        this.cart = cart;
        App.cartCount = this.totalUnidades;
        App.updateCartBadge();
        this.render();
    },

    /** Re-pinta el mini-carrito y, si está montada, la página #/carrito */
    render() {
        this.renderOffcanvas();
        if (document.getElementById('cart-page-root')) this.renderPage();
        this.updateHeaderTotal();
    },

    updateHeaderTotal() {
        const el = document.querySelector('.qc-cart-text strong');
        if (el) el.textContent = this.cart?.total_formateado || '$0';
    },

    get isEmpty() {
        return !this.cart || !this.cart.items || this.cart.items.length === 0;
    },

    /** Cantidad TOTAL de unidades (suma de cantidades), no la cantidad de líneas */
    get totalUnidades() {
        return this.cart?.items?.reduce((n, i) => n + (i.cantidad || 0), 0) || 0;
    },

    // ─────────────────────────── Mini-carrito (offcanvas) ───────────────────────────
    renderOffcanvas() {
        const container = document.getElementById('cart-items');
        const summary = document.getElementById('cart-summary');
        const empty = document.getElementById('cart-empty');
        if (!container) return;

        if (this.isEmpty) {
            empty?.classList.remove('d-none');
            container.classList.add('d-none');
            summary?.classList.add('d-none');
            return;
        }
        empty?.classList.add('d-none');
        container.classList.remove('d-none');
        summary?.classList.remove('d-none');

        container.innerHTML = this.cart.items.map(item => `
            <div class="mini-cart-item d-flex align-items-start gap-2" data-item-id="${item.id}">
                ${this.thumb(item, 'mini-cart-img')}
                <div class="flex-grow-1">
                    <div class="mini-cart-name">${this.escapeHtml(item.nombre)}</div>
                    <small class="text-muted d-block mb-1">${item.subtotal_formateado || App.formatPrice(item.subtotal)}</small>
                    <div class="qc-stepper qc-stepper-sm">
                        <button type="button" class="btn-qty" data-action="minus" data-id="${item.id}">−</button>
                        <input type="number" class="qty-input" value="${item.cantidad}" min="1" max="${item.stock || 99}" data-id="${item.id}" data-stock="${item.stock || 99}">
                        <button type="button" class="btn-qty" data-action="plus" data-id="${item.id}">+</button>
                    </div>
                </div>
                <button class="btn btn-sm btn-link text-danger btn-remove p-0" data-id="${item.id}" title="Quitar"><i class="bi bi-x-lg"></i></button>
            </div>`).join('');

        const content = document.getElementById('cart-summary-content');
        if (content) {
            content.innerHTML = `
                <div class="d-flex justify-content-between mb-1"><span>Total</span>
                    <strong class="text-primary">${this.cart.total_formateado}</strong></div>
                <small class="text-muted">IVA incluido</small>`;
        }
    },

    // ─────────────────────────── Página #/carrito ───────────────────────────
    /** Llamada por el router: monta el contenedor y carga */
    openPage() {
        const view = document.getElementById('view-generic');
        if (!view) return;
        view.innerHTML = '<div id="cart-page-root"><div class="text-center py-5"><div class="spinner-border text-primary"></div></div></div>';
        this.loadCart();
    },

    renderPage() {
        const root = document.getElementById('cart-page-root');
        if (!root) return;

        if (this.isEmpty) {
            root.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-cart-x"></i>
                    <h5>Tu carrito está vacío</h5>
                    <a href="#/catalogo" class="btn btn-accent btn-sm mt-2">Ir al catálogo</a>
                </div>`;
            return;
        }

        const rows = this.cart.items.map(item => `
            <tr data-item-id="${item.id}">
                <td>
                    <div class="d-flex align-items-center gap-3">
                        ${this.thumb(item, 'cart-item-img')}
                        <div>
                            <div class="cart-row-name">${this.escapeHtml(item.nombre)}</div>
                            <small class="text-muted">SKU PROD-${item.producto_id || item.id_producto || item.id}</small>
                        </div>
                    </div>
                </td>
                <td class="text-primary fw-bold">${item.precio_formateado || App.formatPrice(item.precio_unitario)}</td>
                <td>
                    <div class="qc-stepper qc-stepper-sm">
                        <button type="button" class="btn-qty" data-action="minus" data-id="${item.id}">−</button>
                        <input type="number" class="qty-input" value="${item.cantidad}" min="1" max="${item.stock || 99}" data-id="${item.id}" data-stock="${item.stock || 99}">
                        <button type="button" class="btn-qty" data-action="plus" data-id="${item.id}">+</button>
                    </div>
                </td>
                <td class="text-primary fw-bold">${item.subtotal_formateado || App.formatPrice(item.subtotal)}</td>
                <td class="text-end"><button class="btn btn-sm btn-link text-muted btn-remove" data-id="${item.id}" title="Eliminar"><i class="bi bi-x-lg"></i></button></td>
            </tr>`).join('');

        root.innerHTML = `
            <div class="cart-page">
                <h1 class="cart-page-title">Mi Carrito <span class="text-muted fs-6">${this.totalUnidades} producto${this.totalUnidades === 1 ? '' : 's'}</span></h1>
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="cart-table-card">
                            <table class="cart-table">
                                <thead><tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th></th></tr></thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>
                        <a href="#/catalogo" class="cart-keep-shopping"><i class="bi bi-arrow-left"></i> Seguir comprando</a>
                    </div>
                    <div class="col-lg-4">
                        <div class="cart-summary-card">
                            <h6 class="cart-summary-title">Resumen de compra</h6>
                            <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span>${this.cart.subtotal_formateado}</span></div>
                            <div class="d-flex justify-content-between mb-2"><span>IVA (19%)</span><span>${this.cart.iva_formateado}</span></div>
                            <div class="d-flex justify-content-between mb-2"><span>Envío</span><span class="text-success fw-semibold">Gratis ✓</span></div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <strong>TOTAL</strong><strong class="cart-total-amount">${this.cart.total_formateado}</strong>
                            </div>
                            <small class="text-muted d-block mb-3">IVA incluido</small>
                            <button class="btn btn-accent w-100 mb-2" id="btn-proceed-checkout">Proceder al pago <i class="bi bi-arrow-right"></i></button>
                            <a href="#/catalogo" class="btn btn-outline-uct w-100">Seguir comprando</a>
                            <p class="text-muted small text-center mt-3 mb-0"><i class="bi bi-lock"></i> Pago 100% seguro y encriptado</p>
                        </div>
                    </div>
                </div>
                <div class="mt-5">
                    <h2 class="qc-similares-title">Productos recomendados para ti</h2>
                    <div class="row" id="cart-recommended"></div>
                </div>
            </div>`;

        document.getElementById('btn-proceed-checkout')?.addEventListener('click', () => {
            if (!App.user) {
                App.showToast('Inicia sesión para continuar', 'info');
                new bootstrap.Modal(document.getElementById('loginModal')).show();
                return;
            }
            location.hash = '#/checkout';
        });

        this.loadRecommended();
    },

    async loadRecommended() {
        const grid = document.getElementById('cart-recommended');
        if (!grid) return;
        try {
            const data = await (await fetch(`${App.apiBase}/catalogo/destacados`)).json();
            const items = (data.data || []).filter(p => !this.cart.items.some(i => (i.producto_id || i.id_producto) === p.id)).slice(0, 4);
            grid.innerHTML = items.map(p => Catalogo.productCard(p)).join('');
        } catch (e) { grid.innerHTML = ''; }
    },

    // ─────────────────────────── Helpers + mutaciones ───────────────────────────
    thumb(item, cls) {
        return item.imagen_url
            ? `<img src="${item.imagen_url}" class="${cls}" alt="${this.escapeHtml(item.nombre)}">`
            : `<div class="qc-img-ph ${cls}"><i class="bi bi-cpu"></i></div>`;
    },

    initEventListeners() {
        // +/− cantidad (delegado, sirve para offcanvas y página)
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-qty');
            if (!btn) return;
            const input = document.querySelector(`.qty-input[data-id="${btn.dataset.id}"]`);
            if (!input) return;
            let qty = parseInt(input.value) || 1;
            const max = parseInt(input.dataset.stock) || 99;
            qty = btn.dataset.action === 'minus' ? Math.max(1, qty - 1) : Math.min(max, qty + 1);
            input.value = qty;
            await this.updateItem(btn.dataset.id, qty);
        });

        // cantidad por input directo
        document.addEventListener('change', async (e) => {
            if (!e.target.classList.contains('qty-input')) return;
            const input = e.target;
            const max = parseInt(input.dataset.stock) || 99;
            const qty = Math.max(1, Math.min(max, parseInt(input.value) || 1));
            input.value = qty;
            await this.updateItem(input.dataset.id, qty);
        });

        // eliminar item
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-remove');
            if (!btn) return;
            await this.removeItem(btn.dataset.id);
        });

        // "Ver carrito" del offcanvas → página
        document.getElementById('btn-checkout-nav')?.addEventListener('click', (e) => {
            e.preventDefault();
            bootstrap.Offcanvas.getInstance(document.getElementById('cartOffcanvas'))?.hide();
            location.hash = '#/carrito';
        });

        // vaciar carrito (delegado: el botón se re-crea)
        document.addEventListener('click', async (e) => {
            if (e.target.id === 'btn-clear-cart') await this.clearCart();
        });
    },

    async updateItem(itemId, quantity) {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/carrito/${itemId}`, {
                method: 'PATCH', body: JSON.stringify({ cantidad: quantity })
            });
            const data = await resp.json();
            if (data.success) { this.cart = data.data; App.cartCount = this.totalUnidades; App.updateCartBadge(); this.render(); }
            else { App.showToast(data.error?.message || 'Error al actualizar', 'error'); this.loadCart(); }
        } catch (err) { App.showToast('Error de conexión', 'error'); }
    },

    async removeItem(itemId) {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/carrito/${itemId}`, { method: 'DELETE' });
            const data = await resp.json();
            if (data.success) { App.showToast('Producto eliminado', 'success'); this.loadCart(); }
        } catch (err) { App.showToast('Error al eliminar', 'error'); }
    },

    async clearCart() {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/carrito`, { method: 'DELETE' });
            if ((await resp.json()).success) { App.showToast('Carrito vaciado', 'success'); this.loadCart(); }
        } catch (err) { App.showToast('Error al vaciar', 'error'); }
    },

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }
};
