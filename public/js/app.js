/**
 * app.js - Lógica principal de la aplicación
 * Manejo de autenticación, navegación, toast y utilidades
 */

const App = {
    apiBase: window.location.pathname.replace(/\/+$/, '') + '/api',
    token: null,
    user: null,
    cartCount: 0,

    /**
     * Inicializa la aplicación
     */
    init() {
        this.token = localStorage.getItem('uct_auth_token');
        this.user = JSON.parse(localStorage.getItem('uct_user') || 'null');

        this.updateNavbar();
        this.initEventListeners();
        this.loadCartCount();
        if (typeof Auth !== 'undefined') Auth.init();
        this.handlePaymentReturn();
    },

    /**
     * Maneja la vuelta desde MercadoPago. MP redirige a /?pago=ok&pedido=ID&payment_id=...
     * Confirmamos el pago contra MP (vía backend) y mandamos a la página de confirmación.
     */
    async handlePaymentReturn() {
        const qs = new URLSearchParams(location.search);
        const pago = qs.get('pago');
        if (!pago) return;

        const paymentId = qs.get('payment_id') || qs.get('collection_id');
        const pedidoId = qs.get('pedido');
        // Limpiar la query para que un refresh no re-dispare la confirmación.
        history.replaceState({}, '', location.pathname + location.hash);

        if (pago === 'ok' && paymentId && this.token) {
            try {
                await this.fetchAuth(`${this.apiBase}/pagos/confirmar`, {
                    method: 'POST', body: JSON.stringify({ payment_id: paymentId })
                });
            } catch (e) { /* el webhook confirma igual del lado servidor */ }

            if (typeof Carrito !== 'undefined') await Carrito.loadCart();

            if (pedidoId && typeof Checkout !== 'undefined') {
                try {
                    const d = await (await this.fetchAuth(`${this.apiBase}/pedidos/${pedidoId}`)).json();
                    if (d.success) Checkout.lastOrder = {
                        id: d.data.id,
                        total: d.data.total_formateado || this.formatPrice(d.data.total),
                        direccion: d.data.direccion_envio || '',
                        email: this.user?.email || ''
                    };
                } catch (e) { /* confirmacion mostrará el estado igual */ }
            }
            location.hash = '#/confirmacion';
        } else if (pago === 'pending') {
            this.showToast('Tu pago quedó pendiente de confirmación.', 'info');
            location.hash = '#/pedidos';
        } else {
            this.showToast('El pago no se completó con éxito, favor vuelva a intentarlo.', 'error');
            location.hash = '#/carrito';
        }
    },

    /**
     * Actualiza la barra de navegación según estado de autenticación
     */
    updateNavbar() {
        // Un solo dropdown #account: alterna estado invitado / logueado
        const guest = document.getElementById('account-guest');
        const userHead = document.getElementById('account-user');
        const logout = document.getElementById('account-logout');
        const adminLink = document.getElementById('admin-link');
        const greet = document.getElementById('account-greet');
        const greetSm = document.getElementById('account-greet-sm');
        const userNameMenu = document.getElementById('user-name-menu');

        if (this.user) {
            const nombre = this.user.nombre || this.user.email;
            guest?.classList.add('d-none');
            userHead?.classList.remove('d-none');
            logout?.classList.remove('d-none');
            if (greetSm) greetSm.textContent = 'Hola,';
            if (greet) greet.textContent = nombre;
            if (userNameMenu) userNameMenu.textContent = nombre;
            adminLink?.classList.toggle('d-none', this.user.rol !== 'admin');
        } else {
            guest?.classList.remove('d-none');
            userHead?.classList.add('d-none');
            logout?.classList.add('d-none');
            if (greetSm) greetSm.textContent = 'Hola, ingresa';
            if (greet) greet.textContent = 'Mi Cuenta';
            adminLink?.classList.add('d-none');
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

        // Búsqueda global
        const searchForm = document.getElementById('search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const query = document.getElementById('search-input').value.trim();
                // SPA: navegar al catálogo con ?q= en el hash (sin recargar la página)
                const target = '#/catalogo' + (query ? '?q=' + encodeURIComponent(query) : '');
                if (location.hash === target) Router.render();   // re-buscar si ya estamos ahí
                else location.hash = target;
            });
        }
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
        window.location.href = '/';
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
    formatPrice(pesos) {
        return '$' + new Intl.NumberFormat('es-CL').format(Math.round(pesos));
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
            spinner.innerHTML = UI.loader();
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
// App.init() se invoca desde el bootstrap inline de index.html (junto con Router.init()).
