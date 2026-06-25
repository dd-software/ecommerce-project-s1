/**
 * checkout.js - Proceso de checkout y creación de pedido con PayPal y Webpay (Wizard de 2 pasos)
 */

const Checkout = {
    cartId: null,
    cart: null,
    cuponAplicado: false,
    selectedMethod: 'webpay',
    paypalConfig: null,
    paypalButtonsInstance: null,
    tempOrderId: null,

    init() {
        const modalEl = document.getElementById('checkoutModal');
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', async (e) => {
                if (!App.user) {
                    e.preventDefault(); // Evitar que abra el modal
                    
                    // Cerrar offcanvas si está abierto
                    const offcanvasEl = document.getElementById('cartOffcanvas');
                    if (offcanvasEl) {
                        const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                        if (offcanvas) offcanvas.hide();
                    }

                    App.showToast('Inicia sesión para continuar con la compra', 'warning');
                    
                    // Mostrar modal de login
                    const loginModalEl = document.getElementById('loginModal');
                    if (loginModalEl) {
                        const loginModal = bootstrap.Modal.getInstance(loginModalEl) || new bootstrap.Modal(loginModalEl);
                        loginModal.show();
                    }
                    return;
                }
                
                // Resetear el formulario al abrir el modal
                this.resetCheckoutForm();

                // Pre-cargar datos del perfil del usuario
                try {
                    const profileResp = await App.fetchAuth(`${App.apiBase}/auth/perfil`);
                    const profile = await profileResp.json();
                    if (profile) {
                        const nameInput = document.getElementById('shipping-name');
                        const lastnameInput = document.getElementById('shipping-lastname');
                        const phoneInput = document.getElementById('shipping-phone');
                        const addressInput = document.getElementById('shipping-address');
                        
                        if (nameInput) nameInput.value = profile.nombre || '';
                        if (lastnameInput) lastnameInput.value = profile.apellido || '';
                        if (phoneInput) phoneInput.value = profile.telefono || '';
                        if (addressInput) addressInput.value = profile.direccion || '';
                    }
                } catch (err) {
                    console.error("Error al pre-cargar perfil:", err);
                }

                // Cargar el carrito
                await this.loadCart();
                // Pre-cargar el SDK de PayPal en segundo plano (sin renderizar botones aún)
                await this.loadPaypalSdk();
            });
        }

        // Detectar cambios en campos obligatorios para habilitar/deshabilitar el botón
        ['shipping-name', 'shipping-lastname', 'shipping-phone', 'shipping-address'].forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('input', () => this.validateCheckoutForm());
                input.addEventListener('change', () => this.validateCheckoutForm());
            }
        });

        this.initEventListeners();
    },

    /**
     * Valida si el formulario cumple los requisitos básicos para habilitar el botón principal
     */
    validateCheckoutForm() {
        const name = document.getElementById('shipping-name')?.value.trim();
        const lastname = document.getElementById('shipping-lastname')?.value.trim();
        const phone = document.getElementById('shipping-phone')?.value.trim();
        const address = document.getElementById('shipping-address')?.value.trim();
        const btn = document.getElementById('btn-place-order');
        if (!btn) return;

        // El botón se habilita si todos los campos obligatorios están llenos y hay método de pago
        if (name && lastname && phone && address && this.selectedMethod) {
            btn.disabled = false;
        } else {
            btn.disabled = true;
        }

        // Actualizar texto del botón dinámicamente según el método de pago
        if (this.selectedMethod === 'webpay') {
            btn.textContent = 'Pagar con Webpay';
        } else if (this.selectedMethod === 'paypal') {
            btn.textContent = 'Continuar con PayPal';
        }
    },

    /**
     * Restablece el formulario a su estado original (paso 1)
     */
    resetCheckoutForm() {
        // Habilitar campos
        const nameEl = document.getElementById('shipping-name');
        const lastnameEl = document.getElementById('shipping-lastname');
        const addressEl = document.getElementById('shipping-address');
        const phoneEl = document.getElementById('shipping-phone');
        const notesEl = document.getElementById('order-notes');
        const couponEl = document.getElementById('coupon-code');
        const couponBtn = document.getElementById('btn-apply-coupon');

        if (nameEl) { nameEl.disabled = false; nameEl.value = ''; }
        if (lastnameEl) { lastnameEl.disabled = false; lastnameEl.value = ''; }
        if (addressEl) { addressEl.disabled = false; addressEl.value = ''; }
        if (phoneEl) { phoneEl.disabled = false; phoneEl.value = ''; }
        if (notesEl) notesEl.disabled = false;
        if (couponEl) couponEl.disabled = false;
        if (couponBtn) couponBtn.disabled = false;

        // Habilitar opciones de método de pago
        document.querySelectorAll('.payment-option').forEach(opt => {
            opt.style.pointerEvents = 'auto';
            opt.style.opacity = '1';
        });

        // Restablecer método seleccionado
        this.selectedMethod = 'webpay';
        const options = document.querySelectorAll('.payment-option');
        options.forEach(o => {
            if (o.getAttribute('data-method') === 'webpay') {
                o.classList.add('active');
            } else {
                o.classList.remove('active');
            }
        });

        // Mostrar botón principal verde
        const btn = document.getElementById('btn-place-order');
        if (btn) {
            btn.classList.remove('d-none');
            btn.textContent = this.selectedMethod === 'paypal' ? 'Continuar con PayPal' : 'Pagar con Webpay';
        }

        // Ocultar y vaciar contenedor de PayPal
        const paypalContainer = document.getElementById('paypal-button-container');
        if (paypalContainer) {
            paypalContainer.classList.add('d-none');
            paypalContainer.innerHTML = '';
        }

        this.validateCheckoutForm();
    },

    /**
     * Carga el SDK de PayPal de manera dinámica según la configuración del servidor
     */
    async loadPaypalSdk() {
        if (window.paypal) {
            return;
        }

        try {
            const resp = await App.fetchAuth(`${App.apiBase}/pagos/config`);
            const data = await resp.json();

            if (data.success && data.data) {
                this.paypalConfig = data.data;
                const clientId = this.paypalConfig.paypal_client_id || 'sb';

                if (document.getElementById('paypal-sdk-script')) {
                    return;
                }

                const script = document.createElement('script');
                script.id = 'paypal-sdk-script';
                script.src = `https://www.paypal.com/sdk/js?client-id=${clientId}&currency=USD&disable-funding=card,credit`;
                script.onerror = () => {
                    console.error('Error al cargar la SDK de PayPal.');
                };
                document.head.appendChild(script);
            }
        } catch (e) {
            console.error('Error al obtener config de PayPal:', e);
        }
    },

    /**
     * Inicia el flujo de PayPal (Paso 2): Bloquea formulario, crea pedido y muestra botones oficiales
     */
    async initiatePaypalFlow() {
        const btn = document.getElementById('btn-place-order');
        const errorDiv = document.getElementById('checkout-error');
        if (errorDiv) errorDiv.classList.add('d-none');

        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Procesando pedido...';
        }

        try {
            // Guardar cambios del perfil
            await this.saveProfileData();

            // 1. Crear el pedido en el backend
            const orderId = await this.createPendingOrder();
            this.tempOrderId = orderId;

            // 2. Bloquear campos para evitar modificaciones
            document.getElementById('shipping-name').disabled = true;
            document.getElementById('shipping-lastname').disabled = true;
            document.getElementById('shipping-address').disabled = true;
            document.getElementById('shipping-phone').disabled = true;
            document.getElementById('order-notes').disabled = true;
            document.getElementById('coupon-code').disabled = true;
            document.getElementById('btn-apply-coupon').disabled = true;

            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.style.pointerEvents = 'none';
                opt.style.opacity = '0.6';
            });

            // 3. Ocultar el botón verde principal
            if (btn) btn.classList.add('d-none');

            // 4. Mostrar el contenedor de PayPal y renderizar los botones oficiales
            const paypalContainer = document.getElementById('paypal-button-container');
            if (paypalContainer) {
                paypalContainer.classList.remove('d-none');
                this.renderPaypalButtonsForOrder(orderId);
            }

            App.showToast('Pedido registrado. Completa tu pago con PayPal.', 'info');

        } catch (err) {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Continuar con PayPal';
            }
            if (errorDiv) {
                errorDiv.textContent = err.message;
                errorDiv.classList.remove('d-none');
            }
            App.showToast(err.message, 'error');
        }
    },

    /**
     * Rinde los Smart Payment Buttons específicos para el pedido creado
     */
    renderPaypalButtonsForOrder(orderId) {
        const container = document.getElementById('paypal-button-container');
        if (!container || !window.paypal) return;

        container.innerHTML = `
            <div class="alert alert-info text-center py-2 mb-3 shadow-sm" style="font-size: 0.9rem;">
                <i class="bi bi-info-circle me-2"></i>
                Haz clic abajo para completar tu pago de forma segura
            </div>
            <div id="paypal-buttons-inner" class="mb-2"></div>
            <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btn-cancel-paypal">
                <i class="bi bi-arrow-left me-1"></i> Modificar datos o cambiar método
            </button>
        `;

        // Asignar el botón de cancelar para volver al Paso 1
        const cancelBtn = document.getElementById('btn-cancel-paypal');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                this.resetCheckoutForm();
                App.showToast('Formulario liberado para modificaciones', 'info');
            });
        }

        this.paypalButtonsInstance = window.paypal.Buttons({
            style: {
                layout: 'vertical',
                color:  'gold',
                shape:  'rect',
                label:  'paypal'
            },
            createOrder: (data, actions) => {
                const rate = this.paypalConfig?.exchange_rate || 930;
                const totalCLP = this.cart.total; // en centavos
                const totalUSD = (totalCLP / 100) / rate;

                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            currency_code: 'USD',
                            value: totalUSD.toFixed(2)
                        },
                        custom_id: orderId.toString()
                    }]
                });
            },
            onApprove: async (data, actions) => {
                App.showLoading();
                try {
                    const details = await actions.order.capture();
                    
                    // Enviar confirmación al backend
                    const paymentResp = await App.fetchAuth(`${App.apiBase}/pagos/procesar`, {
                        method: 'POST',
                        body: JSON.stringify({
                            pedido_id: orderId,
                            metodo_pago: 'paypal',
                            token_tarjeta: details.id
                        })
                    });

                    const paymentData = await paymentResp.json();
                    App.hideLoading();

                    if (paymentData.success && paymentData.data.estado === 'aprobado') {
                        App.showToast('¡Pago con PayPal aprobado! Pedido confirmado.', 'success');
                        
                        const modalEl = document.getElementById('checkoutModal');
                        if (modalEl) {
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }

                        setTimeout(() => {
                            window.location.href = App.getBasePath() + '/index.html';
                        }, 2000);
                    } else {
                        throw new Error(paymentData.error?.message || 'Error al registrar el pago en el servidor.');
                    }
                } catch (err) {
                    App.hideLoading();
                    App.showToast(err.message, 'error');
                }
            },
            onCancel: (data) => {
                App.showToast('Pago cancelado por el usuario.', 'warning');
            },
            onError: (err) => {
                console.error('Error de PayPal SDK:', err);
                App.showToast('Ocurrió un error al procesar el pago con PayPal.', 'error');
            }
        });

        this.paypalButtonsInstance.render('#paypal-buttons-inner');
    },

    /**
     * Crea un pedido pendiente en el backend
     */
    async createPendingOrder() {
        const direccion = document.getElementById('shipping-address')?.value.trim();
        const telefono = document.getElementById('shipping-phone')?.value.trim();
        const notas = document.getElementById('order-notes')?.value.trim();
        const cupon = document.getElementById('coupon-code')?.value.trim() || null;

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
            throw new Error(orderData.error?.message || 'Error al crear el pedido.');
        }

        return orderData.data.id || orderData.data.pedido_id;
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
                this.validateCheckoutForm();
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
                const btn = document.getElementById('btn-place-order');
                if (btn && btn.disabled) return;

                if (this.selectedMethod === 'webpay') {
                    await this.placeOrder();
                } else if (this.selectedMethod === 'paypal') {
                    await this.initiatePaypalFlow();
                }
            });
        }

        // Botón de pago clásico (Webpay / PayPal Step 1)
        const btn = document.getElementById('btn-place-order');
        if (btn) {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                if (btn.disabled) return;

                if (this.selectedMethod === 'webpay') {
                    await this.placeOrder();
                } else if (this.selectedMethod === 'paypal') {
                    await this.initiatePaypalFlow();
                }
            });
        }

        // Eventos para la selección del método de pago
        const options = document.querySelectorAll('.payment-option');
        options.forEach(opt => {
            opt.addEventListener('click', () => {
                // Si la selección está bloqueada (Paso 2), ignorar clicks
                if (opt.style.pointerEvents === 'none') return;

                options.forEach(o => o.classList.remove('active'));
                opt.classList.add('active');

                this.selectedMethod = opt.getAttribute('data-method');
                this.validateCheckoutForm();
            });
        });

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
     * Crea el pedido y procesa el pago simulado con Webpay
     */
    async placeOrder() {
        const btn = document.getElementById('btn-place-order');
        const errorDiv = document.getElementById('checkout-error');

        if (!this.cartId || !this.cart || !this.cart.items || this.cart.items.length === 0) {
            App.showToast('El carrito está vacío', 'error');
            return;
        }

        const nombre = document.getElementById('shipping-name')?.value.trim();
        const apellido = document.getElementById('shipping-lastname')?.value.trim();
        const telefono = document.getElementById('shipping-phone')?.value.trim();
        const direccion = document.getElementById('shipping-address')?.value.trim();

        if (!nombre || !apellido || !telefono || !direccion) {
            if (errorDiv) {
                errorDiv.textContent = 'Nombre, Apellido, Teléfono y Dirección de envío son obligatorios.';
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
            // Guardar cambios del perfil
            await this.saveProfileData();

            // Paso 1: Crear pedido en estado pendiente
            const orderId = await this.createPendingOrder();
            
            // Paso 2: Procesar pago (simulación Webpay)
            App.showToast('Pedido creado. Procesando pago...', 'info');

            const paymentResp = await App.fetchAuth(`${App.apiBase}/pagos/procesar`, {
                method: 'POST',
                body: JSON.stringify({
                    pedido_id: orderId,
                    metodo_pago: 'webpay',
                    token_tarjeta: 'tok_sim_' + Date.now()
                })
            });

            const paymentData = await paymentResp.json();

            if (paymentData.success && paymentData.data.estado === 'aprobado') {
                App.showToast('¡Pago aprobado! Pedido confirmado.', 'success');
                setTimeout(() => {
                    window.location.href = App.getBasePath() + '/index.html';
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

        if (btn && this.selectedMethod === 'webpay') {
            btn.disabled = false;
            btn.textContent = 'Pagar con Webpay';
        }
    },

    /**
     * Guarda los datos modificados de nombre, apellido y teléfono en el perfil
     */
    async saveProfileData() {
        const nombre = document.getElementById('shipping-name')?.value.trim();
        const apellido = document.getElementById('shipping-lastname')?.value.trim();
        const telefono = document.getElementById('shipping-phone')?.value.trim();

        if (nombre || apellido || telefono) {
            try {
                await App.fetchAuth(`${App.apiBase}/auth/perfil`, {
                    method: 'PATCH',
                    body: JSON.stringify({
                        nombre: nombre,
                        apellido: apellido,
                        telefono: telefono
                    })
                });
            } catch (err) {
                console.error("Error al actualizar perfil durante checkout:", err);
            }
        }
    }
};
