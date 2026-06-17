'use strict';

// ── Config ──────────────────────────────────────────
const API = (() => {
  const p = window.location.pathname.replace(/\/store\/.*$/, '');
  return window.location.origin + p;
})();

// Placeholder local (data URI) para imágenes que no cargan. Cumple CSP (img-src data:).
const App_PLACEHOLDER = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Crect width='100%25' height='100%25' fill='%23e9ecef'/%3E%3Ctext x='50%25' y='50%25' fill='%236c757d' font-family='sans-serif' font-size='16' text-anchor='middle' dominant-baseline='middle'%3ESin imagen%3C/text%3E%3C/svg%3E";

// ── State ────────────────────────────────────────────
const S = {
  products: [],
  categories: [],
  cart: null,
  user: null,
  token: null,
  filters: { categoryId: null, search: '', sort: '' },
  productDetail: null,
};

function loadSession() {
  try {
    S.token = localStorage.getItem('ts_token');
    S.user  = JSON.parse(localStorage.getItem('ts_user') || 'null');
  } catch {}
}
function saveSession(user, token) {
  S.user  = user;  S.token = token;
  localStorage.setItem('ts_token', token);
  localStorage.setItem('ts_user', JSON.stringify(user));
}
function clearSession() {
  S.user = null; S.token = null;
  localStorage.removeItem('ts_token');
  localStorage.removeItem('ts_user');
}

// ── API helpers ──────────────────────────────────────
async function apiFetch(path, opts = {}) {
  const headers = { 'Content-Type': 'application/json', ...(opts.headers || {}) };
  if (S.token) headers['Authorization'] = `Bearer ${S.token}`;
  const res = await fetch(`${API}${path}`, { ...opts, headers, credentials: 'include' });
  const json = await res.json().catch(() => ({}));
  return { ok: res.ok, status: res.status, data: json };
}

// ── Formatting ───────────────────────────────────────
function fmt(n) {
  return '$' + Number(n).toLocaleString('es-CL');
}

