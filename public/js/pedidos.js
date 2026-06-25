/**
 * pedidos.js - Lógica de historial y detalle de pedidos para clientes
 */

const Pedidos = {
    orders: [],

    init() {
        const modalEl = document.getElementById('ordersModal');
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', () => {
                this.showOrdersList();
                this.loadOrders();
            });
        }

        const backBtn = document.getElementById('btn-back-to-orders');
        if (backBtn) {
            backBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.showOrdersList();
            });
        }
    },

    /**
     * Alterna la visualización para mostrar el listado principal
     */
    showOrdersList() {
        document.getElementById('orders-list-view')?.classList.remove('d-none');
        document.getElementById('order-detail-view')?.classList.add('d-none');
    },

    /**
     * Alterna la visualización para mostrar el detalle de una orden
     */
    showOrderDetailView() {
        document.getElementById('orders-list-view')?.classList.add('d-none');
        document.getElementById('order-detail-view')?.classList.remove('d-none');
    },

    /**
     * Carga las órdenes del usuario desde el servidor
     */
    async loadOrders() {
        const container = document.getElementById('orders-list-container');
        if (!container) return;

        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="text-muted mt-2">Cargando tus pedidos...</p>
            </div>
        `;

        try {
            const resp = await App.fetchAuth(`${App.apiBase}/pedidos`);
            const data = await resp.json();

            if (data.success && data.data && data.data.length > 0) {
                this.orders = data.data;
                this.renderOrders(data.data);
            } else {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="bi bi-bag-x display-1 text-muted mb-3 d-block"></i>
                        <h5>Aún no tienes pedidos registrados</h5>
                        <p class="text-muted small mb-4">Visita nuestro catálogo y encuentra los mejores gadgets.</p>
                        <button class="btn btn-primary btn-sm rounded-pill px-4" data-bs-dismiss="modal">
                            Ir a la Tienda
                        </button>
                    </div>
                `;
            }
        } catch (e) {
            console.error('Error al cargar pedidos:', e);
            container.innerHTML = `
                <div class="alert alert-danger text-center">
                    <i class="bi bi-exclamation-triangle me-2"></i> Error al cargar tus pedidos. Por favor, intenta de nuevo.
                </div>
            `;
        }
    },

    /**
     * Renderiza las tarjetas de pedidos del cliente
     */
    renderOrders(pedidos) {
        const container = document.getElementById('orders-list-container');
        if (!container) return;

        container.innerHTML = pedidos.map(p => {
            const date = new Date(p.created_at).toLocaleDateString('es-CL', {
                year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
            });

            return `
                <div class="card mb-3 border shadow-sm rounded-4 overflow-hidden" style="transition: transform 0.2s;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                            <div>
                                <span class="text-muted small fw-semibold">Pedido #${p.id}</span>
                                <h6 class="text-dark mb-0 mt-1"><i class="bi bi-calendar-event me-2 text-muted"></i>${date}</h6>
                            </div>
                            <div>
                                ${this.getStatusBadge(p.estado)}
                            </div>
                        </div>
                        <hr class="text-muted opacity-25My">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <span class="text-muted small d-block">Productos comprados</span>
                                <span class="fw-semibold text-dark"><i class="bi bi-box-seam me-1 text-muted"></i>${p.total_items} ${p.total_items === 1 ? 'item' : 'items'}</span>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small d-block">Monto Total</span>
                                <span class="text-primary fw-bold fs-5">${p.total_formateado}</span>
                            </div>
                            <div>
                                <button class="btn btn-outline-primary btn-sm rounded-pill px-4" onclick="Pedidos.showDetail(${p.id})">
                                    Ver Detalle <i class="bi bi-chevron-right ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    },

    /**
     * Muestra el detalle completo de un pedido específico
     */
    async showDetail(orderId) {
        this.showOrderDetailView();
        const content = document.getElementById('order-detail-content');
        if (!content) return;

        content.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="text-muted mt-2">Cargando detalle del pedido...</p>
            </div>
        `;

        try {
            const resp = await App.fetchAuth(`${App.apiBase}/pedidos/${orderId}`);
            const data = await resp.json();

            if (data.success && data.data) {
                this.renderOrderDetail(data.data);
            } else {
                throw new Error('No se pudo cargar la orden');
            }
        } catch (e) {
            console.error('Error al cargar detalle del pedido:', e);
            content.innerHTML = `
                <div class="alert alert-danger text-center">
                    <i class="bi bi-exclamation-triangle me-2"></i> Error al cargar el detalle del pedido.
                </div>
            `;
        }
    },

    /**
     * Renderiza el contenido del detalle de la orden en HTML
     */
    renderOrderDetail(order) {
        const content = document.getElementById('order-detail-content');
        if (!content) return;

        const date = new Date(order.created_at).toLocaleDateString('es-CL', {
            year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
        });

        // 1. Renderizar Barra de Progreso del Estado (si no es cancelado)
        let trackingProgressHTML = '';
        if (order.estado === 'cancelado') {
            trackingProgressHTML = `
                <div class="alert alert-danger d-flex align-items-center rounded-4 mb-4 shadow-sm" role="alert">
                    <i class="bi bi-x-circle-fill fs-3 me-3"></i>
                    <div>
                        <strong class="d-block fs-6">Este pedido fue cancelado</strong>
                        <span style="font-size: 0.85rem;">Si realizaste un pago, el reembolso será procesado según tu medio de pago.</span>
                    </div>
                </div>
            `;
        } else {
            trackingProgressHTML = this.renderTrackingProgress(order.estado);
        }

        // 2. Renderizar Detalle de Productos
        const itemsHTML = order.detalle.map(item => {
            const img = item.imagen_url || 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=100';
            const priceFormated = App.formatPrice(item.precio_unitario);
            const subtotalFormated = App.formatPrice(item.precio_unitario * item.cantidad);

            return `
                <div class="d-flex align-items-center mb-3 border-bottom pb-3">
                    <img src="${img}" class="img-fluid rounded border me-3" style="width: 60px; height: 60px; object-fit: cover;" alt="${item.nombre_producto}">
                    <div class="flex-fill">
                        <h6 class="mb-0 text-dark" style="font-size: 0.95rem;">${item.nombre_producto}</h6>
                        <span class="text-muted small">${priceFormated} x ${item.cantidad}</span>
                    </div>
                    <span class="fw-bold text-dark text-end">${subtotalFormated}</span>
                </div>
            `;
        }).join('');

        // 3. Renderizar Datos de Pago
        let paymentInfoHTML = '';
        if (order.pago) {
            const payDate = order.pago.fecha_pago ? new Date(order.pago.fecha_pago).toLocaleDateString('es-CL', {
                year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
            }) : 'No registrada';

            const methodFormated = order.pago.metodo_pago === 'paypal' ? 'PayPal <i class="bi bi-paypal text-info"></i>' : 'Webpay <i class="bi bi-credit-card text-primary"></i>';

            paymentInfoHTML = `
                <div class="card bg-light border-0 rounded-4 p-4 mb-4 shadow-sm">
                    <h6 class="fw-bold mb-3"><i class="bi bi-wallet2 me-2 text-primary"></i>Información del Pago</h6>
                    <div class="row g-3" style="font-size: 0.9rem;">
                        <div class="col-md-6">
                            <span class="text-muted d-block">Medio de Pago:</span>
                            <span class="fw-semibold text-dark">${methodFormated}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted d-block">Estado del Pago:</span>
                            <span class="fw-semibold text-capitalize">${order.pago.estado === 'aprobado' ? '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Aprobado</span>' : '<span class="text-warning"><i class="bi bi-hourglass-split me-1"></i>Pendiente</span>'}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted d-block">ID Transacción:</span>
                            <span class="fw-semibold text-dark code-style" style="font-family: monospace;">${order.pago.referencia_externa || 'N/A'}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted d-block">Fecha de Pago:</span>
                            <span class="fw-semibold text-dark">${payDate}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        // 4. Integrar todo en la plantilla
        content.innerHTML = `
            <div class="row">
                <div class="col-12 mb-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 pb-2 border-bottom">
                        <h5 class="fw-bold mb-0">Detalle del Pedido #${order.id}</h5>
                        <span class="text-muted small">${date}</span>
                    </div>
                </div>
                
                <div class="col-12">
                    ${trackingProgressHTML}
                </div>
                
                <div class="col-md-7">
                    <!-- Productos -->
                    <div class="card border shadow-sm rounded-4 p-4 mb-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-bag-check me-2 text-primary"></i>Productos Comprados</h6>
                        <div class="order-items-wrapper">
                            ${itemsHTML}
                        </div>
                    </div>
                    
                    <!-- Pago -->
                    ${paymentInfoHTML}
                </div>
                
                <div class="col-md-5">
                    <!-- Resumen del Pedido -->
                    <div class="card border shadow-sm rounded-4 p-4 mb-4 bg-light-subtle">
                        <h6 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-primary"></i>Resumen Financiero</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal:</span>
                            <span class="text-dark">${order.subtotal_formateado}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">IVA (19%):</span>
                            <span class="text-dark">${order.iva_formateado}</span>
                        </div>
                        ${order.descuento ? `
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Descuento:</span>
                            <span>-${App.formatPrice(order.descuento)}</span>
                        </div>
                        ` : ''}
                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-0">
                            <strong class="fs-6">Monto Total:</strong>
                            <strong class="text-primary fs-5">${order.total_formateado}</strong>
                        </div>
                    </div>

                    <!-- Datos Despacho -->
                    <div class="card border shadow-sm rounded-4 p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-truck me-2 text-primary"></i>Datos de Envío</h6>
                        <div class="mb-3" style="font-size: 0.9rem;">
                            <span class="text-muted d-block"><i class="bi bi-geo-alt me-1"></i>Dirección de Despacho:</span>
                            <span class="fw-semibold text-dark">${App.escapeHtml(order.direccion_envio)}</span>
                        </div>
                        <div class="mb-3" style="font-size: 0.9rem;">
                            <span class="text-muted d-block"><i class="bi bi-telephone me-1"></i>Teléfono de Contacto:</span>
                            <span class="fw-semibold text-dark">${App.escapeHtml(order.telefono_contacto || 'No indicado')}</span>
                        </div>
                        <div class="mb-0" style="font-size: 0.9rem;">
                            <span class="text-muted d-block"><i class="bi bi-journal-text me-1"></i>Notas Adicionales:</span>
                            <span class="fw-semibold text-dark fst-italic">${App.escapeHtml(order.notas || 'Sin notas.')}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Genera un Timeline interactivo e ilustrado del estado del pedido
     */
    renderTrackingProgress(currentState) {
        const states = [
            { key: 'pendiente', label: 'Creado', icon: 'bi-file-earmark-plus' },
            { key: 'pagado', label: 'Pagado', icon: 'bi-wallet2' },
            { key: 'en_preparacion', label: 'En Preparación', icon: 'bi-box-seam' },
            { key: 'enviado', label: 'Enviado', icon: 'bi-truck' },
            { key: 'entregado', label: 'Entregado', icon: 'bi-check-all' }
        ];

        // Definir index activo
        const activeIndex = states.findIndex(s => s.key === currentState);
        
        // Calcular porcentaje del progress bar
        let percent = 0;
        if (activeIndex >= 0) {
            percent = (activeIndex / (states.length - 1)) * 100;
        }

        const stepsHTML = states.map((s, idx) => {
            const isActive = idx <= activeIndex;
            const isCurrent = s.key === currentState;

            const bgClass = isCurrent ? 'bg-success text-white' : (isActive ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary');
            const pulse = isCurrent ? 'pulse-effect' : '';
            const borderClass = isCurrent ? 'border border-3 border-success-subtle' : '';

            return `
                <div class="text-center position-relative flex-fill" style="z-index: 2;">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto shadow-sm ${bgClass} ${pulse} ${borderClass}" 
                         style="width: 40px; height: 40px; transition: background-color 0.3s;">
                        <i class="bi ${s.icon} fs-5"></i>
                    </div>
                    <small class="fw-bold d-block mt-2 ${isActive ? 'text-dark' : 'text-muted'}" style="font-size: 0.75rem;">${s.label}</small>
                </div>
            `;
        }).join('');

        return `
            <div class="card border-0 bg-light rounded-4 p-4 mb-4 shadow-sm overflow-hidden position-relative">
                <div class="d-flex justify-content-between align-items-center position-relative mb-2">
                    <!-- Progress Bar Background -->
                    <div class="position-absolute top-50 start-0 end-0 translate-middle-y bg-secondary-subtle" style="height: 5px; z-index: 1; border-radius: 5px;"></div>
                    <!-- Active Progress Line -->
                    <div class="position-absolute top-50 start-0 bg-primary" style="height: 5px; z-index: 1; width: ${percent}%; transition: width 0.5s ease-in-out; border-radius: 5px;"></div>
                    
                    ${stepsHTML}
                </div>
                
                <style>
                    .pulse-effect {
                        animation: pulse-border 2s infinite;
                    }
                    @keyframes pulse-border {
                        0% {
                            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4);
                        }
                        70% {
                            box-shadow: 0 0 0 8px rgba(25, 135, 84, 0);
                        }
                        100% {
                            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
                        }
                    }
                </style>
            </div>
        `;
    },

    /**
     * Retorna una etiqueta HTML estructurada con el badge correspondiente al estado
     */
    getStatusBadge(estado) {
        switch (estado) {
            case 'pendiente':
                return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 fw-semibold"><i class="bi bi-clock me-1"></i>Pendiente</span>';
            case 'pagado':
                return '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-semibold"><i class="bi bi-check2-circle me-1"></i>Pagado</span>';
            case 'en_preparacion':
                return '<span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3 py-1 fw-semibold"><i class="bi bi-gear-wide-connected me-1"></i>En Preparación</span>';
            case 'enviado':
                return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold"><i class="bi bi-truck me-1"></i>Enviado</span>';
            case 'entregado':
                return '<span class="badge bg-success text-white rounded-pill px-3 py-1 fw-semibold"><i class="bi bi-check-all me-1"></i>Entregado</span>';
            case 'cancelado':
                return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 fw-semibold"><i class="bi bi-x-circle me-1"></i>Cancelado</span>';
            default:
                return `<span class="badge bg-secondary text-white rounded-pill px-3 py-1 fw-semibold">${estado}</span>`;
        }
    }
};

// Adjuntar App.escapeHtml si no existe en App
if (typeof App !== 'undefined' && !App.escapeHtml) {
    App.escapeHtml = function(string) {
        if (!string) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#x27;',
            "/": '&#x2F;',
        };
        const reg = /[&<>"'/]/ig;
        return string.replace(reg, (match) => map[match]);
    };
}
