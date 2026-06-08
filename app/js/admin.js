// ─── Init ────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  if (!requireAdmin()) return;
  loadStats();
  loadProductosAdmin();
  loadCategoriasAdmin();
  loadPedidosAdmin();
  loadInventarioAdmin();
  loadUsuariosAdmin();

  document.getElementById('form-producto')?.addEventListener('submit', guardarProducto);
  document.getElementById('form-categoria')?.addEventListener('submit', guardarCategoria);
});

// ─── Stats ───────────────────────────────────────────────────────────────────
async function loadStats() {
  try {
    const s = await apiFetch('/admin/stats');
    setText('stat-productos', s.total_productos);
    setText('stat-usuarios', s.total_usuarios);
    setText('stat-pedidos', s.total_pedidos);
    setText('stat-ingresos', formatPrice(s.ingresos_totales));
    setText('stat-sin-stock', s.productos_sin_stock);
  } catch {}
}

function setText(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val;
}

// ─── Productos ───────────────────────────────────────────────────────────────
let editingProductoId = null;

async function loadProductosAdmin() {
  const tbody = document.getElementById('tabla-productos');
  if (!tbody) return;
  tbody.innerHTML = `<tr><td colspan="6" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></td></tr>`;
  try {
    const productos = await apiFetch('/admin/productos');
    tbody.innerHTML = productos.map(p => `
      <tr>
        <td><img src="${p.imagen_url || 'https://picsum.photos/seed/'+p.id+'/50/50'}"
                 width="40" height="40" style="object-fit:cover;border-radius:4px" alt=""></td>
        <td>${esc(p.nombre)}</td>
        <td>${esc(p.categoria_nombre)}</td>
        <td>${formatPrice(p.precio)}</td>
        <td>
          <span class="badge ${p.stock <= 0 ? 'bg-danger' : p.stock <= 5 ? 'bg-warning text-dark' : 'bg-success'}">
            ${p.stock}
          </span>
        </td>
        <td>
          <button class="btn btn-sm btn-outline-primary me-1" onclick="editarProducto(${p.id})">
            <i class="bi bi-pencil"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger" onclick="eliminarProducto(${p.id}, '${esc(p.nombre)}')">
            <i class="bi bi-trash"></i>
          </button>
        </td>
      </tr>
    `).join('') || `<tr><td colspan="6" class="text-center text-muted">Sin productos.</td></tr>`;
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-danger">${err.mensaje}</td></tr>`;
  }
}

async function editarProducto(id) {
  try {
    const p = await apiFetch(`/admin/productos/${id}`);
    editingProductoId = id;
    document.getElementById('prod-id').value           = id;
    document.getElementById('prod-nombre').value       = p.nombre;
    document.getElementById('prod-descripcion').value  = p.descripcion || '';
    document.getElementById('prod-precio').value       = p.precio;
    document.getElementById('prod-categoria').value    = p.categoria_id;
    document.getElementById('prod-stock').value        = p.stock;
    document.getElementById('prod-imagen').value       = p.imagenes?.[0]?.url || '';
    document.getElementById('modal-producto-title').textContent = 'Editar Producto';
    new bootstrap.Modal(document.getElementById('modalProducto')).show();
  } catch (err) { showToast(err.mensaje, 'danger'); }
}

function nuevoProducto() {
  editingProductoId = null;
  document.getElementById('form-producto').reset();
  document.getElementById('prod-id').value = '';
  document.getElementById('modal-producto-title').textContent = 'Nuevo Producto';
  new bootstrap.Modal(document.getElementById('modalProducto')).show();
}

async function guardarProducto(e) {
  e.preventDefault();
  const btn = document.getElementById('btn-guardar-producto');
  setLoading(btn, true);

  const body = {
    nombre:       document.getElementById('prod-nombre').value,
    descripcion:  document.getElementById('prod-descripcion').value,
    precio:       parseFloat(document.getElementById('prod-precio').value),
    categoria_id: parseInt(document.getElementById('prod-categoria').value),
    stock:        parseInt(document.getElementById('prod-stock').value),
    imagen_url:   document.getElementById('prod-imagen').value,
  };

  try {
    if (editingProductoId) {
      await apiFetch(`/admin/productos/${editingProductoId}`, { method: 'PUT', body: JSON.stringify(body) });
      showToast('Producto actualizado.');
    } else {
      await apiFetch('/admin/productos', { method: 'POST', body: JSON.stringify(body) });
      showToast('Producto creado.');
    }
    bootstrap.Modal.getInstance(document.getElementById('modalProducto')).hide();
    loadProductosAdmin();
    loadStats();
  } catch (err) {
    showToast(err.mensaje || 'Error al guardar.', 'danger');
  } finally {
    setLoading(btn, false);
  }
}

