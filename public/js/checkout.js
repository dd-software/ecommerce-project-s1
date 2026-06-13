/**
 * checkout.js - Proceso de checkout y creación de pedido
 */

const Checkout = {
    cartId: null,
    cart: null,
    cuponAplicado: false,

    /**
     * Inicializa la página de checkout
     */
    async init() {
        if (!App.user) {
            window.location.href = '/login.html?redirect=checkout.html';
            return;
        }

        await this.loadCart();
        this.initEventListeners();
    },

    /**
     * Carga el carrito para checkout
     */
    async loadCart() {
        const container = document.getElementById('checkout-items');
        if (!container) return;

        try {
            const resp = await App.fetchAuth(`${App.apiBase}/carrito`);
            const data = await resp.json();

            if (data.success && data.data && data.data.items && data.data.items.length > 0) {
                this.cart = data.data;
                this.cartId = data.data.id;
                this.renderItems();
                this.renderSummary();
            } else {
                container.innerHTML = `
                    <div class="alert alert-warning">
                        Tu carrito está vacío. <a href="/catalogo.html">Ir al catálogo</a>
                    </div>`;
                const btn = document.getElementById('btn-place-order');
                if (btn) btn.disabled = true;
            }
        } catch (e) {
            container.innerHTML = '<div class="alert alert-danger">Error al cargar el carrito.</div>';
        }
    },

    /**
     * Renderiza items en checkout
     */
    renderItems() {
        const container = document.getElementById('checkout-items');
        if (!container || !this.cart) return;

        container.innerHTML = this.cart.items.map(item => `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <span class="fw-semibold">${Catalogo.escapeHtml(item.nombre)}</span>
                    <small class="text-muted ms-2">x${item.cantidad}</small>
                </div>
                <span>${item.subtotal_formateado || App.formatPrice(item.subtotal)}</span>
            </div>
        `).join('');
    },

    /**
     * Renderiza resumen de totales
     */
    renderSummary() {
        const container = document.getElementById('checkout-totals');
        if (!container || !this.cart) return;

        let descuentoHTML = '';
        if (this.cuponAplicado && this.cart.descuento) {
            descuentoHTML = `
                <div class="d-flex justify-content-between mb-2 text-success">
                    <span>Descuento:</span>
                    <span>-${App.formatPrice(this.cart.descuento)}</span>
                </div>`;
        }

        container.innerHTML = `
            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal:</span>
                <span>${this.cart.subtotal_formateado}</span>
            </div>
            ${descuentoHTML}
            <div class="d-flex justify-content-between mb-2">
                <span>IVA (19%):</span>
                <span>${this.cart.iva_formateado}</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-3">
                <strong>Total a Pagar:</strong>
                <strong class="text-primary fs-5">${this.cart.total_formateado}</strong>
            </div>`;
    },

    /**
     * Configura eventos
     */
    initEventListeners() {
        // Formulario de checkout
        const form = document.getElementById('checkout-form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.placeOrder();
            });
        }

        // Botón de pago
        const btn = document.getElementById('btn-place-order');
        if (btn) {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                await this.placeOrder();
            });
        }

        // Aplicar cupón
        const cuponBtn = document.getElementById('btn-apply-coupon');
        if (cuponBtn) {
            cuponBtn.addEventListener('click', () => {
                const codigo = document.getElementById('coupon-code')?.value.trim();
                if (codigo) {
                    App.showToast('Cupón será validado al crear el pedido', 'info');
                }
            });
        }
    },

    /**
     * Crea el pedido y procesa el pago
     */
    async placeOrder() {
        const btn = document.getElementById('btn-place-order');
        const errorDiv = document.getElementById('checkout-error');

        if (!this.cartId || !this.cart || !this.cart.items || this.cart.items.length === 0) {
            App.showToast('El carrito está vacío', 'error');
            return;
        }

        // Recopilar datos del formulario
        const direccion = document.getElementById('shipping-address')?.value.trim();
        const telefono = document.getElementById('shipping-phone')?.value.trim();
        const notas = document.getElementById('order-notes')?.value.trim();
        const cupon = document.getElementById('coupon-code')?.value.trim() || null;

        if (!direccion) {
            if (errorDiv) {
                errorDiv.textContent = 'La dirección de envío es obligatoria.';
                errorDiv.classList.remove('d-none');
            }
            return;
        }

        if (errorDiv) errorDiv.classList.add('d-none');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Procesando...';
        }

        try {
            // Paso 1: Crear pedido
            const orderResp = await App.fetchAuth(`${App.apiBase}/checkout`, {
                method: 'POST',
                body: JSON.stringify({
                    carrito_id: this.cartId,
                    direccion_envio: direccion,
                    telefono: telefono,
                    notas: notas,
                    cupon: cupon
                })
            });

            const orderData = await orderResp.json();

            if (!orderData.success) {
                throw new Error(orderData.error?.message || 'Error al crear el pedido');
            }

            const pedido = orderData.data;

            // Paso 2: Procesar pago (simulación)
            App.showToast('Pedido creado. Procesando pago...', 'info');

            const paymentResp = await App.fetchAuth(`${App.apiBase}/pagos/procesar`, {
                method: 'POST',
                body: JSON.stringify({
                    pedido_id: pedido.id || pedido.pedido_id,
                    metodo_pago: 'webpay',
                    token_tarjeta: 'tok_sim_' + Date.now()
                })
            });

            const paymentData = await paymentResp.json();

            if (paymentData.success && paymentData.data.estado === 'aprobado') {
                // Éxito
                App.showToast('¡Pago aprobado! Pedido confirmado.', 'success');
                // Redirigir a confirmación
                setTimeout(() => {
                    window.location.href = `/pedido-confirmado.html?pedido_id=${pedido.id || pedido.pedido_id}`;
                }, 2000);
            } else {
                App.showToast('Pago rechazado: ' + (paymentData.data?.mensaje || 'Error'), 'error');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Reintentar Pago';
                }
            }
        } catch (err) {
            if (errorDiv) {
                errorDiv.textContent = err.message;
                errorDiv.classList.remove('d-none');
            }
            App.showToast(err.message, 'error');
        }

        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Confirmar Pedido';
        }
    }
};
