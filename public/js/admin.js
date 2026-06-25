/**
 * admin.js - Panel de administración
 * Dashboard, CRUD productos, gestión de pedidos y reportes
 */

const Admin = {
    currentSection: 'dashboard',

    /**
     * Abre el panel admin como página de la SPA (#/admin).
     * Inyecta el shell (sidebar + contenido) en view-generic y gatea por rol.
     */
    openPage() {
        const view = document.getElementById('view-generic');
        if (!view) return;
        if (!App.user || App.user.rol !== 'admin') {
            UI.mostrarVacio(view, { icono: 'bi-shield-lock', titulo: 'Acceso restringido', descripcion: 'Esta sección es solo para administradores.', textoBoton: 'Volver al inicio', enlaceBoton: '#/', claseBoton: 'btn-outline-uct' });
            return;
        }
        view.innerHTML = `
            <div class="admin-layout">
                <aside class="admin-sidebar">
                    <h6 class="admin-sidebar-title">Panel admin</h6>
                    <a class="admin-nav-link active" data-section="dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="admin-nav-link" data-section="productos"><i class="bi bi-box-seam"></i> Productos</a>
                    <a class="admin-nav-link" data-section="pedidos"><i class="bi bi-bag"></i> Pedidos</a>
                    <a class="admin-nav-link" data-section="usuarios"><i class="bi bi-people"></i> Usuarios</a>
                    <a class="admin-nav-link" data-section="reportes"><i class="bi bi-graph-up"></i> Reportes</a>
                </aside>
                <div class="admin-main" id="admin-content"></div>
            </div>`;
        this.currentSection = 'dashboard';
        this.initNavigation();
        this.initDelegation();
        this.loadDashboard();
    },

    /**
     * Listeners delegados para las acciones del admin (CSP: nada de onclick/onchange
     * inline, que la política de seguridad bloquea). Se engancha una sola vez a nivel
     * document y cubre también el modal de producto (que vive fuera de #admin-content).
     */
    initDelegation() {
        if (this._delegated) return;
        this._delegated = true;

        document.addEventListener('click', (e) => {
            const el = e.target.closest('[data-admin-action]');
            if (!el) return;
            e.preventDefault();
            const a = el.dataset.adminAction;
            if (a === 'new-product')        this.showProductForm();
            else if (a === 'edit-product')  this.showProductForm(parseInt(el.dataset.id));
            else if (a === 'delete-product') this.deleteProduct(parseInt(el.dataset.id));
            else if (a === 'save-product')  this.saveProduct();
            else if (a === 'page-productos') this.loadProductos(parseInt(el.dataset.page));
            else if (a === 'toggle-user')   this.toggleUser(parseInt(el.dataset.id), parseInt(el.dataset.activo));
            else if (a === 'goto-pedidos')  document.querySelector('.admin-nav-link[data-section="pedidos"]')?.click();
        });

        document.addEventListener('change', (e) => {
            const el = e.target.closest('[data-admin-change]');
            if (!el) return;
            if (el.dataset.adminChange === 'order-status') this.changeOrderStatus(parseInt(el.dataset.id), el.value);
            else if (el.dataset.adminChange === 'ventas-periodo') this.refreshVentas(el.value);
        });
    },

    /**
     * Configura navegación del sidebar
     */
    initNavigation() {
        document.querySelectorAll('.admin-nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.dataset.section;
                this.loadSection(section);

                // Actualizar clase active
                document.querySelectorAll('.admin-nav-link').forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            });
        });
    },

    /**
     * Carga una sección del panel
     */
    async loadSection(section) {
        this.currentSection = section;
        const container = document.getElementById('admin-content');
        if (!container) return;

        App.showLoading();

        switch (section) {
            case 'dashboard': await this.loadDashboard(); break;
            case 'productos': await this.loadProductos(); break;
            case 'pedidos': await this.loadPedidos(); break;
            case 'usuarios': await this.loadUsuarios(); break;
            case 'reportes': await this.loadReportes(); break;
            default: await this.loadDashboard();
        }

        App.hideLoading();
    },

    /**
     * Carga el dashboard con métricas
     */
    async loadDashboard() {
        const container = document.getElementById('admin-content');
        if (!container) return;

        try {
            const resp = await App.fetchAuth(`${App.apiBase}/admin/dashboard`);
            const data = await resp.json();

            if (!data.success) throw new Error('Error');

            const d = data.data;

            // Tarjeta de métrica (estilo Mantis adaptado a QuadCore). Sin % de
            // tendencia: no tenemos histórico, así que el subtexto es un dato real.
            const stat = (label, value, sub, icon, variant = '') => `
                <div class="qc-stat ${variant}">
                    <div class="qc-stat-top">
                        <span class="qc-stat-label">${label}</span>
                        <span class="qc-stat-icon"><i class="bi ${icon}"></i></span>
                    </div>
                    <div class="qc-stat-value">${value}</div>
                    <div class="qc-stat-sub">${sub}</div>
                </div>`;

            const agotados = d.productos_agotados || 0;
            const rows = (d.ultimos_pedidos || []).map(p => `
                <tr>
                    <td class="fw-bold">${ordenNumero(p.id, p.created_at)}</td>
                    <td>${this.escapeHtml(p.cliente_nombre)} ${this.escapeHtml(p.apellido || '')}</td>
                    <td class="text-primary fw-bold">${p.total_formateado}</td>
                    <td>${badgeEstado(p.estado)}</td>
                </tr>`).join('');

            const stock = (d.alertas_stock && d.alertas_stock.length)
                ? d.alertas_stock.map(a => `
                    <div class="admin-stock-item">
                        <span class="admin-stock-name">${this.escapeHtml(a.nombre)}</span>
                        ${this.stockBadge(a.stock, a.stock_minimo)}
                    </div>`).join('')
                : '<p class="text-success mb-0"><i class="bi bi-check-circle"></i> Stock al día.</p>';

            container.innerHTML = `
                <h1 class="admin-page-title">Dashboard</h1>
                <div class="qc-stats-grid">
                    ${stat('Ventas del mes', d.total_ventas_mes?.total_ventas_formateado || '$0', `${d.total_ventas_mes?.total_pedidos || 0} pedidos pagados`, 'bi-graph-up-arrow', 'accent')}
                    ${stat('Pedidos totales', d.total_pedidos, `${d.pedidos_pendientes || 0} pendiente${d.pedidos_pendientes === 1 ? '' : 's'}`, 'bi-bag-check', 'warn')}
                    ${stat('Productos', d.total_productos, agotados > 0 ? `${agotados} agotado${agotados === 1 ? '' : 's'}` : 'Catálogo activo', 'bi-box-seam', 'blue')}
                    ${stat('Usuarios', d.total_usuarios, 'Registrados', 'bi-people', 'green')}
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-lg-8">
                        <div class="cart-table-card">
                            <div class="admin-card-head">
                                <h6>Pedidos recientes</h6>
                                <a href="#" class="pedido-ver admin-goto">Ver todos <i class="bi bi-chevron-right"></i></a>
                            </div>
                            <table class="cart-table">
                                <thead><tr><th>Orden</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
                                <tbody>${rows || '<tr><td colspan="4" class="text-center text-muted py-4">Sin pedidos.</td></tr>'}</tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="cart-summary-card" style="position:static">
                            <h6 class="cart-summary-title">Alertas de stock</h6>
                            ${stock}
                        </div>
                    </div>
                </div>`;

            // "Ver todos" → dispara la sección de pedidos reusando la nav del sidebar
            container.querySelector('.admin-goto')?.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelector('.admin-nav-link[data-section="pedidos"]')?.click();
            });
        } catch (e) {
            container.innerHTML = '<div class="alert alert-danger">Error al cargar dashboard.</div>';
        }
    },

    /**
     * Carga gestión de productos (CRUD)
     */
    async loadProductos(page = 1) {
        const container = document.getElementById('admin-content');
        if (!container) return;

        try {
            const resp = await App.fetchAuth(`${App.apiBase}/admin/productos?pagina=${page}&por_pagina=15`);
            const data = await resp.json();

            if (!data.success) throw new Error('Error');

            const productos = data.data;
            const pag = data.meta?.pagination;

            let html = `
                <div class="admin-page-head">
                    <h1 class="admin-page-title mb-0">Gestión de productos</h1>
                    <button class="btn-grafito" id="btn-new-product" data-admin-action="new-product">
                        <i class="bi bi-plus-lg"></i> Nuevo producto
                    </button>
                </div>`;

            if (productos && productos.length > 0) {
                html += `<div class="cart-table-card">
                    <table class="cart-table admin-table">
                        <thead>
                            <tr><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Estado</th><th class="text-end">Acciones</th></tr>
                        </thead>
                        <tbody>
                            ${productos.map(p => `
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            ${p.imagen_url ? `<img src="${p.imagen_url}" class="admin-thumb">` : '<div class="qc-img-ph admin-thumb"><i class="bi bi-cpu"></i></div>'}
                                            <span class="cart-row-name">${this.escapeHtml(p.nombre)}</span>
                                        </div>
                                    </td>
                                    <td class="text-muted">${this.escapeHtml(p.categoria_nombre || '-')}</td>
                                    <td class="fw-bold">${p.precio_formateado || App.formatPrice(p.precio)}</td>
                                    <td>${this.stockBadge(p.stock, p.stock_minimo)}</td>
                                    <td>${this.activoBadge(p.activo)}</td>
                                    <td class="text-end">
                                        <button class="admin-action" data-admin-action="edit-product" data-id="${p.id}" title="Editar"><i class="bi bi-pencil"></i></button>
                                        <button class="admin-action danger" data-admin-action="delete-product" data-id="${p.id}" title="Eliminar"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>`;

                // Paginación (mismo estilo que el catálogo, por coherencia)
                if (pag && pag.total_pages > 1) {
                    html += `<nav class="qc-pagination"><ul class="pagination justify-content-center">`;
                    html += `<li class="page-item ${page <= 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" ${page <= 1 ? '' : `data-admin-action="page-productos" data-page="${page - 1}"`}>Anterior</a></li>`;
                    for (let i = 1; i <= pag.total_pages; i++) {
                        html += `<li class="page-item ${i === page ? 'active' : ''}">
                            <a class="page-link" href="#" data-admin-action="page-productos" data-page="${i}">${i}</a></li>`;
                    }
                    html += `<li class="page-item ${page >= pag.total_pages ? 'disabled' : ''}">
                        <a class="page-link" href="#" ${page >= pag.total_pages ? '' : `data-admin-action="page-productos" data-page="${page + 1}"`}>Siguiente</a></li>`;
                    html += `</ul></nav>`;
                    html += `<div class="qc-pagination-info">Mostrando página ${page} de ${pag.total_pages} · ${pag.total} productos</div>`;
                }
            } else {
                    UI.mostrarVacio(container, {
                        icono: 'bi-box-seam',
                        titulo: 'Sin productos',
                        descripcion: 'Aún no has agregado productos al catálogo.',
                        textoBoton: 'Crear producto',
                        enlaceBoton: '#NuevoProducto', // o usar el botón "Nuevo producto" existente
                        claseBoton: 'btn-accent'
                    });
            }

            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = '<div class="alert alert-danger">Error al cargar productos.</div>';
        }
    },

    /**
     * Carga gestión de pedidos
     */
    async loadPedidos(page = 1) {
        const container = document.getElementById('admin-content');
        if (!container) return;

        try {
            const resp = await App.fetchAuth(`${App.apiBase}/admin/pedidos?pagina=${page}&por_pagina=15`);
            const data = await resp.json();

            if (!data.success) throw new Error('Error');

            const pedidos = data.data;
            const pag = data.meta?.pagination;

            let html = `<h1 class="admin-page-title">Gestión de pedidos</h1>`;

            if (pedidos && pedidos.length > 0) {
                html += `<div class="cart-table-card">
                    <table class="cart-table admin-table">
                        <thead>
                            <tr><th>Orden</th><th>Cliente</th><th>Items</th><th>Total</th><th>Estado</th><th>Fecha</th><th class="text-end">Cambiar estado</th></tr>
                        </thead>
                        <tbody>
                            ${pedidos.map(p => `
                                <tr>
                                    <td class="fw-bold">${ordenNumero(p.id, p.created_at)}</td>
                                    <td>${this.escapeHtml(p.cliente_nombre)} ${this.escapeHtml(p.apellido || '')}</td>
                                    <td>${p.total_items}</td>
                                    <td class="text-primary fw-bold">${p.total_formateado}</td>
                                    <td>${badgeEstado(p.estado)}</td>
                                    <td class="text-muted">${(p.created_at || '').slice(0, 10)}</td>
                                    <td class="text-end">
                                        ${this.estadoSelect(p.id, p.estado)}
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>`;
            } else {
                UI.mostrarVacio(container, {
                    icono: 'bi-bag',
                    titulo: 'Sin pedidos',
                    descripcion: 'Aún no se ha registrado ningún pedido en la tienda.',
                    textoBoton: 'Ver catálogo',
                    enlaceBoton: '#/catalogo'
                });
            }

            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = '<div class="alert alert-danger">Error al cargar pedidos.</div>';
        }
    },

    /**
     * Carga gestión de usuarios
     */
    async loadUsuarios(page = 1) {
        const container = document.getElementById('admin-content');
        if (!container) return;

        try {
            const resp = await App.fetchAuth(`${App.apiBase}/admin/usuarios?pagina=${page}&por_pagina=20`);
            const data = await resp.json();

            if (!data.success) throw new Error('Error');

            const usuarios = data.data;
            let html = `<h1 class="admin-page-title">Gestión de usuarios</h1>`;

            if (usuarios && usuarios.length > 0) {
                html += `<div class="cart-table-card">
                    <table class="cart-table admin-table">
                        <thead><tr><th>Usuario</th><th>Email</th><th>Rol</th><th>Estado</th><th>Último acceso</th><th class="text-end">Acción</th></tr></thead>
                        <tbody>
                            ${usuarios.map(u => `
                                <tr>
                                    <td class="cart-row-name">${this.escapeHtml(u.nombre)} ${this.escapeHtml(u.apellido)}</td>
                                    <td class="text-muted">${this.escapeHtml(u.email)}</td>
                                    <td><span class="qc-chip">${this.escapeHtml(u.rol)}</span></td>
                                    <td>${this.activoBadge(u.activo, 'Deshabilitado')}</td>
                                    <td class="text-muted">${u.ultimo_login ? (u.ultimo_login || '').slice(0, 10) : 'Nunca'}</td>
                                    <td class="text-end">
                                        <button class="admin-action ${u.activo == 1 ? 'danger' : ''}"
                                                data-admin-action="toggle-user" data-id="${u.id}" data-activo="${u.activo == 1 ? 0 : 1}"
                                                title="${u.activo == 1 ? 'Deshabilitar' : 'Activar'}">
                                            <i class="bi ${u.activo == 1 ? 'bi-person-slash' : 'bi-person-check'}"></i>
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>`;
            } else {
                UI.mostrarVacio(container, {
                    icono: 'bi-people',
                    titulo: 'Sin usuarios',
                    descripcion: 'No hay usuarios registrados aún.'
                });
            }

            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = '<div class="alert alert-danger">Error al cargar usuarios.</div>';
        }
    },

    /**
     * Carga reportes
     */
    async loadReportes() {
        const container = document.getElementById('admin-content');
        if (!container) return;

        try {
            const top = await (await App.fetchAuth(`${App.apiBase}/admin/reportes/productos-mas-vendidos`)).json();

            let html = `<h1 class="admin-page-title">Reportes</h1>`;

            // KPIs + gráfico (se llenan según el período elegido)
            html += `<div class="qc-kpi-strip mb-4" id="kpi-strip"></div>`;

            // Productos más vendidos
            html += `<div class="cart-table-card mb-4">
                <div class="admin-card-head"><h6>Productos más vendidos</h6></div>`;
            if (top.success && top.data && top.data.length > 0) {
                const clp = n => '$' + new Intl.NumberFormat('es-CL').format(n);
                html += `<table class="cart-table admin-table">
                    <thead><tr><th style="width:56px">#</th><th>Producto</th><th>Unidades</th><th class="text-end">Recaudación</th></tr></thead>
                    <tbody>${top.data.map((p, i) => `
                        <tr>
                            <td><span class="admin-rank${i === 0 ? ' top' : ''}">${i + 1}</span></td>
                            <td class="cart-row-name">${this.escapeHtml(p.nombre_producto)}</td>
                            <td class="fw-bold">${p.total_vendido}</td>
                            <td class="text-primary fw-bold text-end">${clp(Math.round(p.total_recaudado))}</td>
                        </tr>`).join('')}</tbody></table>`;
            } else {
                html += '<p class="text-muted mb-0 px-1 pb-1">Todavía no hay ventas registradas.</p>';
            }
            html += '</div>';

            // Ventas por día, con selector de período
            html += `<div class="cart-table-card">
                <div class="admin-card-head">
                    <h6>Ventas por día</h6>
                    <div class="d-flex align-items-center gap-3">
                        <span class="qc-chart-legend"><span class="sw"></span>Ingresos diarios</span>
                        <select id="ventas-periodo" class="form-select form-select-sm admin-status-select" data-admin-change="ventas-periodo">
                            <option value="semana">Última semana</option>
                            <option value="mes" selected>Últimos 30 días</option>
                            <option value="trimestre">Últimos 3 meses</option>
                            <option value="semestre">Últimos 6 meses</option>
                            <option value="anio">Último año</option>
                        </select>
                    </div>
                </div>
                <div id="sales-chart" style="padding:18px 18px 0"></div>
            </div>`;

            container.innerHTML = html;
            await this.refreshVentas('mes');
        } catch (e) {
            container.innerHTML = '<div class="alert alert-danger">Error al cargar reportes.</div>';
        }
    },

    /**
     * Refresca KPIs + gráfico de ventas para el período elegido.
     */
    async refreshVentas(periodo) {
        const kpiBox = document.getElementById('kpi-strip');
        const chartBox = document.getElementById('sales-chart');
        if (!kpiBox || !chartBox) return;

        const clp = n => '$' + new Intl.NumberFormat('es-CL').format(n);
        let ventasArr = [];
        try {
            const ventas = await (await App.fetchAuth(`${App.apiBase}/admin/reportes/ventas?periodo=${encodeURIComponent(periodo)}`)).json();
            ventasArr = (ventas.success && Array.isArray(ventas.data)) ? ventas.data : [];
        } catch (e) { /* deja vacío */ }

        const totalPeriodo = ventasArr.reduce((s, x) => s + Math.round(x.total_ventas), 0);
        const totalPedidos = ventasArr.reduce((s, x) => s + (Number(x.total_pedidos) || 0), 0);
        const ticket = totalPedidos ? Math.round(totalPeriodo / totalPedidos) : 0;
        const mejor = ventasArr.reduce((b, x) => (Math.round(x.total_ventas) > (b ? Math.round(b.total_ventas) : -1) ? x : b), null);
        const fechaCorta = f => new Date(f + 'T00:00:00').toLocaleDateString('es-CL', { day: 'numeric', month: 'short' });

        kpiBox.innerHTML = `
            <div class="qc-kpi"><small>Total período</small><b>${clp(totalPeriodo)}</b></div>
            <div class="qc-kpi"><small>Ticket promedio</small><b>${clp(ticket)}</b></div>
            <div class="qc-kpi"><small>Mejor día</small><b>${mejor ? `${fechaCorta(mejor.fecha)} · ${clp(Math.round(mejor.total_ventas))}` : '—'}</b></div>`;

        if (ventasArr.length > 0) {
            this.renderSalesChart(ventasArr);
        } else {
            chartBox.innerHTML = '<p class="text-muted text-center py-5 mb-0">Sin ventas en el período. El gráfico se llena a medida que entran pedidos pagados.</p>';
        }
    },

    /**
     * Renderiza gráfico de ventas simple
     */
    renderSalesChart(ventasData) {
        const ctx = document.getElementById('sales-chart');
        if (!ctx) return;

        const labels = ventasData.map(v => v.fecha);
        const values = ventasData.map(v => Math.round(v.total_ventas));

        // Crear barras simples con HTML/CSS
        const maxVal = Math.max(...values, 1);
        let html = '<div class="d-flex align-items-end" style="height:200px;gap:2px">';
        ventasData.forEach((v, i) => {
            const height = Math.max(4, (values[i] / maxVal) * 180);
            html += `<div class="flex-fill admin-bar" style="height:${height}px;min-width:6px;border-radius:3px 3px 0 0"
                        title="${v.fecha}: $${new Intl.NumberFormat('es-CL').format(values[i])}"></div>`;
        });
        html += '</div>';
        html += '<div class="d-flex mt-2" style="gap:2px;font-size:0.6rem">';
        // Mostrar algunas fechas
        const step = Math.max(1, Math.floor(ventasData.length / 10));
        ventasData.forEach((v, i) => {
            if (i % step === 0 || i === ventasData.length - 1) {
                const fecha = new Date(v.fecha);
                html += `<div class="flex-fill text-muted" style="min-width:6px;transform:rotate(-45deg);transform-origin:top left">${fecha.getDate()}/${fecha.getMonth()+1}</div>`;
            } else {
                html += '<div class="flex-fill" style="min-width:6px"></div>';
            }
        });
        html += '</div>';
        ctx.innerHTML = html;
    },

    /**
     * Muestra formulario de crear/editar producto (modal)
     */
    async showProductForm(id = null) {
        let product = null;
        let title = 'Nuevo Producto';

        if (id) {
            title = 'Editar Producto';
            try {
                const resp = await fetch(`${App.apiBase}/catalogo/${id}`);
                const data = await resp.json();
                if (data.success) product = data.data;
            } catch (e) { /* fallback */ }
        }

        // Cargar categorías
        let catOptions = '';
        try {
            const resp = await fetch(`${App.apiBase}/catalogo/categorias`);
            const data = await resp.json();
            if (data.success) {
                catOptions = data.data.map(c => `
                    <option value="${c.id}" ${product && product.id_categoria == c.id ? 'selected' : ''}>
                        ${Catalogo.escapeHtml(c.nombre)}
                    </option>`).join('');
            }
        } catch (e) { }

        const modalHtml = `
        <div class="modal fade" id="productModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header modal-header-uct">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="product-form">
                            <input type="hidden" id="prod-id" value="${product ? product.id : ''}">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="prod-nombre" value="${product ? Catalogo.escapeHtml(product.nombre) : ''}" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Categoría *</label>
                                    <select class="form-select" id="prod-categoria" required>
                                        <option value="">Seleccionar...</option>
                                        ${catOptions}
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" id="prod-descripcion" rows="3">${product ? Catalogo.escapeHtml(product.descripcion || '') : ''}</textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Precio ($) *</label>
                                    <input type="number" class="form-control" id="prod-precio" value="${product ? Math.round(product.precio) : ''}" required min="0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Stock</label>
                                    <input type="number" class="form-control" id="prod-stock" value="${product ? product.stock : 0}" min="0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Stock Mínimo</label>
                                    <input type="number" class="form-control" id="prod-stock-min" value="${product ? (product.stock_minimo || 5) : 5}" min="0">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">URL Imagen</label>
                                <input type="url" class="form-control" id="prod-imagen" value="${product ? (product.imagen_url || '') : ''}">
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="prod-activo" ${product && product.activo == 1 ? 'checked' : ''} ${product ? '' : 'checked'}>
                                    <label class="form-check-label">Producto Activo</label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-accent" id="btn-save-product" data-admin-action="save-product">
                            ${product ? 'Guardar Cambios' : 'Crear Producto'}
                        </button>
                    </div>
                </div>
            </div>
        </div>`;

        // Remover modal anterior si existe
        const oldModal = document.getElementById('productModal');
        if (oldModal) oldModal.remove();

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('productModal'));
        modal.show();
    },

    /**
     * Guarda producto (crear/actualizar)
     */
    async saveProduct() {
        const id = document.getElementById('prod-id').value;
        const nombre = document.getElementById('prod-nombre').value.trim();
        const categoria = document.getElementById('prod-categoria').value;
        const descripcion = document.getElementById('prod-descripcion').value.trim();
        const precio = parseInt(document.getElementById('prod-precio').value);
        const stock = parseInt(document.getElementById('prod-stock').value) || 0;
        const stockMin = parseInt(document.getElementById('prod-stock-min').value) || 5;
        const imagen = document.getElementById('prod-imagen').value.trim();
        const activo = document.getElementById('prod-activo').checked ? 1 : 0;

        if (!nombre || !categoria || isNaN(precio)) {
            App.showToast('Completa los campos requeridos', 'error');
            return;
        }

        const body = { nombre, id_categoria: parseInt(categoria), descripcion, precio, stock, stock_minimo: stockMin, imagen_url: imagen, activo };
        const url = id ? `${App.apiBase}/admin/productos/${id}` : `${App.apiBase}/admin/productos`;
        const method = id ? 'PUT' : 'POST';

        try {
            const resp = await App.fetchAuth(url, { method, body: JSON.stringify(body) });
            const data = await resp.json();

            if (data.success) {
                App.showToast(id ? 'Producto actualizado' : 'Producto creado', 'success');
                bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
                this.loadProductos();
            } else {
                App.showToast(data.error?.message || 'Error', 'error');
            }
        } catch (e) {
            App.showToast('Error de conexión', 'error');
        }
    },

    /**
     * Elimina un producto
     */
    async deleteProduct(id) {
        if (!confirm('¿Eliminar este producto? Esta acción es reversible.')) return;

        try {
            const resp = await App.fetchAuth(`${App.apiBase}/admin/productos/${id}`, { method: 'DELETE' });
            const data = await resp.json();
            if (data.success) {
                App.showToast('Producto eliminado', 'success');
                this.loadProductos();
            }
        } catch (e) {
            App.showToast('Error al eliminar', 'error');
        }
    },

    /** Etiquetas legibles de estado */
    estadoLabel(s) {
        return ({ pendiente: 'Pendiente', pagado: 'Pagado', en_preparacion: 'En preparación', enviado: 'Enviado', entregado: 'Entregado', cancelado: 'Cancelado' })[s] || s;
    },

    /**
     * Select con solo las transiciones VÁLIDAS desde el estado actual
     * (mismo flujo que valida el backend). Estados finales: sin cambios.
     */
    estadoSelect(id, estadoActual) {
        const trans = {
            pendiente:      ['pagado', 'cancelado'],
            pagado:         ['en_preparacion', 'cancelado'],
            en_preparacion: ['enviado', 'cancelado'],
            enviado:        ['entregado'],
            entregado:      [],
            cancelado:      [],
        };
        const next = trans[estadoActual] || [];
        // Estado actual como opción seleccionada (deshabilitada): el select siempre
        // refleja dónde está el pedido, no queda "vacío" al volver a la sección.
        const actual = `<option value="" selected disabled>${this.estadoLabel(estadoActual)}</option>`;
        if (next.length === 0) {
            return `<select class="form-select form-select-sm admin-status-select" disabled>${actual}</select>`;
        }
        const opts = next.map(s => `<option value="${s}">→ ${this.estadoLabel(s)}</option>`).join('');
        return `<select class="form-select form-select-sm admin-status-select" data-admin-change="order-status" data-id="${id}">
            ${actual}
            ${opts}
        </select>`;
    },

    /**
     * Cambia estado de un pedido
     */
    async changeOrderStatus(orderId, newStatus) {
        if (!newStatus) return;

        if (!confirm(`¿Cambiar el pedido a "${this.estadoLabel(newStatus)}"?`)) {
            // Resetear select
            this.loadPedidos();
            return;
        }

        try {
            const resp = await App.fetchAuth(`${App.apiBase}/admin/pedidos/${orderId}/estado`, {
                method: 'PATCH',
                body: JSON.stringify({ estado: newStatus, comentario: 'Cambio manual por administrador' })
            });
            const data = await resp.json();

            if (data.success) {
                App.showToast('Estado actualizado', 'success');
                this.loadPedidos();
            } else {
                App.showToast(data.error?.message || 'Error al cambiar estado', 'error');
                this.loadPedidos();
            }
        } catch (e) {
            App.showToast('Error de conexión', 'error');
        }
    },

    /**
     * Activa/desactiva usuario
     */
    async toggleUser(userId, activo) {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/admin/usuarios/${userId}/estado`, {
                method: 'PATCH',
                body: JSON.stringify({ activo })
            });
            const data = await resp.json();
            if (data.success) {
                App.showToast('Estado de usuario actualizado', 'success');
                this.loadUsuarios();
            }
        } catch (e) {
            App.showToast('Error', 'error');
        }
    },

    /** Badge de stock con punto de color (coherente con los estados) */
    stockBadge(stock, min) {
        const color = stock <= 0 ? 'danger' : stock <= (min || 5) ? 'warning' : 'success';
        const label = stock <= 0 ? 'Agotado' : `${stock} u.`;
        return `<span class="pedido-badge ${color}"><span class="pedido-dot"></span>${label}</span>`;
    },

    /** Badge activo/inactivo con punto de color */
    activoBadge(activo, inactivoLabel = 'Inactivo') {
        return activo == 1
            ? '<span class="pedido-badge success"><span class="pedido-dot"></span>Activo</span>'
            : `<span class="pedido-badge secondary"><span class="pedido-dot"></span>${inactivoLabel}</span>`;
    },

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }
};
