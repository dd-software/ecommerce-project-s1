async function loadMyOrders() {
    try {
        const res = await api.request('/orders/me');
        const orders = res.data;
        const container = document.getElementById('orders-container');

        if (orders.length === 0) {
            container.innerHTML = `
                <div class="alert alert-info text-center py-5">
                    <i class="bi bi-bag-x fs-1 d-block mb-3"></i>
                    Aún no tienes compras en tu historial.
                    <br><a href="index.html" class="btn btn-primary mt-3">Ir al catálogo</a>
                </div>
            `;
            return;
        }

        const statusBadges = {
            'pendiente_pago': '<span class="badge text-bg-warning">Pendiente de Pago</span>',
            'pagado': '<span class="badge text-bg-success">Pagado / Aprobado</span>',
            'en_preparacion': '<span class="badge text-bg-info">En Preparación</span>',
            'enviado': '<span class="badge text-bg-primary">Enviado</span>',
            'entregado': '<span class="badge text-bg-dark">Entregado</span>'
        };

        let html = '<div class="accordion" id="ordersAccordion">';

        orders.forEach((order, index) => {
            const date = new Date(order.created_at).toLocaleDateString('es-ES', {
                year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
            });

            const isExpanded = index === 0 ? 'true' : 'false';
            const collapseClass = index === 0 ? 'show' : '';
            const btnClass = index === 0 ? '' : 'collapsed';

            let itemsHtml = order.items.map(item => `
                <div class="d-flex justify-content-between border-bottom py-2">
                    <div>
                        <span class="fw-semibold">${item.product_name}</span>
                        <small class="text-muted ms-2">(SKU: ${item.sku})</small>
                    </div>
                    <div>
                        ${item.quantity} x $${item.unit_price} = <strong>$${(item.quantity * item.unit_price).toFixed(2)}</strong>
                    </div>
                </div>
            `).join('');

            html += `
                <div class="accordion-item mb-3 border rounded shadow-sm">
                    <h2 class="accordion-header">
                        <button class="accordion-button ${btnClass}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${order.id}" aria-expanded="${isExpanded}">
                            <div class="d-flex w-100 justify-content-between align-items-center me-3">
                                <div>
                                    <strong>Orden #${order.id}</strong>
                                    <small class="text-muted d-block mt-1">${date}</small>
                                </div>
                                <div class="text-end">
                                    <div class="fs-5 fw-bold text-primary mb-1">$${parseFloat(order.total_amount).toFixed(2)}</div>
                                    ${statusBadges[order.status] || `<span class="badge bg-secondary">${order.status}</span>`}
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse${order.id}" class="accordion-collapse collapse ${collapseClass}" data-bs-parent="#ordersAccordion">
                        <div class="accordion-body bg-light">
                            <h6 class="fw-bold mb-3">Detalle de Productos:</h6>
                            ${itemsHtml}
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;

    } catch (e) {
        if (e.message.toLowerCase().includes('token') || e.message.toLowerCase().includes('sesión')) {
            window.location.href = 'login.html';
        } else {
            document.getElementById('orders-container').innerHTML = `
                <div class="alert alert-danger">Error cargando órdenes: ${e.message}</div>
            `;
        }
    }
}

document.addEventListener('DOMContentLoaded', loadMyOrders);
