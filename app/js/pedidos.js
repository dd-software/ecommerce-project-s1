async function loadPedidos() {
  if (!requireLogin()) return;

  const lista     = document.getElementById('pedidos-lista');
  const detalle   = document.getElementById('pedido-detalle');
  const nuevoBanner = document.getElementById('nuevo-banner');

  // Verificar si venimos de un checkout exitoso
  const params = new URLSearchParams(window.location.search);
  const nuevoId = params.get('nuevo');
  if (nuevoId && nuevoBanner) {
    nuevoBanner.innerHTML = `
      <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill fs-4"></i>
        <div>
          <strong>¡Pedido #${nuevoId} confirmado!</strong> Tu compra fue procesada exitosamente.
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>`;
  }

  if (lista) lista.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>`;

  try {
    const pedidos = await apiFetch('/checkout/pedidos');

    if (!pedidos.length) {
      lista.innerHTML = `
        <div class="text-center py-5 text-muted">
          <i class="bi bi-bag-x" style="font-size:4rem"></i>
          <h5 class="mt-3">Aún no tienes pedidos</h5>
          <a href="/" class="btn btn-primary mt-3">Ir al catálogo</a>
        </div>`;
      return;
    }

    lista.innerHTML = pedidos.map(p => `
      <div class="card border-0 shadow-sm mb-3 ${nuevoId == p.id ? 'border-success border-2' : ''}">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-6 col-md-3">
              <div class="small text-muted">Pedido</div>
              <div class="fw-bold">#${p.id}</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="small text-muted">Fecha</div>
              <div>${new Date(p.creado_en).toLocaleDateString('es-MX', {day:'2-digit',month:'short',year:'numeric'})}</div>
            </div>
            <div class="col-6 col-md-2 mt-2 mt-md-0">
              <div class="small text-muted">Estado</div>
              <span class="badge ${estadoBadge(p.estado)}">${p.estado}</span>
            </div>
            <div class="col-6 col-md-2 mt-2 mt-md-0">
              <div class="small text-muted">Total</div>
              <div class="fw-bold text-primary">${formatPrice(p.total)}</div>
            </div>
            <div class="col-12 col-md-2 mt-2 mt-md-0 text-md-end">
              <button class="btn btn-outline-primary btn-sm" onclick="verDetalle(${p.id})">
                Ver detalle
              </button>
            </div>
          </div>
        </div>
      </div>
    `).join('');

    // Mostrar detalle del pedido nuevo automáticamente
    if (nuevoId) verDetalle(parseInt(nuevoId));

  } catch (err) {
    lista.innerHTML = `<div class="alert alert-danger">${err.mensaje || 'Error al cargar pedidos.'}</div>`;
  }
}

async function verDetalle(pedidoId) {
  const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
  const body  = document.getElementById('modal-body');
  body.innerHTML = `<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>`;
  modal.show();

  try {
    const p = await apiFetch(`/checkout/pedidos/${pedidoId}`);

    body.innerHTML = `
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <h6 class="mb-0">Pedido #${p.id}</h6>
          <small class="text-muted">${new Date(p.creado_en).toLocaleString('es-MX')}</small>
        </div>
        <span class="badge ${estadoBadge(p.estado)} fs-6">${p.estado}</span>
      </div>
      <table class="table table-sm">
        <thead class="table-light"><tr><th>Producto</th><th class="text-center">Cant.</th><th class="text-end">Subtotal</th></tr></thead>
        <tbody>
          ${p.detalles.map(d => `
            <tr>
              <td>${esc(d.nombre_producto)}<br><small class="text-muted">${formatPrice(d.precio_unitario)} c/u</small></td>
              <td class="text-center">${d.cantidad}</td>
              <td class="text-end">${formatPrice(d.subtotal)}</td>
            </tr>
          `).join('')}
        </tbody>
        <tfoot>
          <tr><td colspan="2" class="text-end text-muted">Subtotal</td><td class="text-end">${formatPrice(p.subtotal)}</td></tr>
          <tr><td colspan="2" class="text-end text-muted">IVA 16%</td><td class="text-end">${formatPrice(p.impuesto)}</td></tr>
          <tr class="fw-bold"><td colspan="2" class="text-end">Total</td><td class="text-end text-primary">${formatPrice(p.total)}</td></tr>
        </tfoot>
      </table>
      ${p.historial.length ? `
        <h6 class="mt-3">Historial</h6>
        <ul class="list-group list-group-flush">
          ${p.historial.map(h => `
            <li class="list-group-item px-0 py-1 small">
              <span class="badge ${estadoBadge(h.estado_nuevo)} me-1">${h.estado_nuevo}</span>
              <span class="text-muted">${new Date(h.cambiado_en).toLocaleString('es-MX')}</span>
              ${h.nota ? `<br><span class="ms-4 text-muted">${esc(h.nota)}</span>` : ''}
            </li>`).join('')}
        </ul>` : ''}
    `;
  } catch (err) {
    body.innerHTML = `<div class="alert alert-danger">${err.mensaje || 'Error.'}</div>`;
  }
}

function estadoBadge(estado) {
  return { pendiente: 'bg-warning text-dark', pagado: 'bg-success', enviado: 'bg-info text-dark',
           entregado: 'bg-primary', cancelado: 'bg-danger' }[estado] || 'bg-secondary';
}

function esc(str) {
  return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

document.addEventListener('DOMContentLoaded', loadPedidos);
