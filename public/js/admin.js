/**
 * admin.js - Panel de administración
 * Dashboard, CRUD productos, gestión de pedidos y reportes
 */

const Admin = {
    currentSection: 'dashboard',

    /**
     * Inicializa el panel admin
     */
    init() {
        if (!App.user || App.user.rol !== 'admin') {
            window.location.href = '/login.html?redirect=admin.html';
            return;
        }

        this.initNavigation();
        this.loadDashboard();
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

                // Actualizar breadcrumb
                const bc = document.getElementById('breadcrumb-current');
                if (bc) bc.textContent = section.charAt(0).toUpperCase() + section.slice(1);

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

            container.innerHTML = `
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card h-100 p-4">
                            <div class="card-icon bg-glass-primary"><i class="bi bi-box-seam"></i></div>
                            <h3 class="fw-bold mb-1">${d.total_productos}</h3>
                            <p class="text-muted small mb-0">Productos en Catálogo</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card h-100 p-4">
                            <div class="card-icon bg-glass-accent"><i class="bi bi-cart-check"></i></div>
                            <h3 class="fw-bold mb-1">${d.total_pedidos}</h3>
                            <p class="text-muted small mb-0">Pedidos Realizados</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card h-100 p-4">
                            <div class="card-icon bg-glass-success"><i class="bi bi-currency-dollar"></i></div>
                            <h3 class="fw-bold mb-1">${d.ventas_hoy?.total_ventas_formateado || '$0'}</h3>
                            <p class="text-muted small mb-0">Ventas de Hoy</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card h-100 p-4">
                            <div class="card-icon bg-glass-danger"><i class="bi bi-clock-history"></i></div>
                            <h3 class="fw-bold mb-1">${d.pedidos_pendientes}</h3>
                            <p class="text-muted small mb-0">Pedidos Pendientes</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-7 mb-4">
                        <div class="table-container h-100">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-bold mb-0">Últimos Pedidos</h5>
                                <button class="btn btn-sm btn-link text-decoration-none" onclick="Admin.loadSection('pedidos')">Ver todos</button>
                            </div>
                            ${d.ultimos_pedidos && d.ultimos_pedidos.length > 0
                                ? `<div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Cliente</th>
                                                <th>Estado</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${d.ultimos_pedidos.slice(0, 5).map(p => `
                                                <tr>
                                                    <td><strong>#${p.id}</strong></td>
                                                    <td>${this.escapeHtml(p.cliente_nombre)}</td>
                                                    <td><span class="badge-estado ${p.estado}">${p.estado}</span></td>
                                                    <td>${App.formatPrice(p.monto_total || 0)}</td>
                                                </tr>`).join('')}
                                        </tbody>
                                    </table>
                                </div>`
                                : '<p class="text-muted">No hay pedidos recientes.</p>'}
                        </div>
                    </div>
                    <div class="col-lg-5 mb-4">
                        <div class="table-container h-100">
                            <h5 class="fw-bold mb-4">Alertas de Stock</h5>
                            ${d.alertas_stock && d.alertas_stock.length > 0
                                ? `<div class="list-group list-group-flush">${d.alertas_stock.map(a => `
                                    <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center border-0 border-bottom">
                                        <div>
                                            <h6 class="mb-0 fw-bold">${this.escapeHtml(a.nombre)}</h6>
                                            <small class="text-muted">Mínimo: ${a.stock_minimo}</small>
                                        </div>
                                        <span class="badge rounded-pill bg-danger">Quedan ${a.stock}</span>
                                    </div>`).join('')}</div>`
                                : '<div class="text-center py-4"><i class="bi bi-check2-circle text-success display-4"></i><p class="text-success mt-2">Todo el stock está correcto.</p></div>'}
                        </div>
                    </div>
                </div>`;
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Gestión de Productos</h4>
                    <button class="btn btn-accent" id="btn-new-product" onclick="Admin.showProductForm()">
                        <i class="bi bi-plus-lg"></i> Nuevo Producto
                    </button>
                </div>`;

            if (productos && productos.length > 0) {
                html += `<div class="table-responsive">
                    <table class="table table-admin table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${productos.map(p => `
                                <tr>
                                    <td>${p.id}</td>
                                    <td><img src="${p.imagen_url || 'https://via.placeholder.com/40'}" width="40" height="40" style="object-fit:cover;border-radius:4px" onerror="this.src='https://via.placeholder.com/40'"></td>
                                    <td>${this.escapeHtml(p.nombre)}</td>
                                    <td>${this.escapeHtml(p.categoria_nombre || '-')}</td>
                                    <td>${p.precio_formateado || App.formatPrice(p.precio)}</td>
                                    <td>
                                        <span class="badge ${p.stock <= 0 ? 'bg-danger' : p.stock <= (p.stock_minimo || 5) ? 'bg-warning text-dark' : 'bg-success'}">
                                            ${p.stock}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge ${p.activo == 1 ? 'bg-success' : 'bg-secondary'}">
                                            ${p.activo == 1 ? 'Activo' : 'Inactivo'}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary btn-action me-1" onclick="Admin.showProductForm(${p.id})" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-action" onclick="Admin.deleteProduct(${p.id})" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>`;

                // Paginación
                if (pag && pag.total_pages > 1) {
                    html += `<nav><ul class="pagination justify-content-center">`;
                    for (let i = 1; i <= pag.total_pages; i++) {
                        html += `<li class="page-item ${i === page ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="Admin.loadProductos(${i});return false">${i}</a></li>`;
                    }
                    html += `</ul></nav>`;
                }
            } else {
                html += '<p class="text-muted">No hay productos registrados.</p>';
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

            let html = `<h4 class="mb-4">Gestión de Pedidos</h4>`;

            if (pedidos && pedidos.length > 0) {
                html += `<div class="table-responsive">
                    <table class="table table-admin table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${pedidos.map(p => `
                                <tr>
                                    <td>#${p.id}</td>
                                    <td>${this.escapeHtml(p.cliente_nombre)} ${this.escapeHtml(p.apellido || '')}</td>
                                    <td>${p.total_items}</td>
                                    <td>${p.total_formateado}</td>
                                    <td><span class="badge-estado ${p.estado}">${p.estado}</span></td>
                                    <td>${new Date(p.created_at).toLocaleDateString('es-CL')}</td>
                                    <td>
                                        <select class="form-select form-select-sm" onchange="Admin.changeOrderStatus(${p.id}, this.value)" style="width:150px">
                                            <option value="">Cambiar estado</option>
                                            <option value="pagado">Pagado</option>
                                            <option value="en_preparacion">En Preparación</option>
                                            <option value="enviado">Enviado</option>
                                            <option value="entregado">Entregado</option>
                                            <option value="cancelado">Cancelado</option>
                                        </select>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>`;
            } else {
                html += '<p class="text-muted">No hay pedidos registrados.</p>';
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
            let html = `<h4 class="mb-4">Gestión de Usuarios</h4>`;

            if (usuarios && usuarios.length > 0) {
                html += `<div class="table-responsive">
                    <table class="table table-admin table-hover">
                        <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Último Login</th><th>Acciones</th></tr></thead>
                        <tbody>
                            ${usuarios.map(u => `
                                <tr>
                                    <td>${u.id}</td>
                                    <td>${this.escapeHtml(u.nombre)} ${this.escapeHtml(u.apellido)}</td>
                                    <td>${this.escapeHtml(u.email)}</td>
                                    <td><span class="badge bg-info">${u.rol}</span></td>
                                    <td><span class="badge ${u.activo == 1 ? 'bg-success' : 'bg-danger'}">${u.activo == 1 ? 'Activo' : 'Deshabilitado'}</span></td>
                                    <td>${u.ultimo_login ? new Date(u.ultimo_login).toLocaleString('es-CL') : 'Nunca'}</td>
                                    <td>
                                        <button class="btn btn-sm ${u.activo == 1 ? 'btn-outline-danger' : 'btn-outline-success'} btn-action"
                                                onclick="Admin.toggleUser(${u.id}, ${u.activo == 1 ? 0 : 1})">
                                            ${u.activo == 1 ? 'Deshabilitar' : 'Activar'}
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>`;
            } else {
                html += '<p class="text-muted">No hay usuarios registrados.</p>';
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
            const [ventasResp, topResp] = await Promise.all([
                App.fetchAuth(`${App.apiBase}/admin/reportes/ventas?periodo=mes`),
                App.fetchAuth(`${App.apiBase}/admin/reportes/productos-mas-vendidos`)
            ]);

            const ventas = await ventasResp.json();
            const top = await topResp.json();

            container.innerHTML = `
                <div class="row g-4">
                    <div class="col-12 col-xl-8">
                        <div class="table-container mb-4">
                            <h5 class="fw-bold mb-4">Ventas Últimos 30 Días</h5>
                            <div style="height: 350px;">
                                <canvas id="sales-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="table-container">
                            <h5 class="fw-bold mb-4">Top Productos</h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle small mb-0">
                                    <thead><tr><th>Producto</th><th>Ventas</th></tr></thead>
                                    <tbody>${top.data.map((p, i) => `
                                        <tr>
                                            <td class="fw-bold">${this.escapeHtml(p.nombre_producto)}</td>
                                            <td><span class="badge bg-glass-success text-success">${p.total_vendido}</span></td>
                                        </tr>`).join('')}</tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>`;

            if (ventas.success && ventas.data && ventas.data.length > 0) {
                setTimeout(() => this.renderSalesChart(ventas.data), 100);
            }
        } catch (e) {
            container.innerHTML = '<div class="alert alert-danger">Error al cargar reportes.</div>';
        }
    },

    /**
     * Renderiza gráfico de ventas simple
     */
    renderSalesChart(ventasData) {
        const ctx = document.getElementById('sales-chart');
        if (!ctx) return;

        // Limpiar canvas anterior si existe
        if (this.myChart) this.myChart.destroy();

        const labels = ventasData.map(v => {
            const d = new Date(v.fecha);
            return `${d.getDate()}/${d.getMonth()+1}`;
        });
        const values = ventasData.map(v => Math.round(v.total_ventas / 100));

        this.myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ventas Diarias ($)',
                    data: values,
                    borderColor: '#003366',
                    backgroundColor: 'rgba(0, 51, 102, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#F2A900'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => '$' + new Intl.NumberFormat('es-CL').format(value)
                        }
                    }
                }
            }
        });
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
                const resp = await App.fetchAuth(`${App.apiBase}/catalogo/${id}`);
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
                catOptions = data.data.map(c =>
                    `<option value="${c.id}" ${product && product.id_categoria == c.id ? 'selected' : ''}>${Admin.escapeHtml(c.nombre)}</option>`
                ).join('');
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
                                    <input type="text" class="form-control" id="prod-nombre" value="${product ? Admin.escapeHtml(product.nombre) : ''}" required>
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
                                <textarea class="form-control" id="prod-descripcion" rows="3">${product ? Admin.escapeHtml(product.descripcion || '') : ''}</textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Precio ($) *</label>
                                    <input type="number" class="form-control" id="prod-precio" value="${product ? Math.round(product.precio / 100) : ''}" required min="0">
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
                        <button type="button" class="btn btn-accent" id="btn-save-product" onclick="Admin.saveProduct()">
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
        const precio = parseInt(document.getElementById('prod-precio').value) * 100;
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
            
            if (!resp.ok) {
                const errorData = await resp.json();
                throw new Error(errorData.error?.message || 'Error al guardar producto');
            }

            const data = await resp.json();
            if (data.success) {
                App.showToast(id ? 'Producto actualizado' : 'Producto creado', 'success');
                bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
                this.loadProductos();
            }
        } catch (e) {
            console.error('Admin Error:', e);
            App.showToast(e.message || 'Error de conexión', 'error');
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

    /**
     * Cambia estado de un pedido
     */
    async changeOrderStatus(orderId, newStatus) {
        if (!newStatus) return;

        if (!confirm(`¿Cambiar pedido #${orderId} a estado "${newStatus}"?`)) {
            // Resetear select
            this.loadPedidos();
            return;
        }

        try {
            const resp = await App.fetchAuth(`${App.apiBase}/admin/pedidos/${orderId}/estado`, {
                method: 'PATCH',
                body: JSON.stringify({ 
                    estado: newStatus, 
                    comentario: 'Cambio manual por administrador' 
                })
            });

            if (!resp.ok) {
                const errorData = await resp.json();
                throw new Error(errorData.error?.message || 'Error al cambiar estado');
            }

            const data = await resp.json();
            if (data.success) {
                App.showToast('Estado actualizado', 'success');
                this.loadPedidos();
            }
        } catch (e) {
            console.error('Admin Error:', e);
            App.showToast(e.message || 'Error de conexión', 'error');
            this.loadPedidos();
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

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }
};