async function eliminarProducto(id, nombre) {
  if (!confirm(`¿Desactivar "${nombre}"?`)) return;
  try {
    await apiFetch(`/admin/productos/${id}`, { method: 'DELETE' });
    showToast('Producto desactivado.', 'warning');
    loadProductosAdmin();
    loadStats();
  } catch (err) { showToast(err.mensaje, 'danger'); }
}

// ─── Categorías ──────────────────────────────────────────────────────────────
async function loadCategoriasAdmin() {
  const lista = document.getElementById('lista-categorias');
  const sel   = document.getElementById('prod-categoria');
  try {
    const cats = await apiFetch('/admin/categorias');
    if (lista) lista.innerHTML = cats.map(c => `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        ${esc(c.nombre)}
        <span class="badge bg-primary-subtle text-primary">#${c.id}</span>
      </li>`).join('') || '<li class="list-group-item text-muted">Sin categorías.</li>';

    if (sel) {
      const current = sel.value;
      sel.innerHTML = '<option value="">Selecciona categoría</option>' +
        cats.map(c => `<option value="${c.id}" ${current == c.id ? 'selected' : ''}>${esc(c.nombre)}</option>`).join('');
    }
  } catch {}
}

async function guardarCategoria(e) {
  e.preventDefault();
  const btn = document.getElementById('btn-guardar-cat');
  setLoading(btn, true);
  try {
    await apiFetch('/admin/categorias', {
      method: 'POST',
      body: JSON.stringify({
        nombre:      document.getElementById('cat-nombre').value,
        descripcion: document.getElementById('cat-descripcion').value,
        imagen_url:  document.getElementById('cat-imagen').value,
      }),
    });
    showToast('Categoría creada.');
    document.getElementById('form-categoria').reset();
    bootstrap.Modal.getInstance(document.getElementById('modalCategoria'))?.hide();
    loadCategoriasAdmin();
  } catch (err) {
    showToast(err.mensaje || 'Error.', 'danger');
  } finally { setLoading(btn, false); }
}

// ─── Pedidos ─────────────────────────────────────────────────────────────────
async function loadPedidosAdmin(estado = '') {
  const tbody = document.getElementById('tabla-pedidos');
  if (!tbody) return;
  tbody.innerHTML = `<tr><td colspan="6" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></td></tr>`;
  try {
    const url = '/admin/pedidos' + (estado ? `?estado=${estado}` : '');
    const pedidos = await apiFetch(url);
    tbody.innerHTML = pedidos.map(p => `
      <tr>
        <td>#${p.id}</td>
        <td>${esc(p.usuario_nombre)}<br><small class="text-muted">${esc(p.usuario_email)}</small></td>
        <td>${new Date(p.creado_en).toLocaleDateString('es-MX')}</td>
        <td><span class="badge ${estadoBadge(p.estado)}">${p.estado}</span></td>
        <td class="fw-bold">${formatPrice(p.total)}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" onclick="verPedidoAdmin(${p.id})">
            <i class="bi bi-eye"></i>
          </button>
        </td>
      </tr>`).join('') || `<tr><td colspan="6" class="text-center text-muted">Sin pedidos.</td></tr>`;
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-danger">${err.mensaje}</td></tr>`;
  }
}

async function verPedidoAdmin(id) {
  const modal = new bootstrap.Modal(document.getElementById('modalPedido'));
  const body  = document.getElementById('modal-pedido-body');
  body.innerHTML = `<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>`;
  modal.show();

  try {
    const p = await apiFetch(`/admin/pedidos/${id}`);
    body.innerHTML = `
      <div class="mb-3">
        <strong>Pedido #${p.id}</strong> — ${new Date(p.creado_en).toLocaleString('es-MX')}<br>
        <small class="text-muted">Cliente: ${esc(p.usuario_nombre)} (${esc(p.usuario_email)})</small>
      </div>
      <table class="table table-sm mb-3">
        <thead class="table-light"><tr><th>Producto</th><th class="text-center">Cant.</th><th class="text-end">Subtotal</th></tr></thead>
        <tbody>${p.detalles.map(d => `
          <tr><td>${esc(d.nombre_producto)}</td><td class="text-center">${d.cantidad}</td>
              <td class="text-end">${formatPrice(d.subtotal)}</td></tr>`).join('')}
        </tbody>
        <tfoot>
          <tr><td colspan="2" class="text-end text-muted">Total</td>
              <td class="text-end fw-bold text-primary">${formatPrice(p.total)}</td></tr>
        </tfoot>
      </table>
      <div class="mb-3">
        <label class="form-label fw-semibold">Cambiar estado</label>
        <div class="d-flex gap-2">
          <select class="form-select form-select-sm" id="nuevo-estado-${id}">
            ${['pendiente','pagado','enviado','entregado','cancelado'].map(e =>
              `<option value="${e}" ${p.estado===e?'selected':''}>${e}</option>`).join('')}
          </select>
          <input class="form-control form-control-sm" id="nota-estado-${id}" placeholder="Nota (opcional)">
          <button class="btn btn-primary btn-sm" onclick="cambiarEstadoAdmin(${id})">Guardar</button>
        </div>
      </div>`;
  } catch (err) {
    body.innerHTML = `<div class="alert alert-danger">${err.mensaje}</div>`;
  }
}

