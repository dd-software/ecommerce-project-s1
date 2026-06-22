<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

AuthMiddleware::verificar();
$adminUser = AuthMiddleware::getAdmin();
$pageTitle = 'Productos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Panel Admin</title>
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
                    <h5 class="mb-0 fw-bold text-dark">Gestión de Productos</h5>
                    <button class="btn btn-primary shadow-sm" onclick="showProductModal()">
                        <i class="bi bi-plus-lg me-1"></i> Nuevo Producto
                    </button>
                </div>

                <div class="admin-card">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <div class="input-group" style="max-width: 300px;">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="search-input" placeholder="Buscar productos..." onkeyup="if(event.key === 'Enter') loadProducts()">
                        </div>
                    </div>
                    <div class="admin-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Imagen</th>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Precio</th>
                                        <th>Stock</th>
                                        <th>Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="products-tbody">
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

    <!-- Modal Producto -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="productModalTitle">Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="product-form">
                        <input type="hidden" id="prod-id">
                        
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-medium">Nombre del producto</label>
                                <input type="text" class="form-control" id="prod-nombre" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Slug (URL)</label>
                                <input type="text" class="form-control" id="prod-slug" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Precio (centavos)</label>
                                <input type="number" class="form-control" id="prod-precio" required min="0">
                                <small class="text-muted">Ej: 15000 = $15.000</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Stock</label>
                                <input type="number" class="form-control" id="prod-stock" required min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Stock mínimo</label>
                                <input type="number" class="form-control" id="prod-stock-min" required min="0" value="5">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Categoría ID</label>
                                <input type="number" class="form-control" id="prod-categoria" required>
                                <small class="text-muted">ID de la categoría (ej: 1, 2, 3)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Estado</label>
                                <select class="form-select" id="prod-activo">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-medium">URL de Imagen</label>
                                <input type="url" class="form-control" id="prod-imagen" placeholder="https://ejemplo.com/img.jpg">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-medium">Descripción</label>
                                <textarea class="form-control" id="prod-descripcion" rows="4"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-save-product" onclick="saveProduct()">Guardar Producto</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= adminAsset('js/admin-dashboard.js') ?>"></script>
    <script>
        let currentPage = 1;
        let productModalInstance;

        document.addEventListener('DOMContentLoaded', () => {
            AdminApp.init('<?= $_SESSION["admin_jwt_token"] ?>', '<?= getApiPath() ?>');
            productModalInstance = new bootstrap.Modal(document.getElementById('productModal'));
            loadProducts();

            // Autogenerar slug
            document.getElementById('prod-nombre').addEventListener('keyup', function() {
                const slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
                document.getElementById('prod-slug').value = slug;
            });
        });

        async function loadProducts(page = 1) {
            currentPage = page;
            const search = document.getElementById('search-input').value;
            const tbody = document.getElementById('products-tbody');
            
            try {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>';
                
                const resp = await AdminApp.fetch(`/admin/productos?page=${page}&q=${encodeURIComponent(search)}`);
                if (resp.success) {
                    const data = resp.data;
                    const meta = resp.meta.pagination;
                    
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No se encontraron productos</td></tr>';
                    } else {
                        tbody.innerHTML = data.map(p => `
                            <tr>
                                <td class="text-muted">#${p.id}</td>
                                <td>
                                    <div class="rounded overflow-hidden" style="width: 40px; height: 40px; background: #f1f5f9;">
                                        ${p.imagen_url ? `<img src="${p.imagen_url}" class="w-100 h-100 object-fit-cover">` : '<i class="bi bi-image text-muted d-flex justify-content-center align-items-center h-100"></i>'}
                                    </div>
                                </td>
                                <td class="fw-medium text-dark">${p.nombre}</td>
                                <td><span class="badge bg-light text-dark border">${p.categoria_nombre || 'Sin categoría'}</span></td>
                                <td class="fw-semibold">${p.precio_formateado}</td>
                                <td>
                                    <span class="badge ${p.stock <= p.stock_minimo ? 'bg-danger' : 'bg-success'} bg-opacity-10 text-${p.stock <= p.stock_minimo ? 'danger' : 'success'} border border-${p.stock <= p.stock_minimo ? 'danger' : 'success'} border-opacity-25">
                                        ${p.stock} un.
                                    </span>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" ${p.activo ? 'checked' : ''} onchange="toggleProductStatus(${p.id}, this.checked)">
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light text-primary me-1" onclick='editProduct(${JSON.stringify(p).replace(/'/g, "&#39;")})' title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light text-danger" onclick="deleteProduct(${p.id})" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('');
                    }

                    // Actualizar paginación
                    document.getElementById('pagination-info').textContent = `Página ${meta.page} de ${meta.total_pages} (${meta.total} productos)`;
                    
                    let pagHtml = '';
                    if (meta.page > 1) pagHtml += `<button class="btn btn-sm btn-outline-secondary" onclick="loadProducts(${meta.page - 1})">Anterior</button>`;
                    if (meta.page < meta.total_pages) pagHtml += `<button class="btn btn-sm btn-outline-secondary" onclick="loadProducts(${meta.page + 1})">Siguiente</button>`;
                    document.getElementById('pagination-controls').innerHTML = pagHtml;

                }
            } catch (e) {
                AdminApp.toast('Error al cargar productos', 'error');
            }
        }

        function showProductModal() {
            document.getElementById('product-form').reset();
            document.getElementById('prod-id').value = '';
            document.getElementById('productModalTitle').textContent = 'Nuevo Producto';
            productModalInstance.show();
        }

        function editProduct(p) {
            document.getElementById('prod-id').value = p.id;
            document.getElementById('prod-nombre').value = p.nombre;
            document.getElementById('prod-slug').value = p.slug;
            document.getElementById('prod-precio').value = p.precio;
            document.getElementById('prod-stock').value = p.stock;
            document.getElementById('prod-stock-min').value = p.stock_minimo;
            document.getElementById('prod-categoria').value = p.id_categoria;
            document.getElementById('prod-activo').value = p.activo ? '1' : '0';
            document.getElementById('prod-imagen').value = p.imagen_url || '';
            document.getElementById('prod-descripcion').value = p.descripcion || '';
            
            document.getElementById('productModalTitle').textContent = `Editar Producto #${p.id}`;
            productModalInstance.show();
        }

        async function saveProduct() {
            const id = document.getElementById('prod-id').value;
            const data = {
                nombre: document.getElementById('prod-nombre').value,
                slug: document.getElementById('prod-slug').value,
                precio: parseInt(document.getElementById('prod-precio').value),
                stock: parseInt(document.getElementById('prod-stock').value),
                stock_minimo: parseInt(document.getElementById('prod-stock-min').value),
                id_categoria: parseInt(document.getElementById('prod-categoria').value),
                activo: parseInt(document.getElementById('prod-activo').value),
                imagen_url: document.getElementById('prod-imagen').value,
                descripcion: document.getElementById('prod-descripcion').value
            };

            const btn = document.getElementById('btn-save-product');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

            try {
                const isEdit = id !== '';
                const url = isEdit ? `/admin/productos/${id}` : `/admin/productos`;
                const method = isEdit ? 'PUT' : 'POST';

                const resp = await AdminApp.fetch(url, {
                    method: method,
                    body: JSON.stringify(data)
                });

                if (resp.success) {
                    AdminApp.toast(`Producto ${isEdit ? 'actualizado' : 'creado'} correctamente`);
                    productModalInstance.hide();
                    loadProducts(currentPage);
                } else {
                    AdminApp.toast(resp.error?.message || 'Error al guardar', 'error');
                }
            } catch (e) {
                AdminApp.toast('Error de conexión', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Guardar Producto';
            }
        }

        async function deleteProduct(id) {
            if (!confirm('¿Está seguro de eliminar este producto? Esta acción no se puede deshacer.')) return;

            try {
                const resp = await AdminApp.fetch(`/admin/productos/${id}`, { method: 'DELETE' });
                if (resp.success) {
                    AdminApp.toast('Producto eliminado');
                    loadProducts(currentPage);
                } else {
                    AdminApp.toast(resp.error?.message || 'Error al eliminar', 'error');
                }
            } catch (e) {
                AdminApp.toast('Error de conexión', 'error');
            }
        }

        async function toggleProductStatus(id, activo) {
            try {
                const resp = await AdminApp.fetch(`/admin/productos/${id}`, {
                    method: 'PUT',
                    body: JSON.stringify({ activo: activo ? 1 : 0 })
                });
                if (resp.success) {
                    AdminApp.toast('Estado actualizado');
                } else {
                    AdminApp.toast(resp.error?.message || 'Error al actualizar', 'error');
                    loadProducts(currentPage); // Revert checkbox
                }
            } catch (e) {
                AdminApp.toast('Error de conexión', 'error');
                loadProducts(currentPage);
            }
        }
    </script>
</body>
</html>
