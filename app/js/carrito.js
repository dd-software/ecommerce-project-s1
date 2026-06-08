async function loadCarrito() {
  if (!requireLogin()) return;

  const contenedor = document.getElementById('carrito-contenido');
  const resumen    = document.getElementById('carrito-resumen');
  if (contenedor) contenedor.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>`;

  try {
    const carrito = await apiFetch('/carrito');

    if (!carrito.items.length) {
      contenedor.innerHTML = `
        <div class="text-center py-5 text-muted">
          <i class="bi bi-cart-x" style="font-size:4rem"></i>
          <h4 class="mt-3">Tu carrito está vacío</h4>
          <a href="/" class="btn btn-primary mt-3">Ir al catálogo</a>
        </div>`;
      if (resumen) resumen.innerHTML = '';
      return;
    }

    contenedor.innerHTML = `
      <div class="table-responsive">
        <table class="table align-middle">
          <thead class="table-light">
            <tr>
              <th>Producto</th>
              <th class="text-center" style="width:130px">Cantidad</th>
              <th class="text-end" style="width:110px">Precio</th>
              <th class="text-end" style="width:110px">Subtotal</th>
              <th style="width:50px"></th>
            </tr>
          </thead>
          <tbody id="cart-rows">
            ${carrito.items.map(item => itemRow(item)).join('')}
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-2">
        <button class="btn btn-outline-danger btn-sm" onclick="vaciarCarrito()">
          <i class="bi bi-trash"></i> Vaciar carrito
        </button>
      </div>
    `;

    renderResumen(carrito);
  } catch (err) {
    contenedor.innerHTML = `<div class="alert alert-danger">${err.mensaje || 'Error al cargar el carrito.'}</div>`;
  }
}

function itemRow(item) {
  return `
    <tr id="item-row-${item.id}">
      <td>
        <div class="d-flex align-items-center gap-3">
          <img src="${item.imagen_url || 'https://picsum.photos/seed/' + item.producto_id + '/80/80'}"
               width="60" height="60" style="object-fit:cover;border-radius:8px" alt="">
          <a href="/producto.html?id=${item.producto_id}" class="text-dark fw-semibold text-decoration-none">
            ${esc(item.nombre)}
          </a>
        </div>
      </td>
      <td class="text-center">
        <div class="input-group input-group-sm" style="width:110px;margin:auto">
          <button class="btn btn-outline-secondary" onclick="cambiarCantidad(${item.id}, ${item.cantidad - 1})">−</button>
          <input type="number" class="form-control text-center" value="${item.cantidad}" min="0"
                 onchange="cambiarCantidad(${item.id}, parseInt(this.value))" style="width:40px">
          <button class="btn btn-outline-secondary" onclick="cambiarCantidad(${item.id}, ${item.cantidad + 1})">+</button>
        </div>
      </td>
      <td class="text-end">${formatPrice(item.precio_unitario)}</td>
      <td class="text-end fw-bold">${formatPrice(item.subtotal)}</td>
      <td class="text-end">
        <button class="btn btn-link text-danger p-0" title="Eliminar" onclick="eliminarItem(${item.id})">
          <i class="bi bi-x-lg"></i>
        </button>
      </td>
    </tr>`;
}

function renderResumen(carrito) {
  const resumen = document.getElementById('carrito-resumen');
  if (!resumen) return;
  resumen.innerHTML = `
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Resumen del pedido</h5>
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">Subtotal (${carrito.cantidad_items} art.)</span>
          <span>${formatPrice(carrito.subtotal)}</span>
        </div>
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">IVA (16%)</span>
          <span>${formatPrice(carrito.impuesto)}</span>
        </div>
        <hr>
        <div class="d-flex justify-content-between fw-bold fs-5">
          <span>Total</span>
          <span class="text-primary">${formatPrice(carrito.total)}</span>
        </div>
        <a href="/checkout.html" class="btn btn-primary w-100 mt-3">
          Proceder al pago <i class="bi bi-arrow-right"></i>
        </a>
        <a href="/" class="btn btn-outline-secondary w-100 mt-2">Seguir comprando</a>
      </div>
    </div>
  `;
}

async function cambiarCantidad(itemId, nuevaCantidad) {
  if (nuevaCantidad < 0) return;
  try {
    const carrito = await apiFetch(`/carrito/items/${itemId}`, {
      method: 'PUT',
      body: JSON.stringify({ cantidad: nuevaCantidad }),
    });
    updateCartBadge(carrito.cantidad_items);
    loadCarrito();
  } catch (err) {
    showToast(err.mensaje || 'Error al actualizar.', 'danger');
  }
}

async function eliminarItem(itemId) {
  try {
    const carrito = await apiFetch(`/carrito/items/${itemId}`, { method: 'DELETE' });
    updateCartBadge(carrito.cantidad_items);
    showToast('Producto eliminado del carrito.', 'warning');
    loadCarrito();
  } catch (err) {
    showToast(err.mensaje || 'Error al eliminar.', 'danger');
  }
}

async function vaciarCarrito() {
  if (!confirm('¿Vaciar todo el carrito?')) return;
  try {
    await apiFetch('/carrito', { method: 'DELETE' });
    updateCartBadge(0);
    showToast('Carrito vaciado.', 'warning');
    loadCarrito();
  } catch (err) {
    showToast(err.mensaje || 'Error.', 'danger');
  }
}

function esc(str) {
  return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

document.addEventListener('DOMContentLoaded', loadCarrito);
