/**
 * carrito.js - Gestión del carrito de compras
 */

const Carrito = {
    cart: null,

    /**
     * Inicializa la página de carrito
     */
    async init() {
        await this.loadCart();
        this.initEventListeners();
    },

    /**
     * Carga el carrito desde la API
     */
    async loadCart() {
        const container = document.getElementById('cart-items');
        const summary = document.getElementById('cart-summary');
        const empty = document.getElementById('cart-empty');

        if (!container) return;

        try {
            const resp = await App.fetchAuth(`${App.apiBase}/carrito`);
            const data = await resp.json();

            if (data.success) {
                this.cart = data.data;
                if (this.cart.items && this.cart.items.length > 0) {
                    this.renderItems();
                    this.renderSummary();
                    if (empty) empty.classList.add('d-none');
                    container.classList.remove('d-none');
                    if (summary) summary.classList.remove('d-none');
                } else {
                    if (empty) empty.classList.remove('d-none');
                    container.classList.add('d-none');
                    if (summary) summary.classList.add('d-none');
                }
                App.cartCount = this.cart.items ? this.cart.items.length : 0;
                App.updateCartBadge();
            }
        } catch (e) {
            container.innerHTML = '<div class="alert alert-danger">Error al cargar el carrito.</div>';
        }
    },

    /**
     * Renderiza los items del carrito
     */
    renderItems() {
        const container = document.getElementById('cart-items');
        if (!container || !this.cart) return;

        container.innerHTML = this.cart.items.map(item => `
            <div class="cart-item row align-items-center" data-item-id="${item.id}">
                <div class="col-md-2 col-3">
                    <img src="${item.imagen_url || 'https://via.placeholder.com/80?text=N/A'}"
                         class="cart-item-img" alt="${this.escapeHtml(item.nombre)}"
                         onerror="this.src='https://via.placeholder.com/80?text=N/A'">
                </div>
                <div class="col-md-4 col-9">
                    <h6 class="mb-1">${this.escapeHtml(item.nombre)}</h6>
                    <small class="text-muted">Precio unitario: ${item.precio_formateado || App.formatPrice(item.precio_unitario)}</small>
                </div>
                <div class="col-md-3 col-6 mt-2 mt-md-0">
                    <div class="input-group input-group-sm" style="max-width:140px">
                        <button class="btn btn-outline-secondary btn-qty" data-action="minus" data-id="${item.id}">−</button>
                        <input type="number" class="form-control text-center qty-input"
                               value="${item.cantidad}" min="1" max="${item.stock || 99}"
                               data-id="${item.id}" data-stock="${item.stock || 99}">
                        <button class="btn btn-outline-secondary btn-qty" data-action="plus" data-id="${item.id}">+</button>
                    </div>
                </div>
                <div class="col-md-2 col-4 mt-2 mt-md-0 text-end">
                    <strong>${item.subtotal_formateado || App.formatPrice(item.subtotal)}</strong>
                </div>
                <div class="col-md-1 col-2 text-end">
                    <button class="btn btn-sm btn-outline-danger btn-remove" data-id="${item.id}" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    },

    /**
     * Renderiza el resumen del carrito
     */
    renderSummary() {
        const container = document.getElementById('cart-summary-content');
        if (!container || !this.cart) return;

        container.innerHTML = `
            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal:</span>
                <span>${this.cart.subtotal_formateado}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>IVA (19%):</span>
                <span>${this.cart.iva_formateado}</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-3">
                <strong>Total:</strong>
                <strong class="text-primary fs-5">${this.cart.total_formateado}</strong>
            </div>
            <button class="btn btn-accent w-100 btn-lg" id="btn-checkout" ${App.user ? '' : 'disabled'}>
                ${App.user ? 'Proceder al Pago' : 'Inicia Sesión para Comprar'}
            </button>
            ${!App.user ? '<p class="text-muted small mt-2 text-center">Debes iniciar sesión para continuar con la compra.</p>' : ''}
            <button class="btn btn-outline-danger w-100 mt-2 btn-sm" id="btn-clear-cart">Vaciar Carrito</button>
        `;
    },

    /**
     * Configura eventos del carrito
     */
    initEventListeners() {
        // Cambiar cantidad con botones +/- (delegado)
        document.addEventListener('click', async (e) => {
            if (e.target.classList.contains('btn-qty') || e.target.closest('.btn-qty')) {
                const btn = e.target.closest('.btn-qty');
                const itemId = btn.dataset.id;
                const action = btn.dataset.action;
                const input = document.querySelector(`.qty-input[data-id="${itemId}"]`);
                if (!input) return;

                let qty = parseInt(input.value) || 1;
                const maxStock = parseInt(input.dataset.stock) || 99;

                if (action === 'minus') qty = Math.max(1, qty - 1);
                if (action === 'plus') qty = Math.min(maxStock, qty + 1);

                input.value = qty;
                await this.updateItem(itemId, qty);
            }
        });

        // Cambiar cantidad con input directo (delegado)
        document.addEventListener('change', async (e) => {
            if (e.target.classList.contains('qty-input')) {
                const input = e.target;
                const itemId = input.dataset.id;
                let qty = parseInt(input.value) || 1;
                const maxStock = parseInt(input.dataset.stock) || 99;

                qty = Math.max(1, Math.min(maxStock, qty));
                input.value = qty;
                await this.updateItem(itemId, qty);
            }
        });

        // Eliminar item (delegado)
        document.addEventListener('click', async (e) => {
            if (e.target.classList.contains('btn-remove') || e.target.closest('.btn-remove')) {
                const btn = e.target.closest('.btn-remove');
                const itemId = btn.dataset.id;

                if (confirm('¿Eliminar este producto del carrito?')) {
                    await this.removeItem(itemId);
                }
            }
        });

        // Proceder al pago (delegado)
        document.addEventListener('click', (e) => {
            if (e.target.id === 'btn-checkout') {
                if (!App.user) {
                    window.location.href = '/login.html';
                } else {
                    window.location.href = '/checkout.html';
                }
            }
        });

        // Vaciar carrito (delegado)
        document.addEventListener('click', async (e) => {
            if (e.target.id === 'btn-clear-cart') {
                if (confirm('¿Vaciar el carrito completamente?')) {
                    await this.clearCart();
                }
            }
        });
    },

    /**
     * Actualiza cantidad de un item
     */
    async updateItem(itemId, quantity) {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/carrito/${itemId}`, {
                method: 'PATCH',
                body: JSON.stringify({ cantidad: quantity })
            });

            const data = await resp.json();
            if (data.success) {
                this.cart = data.data;
                this.renderItems();
                this.renderSummary();
                App.cartCount = this.cart.items ? this.cart.items.length : 0;
                App.updateCartBadge();
            } else {
                App.showToast(data.error?.message || 'Error al actualizar', 'error');
                await this.loadCart();
            }
        } catch (err) {
            App.showToast('Error de conexión', 'error');
        }
    },

    /**
     * Elimina un item del carrito
     */
    async removeItem(itemId) {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/carrito/${itemId}`, {
                method: 'DELETE'
            });

            const data = await resp.json();
            if (data.success) {
                this.cart = data.data;
                await this.loadCart();
                App.showToast('Producto eliminado del carrito', 'success');
            }
        } catch (err) {
            App.showToast('Error al eliminar', 'error');
        }
    },

    /**
     * Vacía el carrito
     */
    async clearCart() {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/carrito`, {
                method: 'DELETE'
            });

            const data = await resp.json();
            if (data.success) {
                await this.loadCart();
                App.showToast('Carrito vaciado', 'success');
            }
        } catch (err) {
            App.showToast('Error al vaciar', 'error');
        }
    },

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }
};
