function getUser() {
  try { return JSON.parse(localStorage.getItem('user')); } catch { return null; }
}
function getToken() { return localStorage.getItem('token'); }
function isLoggedIn() { return !!getToken(); }
function isAdmin() { return getUser()?.rol === 'administrador'; }

function logout() {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  updateCartBadge(0);
  window.location.href = APP_BASE + '/login.html';
}

function updateNavbar() {
  const user = getUser();
  document.querySelectorAll('.nav-guest').forEach(el => el.style.display = user ? 'none' : '');
  document.querySelectorAll('.nav-auth').forEach(el =>  el.style.display = user ? '' : 'none');
  document.querySelectorAll('.nav-admin').forEach(el => el.style.display = (user?.rol === 'administrador') ? '' : 'none');
  document.querySelectorAll('.nav-username').forEach(el => el.textContent = user?.nombre || '');
}

function updateCartBadge(count) {
  document.querySelectorAll('.cart-badge').forEach(el => {
    el.textContent = count;
    el.style.display = count > 0 ? 'inline-block' : 'none';
  });
}

async function loadCartBadge() {
  if (!isLoggedIn()) return;
  try {
    const carrito = await apiFetch('/carrito');
    updateCartBadge(carrito.cantidad_items || 0);
  } catch {}
}

function requireLogin() {
  if (!isLoggedIn()) {
    window.location.href = APP_BASE + '/login.html?redirect=' + encodeURIComponent(window.location.pathname);
    return false;
  }
  return true;
}

function requireAdmin() {
  if (!isLoggedIn() || !isAdmin()) {
    window.location.href = APP_BASE + '/login.html';
    return false;
  }
  return true;
}

document.addEventListener('DOMContentLoaded', () => {
  updateNavbar();
  loadCartBadge();

  const logoutBtn = document.getElementById('logout-btn');
  if (logoutBtn) logoutBtn.addEventListener('click', e => { e.preventDefault(); logout(); });
});
