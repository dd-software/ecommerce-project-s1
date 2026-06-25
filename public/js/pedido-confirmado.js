/**
 * pedido-confirmado.js - Lógica para mostrar la confirmación y detalles del pedido
 */

document.addEventListener('DOMContentLoaded', async () => {
    // Verificar autenticación
    if (!App.user) {
        window.location.href = App.getBasePath() + '/index.html';
        return;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const pedidoId = urlParams.get('pedido_id');
    const pagoExitoso = urlParams.get('pago') === 'exitoso';

    if (!pedidoId) {
        mostrarError('ID de pedido no especificado.');
        return;
    }

    // Mostrar banner de éxito si el pago fue aprobado recientemente
    if (pagoExitoso) {
        const successBanner = document.getElementById('success-banner');
        if (successBanner) {
            successBanner.classList.remove('d-none');
        }
    }

    await cargarDetallePedido(pedidoId);
});

/**
 * Obtiene los detalles del pedido de la API y los renderiza en la UI
 */
async function cargarDetallePedido(pedidoId) {
    const loadingContainer = document.getElementById('loading-container');
    const orderContainer = document.getElementById('order-details-container');
    const errorContainer = document.getElementById('error-container');

    try {
        const resp = await App.fetchAuth(`${App.apiBase}/pedidos/${pedidoId}`);
        const data = await resp.json();

        if (loadingContainer) {
            loadingContainer.classList.add('d-none');
        }

        if (data.success && data.data) {
            const pedido = data.data;
            renderPedido(pedido);
            if (orderContainer) {
                orderContainer.classList.remove('d-none');
            }
        } else {
            const errorMsg = data.error?.message || 'No se pudo encontrar el pedido solicitado.';
            mostrarError(errorMsg);
        }
    } catch (e) {
        console.error(e);
        if (loadingContainer) {
            loadingContainer.classList.add('d-none');
        }
        mostrarError('Error de conexión al cargar los detalles del pedido.');
    }
}

/**
 * Renderiza los campos del pedido en el DOM
 */
function renderPedido(pedido) {
    // Resumen
    document.getElementById('order-id').textContent = `#${pedido.id}`;
    document.getElementById('order-date').textContent = new Date(pedido.created_at).toLocaleDateString();
    
    // Estado Badge
    const statusEl = document.getElementById('order-status');
    statusEl.textContent = pedido.estado.toUpperCase();
    statusEl.className = 'badge ' + getBadgeClass(pedido.estado);

    // Cliente
    document.getElementById('order-customer').textContent = `${pedido.cliente_nombre} ${pedido.cliente_apellido}`;
    document.getElementById('order-email').textContent = pedido.cliente_email;

    // Despacho
    document.getElementById('order-address').textContent = pedido.direccion_envio || 'No especificada';
    document.getElementById('order-phone').textContent = pedido.telefono_contacto || 'No especificado';
    document.getElementById('order-notes').textContent = pedido.notas || 'Ninguna';

    // Productos
    const tbody = document.getElementById('order-items');
    if (tbody && pedido.detalle && pedido.detalle.length > 0) {
        tbody.innerHTML = pedido.detalle.map(item => {
            const precio = parseInt(item.precio_unitario);
            const cantidad = parseInt(item.cantidad);
            const subtotal = precio * cantidad;
            
            return `
                <tr>
                    <td class="ps-4 fw-medium text-dark">${escapeHtml(item.nombre_producto)}</td>
                    <td class="text-center">${cantidad}</td>
                    <td class="text-end">${App.formatPrice(precio)}</td>
                    <td class="text-end pe-4 fw-bold text-dark">${App.formatPrice(subtotal)}</td>
                </tr>
            `;
        }).join('');
    } else if (tbody) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted">No hay productos en este pedido.</td></tr>';
    }

    // Totales
    // Los montos del pedido vienen en centavos como enteros.
    // Usamos los campos formateados proporcionados por el backend.
    document.getElementById('summary-subtotal').textContent = pedido.subtotal_formateado;
    document.getElementById('summary-iva').textContent = pedido.iva_formateado;
    document.getElementById('summary-total').textContent = pedido.total_formateado;

    // Calcular descuento en el frontend (subtotal + iva - total)
    const subtotal = parseInt(pedido.subtotal);
    const iva = parseInt(pedido.iva);
    const total = parseInt(pedido.total);
    const descuento = (subtotal + iva) - total;

    const discountRow = document.getElementById('summary-discount-row');
    if (descuento > 0) {
        document.getElementById('summary-discount').textContent = `-${App.formatPrice(descuento)}`;
        if (discountRow) {
            discountRow.classList.remove('d-none');
        }
    } else {
        if (discountRow) {
            discountRow.classList.add('d-none');
        }
    }
}

/**
 * Retorna la clase CSS del badge de Bootstrap para cada estado
 */
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

/**
 * Muestra un mensaje de error en la interfaz
 */
function mostrarError(mensaje) {
    const errorContainer = document.getElementById('error-container');
    if (errorContainer) {
        errorContainer.textContent = mensaje;
        errorContainer.classList.remove('d-none');
    }
}

/**
 * Escapa HTML para prevenir ataques XSS
 */
function escapeHtml(string) {
    const div = document.createElement('div');
    div.innerText = string;
    return div.innerHTML;
}
