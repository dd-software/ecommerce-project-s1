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
        view.innerHTML = UI.loader();

        const data = await (await App.fetchAuth(`${App.apiBase}/carrito`)).json();
        if (!data.success || !data.data.items || data.data.items.length === 0) {
            UI.mostrarVacio(view, { icono: 'bi-cart-x', titulo: 'Tu carrito está vacío', textoBoton: 'Ir al catálogo', enlaceBoton: '#/catalogo' });
            return;
        }
        this.cart = data.data;
        // Datos guardados del perfil para precargar la dirección/teléfono
        try {
            const pd = await (await App.fetchAuth(`${App.apiBase}/auth/perfil`)).json();
            this.perfil = pd.success ? pd.data : {};
        } catch (e) { this.perfil = {}; }
        this.renderPage();
    },

    renderPage() {
        const view = document.getElementById('view-generic');
        const u = App.user || {};
        const p = this.perfil || {};   // datos guardados para precargar envío
        const pf = (x) => Catalogo.escapeHtml(x || '');
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
                                <input type="tel" class="form-control mb-2" id="co-telefono" value="${pf(p.telefono)}" placeholder="+56 2 2123 4567" required></div>
                        </div>
                    </div>

                    <div class="checkout-step">
                        <h6><span class="checkout-step-n">2</span> Dirección de envío</h6>
                        <label class="form-label">Calle y número *</label>
                        <input class="form-control mb-2" id="co-calle" value="${pf(p.direccion)}" placeholder="Ej: Av. Alemania 0671" required>
                        <label class="form-label">Apartamento / Casa (opcional)</label>
                        <input class="form-control mb-2" id="co-apto" placeholder="Ej: Depto 402, Torre B">
                        <div class="row">
                            <div class="col-md-5"><label class="form-label">Comuna / Ciudad *</label>
                                <input class="form-control mb-2" id="co-ciudad" value="${pf(p.comuna)}" required></div>
                            <div class="col-md-4"><label class="form-label">Región *</label>
                                <input class="form-control mb-2" id="co-region" value="${pf(p.region)}" required></div>
                            <div class="col-md-3"><label class="form-label">Código postal</label>
                                <input class="form-control mb-2" id="co-cp" value="${pf(p.codigo_postal)}"></div>
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
                        <label class="form-check-label" for="co-terms">Acepto los <a href="#" class="qc-terms-link">términos y condiciones y la política de privacidad</a> *</label>
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
        // Link "términos y condiciones" → modal (stopPropagation: que el label no togglee el checkbox)
        document.querySelector('.qc-terms-link')?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            new bootstrap.Modal(document.getElementById('terminosModal')).show();
        });
        // "He leído y acepto" marca el checkbox al cerrar el modal
        const btnAcceptTerms = document.getElementById('btn-terms-accept');
        if (btnAcceptTerms) btnAcceptTerms.onclick = () => { document.getElementById('co-terms').checked = true; };
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

            // Guardar datos de envío en el perfil para precargarlos la próxima compra (fire-and-forget)
            App.fetchAuth(`${App.apiBase}/auth/perfil`, {
                method: 'PATCH',
                body: JSON.stringify({
                    telefono,
                    direccion: calle,
                    comuna: document.getElementById('co-ciudad').value.trim(),
                    region: document.getElementById('co-region').value.trim(),
                    codigo_postal: document.getElementById('co-cp').value.trim()
                })
            }).catch(() => {});  // si falla, no rompe la compra

            const payResp = await App.fetchAuth(`${App.apiBase}/pagos/iniciar`, {
                method: 'POST',
                body: JSON.stringify({ pedido_id: pedidoId, email: document.getElementById('co-email').value.trim() })
            });
            const payData = await payResp.json();
            if (!payData.success) throw new Error(payData.error?.message || 'No se pudo iniciar el pago');

            // MercadoPago: salimos del SPA hacia el checkout de MP. Vuelve a /?pago=ok&pedido=...
            if (payData.data.modo === 'mercadopago' && payData.data.init_point) {
                window.location.href = payData.data.init_point;
                return;
            }

            // Simulado (sin credenciales MP): aprobado al instante.
            if (payData.data.estado === 'aprobado') {
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
                show('No se pudo procesar el pago. Inténtalo nuevamente.');
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
            UI.mostrarVacio(view, { icono: 'bi-bag', titulo: 'No hay un pedido reciente', textoBoton: 'Ir al catálogo', enlaceBoton: '#/catalogo' });
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
                <a href="#/pedidos" class="btn btn-outline-uct w-100">Ver Mis Pedidos</a>
            </div>
            <p class="text-muted small mt-3"><i class="bi bi-shield-lock"></i> Transacción 100% segura y encriptada</p>
        </div>`;
        // "Descargar recibo": arma un comprobante imprimible (Guardar como PDF del navegador).
        // Sin librerías; los datos salen de /api/pedidos/{id}.
        document.getElementById('confirm-receipt')?.addEventListener('click', async () => {
            try {
                const data = await (await App.fetchAuth(`${App.apiBase}/pedidos/${o.id}`)).json();
                if (!data.success) throw new Error('fetch');
                const p = data.data;
                const esc = (s) => Catalogo.escapeHtml(String(s ?? ''));
                const filas = (p.detalle || []).map(d =>
                    `<tr><td>${esc(d.nombre_producto)}</td><td class="c">x${d.cantidad}</td><td class="r">${App.formatPrice(d.precio_unitario * d.cantidad)}</td></tr>`
                ).join('');
                const win = window.open('', '_blank', 'width=620,height=800');
                if (!win) { App.showToast('Permití las ventanas emergentes para descargar el recibo.', 'info'); return; }
                const cliente = ((App.user?.nombre || '') + ' ' + (App.user?.apellido || '')).trim();
                win.document.write(`<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Recibo ${esc(numero)}</title>
                <style>
                  *{box-sizing:border-box}
                  body{font-family:Arial,Helvetica,sans-serif;color:#1a1a1a;background:#ececec;margin:0;padding:18px 10px}
                  .receipt{max-width:520px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 6px 24px rgba(0,0,0,.10)}
                  .r-head{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;color:#fff;background:linear-gradient(135deg,#1c1413 0%,#C82F1D 130%);-webkit-print-color-adjust:exact;print-color-adjust:exact}
                  .r-brand{display:flex;align-items:center;gap:10px}
                  .r-mark{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;background:#F74F3C;border-radius:8px;-webkit-print-color-adjust:exact;print-color-adjust:exact}
                  .r-mark svg{width:17px;height:17px}
                  .r-name{font-size:18px;font-weight:800;line-height:1}
                  .r-name small{display:block;font-size:10px;letter-spacing:2px;opacity:.8;font-weight:700}
                  .r-meta{display:flex;flex-direction:column;align-items:flex-end;gap:8px}
                  .badge{background:#e6f7ef;color:#00a06a;font-weight:700;font-size:12px;padding:5px 12px;border-radius:999px;-webkit-print-color-adjust:exact;print-color-adjust:exact}
                  .r-meta .doc{font-size:11px;letter-spacing:1px;opacity:.85;text-transform:uppercase}
                  .r-body{padding:18px 20px}
                  .ordn{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:14px}
                  .lbl{font-size:10px;letter-spacing:1px;color:#999;text-transform:uppercase}
                  .ordn .n{font-size:20px;font-weight:800}
                  .muted{color:#888;font-size:12px}
                  table{width:100%;border-collapse:collapse;margin:6px 0}
                  th{font-size:11px;letter-spacing:.5px;text-transform:uppercase;color:#999;text-align:left;border-bottom:2px solid #eee;padding:8px}
                  td{padding:10px 8px;border-bottom:1px solid #eee;font-size:14px}
                  .c{text-align:center}.r{text-align:right}
                  .tot{display:flex;justify-content:space-between;font-size:14px;padding:4px 0;color:#444}
                  .tot.big{background:#1a1a1a;color:#fff;font-weight:800;font-size:16px;border-radius:9px;padding:12px 14px;margin-top:9px;-webkit-print-color-adjust:exact;print-color-adjust:exact}
                  .boxes{display:flex;gap:14px;margin-top:20px}
                  .box{flex:1;background:#f6f6f6;border-radius:10px;padding:14px;font-size:13px;-webkit-print-color-adjust:exact;print-color-adjust:exact}
                  .box .t{font-size:10px;letter-spacing:1px;color:#999;font-weight:700;text-transform:uppercase;margin-bottom:8px}
                  .foot{text-align:center;color:#999;font-size:12px;margin-top:22px;padding-top:16px;border-top:1px solid #eee}
                  @media print{body{background:#fff;padding:0}.receipt{box-shadow:none;border-radius:0;max-width:none}}
                </style></head><body>
                  <div class="receipt">
                    <div class="r-head">
                      <div class="r-brand"><span class="r-mark"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h4l2.5-6 3.5 12 3-9 1.5 3H22"/></svg></span><span class="r-name">QuadCore<small>ELECTR&Oacute;NICA</small></span></div>
                      <div class="r-meta"><span class="badge">&#10003; Pagado</span><span class="doc">Recibo de compra</span></div>
                    </div>
                    <div class="r-body">
                      <div class="ordn">
                        <div><div class="lbl">N&deg; de orden</div><div class="n">${esc(numero)}</div></div>
                        <div class="muted r">Fecha de emisi&oacute;n<br><b>${esc((p.created_at || '').slice(0, 10))}</b></div>
                      </div>
                      <table><thead><tr><th>Producto</th><th class="c">Cant.</th><th class="r">Subtotal</th></tr></thead><tbody>${filas}</tbody></table>
                      <div class="tot"><span>Subtotal</span><span>${esc(p.subtotal_formateado || '')}</span></div>
                      ${p.iva_formateado ? `<div class="tot"><span>IVA (19%)</span><span>${esc(p.iva_formateado)}</span></div>` : ''}
                      <div class="tot"><span>Despacho</span><span>Gratis</span></div>
                      <div class="tot big"><span>TOTAL</span><span>${esc(p.total_formateado || o.total)}</span></div>
                      <div class="boxes">
                        <div class="box"><div class="t">Despacho a domicilio</div><b>${esc(cliente)}</b><br>${esc(o.direccion)}</div>
                        <div class="box"><div class="t">M&eacute;todo de pago</div>Pago aprobado<br>v&iacute;a Mercado Pago<br><span class="muted">${esc(o.email)}</span></div>
                      </div>
                      <div class="foot">&iexcl;Gracias por tu compra en QuadCore!<br>&iquest;Dudas con tu pedido? Escribinos a soporte@quadcorestore.com</div>
                    </div>
                  </div>
                </body></html>`);
                win.document.close();
                win.focus();
                win.print();
            } catch (e) {
                App.showToast('No se pudo generar el recibo. Intentá de nuevo.', 'error');
            }
        });
    }
};
