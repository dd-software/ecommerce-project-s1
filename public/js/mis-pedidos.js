/**
 * mis-pedidos.js - Historial de pedidos del usuario
 */

document.addEventListener('DOMContentLoaded', async () => {
    if (!App.user) {
        window.location.href = App.getBasePath() + '/index.html';
        return;
    }

    await cargarMisPedidos();
});

async function cargarMisPedidos() {
    const tbody = document.getElementById('pedidos-lista');
    if (!tbody) return;

    try {
        const resp = await App.fetchAuth(`${App.apiBase}/pedidos`);
        const data = await resp.json();

        if (data.success && data.data && data.data.length > 0) {
            tbody.innerHTML = data.data.map(pedido => {
                const badgeClass = getBadgeClass(pedido.estado);
                
                return `
                    <tr>
                        <td class="fw-bold">#${pedido.id}</td>
                        <td>${new Date(pedido.created_at).toLocaleDateString()}</td>
                        <td><span class="badge ${badgeClass}">${pedido.estado.toUpperCase()}</span></td>
                        <td class="fw-bold text-primary">${pedido.total_formateado || App.formatPrice(pedido.total)}</td>
                        <td>${(pedido.metodo_pago || 'N/A').toUpperCase()}</td>
                        <td>
                            <a href="pedido-confirmado.html?pedido_id=${pedido.id}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Ver detalle
                            </a>
                        </td>
                    </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No tienes pedidos anteriores.</td></tr>';
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Error al cargar el historial de pedidos.</td></tr>';
        console.error(e);
    }
}

function getBadgeClass(estado) {
    switch (estado.toLowerCase()) {
        case 'pagado': return 'bg-success';
        case 'pendiente': return 'bg-warning text-dark';
        case 'cancelado': case 'rechazado': return 'bg-danger';
        case 'en_preparacion': case 'enviado': return 'bg-info text-dark';
        case 'entregado': return 'bg-success';
        default: return 'bg-secondary';
    }
}