// ── Toast ────────────────────────────────────────────
const toastEl = document.getElementById('toastContainer');
function toast(msg, type = 'info') {
  const icons = { success: '✓', error: '✗', info: 'ℹ' };
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<span>${icons[type] || '•'}</span><span>${msg}</span>`;
  toastEl.appendChild(t);
  setTimeout(() => {
    t.style.animation = 'slideOut .3s ease forwards';
    setTimeout(() => t.remove(), 300);
  }, 3000);
}

// ── Overlay helpers ──────────────────────────────────
const overlay = document.getElementById('overlay');
function openOverlay()  { overlay.classList.add('open'); }
function closeOverlay() { overlay.classList.remove('open'); }
function closeAll()     { closeCart(); closeLogin(); closeProductModal(); }

// ── Categories ───────────────────────────────────────
const CATEGORY_ICONS = {
  'Computación': '💻', 'Smartphones': '📱', 'Accesorios': '🎧',
  'Audio': '🎵', 'Ropa Hombre': '👕', 'Ropa Mujer': '👗',
  'Zapatillas': '👟', 'Deporte': '🏋️', 'Hogar': '🏠',
  'Libros': '📚', 'Electrónica': '⚡', 'Gaming': '🎮',
  'default': '📦'
};
function catIcon(name) {
  for (const [k, v] of Object.entries(CATEGORY_ICONS)) {
    if (name && name.toLowerCase().includes(k.toLowerCase())) return v;
  }
  return CATEGORY_ICONS.default;
}

async function loadCategories() {
  const { ok, data } = await apiFetch('/api/catalogo/categorias');
  if (!ok) return;
  S.categories = data.data || [];
  renderNavCategories();
  renderCatCards();
}

function renderNavCategories() {
  const nav = document.getElementById('navList');
  nav.innerHTML = `<li><a href="#" class="${S.filters.categoryId === null ? 'active' : ''}" data-action="filter-cat">Todos</a></li>`;
  S.categories.forEach(c => {
    const li = document.createElement('li');
    li.innerHTML = `<a href="#" class="${S.filters.categoryId === c.id ? 'active' : ''}"
      data-action="filter-cat" data-cat="${c.id}">${escHtml(c.nombre)}</a>`;
    nav.appendChild(li);
  });
}

function renderCatCards() {
  const grid = document.getElementById('catsGrid');
  grid.innerHTML = '';
  S.categories.slice(0, 10).forEach(c => {
    const div = document.createElement('div');
    div.className = `cat-card${S.filters.categoryId === c.id ? ' active' : ''}`;
    div.innerHTML = `<span class="cat-card__icon">${catIcon(c.nombre)}</span>
                     <span class="cat-card__name">${c.nombre}</span>`;
    div.onclick = () => filterCat(c.id);
    grid.appendChild(div);
  });
}

function filterCat(id) {
  S.filters.categoryId = id;
  S.filters.search = '';
  document.getElementById('searchInput').value = '';
  document.getElementById('productsTitle').textContent = id
    ? (S.categories.find(c => c.id === id)?.nombre || 'Productos')
    : 'Productos destacados';
  renderNavCategories();
  renderCatCards();
  loadProducts();
  document.getElementById('products').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ── Products ─────────────────────────────────────────
async function loadProducts() {
  showLoading(true);
  const params = new URLSearchParams();
  if (S.filters.categoryId) params.set('categoria', S.filters.categoryId);
  if (S.filters.search)     params.set('q', S.filters.search);
  const qs = params.toString() ? '?' + params : '';
  const { ok, data } = await apiFetch(`/api/catalogo${qs}`);
  showLoading(false);
  if (!ok) { toast('Error al cargar productos', 'error'); return; }
  let products = data.data || [];

  if (S.filters.sort === 'precio_asc')  products.sort((a,b) => a.precio - b.precio);
  if (S.filters.sort === 'precio_desc') products.sort((a,b) => b.precio - a.precio);
  if (S.filters.sort === 'nombre')      products.sort((a,b) => a.nombre.localeCompare(b.nombre, 'es'));

  S.products = products;
  renderProducts(products);
}

function showLoading(show) {
  document.getElementById('loading').classList.toggle('hidden', !show);
  document.getElementById('productsGrid').style.display = show ? 'none' : '';
}

function renderProducts(products) {
  const grid = document.getElementById('productsGrid');
  const empty = document.getElementById('emptyState');
  if (!products.length) { grid.innerHTML = ''; empty.style.display = ''; return; }
  empty.style.display = 'none';
  grid.innerHTML = products.map(p => productCard(p)).join('');
}

function productCard(p) {
  const inStock = p.stock > 0;
  const lowStock = p.stock > 0 && p.stock <= 5;
  const stockHtml = inStock
    ? `<span class="product-card__stock${lowStock ? ' low' : ''}">
         ${lowStock ? '⚠ Últimas ' + p.stock + ' unidades' : '✓ Stock disponible'}
       </span>`
    : `<span class="product-card__stock out">✗ Sin stock</span>`;

  const img = p.imagen_url
    ? `<img src="${p.imagen_url}" alt="${escHtml(p.nombre)}" loading="lazy">`
    : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:48px">${catIcon(p.categoria_nombre)}</div>`;

  return `
    <div class="product-card" data-action="open-product" data-id="${p.id}">
      <div class="product-card__img">
        ${img}
        ${!inStock ? '<span class="product-card__badge out">Sin stock</span>' : ''}
      </div>
      <div class="product-card__body">
        <span class="product-card__cat">${escHtml(p.categoria_nombre || '')}</span>
        <span class="product-card__name">${escHtml(p.nombre)}</span>
        ${stockHtml}
        <span class="product-card__price">${fmt(p.precio)}</span>
      </div>
      <div class="product-card__footer">
        <button class="btn-add" ${!inStock ? 'disabled' : ''}
          data-action="add-to-cart" data-id="${p.id}">
          <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
          ${inStock ? 'Agregar al carrito' : 'Sin stock'}
        </button>
      </div>
    </div>`;
}

// ── Product Detail Modal ─────────────────────────────
const productModal = document.getElementById('productModal');

async function openProduct(id) {
  const { ok, data } = await apiFetch(`/api/catalogo/${id}`);
  if (!ok) { toast('Error al cargar producto', 'error'); return; }
  const p = data.data;
  S.productDetail = p;

  document.getElementById('pm_cat').textContent   = p.categoria_nombre || '';
  document.getElementById('pm_name').textContent  = p.nombre;
  document.getElementById('pm_price').textContent = fmt(p.precio);
  document.getElementById('pm_desc').textContent  = p.descripcion || '';
  document.getElementById('pm_qty').value         = 1;

  const stockEl = document.getElementById('pm_stock');
  const addBtn  = document.getElementById('pm_add');
  if (p.stock > 0) {
    stockEl.textContent  = p.stock <= 5 ? `⚠ Solo quedan ${p.stock} unidades` : '✓ En stock';
    stockEl.className    = `product-modal__stock${p.stock <= 5 ? ' low' : ''}`;
    addBtn.disabled      = false;
  } else {
    stockEl.textContent  = '✗ Sin stock';
    stockEl.className    = 'product-modal__stock out';
    addBtn.disabled      = true;
  }

  const imgEl = document.getElementById('pm_img');
  imgEl.style.display = '';
  imgEl.src = p.imagen_url || App_PLACEHOLDER;
  imgEl.alt = p.nombre;

  productModal.classList.add('open');
  openOverlay();
}

