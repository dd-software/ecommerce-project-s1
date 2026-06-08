// Auto-detect app base URL so the project works in any XAMPP subdirectory.
// document.currentScript works for sync scripts; el.src is the resolved absolute URL.
const APP_BASE = (() => {
  const script = document.currentScript ||
    [...document.querySelectorAll('script[src]')].find(el => /\/js\/api\.js/.test(el.src));
  return script ? script.src.replace(/\/js\/api\.js.*$/, '') : '';
})();

const API_BASE = APP_BASE + '/api';

async function apiFetch(path, options = {}) {
  const token = localStorage.getItem('token');
  const headers = {
    'Content-Type': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(options.headers || {}),
  };

  let res;
  try {
    res = await fetch(API_BASE + path, { ...options, headers });
  } catch (err) {
    throw { codigo: 'RED_ERROR', mensaje: 'No se pudo conectar al servidor.' };
  }

  const text = await res.text();
  let data;
  try { data = JSON.parse(text); } catch { data = { mensaje: text }; }

  if (res.status === 401) {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = APP_BASE + '/login.html';
    return;
  }

  if (!res.ok) throw data;
  return data;
}

function showToast(mensaje, tipo = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const id = 'toast-' + Date.now();
  const bg = tipo === 'success' ? 'bg-success' : tipo === 'danger' ? 'bg-danger' : 'bg-warning';
  container.insertAdjacentHTML('beforeend', `
    <div id="${id}" class="toast align-items-center text-white ${bg} border-0" role="alert">
      <div class="d-flex">
        <div class="toast-body">${mensaje}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  `);
  const el = document.getElementById(id);
  const toast = new bootstrap.Toast(el, { delay: 3500 });
  toast.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
}

function formatPrice(n) {
  return '$' + Number(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function setLoading(btn, loading) {
  if (!btn) return;
  btn.disabled = loading;
  btn.dataset.original = btn.dataset.original || btn.innerHTML;
  btn.innerHTML = loading
    ? '<span class="spinner-border spinner-border-sm me-1"></span>Cargando...'
    : btn.dataset.original;
}
