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

        // Escuchar cuando se abre el offcanvas del carrito para recargar los datos
        const offcanvasEl = document.getElementById('cartOffcanvas');
        if (offcanvasEl) {
            offcanvasEl.addEventListener('show.bs.offcanvas', () => {
                this.loadCart();
            });
        }
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
            <div class="cart-item py-3 border-bottom d-flex align-items-center" data-item-id="${item.id}">
                <div class="cart-item-image-container me-3">
                    <img src="${item.imagen_url || App.placeholders.img80}"
                         class="cart-item-img rounded" alt="${this.escapeHtml(item.nombre)}"
                         onerror="this.src=App.placeholders.img80"
                         style="width: 65px; height: 65px; object-fit: cover;">
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <h6 class="mb-0 fw-bold text-dark text-truncate" style="max-width: 190px;" title="${this.escapeHtml(item.nombre)}">
                            ${this.escapeHtml(item.nombre)}
                        </h6>
                        <button class="btn btn-link text-danger p-0 btn-remove ms-2" data-id="${item.id}" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="text-muted small mb-2">
                        Precio: ${item.precio_formateado || App.formatPrice(item.precio_unitario)}
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="input-group input-group-sm" style="width: 100px;">
                            <button class="btn btn-outline-secondary btn-qty py-0 px-2" data-action="minus" data-id="${item.id}">−</button>
                            <input type="number" class="form-control text-center qty-input p-0"
                                   value="${item.cantidad}" min="1" max="${item.stock || 99}"
                                   data-id="${item.id}" data-stock="${item.stock || 99}"
                                   style="font-size: 0.85rem; height: 28px;">
                            <button class="btn btn-outline-secondary btn-qty py-0 px-2" data-action="plus" data-id="${item.id}">+</button>
                        </div>
                        <span class="fw-bold text-primary">${item.subtotal_formateado || App.formatPrice(item.total)}</span>
                    </div>
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
                <span>${this.cart.subtotal_formateado || App.formatPrice(this.cart.subtotal)}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>IVA (19%):</span>
                <span>${this.cart.iva_formateado || App.formatPrice(this.cart.iva)}</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-3">
                <strong>Total:</strong>
                <strong class="text-primary fs-5">${this.cart.total_formateado || App.formatPrice(this.cart.total)}</strong>
            </div>
            <button class="btn btn-accent w-100 btn-lg mb-2" id="btn-checkout">
                Proceder al Pago
            </button>
            <button class="btn btn-outline-danger w-100 btn-sm" id="btn-clear-cart">Vaciar Carrito</button>
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
                // Cerrar offcanvas del carrito
                const offcanvasEl = document.getElementById('cartOffcanvas');
                if (offcanvasEl) {
                    const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl) || new bootstrap.Offcanvas(offcanvasEl);
                    if (offcanvas) offcanvas.hide();
                }

                if (!App.user) {
                    App.showToast('Inicia sesión para continuar con la compra', 'warning');
                    const loginModalEl = document.getElementById('loginModal');
                    if (loginModalEl) {
                        const loginModal = bootstrap.Modal.getInstance(loginModalEl) || new bootstrap.Modal(loginModalEl);
                        loginModal.show();
                    }
                } else {
                    // Abrir modal de checkout
                    const modalEl = document.getElementById('checkoutModal');
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modal.show();
                    }
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
