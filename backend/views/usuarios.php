<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

AuthMiddleware::verificar();
$adminUser = AuthMiddleware::getAdmin();
$pageTitle = 'Usuarios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Panel Admin</title>
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
                    <h5 class="mb-0 fw-bold text-dark">Gestión de Usuarios</h5>
                </div>

                <div class="admin-card">
                    <div class="admin-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Registro</th>
                                        <th>Último Acceso</th>
                                        <th>Estado</th>
                                        <th class="text-end">Bloquear/Activar</th>
                                    </tr>
                                </thead>
                                <tbody id="users-tbody">
                                    <tr><td colspan="8" class="text-center py-4 text-muted">Cargando...</td></tr>
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
        const currentUserId = <?= json_encode($adminUser['id']) ?>;

        document.addEventListener('DOMContentLoaded', () => {
            AdminApp.init('<?= $_SESSION["admin_jwt_token"] ?>', '<?= getApiPath() ?>');
            loadUsers();
        });

        async function loadUsers(page = 1) {
            currentPage = page;
            const tbody = document.getElementById('users-tbody');
            
            try {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>';
                
                const resp = await AdminApp.fetch(`/admin/usuarios?page=${page}`);
                if (resp.success) {
                    const data = resp.data;
                    const meta = resp.meta.pagination;
                    
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No se encontraron usuarios</td></tr>';
                    } else {
                        tbody.innerHTML = data.map(u => `
                            <tr>
                                <td class="text-muted">#${u.id}</td>
                                <td class="fw-medium text-dark">${u.nombre} ${u.apellido}</td>
                                <td>${u.email}</td>
                                <td><span class="badge ${u.rol === 'admin' ? 'bg-danger' : 'bg-info'} bg-opacity-10 text-${u.rol === 'admin' ? 'danger' : 'info'} border border-${u.rol === 'admin' ? 'danger' : 'info'} border-opacity-25">${u.rol.toUpperCase()}</span></td>
                                <td class="text-muted small">${u.created_at.split(' ')[0]}</td>
                                <td class="text-muted small">${u.ultimo_login || 'Nunca'}</td>
                                <td>
                                    ${u.activo 
                                        ? '<span class="badge bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle me-1"></i>Activo</span>' 
                                        : '<span class="badge bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle me-1"></i>Bloqueado</span>'
                                    }
                                </td>
                                <td class="text-end">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input" type="checkbox" role="switch" ${u.activo ? 'checked' : ''} 
                                            onchange="toggleUserStatus(${u.id}, this.checked)"
                                            ${u.id == currentUserId ? 'disabled title="No puedes bloquearte a ti mismo"' : ''}>
                                    </div>
                                </td>
                            </tr>
                        `).join('');
                    }

                    // Actualizar paginación
                    document.getElementById('pagination-info').textContent = `Página ${meta.page} de ${meta.total_pages} (${meta.total} usuarios)`;
                    
                    let pagHtml = '';
                    if (meta.page > 1) pagHtml += `<button class="btn btn-sm btn-outline-secondary" onclick="loadUsers(${meta.page - 1})">Anterior</button>`;
                    if (meta.page < meta.total_pages) pagHtml += `<button class="btn btn-sm btn-outline-secondary" onclick="loadUsers(${meta.page + 1})">Siguiente</button>`;
                    document.getElementById('pagination-controls').innerHTML = pagHtml;

                }
            } catch (e) {
                AdminApp.toast('Error al cargar usuarios', 'error');
            }
        }

        async function toggleUserStatus(id, activo) {
            try {
                const resp = await AdminApp.fetch(`/admin/usuarios/${id}/estado`, {
                    method: 'PATCH',
                    body: JSON.stringify({ activo: activo ? 1 : 0 })
                });
                
                if (resp.success) {
                    AdminApp.toast(`Usuario ${activo ? 'activado' : 'bloqueado'} correctamente`);
                    loadUsers(currentPage);
                } else {
                    AdminApp.toast(resp.error?.message || 'Error al actualizar', 'error');
                    loadUsers(currentPage); // revert visual toggle
                }
            } catch (e) {
                AdminApp.toast('Error de conexión', 'error');
                loadUsers(currentPage);
            }
        }
    </script>
</body>
</html>
