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
                        Tu carrito está vacío. <a href="${App.getBasePath()}/index.html">Ir al catálogo</a>
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
                const configResp = await fetch(`${App.apiBase}/pagos/paypal/config`);
                const configData = await configResp.json();

                // Response::json() envuelve bajo 'data'; soportar ambas formas por compatibilidad
                const clientId = configData.data?.client_id || configData.client_id;
                const configurado = configData.data?.configurado ?? configData.configurado ?? true;

                // Rechazar client IDs inválidos o placeholder
                if (!clientId || clientId === 'test' || clientId === 'sb' || clientId.length < 20 || !configurado) {
                    throw new Error(
                        'PayPal no está configurado. El administrador debe ingresar credenciales válidas de Sandbox ' +
                        '(PAYPAL_CLIENT_ID y PAYPAL_SECRET) en el archivo .env del servidor.'
                    );
                }

                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = `https://www.paypal.com/sdk/js?client-id=${clientId}&currency=USD`;
                    script.onload = resolve;
                    script.onerror = () => reject(new Error('No se pudo cargar el SDK de PayPal. Verifica tu conexión.'));
                    document.head.appendChild(script);
                });
            } catch (e) {
                console.error('[PayPal] Error inicializando SDK:', e);
                App.showToast(e.message || 'No se pudo inicializar PayPal', 'error');
                if (btn) btn.classList.remove('d-none');
                paypalContainer.classList.add('d-none');
                return;
            }
        }

        // Renderizar botón PayPal (Server-Side integration)
        paypal.Buttons({
            style: {
                layout: 'vertical',
                color:  'blue',
                shape:  'rect',
                label:  'paypal'
            },
            createOrder: async (data, actions) => {
                try {
                    const resp = await App.fetchAuth(`${App.apiBase}/pagos/paypal/create`, {
                        method: 'POST',
                        body: JSON.stringify({
                            carrito_id: this.cartId
                        })
                    });

                    const orderData = await resp.json();

                    console.log('[PayPal] Respuesta create-order:', resp.status, orderData);

                    if (!orderData.success) {
                        const msg = orderData.error?.message || 'Error al inicializar el pago con PayPal';
                        console.error('[PayPal] createOrder error del servidor:', orderData);
                        throw new Error(msg);
                    }

                    const orderId = orderData.data?.paypal_order_id;
                    if (!orderId) {
                        console.error('[PayPal] createOrder: paypal_order_id no encontrado en respuesta:', orderData);
                        throw new Error('Error interno: no se recibió el ID de orden de PayPal.');
                    }

                    console.log('[PayPal] Order ID creado:', orderId);
                    // PayPal SDK requiere exactamente el string del Order ID, nunca un objeto
                    return orderId;
                } catch (e) {
                    console.error('[PayPal] createOrder excepción:', e);
                    App.showToast(e.message, 'error');
                    throw e; // Re-throw para que PayPal cierre el popup limpiamente
                }
            },
            onApprove: async (data, actions) => {
                try {
                    console.log('[PayPal] onApprove - orderID:', data.orderID);
                    App.showToast('Procesando pago, no cierres esta ventana...', 'info');

                    const resp = await App.fetchAuth(`${App.apiBase}/pagos/paypal/capture`, {
                        method: 'POST',
                        body: JSON.stringify({
                            paypal_order_id: data.orderID
                        })
                    });

                    const captureData = await resp.json();
                    console.log('[PayPal] Respuesta capture:', resp.status, captureData);

                    if (captureData.success) {
                        App.showToast('¡Pago aprobado! Pedido confirmado.', 'success');

                        const modal = bootstrap.Modal.getInstance(document.getElementById('checkoutModal'));
                        if (modal) modal.hide();

                        // Vaciar el carrito en el frontend
                        if (typeof Carrito !== 'undefined') {
                            await Carrito.clearCart();
                        }

                        // Redirigir a confirmacion de pedido con banner de éxito
                        setTimeout(() => {
                            const pedidoId = captureData.data?.pedido_id || '';
                            window.location.href = App.getBasePath() + '/pedido-confirmado.html?pago=exitoso&pedido_id=' + pedidoId;
                        }, 1500);
                    } else {
                        const msg = captureData.error?.message || 'Error al confirmar pago';
                        console.error('[PayPal] capture error del servidor:', captureData);
                        throw new Error(msg);
                    }
                } catch (err) {
                    console.error('[PayPal] onApprove excepción:', err);
                    App.showToast(err.message || 'Error en la transacción', 'error');
                    if (btn) btn.classList.remove('d-none');
                    paypalContainer.classList.add('d-none');
                }
            },
            onCancel: async (data) => {
                console.log('[PayPal] Pago cancelado por el usuario:', data);
                App.showToast('Pago cancelado. Puedes intentarlo nuevamente.', 'warning');
                // Reactivar carrito para que el usuario pueda reintentar
                try {
                    await App.fetchAuth(`${App.apiBase}/carrito/reactivar`, { method: 'POST' });
                } catch (e) {
                    console.warn('[PayPal] No se pudo reactivar carrito:', e.message);
                }
                if (btn) btn.classList.remove('d-none');
                paypalContainer.classList.add('d-none');
                // Recargar datos del carrito
                await this.loadCart();
            },
            onError: (err) => {
                console.error('[PayPal] SDK Error:', err);
                App.showToast('Ocurrió un error con PayPal. Revisa la consola para más detalles.', 'error');
                if (btn) btn.classList.remove('d-none');
                paypalContainer.classList.add('d-none');
            }
        }).render('#paypal-button-container');
    }
};
