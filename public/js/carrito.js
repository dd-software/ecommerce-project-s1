/**
 * carrito.js - Carrito de compras
 * Dos vistas sobre el mismo dato:
 *   - Mini-carrito (drawer lateral): vistazo rápido, botón "Ir a pagar".
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

    // ─────────────────────────── Mini-carrito (drawer) ───────────────────────────
    renderOffcanvas() {
        const container = document.getElementById('cart-items');
        const summary = document.getElementById('cart-summary');
        const empty = document.getElementById('cart-empty');
        const ship = document.getElementById('cart-ship');
        if (!container) return;

        const count = this.totalUnidades;
        const countEl = document.getElementById('cart-count-drawer');
        const subEl = document.getElementById('cart-subtitle');
        if (countEl) countEl.textContent = count;

        if (this.isEmpty) {
            container.innerHTML = '';
            summary?.classList.add('d-none');
            ship?.classList.add('d-none');
            if (countEl) countEl.style.display = 'none';
            if (subEl) subEl.textContent = 'No tienes productos aún';
            if (empty) empty.innerHTML = this.emptyMarkup();
            return;
        }

        if (empty) empty.innerHTML = '';
        if (countEl) countEl.style.display = '';
        summary?.classList.remove('d-none');
        if (subEl) subEl.textContent = `${count} producto${count === 1 ? '' : 's'} en tu carrito`;

        container.innerHTML = this.cart.items.map(item => `
            <div class="ci" data-item-id="${item.id}">
                <span class="ci-thumb">${this.thumb(item, 'ci-img')}</span>
                <div class="ci-main">
                    <button class="ci-remove btn-remove" data-id="${item.id}" aria-label="Quitar"><svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M5 7h14M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                    <p class="ci-name">${this.escapeHtml(item.nombre)}</p>
                    <div class="ci-foot">
                        <span class="qty">
                            <button type="button" class="btn-qty" data-action="minus" data-id="${item.id}" aria-label="Restar">−</button>
                            <input class="qty-input" type="text" inputmode="numeric" value="${item.cantidad}" data-id="${item.id}" data-stock="${item.stock || 99}" readonly aria-label="Cantidad">
                            <button type="button" class="btn-qty" data-action="plus" data-id="${item.id}" aria-label="Sumar">+</button>
                        </span>
                        <span class="ci-price">${item.subtotal_formateado || App.formatPrice(item.subtotal)}</span>
                    </div>
                </div>
            </div>`).join('');

        // Barra de envío gratis (umbral $50.000)
        const subtotal = this.cart.items.reduce((s, i) => s + (i.subtotal || 0), 0);
        const FREE = 50000;
        if (ship) {
            ship.classList.remove('d-none');
            const truck = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M3 7h11v8H3z" stroke="currentColor" stroke-width="1.6"/><path d="M14 10h4l3 3v2h-7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="7" cy="17" r="2" stroke="currentColor" stroke-width="1.6"/><circle cx="17" cy="17" r="2" stroke="currentColor" stroke-width="1.6"/></svg>';
            if (subtotal >= FREE) {
                ship.innerHTML = `<div class="t"><span><b>¡Tienes envío gratis!</b></span><span class="ic">${truck}</span></div><div class="bar"><span class="fill" style="width:100%"></span></div>`;
            } else {
                const pct = Math.min(100, Math.round(subtotal / FREE * 100));
                ship.innerHTML = `<div class="t"><span>Te faltan <b>${App.formatPrice(FREE - subtotal)}</b> para envío <b>gratis</b></span><span class="ic">${truck}</span></div><div class="bar"><span class="fill" style="width:${pct}%"></span></div>`;
            }
        }

        const content = document.getElementById('cart-summary-content');
        if (content) {
            const envio = subtotal >= FREE
                ? '<div class="r free"><span>Despacho</span> <b>Gratis</b></div>'
                : '<div class="r"><span>Despacho</span> <b>Se calcula al pagar</b></div>';
            content.innerHTML = `
                <div class="cd-sum">
                    <div class="r"><span>Subtotal</span> <b>${this.cart.subtotal_formateado || App.formatPrice(subtotal)}</b></div>
                    ${envio}
                </div>
                <div class="cd-total">
                    <span class="l">Total</span>
                    <span class="v">${this.cart.total_formateado}<small>IVA incluido</small></span>
                </div>`;
        }
    },

    emptyMarkup() {
        return `
            <div class="cd-empty">
                <div class="empty-art">
                    <span class="ring"></span><span class="ring r2"></span>
                    <svg width="52" height="52" viewBox="0 0 24 24" fill="none"><path d="M5 6h15l-1.5 9h-12L4 3H2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="20" r="1.6" fill="currentColor"/><circle cx="17" cy="20" r="1.6" fill="currentColor"/></svg>
                </div>
                <h3 class="empty-title">Tu carrito está vacío</h3>
                <p class="empty-sub">Aún no agregas productos. Explora el catálogo y encuentra lo que necesitas.</p>
                <a href="#/catalogo" class="btn-empty"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h16M4 18h10" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg> Ver catálogo</a>
                <div class="empty-perks">
                    <div class="empty-perk"><span class="p-ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 7h11v8H3z" stroke="currentColor" stroke-width="1.7"/><path d="M14 10h4l3 3v2h-7z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="7" cy="17" r="2" stroke="currentColor" stroke-width="1.7"/><circle cx="17" cy="17" r="2" stroke="currentColor" stroke-width="1.7"/></svg></span><span><b>Envío gratis</b><span>En compras sobre $50.000</span></span></div>
                    <div class="empty-perk"><span class="p-ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 2 4 5v6c0 5 3.5 8.5 8 11 4.5-2.5 8-6 8-11V5l-8-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span><span><b>Garantía oficial</b><span>Respaldo en todos los productos</span></span></div>
                    <div class="empty-perk"><span class="p-ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M3 9.5h18" stroke="currentColor" stroke-width="1.7"/></svg></span><span><b>Pago seguro</b><span>Hasta 12 cuotas con tu tarjeta</span></span></div>
                </div>
            </div>`;
    },

    openDrawer() {
        this.loadCart();   // datos frescos en cada apertura
        document.getElementById('cartOverlay')?.classList.add('open');
        const d = document.getElementById('cartDrawer');
        if (d) { d.classList.add('open'); d.setAttribute('aria-hidden', 'false'); }
        document.body.style.overflow = 'hidden';
    },

    closeDrawer() {
        document.getElementById('cartOverlay')?.classList.remove('open');
        const d = document.getElementById('cartDrawer');
        if (d) { d.classList.remove('open'); d.setAttribute('aria-hidden', 'true'); }
        document.body.style.overflow = '';
    },

    // ─────────────────────────── Página #/carrito ───────────────────────────
    /** Llamada por el router: monta el contenedor y carga */
    openPage() {
        const view = document.getElementById('view-generic');
        if (!view) return;
        view.innerHTML = `<div id="cart-page-root">${UI.loader('Cargando carrito...')}</div>`;
        this.loadCart();
    },

    renderPage() {
        const root = document.getElementById('cart-page-root');
        if (!root) return;

        if (this.isEmpty) {
            UI.mostrarVacio(root, {
                icono: 'bi-cart-x',
                titulo: 'Tu carrito está vacío',
                descripcion: 'Agrega productos para comenzar tu compra.',
                textoBoton: 'Ir al catálogo',
                enlaceBoton: '#/catalogo'
            });
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
        // +/− cantidad (delegado, sirve para drawer y página)
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

        // Abrir / cerrar drawer
        document.getElementById('cart-open')?.addEventListener('click', (e) => { e.preventDefault(); this.openDrawer(); });
        document.getElementById('cartClose')?.addEventListener('click', () => this.closeDrawer());
        document.getElementById('cartOverlay')?.addEventListener('click', () => this.closeDrawer());
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') this.closeDrawer(); });

        // Navegar desde un enlace del drawer → cerrarlo (la navegación sigue su curso)
        document.getElementById('cartDrawer')?.addEventListener('click', (e) => {
            if (e.target.closest('a[href^="#/"]')) this.closeDrawer();
        });

        // "Ir a pagar" → checkout (con guard de login, igual que la página)
        document.getElementById('btn-checkout-nav')?.addEventListener('click', () => {
            if (!App.user) {
                App.showToast('Inicia sesión para continuar', 'info');
                this.closeDrawer();
                new bootstrap.Modal(document.getElementById('loginModal')).show();
                return;
            }
            this.closeDrawer();
            location.hash = '#/checkout';
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
