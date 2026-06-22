<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

AuthMiddleware::verificar();
$adminUser = AuthMiddleware::getAdmin();
$pageTitle = 'Inicio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel Admin</title>
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
                    <h5 class="mb-0 fw-bold text-dark">Resumen del Día</h5>
                    <button class="btn btn-sm btn-primary" onclick="loadDashboardStats()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
                    </button>
                </div>

                <div class="row g-4 mb-4" id="stats-container">
                    <!-- Stats cargados vía JS -->
                    <div class="col-12 text-center text-muted py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Cargando estadísticas...</p>
                    </div>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-8">
                        <div class="admin-card">
                            <div class="admin-card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold">Últimos Pedidos</h6>
                                <a href="<?= getBackendPath() ?>/views/pedidos.php" class="btn btn-sm btn-light">Ver todos</a>
                            </div>
                            <div class="admin-card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Cliente</th>
                                                <th>Total</th>
                                                <th>Estado</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recent-orders-tbody">
                                            <tr><td colspan="5" class="text-center py-4 text-muted">Cargando...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="admin-card border-danger border-opacity-25">
                            <div class="admin-card-header bg-danger bg-opacity-10 text-danger d-flex justify-content-between">
                                <h6 class="mb-0 fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Alertas de Stock</h6>
                            </div>
                            <div class="admin-card-body p-0">
                                <ul class="list-group list-group-flush" id="stock-alerts-list">
                                    <li class="list-group-item text-center py-4 text-muted">Cargando...</li>
                                </ul>
                            </div>
                        </div>
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
        document.addEventListener('DOMContentLoaded', () => {
            AdminApp.init('<?= $_SESSION["admin_jwt_token"] ?>', '<?= getApiPath() ?>');
            loadDashboardStats();
        });

        async function loadDashboardStats() {
            try {
                const resp = await AdminApp.fetch('/admin/dashboard');
                if (resp.success) {
                    renderStats(resp.data.estadisticas);
                    renderRecentOrders(resp.data.ultimos_pedidos);
                    renderStockAlerts(resp.data.alertas_stock);
                }
            } catch (e) {
                AdminApp.toast('Error al cargar datos del dashboard', 'error');
            }
        }

        function renderStats(stats) {
            const container = document.getElementById('stats-container');
            container.innerHTML = `
                <div class="col-md-3">
                    <div class="admin-card border-start border-4 border-success">
                        <div class="admin-card-body">
                            <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Ventas de Hoy</h6>
                            <h3 class="mb-0 fw-bold text-dark">${stats.ventas_hoy.total_ventas_formateado}</h3>
                            <small class="text-success"><i class="bi bi-bag-check me-1"></i> ${stats.ventas_hoy.total_pedidos} pedidos</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="admin-card border-start border-4 border-primary">
                        <div class="admin-card-body">
                            <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Ventas del Mes</h6>
                            <h3 class="mb-0 fw-bold text-dark">${stats.ventas_mes.total_ventas_formateado}</h3>
                            <small class="text-primary"><i class="bi bi-graph-up me-1"></i> ${stats.ventas_mes.total_pedidos} pedidos</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="admin-card border-start border-4 border-warning">
                        <div class="admin-card-body">
                            <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Pedidos Pendientes</h6>
                            <h3 class="mb-0 fw-bold text-dark">${stats.pedidos_pendientes}</h3>
                            <small class="text-warning"><i class="bi bi-clock-history me-1"></i> Requieren atención</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="admin-card border-start border-4 border-info">
                        <div class="admin-card-body">
                            <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Total Productos</h6>
                            <h3 class="mb-0 fw-bold text-dark">${stats.total_productos}</h3>
                            <small class="text-info"><i class="bi bi-box me-1"></i> En catálogo</small>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderRecentOrders(orders) {
            const tbody = document.getElementById('recent-orders-tbody');
            if (!orders || orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay pedidos recientes</td></tr>';
                return;
            }

            const badges = {
                'pendiente': 'warning', 'pagado': 'success', 'enviado': 'primary', 'entregado': 'success', 'cancelado': 'danger'
            };

            tbody.innerHTML = orders.map(o => `
                <tr>
                    <td class="fw-medium">#${o.id}</td>
                    <td>${o.cliente_nombre} ${o.apellido}</td>
                    <td class="fw-bold">${o.total_formateado}</td>
                    <td><span class="badge bg-${badges[o.estado] || 'secondary'} bg-opacity-10 text-${badges[o.estado] || 'secondary'} fw-medium">${o.estado.toUpperCase()}</span></td>
                    <td class="text-muted small">${o.created_at.split(' ')[0]}</td>
                </tr>
            `).join('');
        }

        function renderStockAlerts(alerts) {
            const list = document.getElementById('stock-alerts-list');
            if (!alerts || alerts.length === 0) {
                list.innerHTML = '<li class="list-group-item text-center py-3 text-success"><i class="bi bi-check-circle me-2"></i>Stock saludable</li>';
                return;
            }

            list.innerHTML = alerts.map(a => `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 text-dark fw-medium" style="font-size: 0.9rem;">${a.nombre}</h6>
                        <small class="text-muted">Min: ${a.stock_minimo}</small>
                    </div>
                    <span class="badge bg-danger rounded-pill">${a.stock} disp.</span>
                </li>
            `).join('');
        }
    </script>
</body>
</html>
