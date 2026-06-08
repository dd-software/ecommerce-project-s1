async function loadCheckout() {
  if (!requireLogin()) return;

  const contenedor = document.getElementById('checkout-items');
  const resumen    = document.getElementById('checkout-resumen');

  try {
    const carrito = await apiFetch('/carrito');

    if (!carrito.items.length) {
      window.location.href = '/carrito.html';
      return;
    }

    if (contenedor) {
      contenedor.innerHTML = carrito.items.map(item => `
        <div class="d-flex align-items-center gap-3 py-2 border-bottom">
          <img src="${item.imagen_url || 'https://picsum.photos/seed/' + item.producto_id + '/60/60'}"
               width="50" height="50" style="object-fit:cover;border-radius:6px" alt="">
          <div class="flex-grow-1">
            <div class="fw-semibold">${esc(item.nombre)}</div>
            <small class="text-muted">${formatPrice(item.precio_unitario)} × ${item.cantidad}</small>
          </div>
          <div class="fw-bold">${formatPrice(item.subtotal)}</div>
        </div>
      `).join('');
    }

    if (resumen) {
      resumen.innerHTML = `
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">Subtotal</span><span>${formatPrice(carrito.subtotal)}</span>
        </div>
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">IVA (16%)</span><span>${formatPrice(carrito.impuesto)}</span>
        </div>
        <hr>
        <div class="d-flex justify-content-between fw-bold fs-5">
          <span>Total</span><span class="text-primary">${formatPrice(carrito.total)}</span>
        </div>
      `;
    }
  } catch (err) {
    if (contenedor) contenedor.innerHTML = `<div class="alert alert-danger">${err.mensaje || 'Error.'}</div>`;
  }
}

async function confirmarPedido() {
  const btn = document.getElementById('btn-confirmar');
  setLoading(btn, true);
  try {
    const pedido = await apiFetch('/checkout', { method: 'POST' });
    updateCartBadge(0);
    // Redirigir a confirmación
    window.location.href = `/pedidos.html?nuevo=${pedido.id}`;
  } catch (err) {
    showToast(err.mensaje || 'Error al procesar el pedido.', 'danger');
    setLoading(btn, false);
  }
}

function esc(str) {
  return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

document.addEventListener('DOMContentLoaded', () => {
  loadCheckout();
  document.getElementById('btn-confirmar')?.addEventListener('click', confirmarPedido);
});
