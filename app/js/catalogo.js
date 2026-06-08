let currentPage = 1;
let totalPages  = 1;
const limit     = 12;

async function loadCategorias() {
  try {
    const cats = await apiFetch('/catalogo/categorias');
    const sel  = document.getElementById('filter-categoria');
    if (!sel) return;
    cats.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.id;
      opt.textContent = c.nombre;
      sel.appendChild(opt);
    });
  } catch {}
}

async function loadProductos(page = 1) {
  currentPage = page;

  const q        = document.getElementById('search-input')?.value || '';
  const catId    = document.getElementById('filter-categoria')?.value || '';
  const orden    = document.getElementById('filter-orden')?.value || 'nombre';
  const precioMin = document.getElementById('filter-min')?.value || '';
  const precioMax = document.getElementById('filter-max')?.value || '';

  const params = new URLSearchParams({ page, limit, orden });
  if (q)        params.set('q', q);
  if (catId)    params.set('categoriaId', catId);
  if (precioMin) params.set('precioMin', precioMin);
  if (precioMax) params.set('precioMax', precioMax);

  const grid    = document.getElementById('products-grid');
  const pager   = document.getElementById('pagination');
  const counter = document.getElementById('results-count');

  if (grid) grid.innerHTML = `
    <div class="col-12 text-center py-5">
      <div class="spinner-border text-primary"></div>
    </div>`;

  try {
    const data = await apiFetch('/catalogo/productos?' + params);
    totalPages  = data.total_paginas;

    if (counter) counter.textContent = `${data.total} producto${data.total !== 1 ? 's' : ''}`;

    if (!data.data.length) {
      grid.innerHTML = `
        <div class="col-12 text-center py-5 text-muted">
          <i class="bi bi-search fs-1"></i>
          <p class="mt-3">No se encontraron productos.</p>
        </div>`;
      if (pager) pager.innerHTML = '';
      return;
    }

    grid.innerHTML = data.data.map(p => `
      <div class="col-6 col-md-4 col-lg-3">
        <div class="card product-card h-100 border-0 shadow-sm">
          <a href="/producto.html?id=${p.id}">
            <img src="${p.imagen_url || 'https://picsum.photos/seed/' + p.id + '/400/300'}"
                 class="card-img-top product-img" alt="${esc(p.nombre)}" loading="lazy">
          </a>
          <div class="card-body d-flex flex-column">
            <span class="badge bg-secondary-subtle text-secondary mb-1 small">${esc(p.categoria_nombre)}</span>
            <h6 class="card-title">
              <a href="/producto.html?id=${p.id}" class="text-dark text-decoration-none">${esc(p.nombre)}</a>
            </h6>
            <div class="mt-auto">
              <div class="d-flex align-items-center justify-content-between mt-2">
                <span class="fw-bold text-primary fs-5">${formatPrice(p.precio)}</span>
                ${p.tiene_stock
                  ? `<button class="btn btn-primary btn-sm" onclick="addToCart(${p.id}, this)">
                       <i class="bi bi-cart-plus"></i>
                     </button>`
                  : `<span class="badge bg-danger">Sin stock</span>`}
              </div>
            </div>
          </div>
        </div>
      </div>
    `).join('');

    renderPagination(data.page, data.total_paginas);
  } catch (err) {
    if (grid) grid.innerHTML = `<div class="col-12"><div class="alert alert-danger">${err.mensaje || 'Error al cargar productos.'}</div></div>`;
  }
}

function renderPagination(page, total) {
  const pager = document.getElementById('pagination');
  if (!pager || total <= 1) { if (pager) pager.innerHTML = ''; return; }

  let html = '<nav><ul class="pagination justify-content-center">';
  html += `<li class="page-item ${page <= 1 ? 'disabled' : ''}">
    <a class="page-link" href="#" onclick="loadProductos(${page - 1}); return false;">«</a></li>`;

  const start = Math.max(1, page - 2);
  const end   = Math.min(total, page + 2);
  for (let i = start; i <= end; i++) {
    html += `<li class="page-item ${i === page ? 'active' : ''}">
      <a class="page-link" href="#" onclick="loadProductos(${i}); return false;">${i}</a></li>`;
  }

  html += `<li class="page-item ${page >= total ? 'disabled' : ''}">
    <a class="page-link" href="#" onclick="loadProductos(${page + 1}); return false;">»</a></li>`;
  html += '</ul></nav>';
  pager.innerHTML = html;
}

async function addToCart(productoId, btn) {
  if (!isLoggedIn()) { window.location.href = '/login.html'; return; }
  setLoading(btn, true);
  try {
    const data = await apiFetch('/carrito/items', {
      method: 'POST',
      body: JSON.stringify({ producto_id: productoId, cantidad: 1 }),
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

let searchTimer;
function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadProductos(1), 400);
}

document.addEventListener('DOMContentLoaded', () => {
  loadCategorias();
  loadProductos(1);

  document.getElementById('search-input')?.addEventListener('input', onSearchInput);
  document.getElementById('filter-categoria')?.addEventListener('change', () => loadProductos(1));
  document.getElementById('filter-orden')?.addEventListener('change', () => loadProductos(1));
  document.getElementById('btn-filter')?.addEventListener('click', () => loadProductos(1));
});
