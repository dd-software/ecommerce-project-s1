<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

AuthMiddleware::verificar();
$adminUser = AuthMiddleware::getAdmin();
$pageTitle = 'Categorías';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías - Panel Admin</title>
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
                    <h5 class="mb-0 fw-bold text-dark">Gestión de Categorías</h5>
                    <button class="btn btn-primary shadow-sm" onclick="showCategoryModal()">
                        <i class="bi bi-plus-lg me-1"></i> Nueva Categoría
                    </button>
                </div>

                <div class="admin-card">
                    <div class="admin-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Slug</th>
                                        <th>Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="categories-tbody">
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Categoría -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="categoryModalTitle">Nueva Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="category-form">
                        <input type="hidden" id="cat-id">
                        
                        <div class="mb-3">
                            <label class="form-label fw-medium">Nombre de la categoría</label>
                            <input type="text" class="form-control" id="cat-nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Slug (URL)</label>
                            <input type="text" class="form-control" id="cat-slug" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Estado</label>
                            <select class="form-select" id="cat-activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Descripción</label>
                            <textarea class="form-control" id="cat-descripcion" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-save-category" onclick="saveCategory()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= adminAsset('js/admin-dashboard.js') ?>"></script>
    <script>
        let categoryModalInstance;

        document.addEventListener('DOMContentLoaded', () => {
            AdminApp.init('<?= $_SESSION["admin_jwt_token"] ?>', '<?= getApiPath() ?>');
            categoryModalInstance = new bootstrap.Modal(document.getElementById('categoryModal'));
            loadCategories();

            // Autogenerar slug
            document.getElementById('cat-nombre').addEventListener('keyup', function() {
                const slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
                document.getElementById('cat-slug').value = slug;
            });
        });

        async function loadCategories() {
            const tbody = document.getElementById('categories-tbody');
            
            try {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>';
                
                const resp = await AdminApp.fetch(`/admin/categorias`);
                if (resp.success) {
                    const data = resp.data;
                    
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No se encontraron categorías</td></tr>';
                    } else {
                        tbody.innerHTML = data.map(c => `
                            <tr>
                                <td class="text-muted">#${c.id}</td>
                                <td class="fw-medium text-dark">${c.nombre}</td>
                                <td><span class="text-muted small">${c.slug}</span></td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" ${c.activo ? 'checked' : ''} onchange="toggleCategoryStatus(${c.id}, this.checked)">
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light text-primary me-1" onclick='editCategory(${JSON.stringify(c).replace(/'/g, "&#39;")})' title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light text-danger" onclick="deleteCategory(${c.id})" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('');
                    }
                }
            } catch (e) {
                AdminApp.toast('Error al cargar categorías', 'error');
            }
        }

        function showCategoryModal() {
            document.getElementById('category-form').reset();
            document.getElementById('cat-id').value = '';
            document.getElementById('categoryModalTitle').textContent = 'Nueva Categoría';
            categoryModalInstance.show();
        }

        function editCategory(c) {
            document.getElementById('cat-id').value = c.id;
            document.getElementById('cat-nombre').value = c.nombre;
            document.getElementById('cat-slug').value = c.slug;
            document.getElementById('cat-activo').value = c.activo ? '1' : '0';
            document.getElementById('cat-descripcion').value = c.descripcion || '';
            
            document.getElementById('categoryModalTitle').textContent = `Editar Categoría #${c.id}`;
            categoryModalInstance.show();
        }

        async function saveCategory() {
            const id = document.getElementById('cat-id').value;
            const data = {
                nombre: document.getElementById('cat-nombre').value,
                slug: document.getElementById('cat-slug').value,
                activo: parseInt(document.getElementById('cat-activo').value),
                descripcion: document.getElementById('cat-descripcion').value,
                id_padre: null
            };

            const btn = document.getElementById('btn-save-category');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

            try {
                const isEdit = id !== '';
                const url = isEdit ? `/admin/categorias/${id}` : `/admin/categorias`;
                const method = isEdit ? 'PUT' : 'POST';

                const resp = await AdminApp.fetch(url, {
                    method: method,
                    body: JSON.stringify(data)
                });

                if (resp.success) {
                    AdminApp.toast(`Categoría ${isEdit ? 'actualizada' : 'creada'} correctamente`);
                    categoryModalInstance.hide();
                    loadCategories();
                } else {
                    AdminApp.toast(resp.error?.message || 'Error al guardar', 'error');
                }
            } catch (e) {
                AdminApp.toast('Error de conexión', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Guardar';
            }
        }

        async function deleteCategory(id) {
            if (!confirm('¿Está seguro de eliminar esta categoría?')) return;

            try {
                const resp = await AdminApp.fetch(`/admin/categorias/${id}`, { method: 'DELETE' });
                if (resp.success) {
                    AdminApp.toast('Categoría eliminada');
                    loadCategories();
                } else {
                    AdminApp.toast(resp.error?.message || 'Error al eliminar', 'error');
                }
            } catch (e) {
                AdminApp.toast('Error de conexión', 'error');
            }
        }

        async function toggleCategoryStatus(id, activo) {
            try {
                const resp = await AdminApp.fetch(`/admin/categorias/${id}`, {
                    method: 'PUT',
                    body: JSON.stringify({ activo: activo ? 1 : 0 })
                });
                if (resp.success) {
                    AdminApp.toast('Estado actualizado');
                } else {
                    AdminApp.toast(resp.error?.message || 'Error al actualizar', 'error');
                    loadCategories();
                }
            } catch (e) {
                AdminApp.toast('Error de conexión', 'error');
                loadCategories();
            }
        }
    </script>
</body>
</html>