function closeProductModal() {
  productModal.classList.remove('open');
  closeOverlay();
}

document.getElementById('pm_add').addEventListener('click', () => {
  if (!S.productDetail) return;
  const qty = parseInt(document.getElementById('pm_qty').value) || 1;
  addToCart(S.productDetail.id, qty);
  closeProductModal();
});

function adjustQty(delta) {
  const inp = document.getElementById('pm_qty');
  const max = S.productDetail?.stock || 99;
  inp.value = Math.max(1, Math.min(max, (parseInt(inp.value) || 1) + delta));
}

// ── Cart ─────────────────────────────────────────────
const cartSidebar = document.getElementById('cartSidebar');

async function loadCart() {
  const { ok, data } = await apiFetch('/api/carrito');
  if (!ok) return;
  S.cart = data.data;
  updateCartBadge();
}

function updateCartBadge() {
  const count = S.cart?.items?.reduce((s, i) => s + i.cantidad, 0) || 0;
  const el = document.getElementById('cartCount');
  el.textContent = count;
  el.classList.toggle('hidden', count === 0);
}

function openCart() {
  renderCart();
  cartSidebar.classList.add('open');
  openOverlay();
}
function closeCart() {
  cartSidebar.classList.remove('open');
  if (!document.getElementById('loginModal').classList.contains('open') &&
      !productModal.classList.contains('open')) {
    closeOverlay();
  }
}

function renderCart() {
  const items = S.cart?.items || [];
  const el = document.getElementById('cartItems');
  const footer = document.getElementById('cartFooter');

  if (!items.length) {
    el.innerHTML = `<div class="cart-empty">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/>
        <path d="M16 10a4 4 0 01-8 0"/>
      </svg>
      <p>Tu carrito está vacío</p>
    </div>`;
    footer.innerHTML = '';
    return;
  }

  el.innerHTML = items.map(i => `
    <div class="cart-item">
      <img class="cart-item__img" src="${i.imagen_url || App_PLACEHOLDER}" alt="${escHtml(i.nombre)}">
      <div class="cart-item__info">
        <div class="cart-item__name">${escHtml(i.nombre)}</div>
        <div class="cart-item__price">${fmt(i.precio_unitario)}</div>
        <div class="cart-item__qty">
          <button class="qty-btn" data-action="update-cart-item" data-id="${i.id}" data-qty="${i.cantidad - 1}">−</button>
          <span class="qty-val">${i.cantidad}</span>
          <button class="qty-btn" data-action="update-cart-item" data-id="${i.id}" data-qty="${i.cantidad + 1}">+</button>
        </div>
      </div>
      <button class="cart-item__remove" data-action="remove-cart-item" data-id="${i.id}">✕</button>
    </div>`).join('');

  const total = S.cart?.total_con_iva ?? items.reduce((s, i) => s + i.precio_unitario * i.cantidad, 0);
  footer.innerHTML = `
    <div class="cart-total"><span>Total</span><span>${S.cart?.total_formateado || fmt(total)}</span></div>
    <button class="btn-checkout" data-action="checkout">Ir al checkout →</button>
    <button data-action="clear-cart" style="text-align:center;color:var(--gray2);font-size:12px;width:100%">Vaciar carrito</button>`;
}

async function addToCart(productId, cantidad = 1) {
  const { ok, data } = await apiFetch('/api/carrito', {
    method: 'POST', body: JSON.stringify({ producto_id: productId, cantidad })
  });
  if (!ok) { toast(data.error?.message || 'Error al agregar', 'error'); return; }
  S.cart = data.data;
  updateCartBadge();
  toast('Producto agregado al carrito', 'success');
}

