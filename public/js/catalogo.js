/**
 * catalogo.js - Funcionalidad de catálogo de productos
 * Búsqueda, filtros, listado y detalle de productos
 */

const Catalogo = {
    currentPage: 1,
    totalPages: 1,
    filters: {},

    /**
     * Inicializa la página de catálogo
     */
    async init() {
        // Cargar categorías
        await this.loadCategories();

        // Cargar productos iniciales
        await this.loadProducts();

        // Event listeners de filtros
        this.initFilters();
    },

    /**
     * Carga categorías desde la API
     */
    async loadCategories() {
        try {
            const resp = await fetch(`${App.apiBase}/catalogo/categorias`);
            const data = await resp.json();

            if (data.success) {
                this.renderCategories(data.data);
            }
        } catch (e) {
            console.error('Error cargando categorías:', e);
        }
    },

    /**
     * Renderiza categorías como checkboxes con contador
     */
    renderCategories(categories) {
        const container = document.getElementById('category-list');
        if (!container) return;
        container.innerHTML = categories.map(c => `
            <label class="qc-check-row">
                <input type="checkbox" class="qc-cat-cb" value="${c.id}">
                <span class="qc-check-name">${this.escapeHtml(c.nombre)}</span>
                <span class="qc-check-count">${c.total_productos ?? 0}</span>
            </label>`).join('');
    },

    /**
     * Carga marcas y las pinta como checkboxes con contador
     */
    async loadMarcas() {
        const container = document.getElementById('marca-list');
        if (!container) return;
        try {
            const resp = await fetch(`${App.apiBase}/catalogo/marcas`);
            const data = await resp.json();
            if (data.success) {
                container.innerHTML = data.data.map(m => `
                    <label class="qc-check-row">
                        <input type="checkbox" class="qc-marca-cb" value="${this.escapeHtml(m.marca)}">
                        <span class="qc-check-name">${this.escapeHtml(m.marca)}</span>
                        <span class="qc-check-count">${m.total}</span>
                    </label>`).join('');
            }
        } catch (e) {
            console.error('Error cargando marcas:', e);
        }
    },

    /**
     * Carga productos desde la API
     */
    async loadProducts() {
        const container = document.getElementById('products-container');
        if (!container) return;

        container.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>';

        const params = new URLSearchParams();
        if (this.filters.categorias && this.filters.categorias.length) params.set('categorias', this.filters.categorias.join(','));
        else if (this.filters.categoria) params.set('categoria', this.filters.categoria);
        if (this.filters.marcas && this.filters.marcas.length) params.set('marcas', this.filters.marcas.join(','));
        if (this.filters.q) params.set('q', this.filters.q);
        if (this.filters.precio_min) params.set('precio_min', this.filters.precio_min);
        if (this.filters.precio_max) params.set('precio_max', this.filters.precio_max);
        if (this.filters.en_stock) params.set('en_stock', '1');
        if (this.filters.ordenar) params.set('ordenar', this.filters.ordenar);
        params.set('pagina', this.currentPage);
        params.set('por_pagina', 12);

        // Leer búsqueda de URL
        const urlParams = new URLSearchParams(window.location.search);
        const searchQuery = urlParams.get('q');
        if (searchQuery && !this.filters.q) {
            this.filters.q = searchQuery;
            params.set('q', searchQuery);
            const searchInput = document.getElementById('search-input');
            if (searchInput) searchInput.value = searchQuery;
        }

        try {
            const resp = await fetch(`${App.apiBase}/catalogo?${params.toString()}`);
            const data = await resp.json();

            if (data.success) {
                this.renderProducts(data.data);
                this.renderPagination(data.meta?.pagination);
                this.updateCounts(data.meta?.pagination?.total ?? data.data.length);
            }
        } catch (e) {
            container.innerHTML = '<div class="col-12 text-center py-5 text-danger">Error al cargar productos.</div>';
        }
    },

    /**
     * Carga productos destacados (home) y los pinta en #featured-container
     */
    async loadFeatured() {
        const container = document.getElementById('featured-container');
        if (!container) return;
        container.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>';
        try {
            const resp = await fetch(`${App.apiBase}/catalogo/destacados`);
            const data = await resp.json();
            container.innerHTML = (data.success && data.data.length)
                ? data.data.map(p => this.productCard(p)).join('')
                : '<div class="col-12 text-center py-4 text-muted">No hay destacados por ahora.</div>';
        } catch (e) {
            container.innerHTML = '<div class="col-12 text-center py-4 text-danger">Error al cargar destacados.</div>';
        }
    },

    /**
     * Renderiza las cards de productos
     */
    renderProducts(products) {
        const container = document.getElementById('products-container');
        if (!container) return;

        if (products.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="empty-state">
                        <i class="bi bi-search"></i>
                        <h5>No se encontraron productos</h5>
                        <p class="text-muted">Intenta con otros filtros o términos de búsqueda.</p>
                    </div>
                </div>`;
            return;
        }

        container.innerHTML = products.map(p => this.productCard(p)).join('');
    },

    /**
     * Genera HTML de una card de producto
     */
    productCard(p) {
        const stockBadge = p.sin_stock
            ? '<span class="qc-badge-stock agotado">Agotado</span>'
            : '<span class="qc-badge-stock">En Stock</span>';

        const media = p.imagen_url
            ? `<img src="${p.imagen_url}" class="card-img-top" alt="${this.escapeHtml(p.nombre)}">`
            : `<div class="qc-img-ph"><i class="bi bi-cpu"></i></div>`;

        const addButton = p.sin_stock
            ? '<button class="btn btn-secondary btn-sm w-100" disabled>Sin stock</button>'
            : `<button class="btn btn-accent btn-sm w-100 add-to-cart-btn" data-id="${p.id}" data-name="${this.escapeHtml(p.nombre)}"><i class="bi bi-cart-plus"></i> Agregar al carrito</button>`;

        return `
            <div class="col-6 col-lg-4 col-xl-3 mb-4">
                <div class="product-card position-relative">
                    <div class="qc-card-media">
                        ${media}
                        ${stockBadge}
                    </div>
                    <div class="card-body">
                        <span class="card-category">${this.escapeHtml(p.marca || p.categoria_nombre || '')}</span>
                        <h5 class="card-title">${this.escapeHtml(p.nombre)}</h5>
                        <span class="card-price">${p.precio_formateado || App.formatPrice(p.precio)}</span>
                        <div class="mt-2">
                            ${addButton}
                        </div>
                        <a href="#/producto/${p.id}" class="btn btn-outline-uct btn-sm w-100 mt-1">Ver detalle</a>
                    </div>
                </div>
            </div>`;
    },

    /**
     * Renderiza paginación
     */
    renderPagination(pagination) {
        const container = document.getElementById('pagination-container');
        if (!container || !pagination) return;

        this.totalPages = pagination.total_pages;
        if (this.totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '<nav class="qc-pagination"><ul class="pagination justify-content-center">';

        html += `<li class="page-item ${this.currentPage <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${this.currentPage - 1}">Anterior</a></li>`;

        for (let i = 1; i <= pagination.total_pages; i++) {
            html += `<li class="page-item ${i === this.currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }

        html += `<li class="page-item ${this.currentPage >= pagination.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${this.currentPage + 1}">Siguiente</a></li>`;

        html += '</ul></nav>';
        html += `<div class="qc-pagination-info">Mostrando página ${this.currentPage} de ${pagination.total_pages} · ${pagination.total} productos</div>`;

        container.innerHTML = html;

        container.querySelectorAll('a[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.dataset.page);
                if (page >= 1 && page <= pagination.total_pages) {
                    this.currentPage = page;
                    this.loadProducts();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });
    },

    /**
     * Configura event listeners de filtros
     */
    initFilters() {
        // Checkboxes de categorías y marcas: aplican al instante (delegado)
        const sidebar = document.getElementById('category-list')?.closest('.qc-filters');
        if (sidebar) {
            sidebar.addEventListener('change', (e) => {
                if (e.target.classList.contains('qc-cat-cb') || e.target.classList.contains('qc-marca-cb')) {
                    this.applyFilters();
                }
                if (e.target.id === 'filter-stock') this.applyFilters();
            });
        }

        // Botón "Filtrar" (aplica precio + todo)
        document.getElementById('btn-filter')?.addEventListener('click', () => this.applyFilters());

        // Botón "Limpiar filtros"
        document.getElementById('btn-clear-filters')?.addEventListener('click', () => this.clearFilters());

        // Enter en los inputs de precio
        ['filter-price-min', 'filter-price-max'].forEach(id => {
            document.getElementById(id)?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') this.applyFilters();
            });
        });

        // Ordenamiento
        document.getElementById('sort-select')?.addEventListener('change', (e) => {
            this.filters.ordenar = e.target.value;
            this.currentPage = 1;
            this.loadProducts();
        });

        // Agregar al carrito (delegado; closest para que funcione al clickear el ícono)
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.add-to-cart-btn');
            if (!btn) return;

            const productoId = btn.dataset.id;
            const nombre = btn.dataset.name;
            const original = btn.innerHTML;
            try {
                btn.disabled = true;
                btn.textContent = 'Agregando...';
                const resp = await App.fetchAuth(`${App.apiBase}/carrito`, {
                    method: 'POST',
                    body: JSON.stringify({ producto_id: parseInt(productoId), cantidad: 1, session_id: App.getSessionId() })
                });
                const data = await resp.json();
                if (data.success) {
                    App.cartCount = data.data.items ? data.data.items.length : 0;
                    App.updateCartBadge();
                    App.showToast(`${nombre} agregado al carrito`, 'success');
                } else {
                    App.showToast(data.error?.message || 'Error al agregar', 'error');
                }
            } catch (err) {
                App.showToast('Error de conexión', 'error');
            }
            btn.disabled = false;
            btn.innerHTML = original;
        });
    },

    /**
     * Lee el estado de los filtros del sidebar a this.filters
     */
    collectFilters() {
        this.filters.categorias = [...document.querySelectorAll('.qc-cat-cb:checked')].map(x => x.value);
        this.filters.marcas = [...document.querySelectorAll('.qc-marca-cb:checked')].map(x => x.value);
        this.filters.precio_min = document.getElementById('filter-price-min')?.value || null;
        this.filters.precio_max = document.getElementById('filter-price-max')?.value || null;
        this.filters.en_stock = document.getElementById('filter-stock')?.checked || false;
        // si el usuario filtra desde el sidebar, descartamos el deep-link de categoría única
        if (this.filters.categorias.length) this.filters.categoria = null;
    },

    applyFilters() {
        this.collectFilters();
        this.currentPage = 1;
        this.loadProducts();
    },

    clearFilters() {
        document.querySelectorAll('.qc-cat-cb, .qc-marca-cb').forEach(x => x.checked = false);
        const min = document.getElementById('filter-price-min'); if (min) min.value = '';
        const max = document.getElementById('filter-price-max'); if (max) max.value = '';
        const st = document.getElementById('filter-stock'); if (st) st.checked = false;
        this.filters = { ordenar: this.filters.ordenar };  // conserva el orden, limpia el resto
        this.currentPage = 1;
        this.loadProducts();
    },

    /**
     * Actualiza el contador "N resultados" y el label del botón Filtrar
     */
    updateCounts(total) {
        const rc = document.getElementById('results-count');
        if (rc) rc.textContent = `${total} resultado${total === 1 ? '' : 's'}`;
        const bf = document.getElementById('btn-filter');
        if (bf) bf.textContent = `Filtrar (${total})`;
    },

    /**
     * Escapa HTML para prevenir XSS
     */
    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    },

    /**
     * Carga detalle de producto
     */
    async loadDetail(productId) {
        const container = document.getElementById('product-detail');
        if (!container) return;

        try {
            const resp = await fetch(`${App.apiBase}/catalogo/${productId}`);
            const data = await resp.json();

            if (data.success) {
                this.renderDetail(data.data);
            } else {
                container.innerHTML = '<div class="alert alert-danger">Producto no encontrado.</div>';
            }
        } catch (e) {
            container.innerHTML = '<div class="alert alert-danger">Error al cargar producto.</div>';
        }
    },

    /**
     * Renderiza detalle de producto
     */
    renderDetail(product) {
        const container = document.getElementById('product-detail');
        if (!container) return;

        const addButton = product.sin_stock
            ? '<button class="btn btn-secondary btn-lg" disabled>Sin Stock</button>'
            : `<button class="btn btn-accent btn-lg" id="btn-add-detail" data-id="${product.id}">Agregar al Carrito</button>`;

        container.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    ${product.imagen_url
                        ? `<img src="${product.imagen_url}" class="img-fluid rounded" alt="${this.escapeHtml(product.nombre)}">`
                        : `<div class="qc-img-ph qc-img-ph-lg"><i class="bi bi-cpu"></i></div>`}
                </div>
                <div class="col-md-6">
                    <span class="badge bg-secondary mb-2">${this.escapeHtml(product.categoria_nombre || '')}</span>
                    <h2>${this.escapeHtml(product.nombre)}</h2>
                    <h3 class="text-primary mb-3">${product.precio_formateado}</h3>
                    <p class="lead">${this.escapeHtml(product.descripcion || 'Sin descripción')}</p>
                    <div class="mb-3">
                        <span class="badge ${product.sin_stock ? 'bg-danger' : 'bg-success'} fs-6">
                            ${product.sin_stock ? 'Sin Stock' : 'Stock: ' + product.stock + ' unidades'}
                        </span>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="number" id="qty-detail" class="form-control" style="width:80px" value="1" min="1" max="${product.stock}">
                        ${addButton}
                    </div>
                    <hr class="my-4">
                    <p class="text-muted small">SKU: PROD-${product.id} | ${product.sin_stock ? '⚠️ Producto agotado' : '✅ Disponible'}</p>
                </div>
            </div>`;

        // Botón agregar del detalle
        const btnDetail = document.getElementById('btn-add-detail');
        if (btnDetail) {
            btnDetail.addEventListener('click', async () => {
                const qty = parseInt(document.getElementById('qty-detail').value) || 1;
                btnDetail.disabled = true;
                btnDetail.textContent = 'Agregando...';

                try {
                    const resp = await App.fetchAuth(`${App.apiBase}/carrito`, {
                        method: 'POST',
                        body: JSON.stringify({
                            producto_id: product.id,
                            cantidad: qty,
                            session_id: App.getSessionId()
                        })
                    });
                    const data = await resp.json();

                    if (data.success) {
                        App.cartCount = data.data.items ? data.data.items.length : 0;
                        App.updateCartBadge();
                        App.showToast(`${product.nombre} (x${qty}) agregado al carrito`, 'success');
                    } else {
                        App.showToast(data.error?.message || 'Error', 'error');
                    }
                } catch (err) {
                    App.showToast('Error de conexión', 'error');
                }

                btnDetail.disabled = false;
                btnDetail.textContent = 'Agregar al Carrito';
            });
        }
    }
};
