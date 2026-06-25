/**
 * cuenta.js - Mis pedidos (#/pedidos, #/pedido/:id) y Perfil (#/perfil)
 * Páginas de la cuenta del usuario. Requieren sesión; renderizan en view-generic.
 */

// Estado de pedido → etiqueta + color + ícono (badge)
const ESTADOS_PEDIDO = {
    pendiente:      ['Pendiente de pago', 'warning', 'bi-hourglass-split'],
    pagado:         ['Pago confirmado', 'success', 'bi-check-circle'],
    en_preparacion: ['Preparando pedido', 'info', 'bi-gear'],
    enviado:        ['En camino', 'info', 'bi-truck'],
    entregado:      ['Entregado', 'success', 'bi-check2-all'],
    cancelado:      ['Cancelado', 'danger', 'bi-x-circle'],
};

function ordenNumero(id, fecha) {
    const año = (fecha || '').slice(0, 4) || new Date().getFullYear();
    return `#QC-${año}-${String(id).padStart(6, '0')}`;
}

function badgeEstado(estado) {
    const [label, color] = ESTADOS_PEDIDO[estado] || [estado, 'secondary'];
    return `<span class="pedido-badge ${color}"><span class="pedido-dot"></span>${label}</span>`;
}

function requireLogin(view, titulo) {
    if (App.token) return false;
    UI.mostrarVacio(view, { icono: 'bi-person-lock', titulo, descripcion: 'Inicia sesión para continuar.' });
    return true;
}