async function updateCartItem(detalleId, cantidad) {
  if (cantidad < 1) { removeCartItem(detalleId); return; }
  const { ok, data } = await apiFetch(`/api/carrito/${detalleId}`, {
    method: 'PATCH', body: JSON.stringify({ cantidad })
  });
  if (!ok) { toast('Error al actualizar', 'error'); return; }
  S.cart = data.data;
  updateCartBadge();
  renderCart();
}

async function removeCartItem(detalleId) {
  const { ok, data } = await apiFetch(`/api/carrito/${detalleId}`, { method: 'DELETE' });
  if (!ok) { toast('Error al eliminar', 'error'); return; }
  S.cart = data.data;
  updateCartBadge();
  renderCart();
}

async function clearCart() {
  const { ok, data } = await apiFetch('/api/carrito', { method: 'DELETE' });
  if (!ok) { toast('Error al vaciar carrito', 'error'); return; }
  S.cart = data.data;
  updateCartBadge();
  renderCart();
}

function handleCheckout() {
  if (!S.user) { closeCart(); openLogin(); toast('Inicia sesión para continuar', 'info'); return; }
  toast('Funcionalidad de checkout próximamente', 'info');
}

// ── Auth ─────────────────────────────────────────────
const loginModal = document.getElementById('loginModal');

function openLogin()  { loginModal.classList.add('open'); openOverlay(); }
function closeLogin() { loginModal.classList.remove('open'); if (!cartSidebar.classList.contains('open') && !productModal.classList.contains('open')) closeOverlay(); }

function showTab(tab) {
  document.getElementById('loginForm').style.display    = tab === 'login' ? '' : 'none';
  document.getElementById('registerForm').style.display = tab === 'register' ? '' : 'none';
  document.querySelectorAll('.tab').forEach((t, i) => t.classList.toggle('active', (i === 0) === (tab === 'login')));
}

async function handleLogin(e) {
  e.preventDefault();
  const btn = e.target.querySelector('.btn-submit');
  const email = document.getElementById('loginEmail').value.trim();
  const pass  = document.getElementById('loginPass').value;
  const errEl = document.getElementById('loginError');
  errEl.classList.remove('show');
  btn.disabled = true; btn.textContent = 'Ingresando...';
  const { ok, data } = await apiFetch('/api/auth/login', {
    method: 'POST', body: JSON.stringify({ email, password: pass })
  });
  btn.disabled = false; btn.textContent = 'Iniciar sesión';
  if (!ok) { errEl.textContent = data.error?.message || 'Credenciales incorrectas'; errEl.classList.add('show'); return; }
  saveSession(data.data.usuario, data.data.token);
  updateUserUI();
  closeLogin();
  toast(`Bienvenido, ${S.user.nombre}`, 'success');
  if (S.cart?.items?.length) apiFetch('/api/carrito/sincronizar', { method: 'POST' });
}

async function handleRegister(e) {
  e.preventDefault();
  const btn  = e.target.querySelector('.btn-submit');
  const errEl = document.getElementById('registerError');
  errEl.classList.remove('show');
  const body = {
    nombre:    document.getElementById('regNombre').value.trim(),
    apellido:  document.getElementById('regApellido').value.trim(),
    email:     document.getElementById('regEmail').value.trim(),
    password:  document.getElementById('regPass').value,
  };
  btn.disabled = true; btn.textContent = 'Registrando...';
  const { ok, data } = await apiFetch('/api/auth/registro', {
    method: 'POST', body: JSON.stringify(body)
  });
  btn.disabled = false; btn.textContent = 'Crear cuenta';
  if (!ok) { errEl.textContent = data.error?.message || 'Error al registrarse'; errEl.classList.add('show'); return; }
  saveSession(data.data.usuario, data.data.token);
  updateUserUI();
  closeLogin();
  toast(`Cuenta creada. Bienvenido, ${S.user.nombre}!`, 'success');
}

async function handleLogout() {
  await apiFetch('/api/auth/logout', { method: 'POST' });
  clearSession();
  S.cart = null;
  updateUserUI();
  updateCartBadge();
  closeUserMenu();
  toast('Sesión cerrada', 'info');
}

function updateUserUI() {
  const label = document.getElementById('userLabel');
  const menu  = document.getElementById('userMenu');
  if (S.user) {
    label.textContent = S.user.nombre.split(' ')[0];
    menu.innerHTML = `
      <div class="user-name" style="padding:12px 16px 4px">${escHtml(S.user.nombre)}</div>
      <hr>
      <a href="#" data-action="close-user-menu">Mi perfil</a>
      <a href="#" data-action="close-user-menu">Mis pedidos</a>
      ${S.user.rol === 'admin' ? '<a href="#" data-action="close-user-menu">Administración</a>' : ''}
      <hr>
      <button data-action="logout">Cerrar sesión</button>`;
  } else {
    label.textContent = 'Iniciar sesión';
    menu.innerHTML = `
      <a href="#" data-action="open-login">Iniciar sesión</a>
      <a href="#" data-action="open-register">Crear cuenta</a>`;
  }
}