async function cambiarEstadoAdmin(id) {
  const estado = document.getElementById(`nuevo-estado-${id}`)?.value;
  const nota   = document.getElementById(`nota-estado-${id}`)?.value;
  try {
    await apiFetch(`/admin/pedidos/${id}/estado`, {
      method: 'PUT',
      body: JSON.stringify({ estado, nota }),
    });
    showToast('Estado actualizado.');
    bootstrap.Modal.getInstance(document.getElementById('modalPedido')).hide();
    loadPedidosAdmin();
    loadStats();
  } catch (err) { showToast(err.mensaje, 'danger'); }
}

// ─── Inventario ──────────────────────────────────────────────────────────────
async function loadInventarioAdmin() {
  const tbody = document.getElementById('tabla-inventario');
  if (!tbody) return;
  try {
    const inv = await apiFetch('/admin/inventario');
    tbody.innerHTML = inv.map(i => `
      <tr>
        <td><img src="${i.imagen_url || 'https://picsum.photos/seed/'+i.producto_id+'/40/40'}"
                 width="36" height="36" style="object-fit:cover;border-radius:4px" alt=""> ${esc(i.nombre)}</td>
        <td>
          <div class="input-group input-group-sm" style="width:120px">
            <input type="number" class="form-control" id="stock-${i.producto_id}" value="${i.stock}" min="0">
            <button class="btn btn-outline-primary" onclick="actualizarStockAdmin(${i.producto_id})">✓</button>
          </div>
        </td>
        <td><span class="badge ${i.stock<=0?'bg-danger':i.stock<=5?'bg-warning text-dark':'bg-success'}">${i.stock}</span></td>
      </tr>`).join('') || `<tr><td colspan="3" class="text-muted text-center">Sin datos.</td></tr>`;
  } catch {}
}

async function actualizarStockAdmin(productoId) {
  const stock = parseInt(document.getElementById(`stock-${productoId}`)?.value);
  try {
    await apiFetch(`/admin/inventario/${productoId}`, {
      method: 'PUT', body: JSON.stringify({ stock }),
    });
    showToast('Stock actualizado.');
    loadInventarioAdmin();
    loadStats();
  } catch (err) { showToast(err.mensaje, 'danger'); }
}

// ─── Usuarios ────────────────────────────────────────────────────────────────
async function loadUsuariosAdmin() {
  const tbody = document.getElementById('tabla-usuarios');
  if (!tbody) return;
  try {
    const users = await apiFetch('/admin/usuarios');
    tbody.innerHTML = users.map(u => `
      <tr>
        <td>#${u.id}</td>
        <td>${esc(u.nombre)}</td>
        <td>${esc(u.email)}</td>
        <td><span class="badge ${u.rol==='administrador'?'bg-danger':'bg-primary'}">${u.rol}</span></td>
        <td>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" ${u.habilitado?'checked':''}
                   onchange="toggleUsuario(${u.id}, this.checked)" ${u.rol==='administrador'?'disabled':''}>
          </div>
        </td>
      </tr>`).join('');
  } catch {}
}

async function toggleUsuario(id, habilitado) {
  try {
    await apiFetch(`/admin/usuarios/${id}`, {
      method: 'PUT', body: JSON.stringify({ habilitado }),
    });
    showToast(habilitado ? 'Usuario habilitado.' : 'Usuario deshabilitado.', habilitado ? 'success' : 'warning');
  } catch (err) { showToast(err.mensaje, 'danger'); loadUsuariosAdmin(); }
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
function estadoBadge(estado) {
  return {pendiente:'bg-warning text-dark',pagado:'bg-success',enviado:'bg-info text-dark',
          entregado:'bg-primary',cancelado:'bg-danger'}[estado]||'bg-secondary';
}
function esc(str) {
  return String(str||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