const Pedidos = {
    pedidos: [],
    filtro: 'todos',

    async openPage() {
        const view = document.getElementById('view-generic');
        if (!view || requireLogin(view, 'Mis pedidos')) return;
        view.innerHTML = UI.loader('Cargando pedidos...');
        try {
            const data = await (await App.fetchAuth(`${App.apiBase}/pedidos`)).json();
            this.pedidos = data.success ? data.data : [];
        } catch (e) { this.pedidos = []; }
        this.filtro = 'todos';
        this.render();
    },

    render() {
        const view = document.getElementById('view-generic');
        if (!this.pedidos.length) {
            UI.mostrarVacio(view, {
                icono: 'bi-bag',
                titulo: 'No tienes pedidos aún',
                descripcion: 'Cuando realices tu primera compra, aparecerá aquí el historial.',
                textoBoton: 'Ir al catálogo',
                enlaceBoton: '#/catalogo'
            });
            return;
        }

        // Pills: Todos + estados presentes en los datos
        const estados = [...new Set(this.pedidos.map(p => p.estado))];
        const pills = ['todos', ...estados].map(e => {
            const label = e === 'todos' ? 'Todos' : (ESTADOS_PEDIDO[e]?.[0] || e);
            return `<button class="pedido-pill ${this.filtro === e ? 'active' : ''}" data-filtro="${e}">${label}</button>`;
        }).join('');

        const lista = this.filtro === 'todos' ? this.pedidos : this.pedidos.filter(p => p.estado === this.filtro);
        const rows = lista.map(p => `
            <tr>
                <td class="fw-bold">${ordenNumero(p.id, p.created_at)}</td>
                <td class="text-muted">${(p.created_at || '').slice(0, 10)}</td>
                <td class="text-primary fw-bold">${p.total_formateado || App.formatPrice(p.total)}</td>
                <td>${badgeEstado(p.estado)}</td>
                <td class="text-end"><a href="#/pedido/${p.id}" class="pedido-ver">Ver detalle <i class="bi bi-chevron-right"></i></a></td>
            </tr>`).join('');

        view.innerHTML = `
            <div class="cuenta-page">
                <div class="gw-page">
                    <header class="gw-head">
                        <p class="gw-kicker"><i class="bi bi-bag-check"></i> Mi cuenta</p>
                        <h1>Mis pedidos</h1>
                        <p class="lead">${this.pedidos.length} pedido${this.pedidos.length === 1 ? '' : 's'} en tu historial.</p>
                    </header>
                </div>
                <div class="pedido-pills mb-3 mt-4">${pills}</div>
                <div class="cart-table-card">
                    <table class="cart-table pedidos-table">
                        <thead><tr><th>Orden</th><th>Fecha</th><th>Total</th><th>Estado</th><th class="text-end">Acción</th></tr></thead>
                        <tbody>${rows || '<tr><td colspan="5" class="text-center text-muted py-4">No hay pedidos en este filtro.</td></tr>'}</tbody>
                    </table>
                </div>
            </div>`;

        view.querySelectorAll('.pedido-pill').forEach(b => b.addEventListener('click', () => {
            this.filtro = b.dataset.filtro;
            this.render();
        }));
    },

    async openDetail(id) {
        const view = document.getElementById('view-generic');
        if (!view || requireLogin(view, 'Detalle del pedido')) return;
        view.innerHTML = UI.loader('Cargando detalle...');
        let pedido;
        try {
            const data = await (await App.fetchAuth(`${App.apiBase}/pedidos/${id}`)).json();
            if (!data.success) throw new Error();
            pedido = data.data;
        } catch (e) {
            view.innerHTML = '<div class="alert alert-danger">No se pudo cargar el pedido.</div>';
            return;
        }

        const items = (pedido.detalle || []).map(d => `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        ${d.imagen_url ? `<img src="${d.imagen_url}" class="cart-item-img">` : '<div class="qc-img-ph cart-item-img"><i class="bi bi-cpu"></i></div>'}
                        <span class="cart-row-name">${Catalogo.escapeHtml(d.nombre_producto)}</span>
                    </div>
                </td>
                <td>x${d.cantidad}</td>
                <td class="text-primary fw-bold">${App.formatPrice(d.precio_unitario * d.cantidad)}</td>
            </tr>`).join('');

        view.innerHTML = `
            <div class="cuenta-page">
                <a href="#/pedidos" class="cart-keep-shopping mb-2"><i class="bi bi-arrow-left"></i> Volver a mis pedidos</a>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h1 class="cart-page-title mb-0">Pedido ${ordenNumero(pedido.id, pedido.created_at)}</h1>
                    ${badgeEstado(pedido.estado)}
                </div>
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="cart-table-card">
                            <table class="cart-table"><thead><tr><th>Producto</th><th>Cant.</th><th>Subtotal</th></tr></thead>
                            <tbody>${items}</tbody></table>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="cart-summary-card">
                            <h6 class="cart-summary-title">Resumen</h6>
                            <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span>${pedido.subtotal_formateado}</span></div>
                            <div class="d-flex justify-content-between mb-2"><span>IVA (19%)</span><span>${pedido.iva_formateado}</span></div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3"><strong>Total</strong><strong class="cart-total-amount">${pedido.total_formateado}</strong></div>
                            <div class="confirm-box mb-0">
                                <small class="text-muted text-uppercase"><i class="bi bi-box-seam"></i> Envío a</small>
                                <p class="mb-0 mt-1">${Catalogo.escapeHtml(pedido.direccion_envio || 'Sin dirección')}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
    }
};

const Perfil = {
    async openPage() {
        const view = document.getElementById('view-generic');
        if (!view || requireLogin(view, 'Mi perfil')) return;
        view.innerHTML = UI.loader('Cargando perfil...');
        let u;
        try {
            const data = await (await App.fetchAuth(`${App.apiBase}/auth/perfil`)).json();
            u = data.success ? data.data : null;
        } catch (e) { u = null; }
        if (!u) { view.innerHTML = '<div class="alert alert-danger">No se pudo cargar el perfil.</div>'; return; }

        const v = (x) => Catalogo.escapeHtml(x || '');
        view.innerHTML = `
            <div class="cuenta-page" style="max-width:620px">
                <h1 class="cart-page-title">Mi Perfil</h1>
                <div class="cart-summary-card">
                    <form id="perfil-form">
                        <div class="row">
                            <div class="col-md-6"><label class="form-label">Nombre</label>
                                <input class="form-control mb-2" id="pf-nombre" value="${v(u.nombre)}" required></div>
                            <div class="col-md-6"><label class="form-label">Apellido</label>
                                <input class="form-control mb-2" id="pf-apellido" value="${v(u.apellido)}" required></div>
                        </div>
                        <label class="form-label">Email</label>
                        <input class="form-control mb-2" value="${v(u.email)}" disabled>

                        <hr class="my-3">
                        <h6 class="mb-1">Datos de envío <span class="text-muted fw-normal">(opcional)</span></h6>
                        <p class="text-muted small mb-3">Si los rellenas, se autocompletan al momento de pagar.</p>
                        <label class="form-label">Teléfono</label>
                        <input class="form-control mb-2" id="pf-telefono" value="${v(u.telefono)}" placeholder="+56 2 2123 4567">
                        <label class="form-label">Dirección (calle y número)</label>
                        <input class="form-control mb-2" id="pf-direccion" value="${v(u.direccion)}" placeholder="Av. Providencia 1234">
                        <div class="row">
                            <div class="col-md-5"><label class="form-label">Comuna</label>
                                <input class="form-control mb-2" id="pf-comuna" value="${v(u.comuna)}"></div>
                            <div class="col-md-4"><label class="form-label">Región</label>
                                <input class="form-control mb-2" id="pf-region" value="${v(u.region)}"></div>
                            <div class="col-md-3"><label class="form-label">Cód. postal</label>
                                <input class="form-control mb-2" id="pf-cp" value="${v(u.codigo_postal)}"></div>
                        </div>

                        <p class="text-muted small mt-1">Cuenta ${v(u.rol)} · miembro desde ${(u.created_at || '').slice(0, 10)}</p>
                        <button class="btn btn-primary" type="submit">Guardar cambios</button>
                    </form>
                </div>
            </div>`;

        document.getElementById('perfil-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type=submit]');
            btn.disabled = true;
            try {
                const val = (id) => document.getElementById(id).value.trim();
                const resp = await App.fetchAuth(`${App.apiBase}/auth/perfil`, {
                    method: 'PATCH',
                    body: JSON.stringify({
                        nombre: val('pf-nombre'), apellido: val('pf-apellido'),
                        telefono: val('pf-telefono'), direccion: val('pf-direccion'),
                        comuna: val('pf-comuna'), region: val('pf-region'), codigo_postal: val('pf-cp')
                    })
                });
                const data = await resp.json();
                if (data.success) {
                    App.user = { ...App.user, nombre: data.data.nombre, apellido: data.data.apellido };
                    localStorage.setItem('uct_user', JSON.stringify(App.user));
                    App.updateNavbar?.();
                    App.showToast('Perfil actualizado', 'success');
                } else {
                    App.showToast(data.error?.message || 'No se pudo guardar', 'error');
                }
            } catch (err) { App.showToast('Error de conexión', 'error'); }
            btn.disabled = false;
        });
    }
};