const userMenu = document.getElementById('userMenu');
function toggleUserMenu() {
  if (userMenu.classList.contains('open')) closeUserMenu();
  else { loadCart(); userMenu.classList.add('open'); }
}
function closeUserMenu() { userMenu.classList.remove('open'); }
document.addEventListener('click', e => {
  if (!e.target.closest('#btnUser') && !e.target.closest('#userMenu')) closeUserMenu();
});

// ── Search ───────────────────────────────────────────
let searchTimer;
document.getElementById('searchInput').addEventListener('input', e => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    S.filters.search = e.target.value.trim();
    S.filters.categoryId = null;
    document.getElementById('productsTitle').textContent = S.filters.search
      ? `Resultados para "${S.filters.search}"`
      : 'Productos destacados';
    renderNavCategories();
    renderCatCards();
    loadProducts();
  }, 400);
});

document.getElementById('searchInput').addEventListener('keydown', e => {
  if (e.key === 'Enter') { clearTimeout(searchTimer); S.filters.search = e.target.value.trim(); loadProducts(); }
});

document.querySelector('.search-btn').addEventListener('click', () => {
  S.filters.search = document.getElementById('searchInput').value.trim();
  loadProducts();
});

// ── Sort ─────────────────────────────────────────────
document.getElementById('sortSelect').addEventListener('change', e => {
  S.filters.sort = e.target.value;
  renderProducts(S.products);
});

// ── Delegación de eventos (reemplaza onclick inline; cumple CSP) ──────
document.addEventListener('click', (e) => {
  const el = e.target.closest('[data-action]');
  if (!el) return;
  const action = el.dataset.action;
  const id = el.dataset.id ? parseInt(el.dataset.id, 10) : null;

  // Evitar el salto de los enlaces href="#"
  if (el.tagName === 'A' || el.dataset.action) e.preventDefault();

  switch (action) {
    case 'filter-cat':          filterCat(el.dataset.cat ? parseInt(el.dataset.cat, 10) : null); break;
    case 'scroll-products':     document.getElementById('products').scrollIntoView({ behavior: 'smooth' }); break;
    case 'toggle-user':         toggleUserMenu(); break;
    case 'open-cart':           openCart(); break;
    case 'close-cart':          closeCart(); break;
    case 'close-all':           closeAll(); break;
    case 'close-login':         closeLogin(); break;
    case 'show-tab':            showTab(el.dataset.tab); break;
    case 'close-product-modal': closeProductModal(); break;
    case 'adjust-qty':          adjustQty(parseInt(el.dataset.delta, 10)); break;
    case 'open-product':        openProduct(id); break;
    case 'add-to-cart':         e.stopPropagation(); addToCart(id); break;
    case 'update-cart-item':    updateCartItem(id, parseInt(el.dataset.qty, 10)); break;
    case 'remove-cart-item':    removeCartItem(id); break;
    case 'checkout':            handleCheckout(); break;
    case 'clear-cart':          clearCart(); break;
    case 'logout':              handleLogout(); break;
    case 'close-user-menu':     closeUserMenu(); break;
    case 'open-login':          openLogin(); closeUserMenu(); break;
    case 'open-register':       showTab('register'); openLogin(); closeUserMenu(); break;
  }
});

// ── Formularios (reemplaza onsubmit inline) ──────────
document.getElementById('loginForm').addEventListener('submit', handleLogin);
document.getElementById('registerForm').addEventListener('submit', handleRegister);

// ── Fallback global de imágenes rotas (captura: 'error' de img no propaga) ──
document.addEventListener('error', (e) => {
  const el = e.target;
  if (el && el.tagName === 'IMG' && el.dataset.fb !== '1') {
    el.dataset.fb = '1';
    el.src = App_PLACEHOLDER;
  }
}, true);

// ── Utils ────────────────────────────────────────────
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Init ─────────────────────────────────────────────
async function init() {
  loadSession();
  updateUserUI();
  await Promise.all([loadCategories(), loadProducts(), loadCart()]);
}

init();
