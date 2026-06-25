const ui = {
    showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) return;
        
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-bg-${type} border-0 mb-2`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body fw-semibold">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
        
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    },
    
    renderHeader() {
        const headerContainer = document.getElementById('main-header');
        if (!headerContainer) return;
        
        const user = api.getUser();
        
        // Determinar qué links mostrar según sesión
        let accountLinksHtml = '';
        if (user) {
            let adminLink = user.role === 'admin' ? `<li><a class="dropdown-item text-danger fw-bold" href="admin.html">Dashboard Admin</a></li>` : '';
            accountLinksHtml = `
                <div class="dropdown">
                    <a href="#" class="nav-icon-link dropdown-toggle text-decoration-none" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <span>Hola, ${user.first_name}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        ${adminLink}
                        <li><a class="dropdown-item" href="mis-compras.html"><i class="bi bi-box-seam me-2"></i>Mis Compras</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-muted" href="#" onclick="logout()"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </div>
            `;
        } else {
            accountLinksHtml = `
                <a href="login.html" class="nav-icon-link">
                    <i class="bi bi-person"></i>
                    <span>Iniciar Sesión</span>
                </a>
            `;
        }

        headerContainer.innerHTML = `
            <!-- Top Banner -->
            <div class="top-banner">
                ENVÍO GRATIS en productos seleccionados sobre $50.000 🚚
            </div>

            <!-- Main Header -->
            <div class="main-header">
                <div class="container d-flex align-items-center justify-content-between">
                    <!-- Logo -->
                    <a href="index.html" class="text-decoration-none">
                        <img src="img/logo.png" alt="UCT Logo" class="brand-logo" onerror="this.outerHTML='<h3 class=\\'fw-bold text-uct-blue mb-0\\'>UCT E-commerce</h3>'">
                    </a>

                    <!-- Search Bar -->
                    <div class="flex-grow-1 mx-4 mx-lg-5 d-none d-md-block">
                        <div class="search-container">
                            <input type="text" class="form-control search-input" placeholder="¿Qué estás buscando?">
                            <button class="search-btn"><i class="bi bi-search"></i></button>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex align-items-center gap-4">
                        ${accountLinksHtml}
                        <a href="cart.html" class="nav-icon-link position-relative">
                            <i class="bi bi-cart3"></i>
                            <span>Carro</span>
                            <!-- <span class="cart-badge">0</span> -->
                        </a>
                    </div>
                </div>
            </div>
        `;

        // Conectar buscador
        const searchInput = headerContainer.querySelector('.search-input');
        const searchBtn   = headerContainer.querySelector('.search-btn');
        const doSearch    = () => {
            const q = (searchInput?.value ?? '').trim();
            if (typeof window.performSearch === 'function') window.performSearch(q);
        };
        if (searchBtn)   searchBtn.addEventListener('click', doSearch);
        if (searchInput) searchInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') doSearch();
        });
    }
};

window.logout = function() {
    api.clearToken();
    localStorage.removeItem('user');
    window.location.href = 'index.html';
};

document.addEventListener('DOMContentLoaded', () => {
    if(!document.getElementById('toast-container')){
        document.body.insertAdjacentHTML('beforeend', '<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
    }
    ui.renderHeader();
});
