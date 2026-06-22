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
            window.location.href = App.getBasePath() + '/login.html?redirect=checkout.html';
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
     * Valida el formulario y muestra botones de PayPal
     */
    async placeOrder() {
        const btn = document.getElementById('btn-place-order');
        const errorDiv = document.getElementById('checkout-error');

        if (!this.cartId || !this.cart || !this.cart.items || this.cart.items.length === 0) {
            App.showToast('El carrito está vacío', 'error');
            return;
        }

        const direccion = document.getElementById('shipping-address')?.value.trim();
        if (!direccion) {
            if (errorDiv) {
                errorDiv.textContent = 'La dirección de envío es obligatoria.';
                errorDiv.classList.remove('d-none');
            }
            return;
        }

        if (errorDiv) errorDiv.classList.add('d-none');
        if (btn) btn.classList.add('d-none'); // Ocultar botón normal
        
        const paypalContainer = document.getElementById('paypal-button-container');
        paypalContainer.classList.remove('d-none');
        paypalContainer.innerHTML = ''; // Limpiar si ya había botones

        // Cargar script de PayPal dinámicamente si no existe
        if (!window.paypal) {
            try {
                const configResp = await fetch(App.apiBase.replace('/api', '') + '/backend/payments/paypal-config.php');
                const configData = await configResp.json();
                
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = `https://www.paypal.com/sdk/js?client-id=${configData.client_id}&currency=USD`;
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            } catch (e) {
                App.showToast('No se pudo inicializar PayPal', 'error');
                if (btn) btn.classList.remove('d-none');
                paypalContainer.classList.add('d-none');
                return;
            }
        }

        // Renderizar PayPal usando lógica Server-Side
        paypal.Buttons({
            createOrder: async (data, actions) => {
                try {
                    const resp = await App.fetchAuth(`${App.apiBase}/pagos/paypal/create`, {
                        method: 'POST',
                        body: JSON.stringify({
                            carrito_id: this.cartId
                        })
                    });
                    
                    const orderData = await resp.json();
                    
                    if (!orderData.success) {
                        throw new Error(orderData.error?.message || 'Error al inicializar el pago');
                    }
                    
                    return orderData.data.paypal_order_id;
                } catch (e) {
                    App.showToast(e.message, 'error');
                    throw e;
                }
            },
            onApprove: async (data, actions) => {
                try {
                    App.showToast('Procesando pago, no cierres esta ventana...', 'info');
                    
                    const resp = await App.fetchAuth(`${App.apiBase}/pagos/paypal/capture`, {
                        method: 'POST',
                        body: JSON.stringify({
                            paypal_order_id: data.orderID
                        })
                    });
                    
                    const captureData = await resp.json();
                    
                    if (captureData.success) {
                        App.showToast('¡Pago aprobado! Pedido confirmado.', 'success');
                        setTimeout(() => {
                            window.location.href = App.getBasePath() + `/pedido-confirmado.html?pedido_id=${captureData.data.pedido_id}`;
                        }, 2000);
                        
                        const modal = bootstrap.Modal.getInstance(document.getElementById('checkoutModal'));
                        if (modal) modal.hide();
                        
                        // Vaciar el carrito en el frontend ya que se procesó
                        if (typeof Carrito !== 'undefined') {
                            await Carrito.clearCart();
                        }
                    } else {
                        throw new Error(captureData.error?.message || 'Error al confirmar pago');
                    }
                } catch (err) {
                    console.error(err);
                    App.showToast(err.message || 'Error en la transacción', 'error');
                    if (btn) btn.classList.remove('d-none');
                    paypalContainer.classList.add('d-none');
                }
            },
            onError: (err) => {
                console.error('PayPal Error:', err);
                App.showToast('Ocurrió un error con PayPal', 'error');
                if (btn) btn.classList.remove('d-none');
                paypalContainer.classList.add('d-none');
            }
        }).render('#paypal-button-container');
    }
};
