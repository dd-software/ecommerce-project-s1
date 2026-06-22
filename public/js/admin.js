/**
 * admin.js - Lógica del Panel de Administración
 */

const Admin = {
    isDashboardActive: false,

    init() {
        if (!App.user || App.user.rol !== 'admin') {
            return;
        }
        // Listener is setup in HTML (toggleDashboard)
    },

    toggleDashboard(e) {
        if (e) e.preventDefault();
        
        const mainContent = document.getElementById('main-content');
        const heroSection = document.getElementById('hero-section');
        const productsSection = document.getElementById('products-section');
        const dashboardContainer = document.getElementById('admin-dashboard-container');
        
        this.isDashboardActive = !this.isDashboardActive;
        
        if (this.isDashboardActive) {
            if (heroSection) heroSection.closest('main').classList.add('d-none');
            if (productsSection) productsSection.classList.add('d-none');
            dashboardContainer.classList.remove('d-none');
            
            this.loadDashboard();
        } else {
            if (heroSection) heroSection.closest('main').classList.remove('d-none');
            if (productsSection) productsSection.classList.remove('d-none');
            dashboardContainer.classList.add('d-none');
        }
    },

    async loadDashboard() {
        App.showLoading();
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/admin/dashboard`);
            const data = await resp.json();
            
            if (data.success && data.data) {
                this.renderStats(data.data.estadisticas);
                this.loadProducts();
                this.loadOrders();
                this.loadUsers();
            } else {
                App.showToast('Error al cargar dashboard', 'error');
            }
        } catch (e) {
            console.error(e);
            App.showToast('Error de conexión', 'error');
        } finally {
            App.hideLoading();
        }
    },

    renderStats(stats) {
        const container = document.getElementById('admin-stats');
        container.innerHTML = `
            <div class="col-md-3">
                <div class="admin-card success">
                    <h6 class="text-muted text-uppercase fw-bold mb-2">Ventas Totales</h6>
                    <h3 class="mb-0 fw-bold">${App.formatPrice(stats.total_ventas || 0)}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="admin-card">
                    <h6 class="text-muted text-uppercase fw-bold mb-2">Productos</h6>
                    <h3 class="mb-0 fw-bold">${stats.total_productos || 0}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="admin-card warning">
                    <h6 class="text-muted text-uppercase fw-bold mb-2">Pedidos</h6>
                    <h3 class="mb-0 fw-bold">${stats.total_pedidos || 0}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="admin-card danger">
                    <h6 class="text-muted text-uppercase fw-bold mb-2">Clientes</h6>
                    <h3 class="mb-0 fw-bold">${stats.total_clientes || 0}</h3>
                </div>
            </div>
        `;
    },

    async loadProducts() {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/admin/productos`);
            const data = await resp.json();
            if (data.success) {
                const tbody = document.getElementById('admin-products-tbody');
                tbody.innerHTML = data.data.map(p => `
                    <tr>
                        <td>#${p.id}</td>
                        <td><img src="${p.imagen_url || 'https://via.placeholder.com/40'}" alt="${p.nombre}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;"></td>
                        <td class="fw-medium">${p.nombre}</td>
                        <td>${App.formatPrice(p.precio)}</td>
                        <td><span class="badge bg-${p.stock < 10 ? 'danger' : 'success'} rounded-pill">${p.stock}</span></td>
                        <td><span class="badge bg-${p.activo ? 'success' : 'secondary'}">${p.activo ? 'Activo' : 'Inactivo'}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="Admin.editProduct(${p.id})"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="Admin.deleteProduct(${p.id})"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            console.error('Error loadProducts', e);
        }
    },

    async loadOrders() {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/admin/pedidos`);
            const data = await resp.json();
            if (data.success) {
                const tbody = document.getElementById('admin-orders-tbody');
                tbody.innerHTML = data.data.map(o => `
                    <tr>
                        <td>#${o.id}</td>
                        <td>${o.usuario_nombre} ${o.usuario_apellido}</td>
                        <td class="fw-bold">${App.formatPrice(o.total)}</td>
                        <td><span class="badge bg-primary rounded-pill">${o.estado}</span></td>
                        <td>${new Date(o.creado_en).toLocaleDateString()}</td>
                        <td>
                            <select class="form-select form-select-sm d-inline-block w-auto" onchange="Admin.changeOrderStatus(${o.id}, this.value)">
                                <option value="pendiente" ${o.estado === 'pendiente' ? 'selected' : ''}>Pendiente</option>
                                <option value="pagado" ${o.estado === 'pagado' ? 'selected' : ''}>Pagado</option>
                                <option value="enviado" ${o.estado === 'enviado' ? 'selected' : ''}>Enviado</option>
                                <option value="entregado" ${o.estado === 'entregado' ? 'selected' : ''}>Entregado</option>
                                <option value="cancelado" ${o.estado === 'cancelado' ? 'selected' : ''}>Cancelado</option>
                            </select>
                        </td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            console.error('Error loadOrders', e);
        }
    },

    async loadUsers() {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/admin/usuarios`);
            const data = await resp.json();
            if (data.success) {
                const tbody = document.getElementById('admin-users-tbody');
                tbody.innerHTML = data.data.map(u => `
                    <tr>
                        <td>#${u.id}</td>
                        <td class="fw-medium">${u.nombre} ${u.apellido}</td>
                        <td>${u.email}</td>
                        <td><span class="badge bg-${u.rol === 'admin' ? 'danger' : 'info'}">${u.rol}</span></td>
                        <td><span class="badge bg-${u.estado === 'activo' ? 'success' : 'secondary'}">${u.estado}</span></td>
                        <td>
                            <button class="btn btn-sm btn-${u.estado === 'activo' ? 'warning' : 'success'}" onclick="Admin.toggleUserStatus(${u.id})">
                                ${u.estado === 'activo' ? '<i class="bi bi-slash-circle"></i> Bloquear' : '<i class="bi bi-check-circle"></i> Activar'}
                            </button>
                        </td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            console.error('Error loadUsers', e);
        }
    },

    async changeOrderStatus(id, status) {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/admin/pedidos/${id}/estado`, {
                method: 'PATCH',
                body: JSON.stringify({ estado: status })
            });
            const data = await resp.json();
            if (data.success) {
                App.showToast('Estado actualizado correctamente');
                this.loadOrders();
            } else {
                App.showToast(data.message || 'Error al actualizar', 'error');
            }
        } catch (e) {
            App.showToast('Error de conexión', 'error');
        }
    },

    async toggleUserStatus(id) {
        try {
            const resp = await App.fetchAuth(`${App.apiBase}/admin/usuarios/${id}/estado`, {
                method: 'PATCH'
            });
            const data = await resp.json();
            if (data.success) {
                App.showToast('Estado de usuario actualizado');
                this.loadUsers();
            } else {
                App.showToast(data.message || 'Error al actualizar', 'error');
            }
        } catch (e) {
            App.showToast('Error de conexión', 'error');
        }
    },
    
    showCreateProductModal() {
        App.showToast('Funcionalidad de crear producto en desarrollo', 'warning');
    },
    
    editProduct(id) {
        App.showToast('Funcionalidad de editar producto en desarrollo', 'warning');
    },
    
    deleteProduct(id) {
        if(confirm('¿Estás seguro de eliminar este producto?')) {
            App.fetchAuth(`${App.apiBase}/admin/productos/${id}`, { method: 'DELETE' })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        App.showToast('Producto eliminado');
                        this.loadProducts();
                    } else {
                        App.showToast(data.message || 'Error', 'error');
                    }
                });
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    Admin.init();
});
