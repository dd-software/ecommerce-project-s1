async function loadProducto() {
  const params = new URLSearchParams(window.location.search);
  const id     = parseInt(params.get('id'));
  if (!id) { window.location.href = '/'; return; }

  const contenedor = document.getElementById('producto-detalle');
  if (contenedor) contenedor.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>`;

  try {
    const p = await apiFetch(`/catalogo/productos/${id}`);

    document.title = p.nombre + ' — Tienda';

    const imgSrc = p.imagenes?.[0] || `https://picsum.photos/seed/${id}/600/400`;

    if (contenedor) {
      contenedor.innerHTML = `
        <div class="row g-4">
          <div class="col-md-6">
            <div class="position-relative">
              <img id="main-img" src="${imgSrc}" class="img-fluid rounded shadow-sm w-100"
                   style="max-height:420px;object-fit:cover" alt="${esc(p.nombre)}">
            </div>
            ${p.imagenes?.length > 1 ? `
              <div class="d-flex gap-2 mt-2">
                ${p.imagenes.map((url, i) => `
                  <img src="${url}" class="img-thumbnail" width="70" height="70"
                       style="cursor:pointer;object-fit:cover"
                       onclick="document.getElementById('main-img').src='${url}'" alt="">
                `).join('')}
              </div>` : ''}
          </div>
          <div class="col-md-6">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Catálogo</a></li>
                <li class="breadcrumb-item text-muted">${esc(p.categoria_nombre)}</li>
              </ol>
            </nav>
            <h2 class="fw-bold">${esc(p.nombre)}</h2>
            <div class="fs-2 fw-bold text-primary mb-3">${formatPrice(p.precio)}</div>
            <p class="text-muted">${esc(p.descripcion || '')}</p>

            <div class="mb-3">
              ${p.tiene_stock
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle">
                     <i class="bi bi-check-circle-fill me-1"></i>En stock (${p.stock} disponibles)
                   </span>`
                : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                     <i class="bi bi-x-circle-fill me-1"></i>Sin stock
                   </span>`}
            </div>

            <div class="d-flex align-items-center gap-2 mb-4">
              <label class="form-label mb-0 me-1">Cantidad:</label>
              <input type="number" id="qty-input" class="form-control" style="width:80px"
                     value="1" min="1" max="${p.stock}">
              <button id="btn-agregar" class="btn btn-primary" ${!p.tiene_stock ? 'disabled' : ''}
                      onclick="addToCartDetail(${p.id})">
                <i class="bi bi-cart-plus me-1"></i>Agregar al carrito
              </button>
            </div>
            <a href="/" class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-arrow-left me-1"></i>Volver al catálogo
            </a>
          </div>
        </div>
      `;
    }
  } catch (err) {
    if (contenedor) contenedor.innerHTML = `
      <div class="text-center py-5">
        <div class="alert alert-warning d-inline-block">
          ${err.mensaje || 'Producto no encontrado.'}
          <br><a href="/" class="btn btn-sm btn-primary mt-2">Ir al catálogo</a>
        </div>
      </div>`;
  }
}

async function addToCartDetail(productoId) {
  if (!isLoggedIn()) { window.location.href = '/login.html'; return; }
  const btn      = document.getElementById('btn-agregar');
  const cantidad = parseInt(document.getElementById('qty-input')?.value || 1);
  setLoading(btn, true);
  try {
    const data = await apiFetch('/carrito/items', {
      method: 'POST',
      body: JSON.stringify({ producto_id: productoId, cantidad }),
    });
    updateCartBadge(data.cantidad_items);
    showToast('Producto agregado al carrito.');
  } catch (err) {
    showToast(err.mensaje || 'Error al agregar.', 'danger');
  } finally {
    setLoading(btn, false);
  }
}

function esc(str) {
  return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

document.addEventListener('DOMContentLoaded', loadProducto);
