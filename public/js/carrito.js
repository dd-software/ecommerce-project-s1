/**
 * carrito.js - Gestión del carrito de compras
 * Maneja el offcanvas del carrito, agregar/quitar productos y sincronización con la API
 */

// Placeholder SVG local — no requiere servicios externos
const IMG_PLACEHOLDER_THUMB = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Crect width='60' height='60' fill='%23e2e8f0'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' font-family='sans-serif' font-size='9' fill='%2394a3b8'%3EImg%3C/text%3E%3C/svg%3E";

const Carrito = {
    items: [],

    /**
     * Inicializa el carrito
     */
    async init() {
        await this.cargar();
        this.initEventListeners();
    },

    /**
     * Carga el carrito desde la API
     */
    async cargar() {
        try {
            const sessionId = App.getSessionId();
            const headers = { 'X-Session-Id': sessionId };
            if (App.token) {
                headers['Authorization'] = `Bearer ${App.token}`;
            }

            const resp = await fetch(`${App.apiBase}/carrito`, { headers });
            if (!resp.ok) return;

            const data = await resp.json();
            if (data.success && data.data) {
                this.items = data.data.items || [];
                this.render();
                App.cartCount = this.items.length;
                App.updateCartBadge();
            }
        } catch (e) {
            console.error('Error cargando carrito:', e);
        }
    },

    /**
     * Renderiza los ítems del carrito en el offcanvas
     */
    render() {
        const cartEmpty   = document.getElementById('cart-empty');
        const cartItems   = document.getElementById('cart-items');
        const cartSummary = document.getElementById('cart-summary');

        if (!cartItems) return;

        if (this.items.length === 0) {
            if (cartEmpty)   cartEmpty.classList.remove('d-none');
            if (cartItems)   cartItems.classList.add('d-none');
            if (cartSummary) cartSummary.classList.add('d-none');
            return;
        }

        if (cartEmpty)   cartEmpty.classList.add('d-none');
        if (cartItems)   cartItems.classList.remove('d-none');
        if (cartSummary) cartSummary.classList.remove('d-none');

        // Renderizar items
        cartItems.innerHTML = this.items.map(item => this.itemHtml(item)).join('');

        // Totales
        const subtotal = this.items.reduce((sum, item) => sum + (item.subtotal || item.precio * item.cantidad), 0);
        const summaryContent = document.getElementById('cart-summary-content');
        if (summaryContent) {
            summaryContent.innerHTML = `
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal (${this.items.length} producto${this.items.length !== 1 ? 's' : ''})</span>
                    <strong>${App.formatPrice(subtotal)}</strong>
                </div>
                <div class="d-flex justify-content-between text-muted small">
                    <span>Envío</span>
                    <span>Calculado al pagar</span>
                </div>
            `;
        }

        // Attach event listeners a los botones de cantidad/eliminar
        this.attachItemListeners();
    },

    /**
     * Genera el HTML de un ítem del carrito
     */
    itemHtml(item) {
        const precio = item.precio_unitario || item.precio;
        const subtotal = item.subtotal || (precio * item.cantidad);
        return `
            <div class="d-flex align-items-start mb-3 pb-3 border-bottom cart-item" data-id="${item.id}">
                <img src="${item.imagen_url || IMG_PLACEHOLDER_THUMB}"
                     alt="${this.escapeHtml(item.nombre)}"
                     class="rounded me-3 flex-shrink-0"
                     style="width:60px;height:60px;object-fit:cover;"
                     onerror="this.onerror=null;this.src=IMG_PLACEHOLDER_THUMB">
                <div class="flex-grow-1">
                    <p class="mb-1 fw-semibold small lh-sm">${this.escapeHtml(item.nombre)}</p>
                    <p class="mb-2 text-primary fw-bold small">${App.formatPrice(precio)}</p>
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="width:100px">
                            <button class="btn btn-outline-secondary btn-qty-minus" type="button" data-id="${item.id}" data-qty="${item.cantidad}">−</button>
                            <input type="number" class="form-control text-center px-1 qty-input" value="${item.cantidad}" min="1" max="99" data-id="${item.id}" readonly>
                            <button class="btn btn-outline-secondary btn-qty-plus" type="button" data-id="${item.id}" data-qty="${item.cantidad}">+</button>
                        </div>
                        <button class="btn btn-link text-danger p-0 btn-remove-item" data-id="${item.id}" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="ms-2 text-end">
                    <span class="fw-bold small">${App.formatPrice(subtotal)}</span>
                </div>
            </div>`;
    },

    /**
     * Configura los event listeners del carrito
     */
    initEventListeners() {
        // Abrir carrito → cargar datos frescos
        const cartToggle = document.querySelector('[data-bs-target="#cartOffcanvas"]');
        if (cartToggle) {
            cartToggle.addEventListener('click', () => this.cargar());
        }

        // Vaciar carrito
        document.addEventListener('click', async (e) => {
            if (e.target.id === 'btn-vaciar-carrito') {
                await this.vaciar();
            }
        });
    },

    /**
     * Agrega listeners a botones dentro de los ítems (se llama tras render)
     */
    attachItemListeners() {
        const cartItems = document.getElementById('cart-items');
        if (!cartItems) return;

        // Disminuir cantidad
        cartItems.querySelectorAll('.btn-qty-minus').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id  = btn.dataset.id;
                const qty = parseInt(btn.dataset.qty);
                if (qty <= 1) {
                    await this.eliminar(id);
                } else {
                    await this.actualizarCantidad(id, qty - 1);
                }
            });
        });

        // Aumentar cantidad
        cartItems.querySelectorAll('.btn-qty-plus').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id  = btn.dataset.id;
                const qty = parseInt(btn.dataset.qty);
                await this.actualizarCantidad(id, qty + 1);
            });
        });

        // Eliminar ítem
        cartItems.querySelectorAll('.btn-remove-item').forEach(btn => {
            btn.addEventListener('click', async () => {
                await this.eliminar(btn.dataset.id);
            });
        });
    },

    /**
     * Actualiza la cantidad de un ítem
     */
    async actualizarCantidad(itemId, nuevaCantidad) {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/carrito/${itemId}`, {
                method: 'PATCH',
                body: JSON.stringify({ cantidad: nuevaCantidad, session_id: App.getSessionId() })
            });
            const data = await resp.json();
            if (data.success) {
                this.items = data.data.items || [];
                App.cartCount = this.items.length;
                App.updateCartBadge();
                this.render();
            } else {
                App.showToast(data.error?.message || 'Error al actualizar', 'error');
            }
        } catch (e) {
            App.showToast('Error de conexión', 'error');
        }
    },

    /**
     * Elimina un ítem del carrito
     */
    async eliminar(itemId) {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/carrito/${itemId}`, {
                method: 'DELETE',
                body: JSON.stringify({ session_id: App.getSessionId() })
            });
            const data = await resp.json();
            if (data.success) {
                this.items = data.data.items || [];
                App.cartCount = this.items.length;
                App.updateCartBadge();
                this.render();
                App.showToast('Producto eliminado del carrito', 'info');
            }
        } catch (e) {
            App.showToast('Error de conexión', 'error');
        }
    },

    /**
     * Vacía el carrito completo
     */
    async vaciar() {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/carrito`, {
                method: 'DELETE',
                body: JSON.stringify({ session_id: App.getSessionId() })
            });
            const data = await resp.json();
            if (data.success) {
                this.items = [];
                App.cartCount = 0;
                App.updateCartBadge();
                this.render();
                App.showToast('Carrito vaciado', 'info');
            }
        } catch (e) {
            App.showToast('Error de conexión', 'error');
        }
    },

    /**
     * Devuelve los ítems actuales (usado por Checkout)
     */
    getItems() {
        return this.items;
    },

    /**
     * Escapa HTML para prevenir XSS
     */
    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }
};
