<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

AuthMiddleware::verificar();
$adminUser = AuthMiddleware::getAdmin();
$pageTitle = 'Pedidos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos - Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= adminAsset('css/admin.css') ?>">
</head>
<body>
    <div class="admin-wrapper">
        <?php include __DIR__ . '/partials/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/partials/header.php'; ?>
            
            <main class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0 fw-bold text-dark">Gestión de Pedidos</h5>
                </div>

                <div class="admin-card">
                    <div class="admin-card-header d-flex flex-wrap gap-3 align-items-center bg-light border-bottom">
                        <select id="filter-status" class="form-select form-select-sm" style="max-width: 200px;" onchange="loadOrders()">
                            <option value="">Todos los estados</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="pagado">Pagado</option>
                            <option value="en_preparacion">En Preparación</option>
                            <option value="enviado">Enviado</option>
                            <option value="entregado">Entregado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                        <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('filter-status').value=''; loadOrders();">
                            Limpiar Filtros
                        </button>
                    </div>
                    <div class="admin-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID / Ref</th>
                                        <th>Cliente</th>
                                        <th>Total</th>
                                        <th>Método</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="orders-tbody">
                                    <tr><td colspan="6" class="text-center py-4 text-muted">Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small" id="pagination-info">Mostrando 0 de 0</span>
                        <div id="pagination-controls" class="btn-group"></div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= adminAsset('js/admin-dashboard.js') ?>"></script>
    <script>
        let currentPage = 1;

        document.addEventListener('DOMContentLoaded', () => {
            AdminApp.init('<?= $_SESSION["admin_jwt_token"] ?>', '<?= getApiPath() ?>');
            loadOrders();
        });

        async function loadOrders(page = 1) {
            currentPage = page;
            const status = document.getElementById('filter-status').value;
            const tbody = document.getElementById('orders-tbody');
            
            try {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>';
                
                let url = `/admin/pedidos?page=${page}`;
                if (status) url += `&estado=${encodeURIComponent(status)}`;
                
                const resp = await AdminApp.fetch(url);
                if (resp.success) {
                    const data = resp.data;
                    const meta = resp.meta.pagination;
                    
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No se encontraron pedidos</td></tr>';
                    } else {
                        const badges = {
                            'pendiente': 'warning', 'pagado': 'success', 'en_preparacion': 'info', 'enviado': 'primary', 'entregado': 'success', 'cancelado': 'danger'
                        };

                        tbody.innerHTML = data.map(o => `
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark d-block">#${o.id}</span>
                                    ${o.paypal_order_id ? `<small class="text-muted"><i class="bi bi-paypal text-primary"></i> ${o.paypal_order_id.substring(0,8)}...</small>` : ''}
                                </td>
                                <td>
                                    <div class="fw-medium">${o.cliente_nombre} ${o.apellido}</div>
                                    <div class="small text-muted">${o.email}</div>
                                </td>
                                <td class="fw-bold">${o.total_formateado} <small class="fw-normal text-muted d-block">${o.total_items} items</small></td>
                                <td><span class="badge bg-light text-dark border">${o.metodo_pago || 'N/A'}</span></td>
                                <td class="text-muted small">${o.created_at}</td>
                                <td>
                                    <select class="form-select form-select-sm d-inline-block w-auto border-${badges[o.estado] || 'secondary'} text-${badges[o.estado] || 'secondary'} fw-medium bg-${badges[o.estado] || 'secondary'} bg-opacity-10" onchange="updateOrderStatus(${o.id}, this.value)">
                                        <option value="pendiente" ${o.estado === 'pendiente' ? 'selected' : ''}>Pendiente</option>
                                        <option value="pagado" ${o.estado === 'pagado' ? 'selected' : ''}>Pagado</option>
                                        <option value="en_preparacion" ${o.estado === 'en_preparacion' ? 'selected' : ''}>En Preparación</option>
                                        <option value="enviado" ${o.estado === 'enviado' ? 'selected' : ''}>Enviado</option>
                                        <option value="entregado" ${o.estado === 'entregado' ? 'selected' : ''}>Entregado</option>
                                        <option value="cancelado" ${o.estado === 'cancelado' ? 'selected' : ''}>Cancelado</option>
                                    </select>
                                </td>
                            </tr>
                        `).join('');
                    }

                    // Actualizar paginación
                    document.getElementById('pagination-info').textContent = `Página ${meta.page} de ${meta.total_pages} (${meta.total} pedidos)`;
                    
                    let pagHtml = '';
                    if (meta.page > 1) pagHtml += `<button class="btn btn-sm btn-outline-secondary" onclick="loadOrders(${meta.page - 1})">Anterior</button>`;
                    if (meta.page < meta.total_pages) pagHtml += `<button class="btn btn-sm btn-outline-secondary" onclick="loadOrders(${meta.page + 1})">Siguiente</button>`;
                    document.getElementById('pagination-controls').innerHTML = pagHtml;

                }
            } catch (e) {
                AdminApp.toast('Error al cargar pedidos', 'error');
            }
        }

        async function updateOrderStatus(id, status) {
            try {
                const resp = await AdminApp.fetch(`/admin/pedidos/${id}/estado`, {
                    method: 'PATCH',
                    body: JSON.stringify({ estado: status })
                });
                
                if (resp.success) {
                    AdminApp.toast('Estado del pedido actualizado correctamente');
                    loadOrders(currentPage);
                } else {
                    AdminApp.toast(resp.error?.message || 'Error al actualizar estado', 'error');
                    loadOrders(currentPage);
                }
            } catch (e) {
                AdminApp.toast('Error de conexión', 'error');
                loadOrders(currentPage);
            }
        }
    </script>
</body>
</html>
