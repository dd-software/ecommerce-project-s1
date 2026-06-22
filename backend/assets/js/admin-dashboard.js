/**
 * Javascript global para el panel de administración
 */
const AdminApp = {
    jwtToken: null,
    apiBase: '',

    init(token, apiBase) {
        this.jwtToken = token;
        this.apiBase = apiBase;
        
        // Sidebar toggle en móvil
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    },

    /**
     * Petición AJAX autenticada a la API
     */
    async fetch(endpoint, options = {}) {
        const headers = {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${this.jwtToken}`,
            ...options.headers
        };

        try {
            const resp = await fetch(`${this.apiBase}${endpoint}`, { ...options, headers });
            const data = await resp.json();
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    /**
     * Mostrar un toast (notificación)
     */
    toast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        const iconClass = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';

        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white ${bgClass} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center">
                    <i class="bi ${iconClass} me-2 fs-5"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        container.appendChild(toastEl);
        const bsToast = new bootstrap.Toast(toastEl, { delay: 3000 });
        bsToast.show();

        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }
};
