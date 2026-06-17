/**
 * app.js - Lógica principal de la aplicación
 * Manejo de autenticación, navegación, toast y utilidades
 */

const App = {
    apiBase: window.location.href.replace(/\/[^/]*$/, '') + '/api',
    token: null,
    user: null,
    cartCount: 0,

    // Placeholder local (data URI) usado cuando una imagen no carga.
    // Cumple CSP (img-src 'self' data:) sin depender de servicios externos.
    PLACEHOLDER: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='220'%3E%3Crect width='100%25' height='100%25' fill='%23e9ecef'/%3E%3Ctext x='50%25' y='50%25' fill='%236c757d' font-family='sans-serif' font-size='18' text-anchor='middle' dominant-baseline='middle'%3ESin imagen%3C/text%3E%3C/svg%3E",

    _initialized: false,

    /**
     * Inicializa la aplicación (idempotente: evita doble inicialización)
     */
    init() {
        if (this._initialized) return;
        this._initialized = true;

        this.token = localStorage.getItem('uct_auth_token');
        this.user = JSON.parse(localStorage.getItem('uct_user') || 'null');

        this.updateNavbar();
        this.initEventListeners();
        this.initImageFallback();
        this.loadCartCount();
        if (typeof Auth !== 'undefined') Auth.init();
    },

    /**
     * Maneja el fallback de imágenes rotas con un único listener en fase de
     * captura (los eventos 'error' de <img> no propagan, por eso useCapture).
     * Reemplaza el uso de onerror inline, que viola la CSP (script-src 'self').
     */
    initImageFallback() {
        document.addEventListener('error', (e) => {
            const el = e.target;
            if (el && el.tagName === 'IMG' && el.dataset.fallbackApplied !== '1') {
                el.dataset.fallbackApplied = '1';
                el.src = App.PLACEHOLDER;
            }
        }, true);
    },

    /**
     * Actualiza la barra de navegación según estado de autenticación
     */
    updateNavbar() {
        const authLinks = document.getElementById('auth-links');
        const userLinks = document.getElementById('user-links');
        const adminLink = document.getElementById('admin-link');
        const userName = document.getElementById('user-name');
        const cartCount = document.getElementById('cart-count');

        if (this.user) {
            if (authLinks) authLinks.classList.add('d-none');
            if (userLinks) userLinks.classList.remove('d-none');
            if (userName) userName.textContent = this.user.nombre || this.user.email;

            // Mostrar link admin solo si es admin
            if (adminLink) {
                if (this.user.rol === 'admin') {
                    adminLink.classList.remove('d-none');
                } else {
                    adminLink.classList.add('d-none');
                }
            }
        } else {
            if (authLinks) authLinks.classList.remove('d-none');
            if (userLinks) userLinks.classList.add('d-none');
            if (adminLink) adminLink.classList.add('d-none');
        }
    },

    /**
     * Configura event listeners globales
     */
    initEventListeners() {
        // Logout
        const logoutBtn = document.getElementById('btn-logout');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.logout();
            });
        }
        // La búsqueda se maneja en main.js (búsqueda en la misma página, sin recargar)
    },

    /**
     * Carga el contador del carrito
     */
    async loadCartCount() {
        try {
            const sessionId = this.getSessionId();
            const headers = {};
            if (this.token) {
                headers['Authorization'] = `Bearer ${this.token}`;
            }
            if (sessionId) {
                headers['X-Session-Id'] = sessionId;
            }

            const resp = await fetch(`${this.apiBase}/carrito`, { headers });
            const data = await resp.json();

            if (data.success && data.data) {
                this.cartCount = data.data.items ? data.data.items.length : 0;
                this.updateCartBadge();
            }
        } catch (e) {
            // Silencioso
        }
    },

    /**
     * Actualiza el badge del carrito en el navbar
     */
    updateCartBadge() {
        const badge = document.getElementById('cart-count');
        if (badge) {
            badge.textContent = this.cartCount;
            badge.classList.toggle('d-none', this.cartCount === 0);
        }
    },

    /**
     * Cierra sesión
     */
    async logout() {
        try {
            await fetch(`${this.apiBase}/auth/logout`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${this.token}` }
            });
        } catch (e) {
            // Silencioso
        }

        localStorage.removeItem('uct_auth_token');
        localStorage.removeItem('uct_user');
        this.token = null;
        this.user = null;
        // Recargar la página actual (sin query) en lugar de ir a la raíz del servidor
        window.location.href = window.location.href.split('?')[0];
    },

    /**
     * Guarda datos de autenticación
     */
    setAuth(token, user) {
        this.token = token;
        this.user = user;
        localStorage.setItem('uct_auth_token', token);
        localStorage.setItem('uct_user', JSON.stringify(user));
        this.updateNavbar();
        this.loadCartCount();
    },

    /**
     * Obtiene o genera un session ID para visitantes
     */
    getSessionId() {
        let sessionId = localStorage.getItem('uct_session_id');
        if (!sessionId) {
            sessionId = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('uct_session_id', sessionId);
        }
        return sessionId;
    },

    /**
     * Muestra un toast de notificación
     */
    showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) {
            // Crear container si no existe
            const div = document.createElement('div');
            div.id = 'toast-container';
            div.className = 'toast-container';
            document.body.appendChild(div);
        }

        const toastEl = document.createElement('div');
        const bgClass = type === 'success' ? 'bg-success' :
                        type === 'error' ? 'bg-danger' :
                        type === 'warning' ? 'bg-warning text-dark' : 'bg-info';

        toastEl.className = `toast align-items-center text-white ${bgClass} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        document.getElementById('toast-container').appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    },

    /**
     * Formatea un precio en centavos a string
     */
    formatPrice(amount) {
        return '$' + new Intl.NumberFormat('es-CL').format(Math.round(amount));
    },

    /**
     * Muestra/oculta spinner de carga
     */
    showLoading() {
        let spinner = document.getElementById('loading-spinner');
        if (!spinner) {
            spinner = document.createElement('div');
            spinner.id = 'loading-spinner';
            spinner.className = 'spinner-overlay';
            spinner.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>';
            document.body.appendChild(spinner);
        }
        spinner.style.display = 'flex';
    },

    hideLoading() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.style.display = 'none';
        }
    },

    /**
     * Realiza una petición fetch con headers de auth
     */
    async fetchAuth(url, options = {}) {
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        const sessionId = this.getSessionId();
        if (sessionId) {
            headers['X-Session-Id'] = sessionId;
        }

        return fetch(url, { ...options, headers });
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => App.init());
