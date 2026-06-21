/**
 * checkout.js - Página de checkout (#/checkout) + confirmación (#/confirmacion)
 * Renderiza en view-generic (SPA). Reusa los totales del carrito y el flujo
 * crear pedido → procesar pago que ya expone el backend.
 */

const Checkout = {
    cart: null,
    lastOrder: null,   // datos del último pedido aprobado, para la confirmación

    // ─────────────────────────── Página #/checkout ───────────────────────────
    async openPage() {
        const view = document.getElementById('view-generic');
        if (!view) return;
        if (!App.user) {
            App.showToast('Inicia sesión para continuar', 'info');
            location.hash = '#/carrito';
            new bootstrap.Modal(document.getElementById('loginModal')).show();
            return;
        }
        view.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';

        const data = await (await App.fetchAuth(`${App.apiBase}/carrito`)).json();
        if (!data.success || !data.data.items || data.data.items.length === 0) {
            view.innerHTML = `<div class="empty-state"><i class="bi bi-cart-x"></i>
                <h5>Tu carrito está vacío</h5><a href="#/catalogo" class="btn btn-accent btn-sm mt-2">Ir al catálogo</a></div>`;
            return;
        }
        this.cart = data.data;
        this.renderPage();
    },

    renderPage() {
        const view = document.getElementById('view-generic');
        const u = App.user || {};
        const itemsResumen = this.cart.items.map(i => `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div><span class="fw-semibold">${Catalogo.escapeHtml(i.nombre)}</span>
                    <small class="text-muted ms-1">x${i.cantidad}</small></div>
                <span>${i.subtotal_formateado || App.formatPrice(i.subtotal)}</span>
            </div>`).join('');

        view.innerHTML = `
        <div class="checkout-page">
            <h1 class="cart-page-title">Finalizar compra</h1>
            <form id="checkout-form" class="row g-4">
                <div class="col-lg-7">
                    <div class="checkout-step">
                        <h6><span class="checkout-step-n">1</span> Datos personales</h6>
                        <label class="form-label">Nombre completo *</label>
                        <input class="form-control mb-2" id="co-nombre" value="${Catalogo.escapeHtml((u.nombre || '') + (u.apellido ? ' ' + u.apellido : ''))}" required>
                        <div class="row">
                            <div class="col-md-6"><label class="form-label">Email *</label>
                                <input type="email" class="form-control mb-2" id="co-email" value="${Catalogo.escapeHtml(u.email || '')}" required></div>
                            <div class="col-md-6"><label class="form-label">Teléfono *</label>
                                <input type="tel" class="form-control mb-2" id="co-telefono" placeholder="+56 9 1234 5678" required></div>
                        </div>
                    </div>

                    <div class="checkout-step">
                        <h6><span class="checkout-step-n">2</span> Dirección de envío</h6>
                        <label class="form-label">Calle y número *</label>
                        <input class="form-control mb-2" id="co-calle" placeholder="Ej: Av. Alemania 0671" required>
                        <label class="form-label">Apartamento / Casa (opcional)</label>
                        <input class="form-control mb-2" id="co-apto" placeholder="Ej: Depto 402, Torre B">
                        <div class="row">
                            <div class="col-md-5"><label class="form-label">Ciudad *</label>
                                <input class="form-control mb-2" id="co-ciudad" required></div>
                            <div class="col-md-4"><label class="form-label">Región *</label>
                                <input class="form-control mb-2" id="co-region" required></div>
                            <div class="col-md-3"><label class="form-label">Código postal</label>
                                <input class="form-control mb-2" id="co-cp"></div>
                        </div>
                    </div>

                    <div class="checkout-step">
                        <h6><span class="checkout-step-n">3</span> Método de pago</h6>
                        ${this.payOption('tarjeta', 'Tarjeta de Crédito / Débito', 'bi-credit-card', true)}
                        ${this.payOption('transferencia', 'Transferencia Bancaria', 'bi-bank')}
                        ${this.payOption('mercadopago', 'Mercado Pago', 'bi-wallet2')}
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="co-terms" required>
                        <label class="form-check-label" for="co-terms">Acepto los términos y condiciones y la política de privacidad *</label>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="cart-summary-card checkout-summary">
                        <h6 class="cart-summary-title">Resumen de compra</h6>
                        <div class="checkout-resumen-items">${itemsResumen}</div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2"><span>Productos (${this.cart.items.length})</span><span>${this.cart.subtotal_formateado}</span></div>
                        <div class="d-flex justify-content-between mb-2"><span>IVA (19%)</span><span>${this.cart.iva_formateado}</span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Envío</span><span class="text-success fw-semibold">Gratis ✓</span></div>
                        <div class="mb-2">
                            <label class="form-label">Cupón de descuento</label>
                            <div class="input-group input-group-sm">
                                <input class="form-control" id="co-cupon" placeholder="Código">
                                <button class="btn btn-outline-secondary" type="button" id="co-apply-cupon">Aplicar</button>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-end mb-3">
                            <strong>Total a pagar</strong><strong class="cart-total-amount">${this.cart.total_formateado}</strong>
                        </div>
                        <div id="checkout-error" class="alert alert-danger d-none py-2"></div>
                        <button type="submit" class="btn btn-accent w-100 mb-2" id="btn-place-order"><i class="bi bi-lock"></i> Confirmar pago</button>
                        <a href="#/carrito" class="btn btn-outline-uct w-100">Volver al carrito</a>
                        <p class="text-muted small text-center mt-3 mb-0"><i class="bi bi-shield-check"></i> Pago 100% seguro y encriptado</p>
                    </div>
                </div>
            </form>
        </div>`;

        document.getElementById('checkout-form').addEventListener('submit', (e) => { e.preventDefault(); this.placeOrder(); });
        document.getElementById('co-apply-cupon').addEventListener('click', () => {
            const c = document.getElementById('co-cupon').value.trim();
            // ponytail: el cupón se valida/aplica en el backend al confirmar; sin preview en vivo.
            App.showToast(c ? `Cupón "${c}" se aplicará al confirmar` : 'Ingresa un código', c ? 'info' : 'error');
        });
    },

    payOption(value, label, icon, checked = false) {
        return `
            <label class="checkout-pay">
                <input type="radio" name="metodo_pago" value="${value}" ${checked ? 'checked' : ''}>
                <i class="bi ${icon}"></i> <span>${label}</span>
            </label>`;
    },

    async placeOrder() {
        const err = document.getElementById('checkout-error');
        const btn = document.getElementById('btn-place-order');
        const show = (msg) => { if (err) { err.textContent = msg; err.classList.remove('d-none'); } };
        if (err) err.classList.add('d-none');

        const calle = document.getElementById('co-calle').value.trim();
        const telefono = document.getElementById('co-telefono').value.trim();
        if (!calle) return show('La calle y número son obligatorios.');
        if (!document.getElementById('co-terms').checked) return show('Debes aceptar los términos y condiciones.');

        const direccion = [
            calle,
            document.getElementById('co-apto').value.trim(),
            document.getElementById('co-ciudad').value.trim(),
            document.getElementById('co-region').value.trim(),
            document.getElementById('co-cp').value.trim() ? 'CP ' + document.getElementById('co-cp').value.trim() : ''
        ].filter(Boolean).join(', ');
        const metodo = document.querySelector('input[name="metodo_pago"]:checked')?.value || 'tarjeta';
        const cupon = document.getElementById('co-cupon').value.trim() || null;

        btn.disabled = true;
        btn.innerHTML = 'Procesando...';
        try {
            const orderResp = await App.fetchAuth(`${App.apiBase}/checkout`, {
                method: 'POST',
                body: JSON.stringify({ carrito_id: this.cart.id, direccion_envio: direccion, telefono, cupon })
            });
            const orderData = await orderResp.json();
            if (!orderData.success) throw new Error(orderData.error?.message || 'Error al crear el pedido');
            const pedido = orderData.data;
            const pedidoId = pedido.id || pedido.pedido_id;

            const payResp = await App.fetchAuth(`${App.apiBase}/pagos/procesar`, {
                method: 'POST',
                body: JSON.stringify({ pedido_id: pedidoId, metodo_pago: metodo, token_tarjeta: 'tok_sim_' + Date.now() })
            });
            const payData = await payResp.json();

            if (payData.success && payData.data.estado === 'aprobado') {
                this.lastOrder = {
                    id: pedidoId,
                    total: pedido.total_formateado || App.formatPrice(pedido.total),
                    direccion,
                    email: document.getElementById('co-email').value.trim()
                };
                await Carrito.loadCart();      // carrito quedó vacío
                location.hash = '#/confirmacion';
            } else {
                // El carrito sigue activo tras un rechazo (el checkout ya no lo vacía),
                // así que reintentar crea un pedido nuevo del mismo carrito.
                show('Pago rechazado: ' + (payData.data?.mensaje || 'Inténtalo nuevamente'));
                btn.disabled = false;
                btn.innerHTML = 'Reintentar pago';
            }
        } catch (e) {
            show(e.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-lock"></i> Confirmar pago';
        }
    },

    // ─────────────────────────── Página #/confirmacion ───────────────────────────
    openConfirmacion() {
        const view = document.getElementById('view-generic');
        if (!view) return;
        const o = this.lastOrder;
        if (!o) {   // entraron directo sin comprar
            view.innerHTML = `<div class="empty-state"><i class="bi bi-bag"></i>
                <h5>No hay un pedido reciente</h5><a href="#/catalogo" class="btn btn-accent btn-sm mt-2">Ir al catálogo</a></div>`;
            return;
        }
        const numero = `#QC-${new Date().getFullYear()}-${String(o.id).padStart(6, '0')}`;
        view.innerHTML = `
        <div class="confirm-page">
            <div class="confirm-check"><i class="bi bi-check-lg"></i></div>
            <h1 class="confirm-title">¡Pago confirmado!</h1>
            <p class="text-muted">Tu pedido ha sido procesado exitosamente.</p>
            <div class="confirm-order">
                <small class="text-muted text-uppercase">Número de orden</small>
                <div class="confirm-order-n">${numero}</div>
            </div>
            <div class="confirm-line"><i class="bi bi-clock"></i> Entrega estimada <b>2-3 días hábiles</b></div>
            <div class="confirm-box">
                <small class="text-muted text-uppercase"><i class="bi bi-box-seam"></i> Tu pedido será entregado en</small>
                <p class="mb-0 mt-1">${Catalogo.escapeHtml(o.direccion)}</p>
            </div>
            <div class="confirm-line"><i class="bi bi-envelope"></i> Confirmación enviada a <b>${Catalogo.escapeHtml(o.email)}</b></div>
            <div class="confirm-actions">
                <button class="btn btn-accent w-100" id="confirm-receipt"><i class="bi bi-download"></i> Descargar recibo</button>
                <a href="#/" class="btn btn-outline-uct w-100">Volver al inicio</a>
                <a href="#/pedidos" class="btn btn-outline-uct w-100">Ver mis compras</a>
            </div>
            <p class="text-muted small mt-3"><i class="bi bi-shield-lock"></i> Transacción 100% segura y encriptada</p>
        </div>`;
        // ponytail: "Descargar recibo" es stub; hay endpoint /api/exportar/pedidos para PDF/CSV real si se pide.
        document.getElementById('confirm-receipt')?.addEventListener('click', () => App.showToast('Descarga de recibo: próximamente', 'info'));
    }
};
