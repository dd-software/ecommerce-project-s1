async function loadAdminData() {
    try {
        // Carga secuencial para evitar race conditions con putenv() en Apache/XAMPP
        const ordersRes = await api.request('/admin/orders');
        renderOrders(ordersRes.data);
        
        const alertsRes = await api.request('/inventory/alerts');
        renderAlerts(alertsRes.data);
    } catch(e) {
        if(e.message.toLowerCase().includes('denegado') || e.message.toLowerCase().includes('token')) {
            ui.showToast('Acceso Denegado. Se requiere una cuenta de Administrador (ej. admin@uct.cl).', 'danger');
            setTimeout(() => window.location.href = 'index.html', 3000);
        }
    }
}

function renderOrders(orders) {
    const tbody = document.getElementById('orders-table');
    if(orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No hay órdenes registradas.</td></tr>';
        return;
    }

    tbody.innerHTML = orders.map(o => `
        <tr>
            <td class="fw-bold">#${o.id}</td>
            <td>${o.customer_email}</td>
            <td class="text-success fw-bold">$${o.total_amount}</td>
            <td>
                <select class="form-select form-select-sm" onchange="updateStatus(${o.id}, this.value)">
                    <option value="pendiente_pago" ${o.status === 'pendiente_pago'?'selected':''}>Pendiente</option>
                    <option value="pagado" ${o.status === 'pagado'?'selected':''}>Pagado</option>
                    <option value="en_preparacion" ${o.status === 'en_preparacion'?'selected':''}>En Preparación</option>
                    <option value="enviado" ${o.status === 'enviado'?'selected':''}>Enviado</option>
                    <option value="entregado" ${o.status === 'entregado'?'selected':''}>Entregado</option>
                </select>
            </td>
            <td class="text-muted small">${new Date(o.created_at).toLocaleString()}</td>
        </tr>
    `).join('');
}

async function updateStatus(orderId, status) {
    try {
        await api.request(`/admin/orders/${orderId}/status`, 'PUT', { status });
        ui.showToast('Estado logístico de la orden actualizado', 'success');
    } catch(e) {
        loadAdminData(); // Revierte el select en caso de error
    }
}

function renderAlerts(alerts) {
    const tbody = document.getElementById('alerts-table');
    if(alerts.length === 0) {
         tbody.innerHTML = '<tr><td colspan="4" class="text-center text-success fw-bold py-4">No hay alertas de stock. Inventario saludable.</td></tr>';
         return;
    }

    tbody.innerHTML = alerts.map(a => `
        <tr class="table-danger">
            <td><span class="badge text-bg-dark">${a.sku}</span></td>
            <td class="fw-semibold">${a.product_name}</td>
            <td class="text-danger fw-bold fs-5">${a.stock}</td>
            <td class="text-muted">${a.min_stock_alert}</td>
        </tr>
    `).join('');
}

document.addEventListener('DOMContentLoaded', loadAdminData);
