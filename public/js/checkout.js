/**
 * checkout.js - Proceso de pago y confirmación de pedido
 * Muestra el resumen del carrito, captura dirección y procesa el pedido
 */

// Placeholder SVG local
const IMG_PLACEHOLDER_40 = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40'%3E%3Crect width='40' height='40' fill='%23e2e8f0'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' font-family='sans-serif' font-size='8' fill='%2394a3b8'%3EImg%3C/text%3E%3C/svg%3E";

const Checkout = {
    /**
     * Inicializa el checkout (se llama cuando se abre el modal)
     */
    init() {
        this.renderResumen();
        this.initEventListeners();
    },

    /**
     * Renderiza el resumen del pedido en el modal
     */
    renderResumen() {
        const items = (typeof Carrito !== 'undefined') ? Carrito.getItems() : [];

        const checkoutItems  = document.getElementById('checkout-items');
        const checkoutTotals = document.getElementById('checkout-totals');

        if (!checkoutItems) return;

        if (items.length === 0) {
            checkoutItems.innerHTML = '<p class="text-muted text-center">Tu carrito está vacío.</p>';
            if (checkoutTotals) checkoutTotals.innerHTML = '';
            return;
        }

        // Items
        checkoutItems.innerHTML = items.map(item => {
            const precio   = item.precio_unitario || item.precio;
            const subtotal = item.subtotal || (precio * item.cantidad);
            return `
                <div class="d-flex justify-content-between align-items-center mb-2 small">
                    <div class="d-flex align-items-center gap-2">
                        <img src="${item.imagen_url || IMG_PLACEHOLDER_40}"
                             alt="${this.escapeHtml(item.nombre)}"
                             style="width:40px;height:40px;object-fit:cover;border-radius:6px;"
                             onerror="this.onerror=null;this.src=IMG_PLACEHOLDER_40">
                        <span>${this.escapeHtml(item.nombre)} <span class="text-muted">x${item.cantidad}</span></span>
                    </div>
                    <strong>${App.formatPrice(subtotal)}</strong>
                </div>`;
        }).join('');

        // Totales
        const subtotalTotal = items.reduce((sum, item) => {
            const precio   = item.precio_unitario || item.precio;
            const subtotal = item.subtotal || (precio * item.cantidad);
            return sum + subtotal;
        }, 0);

        if (checkoutTotals) {
            checkoutTotals.innerHTML = `
                <div class="d-flex justify-content-between mb-1">
                    <span>Subtotal</span>
                    <span>${App.formatPrice(subtotalTotal)}</span>
                </div>
                <div class="d-flex justify-content-between mb-2 text-muted small">
                    <span>Envío</span>
                    <span>Por confirmar</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between fw-bold fs-6">
                    <span>Total</span>
                    <span class="text-primary">${App.formatPrice(subtotalTotal)}</span>
                </div>`;
        }
    },

    /**
     * Configura event listeners del formulario de checkout
     */
    initEventListeners() {
        const form = document.getElementById('checkout-form');
        if (form) {
            // Evitar doble binding
            const newForm = form.cloneNode(true);
            form.parentNode.replaceChild(newForm, form);
            newForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.procesarPedido();
            });
        }

        // Cupón
        const btnCupon = document.getElementById('btn-apply-coupon');
        if (btnCupon) {
            btnCupon.addEventListener('click', () => this.aplicarCupon());
        }
    },

    /**
     * Aplica un cupón de descuento (placeholder — la API puede soportarlo)
     */
    aplicarCupon() {
        const code = document.getElementById('coupon-code')?.value?.trim();
        if (!code) {
            App.showToast('Ingresa un código de cupón', 'warning');
            return;
        }
        // Aquí se puede integrar un endpoint de validación
        App.showToast('Cupón no válido o expirado', 'error');
    },

    /**
     * Procesa el pedido enviando datos a la API de checkout
     */
    async procesarPedido() {
        if (!App.user || !App.token) {
            App.showToast('Debes iniciar sesión para continuar', 'error');
            return;
        }

        const direccion = document.getElementById('shipping-address')?.value?.trim();
        if (!direccion) {
            App.showToast('Ingresa una dirección de envío', 'warning');
            document.getElementById('shipping-address')?.focus();
            return;
        }

        const items = (typeof Carrito !== 'undefined') ? Carrito.getItems() : [];
        if (items.length === 0) {
            App.showToast('Tu carrito está vacío', 'warning');
            return;
        }

        const btn = document.getElementById('btn-place-order');
        const errorEl = document.getElementById('checkout-error');
        if (btn) { btn.disabled = true; btn.textContent = 'Procesando...'; }
        if (errorEl) errorEl.classList.add('d-none');

        try {
            const payload = {
                direccion_envio: direccion,
                telefono:        document.getElementById('shipping-phone')?.value?.trim() || '',
                notas:           document.getElementById('order-notes')?.value?.trim() || '',
                session_id:      App.getSessionId()
            };

            const resp = await App.fetchAuth(`${App.apiBase}/checkout`, {
                method: 'POST',
                body:   JSON.stringify(payload)
            });
            const data = await resp.json();

            if (data.success) {
                // Pedido creado → procesar pago
                const pedidoId = data.data?.pedido_id || data.data?.id;
                await this.procesarPago(pedidoId, data.data?.total || 0);
            } else {
                const msg = data.error?.message || 'Error al crear el pedido';
                if (errorEl) { errorEl.textContent = msg; errorEl.classList.remove('d-none'); }
                App.showToast(msg, 'error');
            }
        } catch (e) {
            const msg = 'Error de conexión. Intenta nuevamente.';
            if (errorEl) { errorEl.textContent = msg; errorEl.classList.remove('d-none'); }
            App.showToast(msg, 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = 'Confirmar Pedido y Pagar'; }
        }
    },

    /**
     * Procesa el pago del pedido
     */
    async procesarPago(pedidoId, total) {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/pagos/procesar`, {
                method: 'POST',
                body:   JSON.stringify({
                    pedido_id:    pedidoId,
                    monto:        total,
                    metodo_pago:  'tarjeta',  // valor por defecto
                    session_id:   App.getSessionId()
                })
            });
            const data = await resp.json();

            if (data.success) {
                // Limpiar carrito local
                if (typeof Carrito !== 'undefined') {
                    Carrito.items = [];
                    App.cartCount = 0;
                    App.updateCartBadge();
                }

                // Cerrar modal y mostrar confirmación
                const modalEl = document.getElementById('checkoutModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }

                App.showToast('¡Pedido confirmado! Recibirás un email con los detalles.', 'success');
                // Redirigir a confirmación
                setTimeout(() => {
                    window.location.href = `pedido-confirmado.html?pedido_id=${pedidoId}`;
                }, 1500);
            } else {
                const msg = data.error?.message || 'Error al procesar el pago';
                App.showToast(msg, 'error');
                const errorEl = document.getElementById('checkout-error');
                if (errorEl) { errorEl.textContent = msg; errorEl.classList.remove('d-none'); }
            }
        } catch (e) {
            App.showToast('Error al procesar el pago', 'error');
        }
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
