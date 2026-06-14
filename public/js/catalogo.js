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
     * Renderiza categorías en el sidebar
     */
    renderCategories(categories) {
        const container = document.getElementById('category-list');
        if (!container) return;

        // Separar padres e hijos
        const padres = categories.filter(c => !c.id_padre);
        const hijos = categories.filter(c => c.id_padre);

        let html = '<li class="list-group-item"><a href="#" class="text-decoration-none text-dark fw-bold" data-cat="">Todas las categorías</a></li>';

        padres.forEach(padre => {
            html += `<li class="list-group-item">
                <a href="#" class="text-decoration-none text-dark fw-semibold" data-cat="${padre.id}">${this.escapeHtml(padre.nombre)}</a>`;

            const sub = hijos.filter(h => h.id_padre == padre.id);
            if (sub.length > 0) {
                html += '<ul class="list-unstyled ms-3 mt-1">';
                sub.forEach(s => {
                    html += `<li><a href="#" class="text-decoration-none text-muted small" data-cat="${s.id}">${this.escapeHtml(s.nombre)} (${s.total_productos})</a></li>`;
                });
                html += '</ul>';
            }
            html += '</li>';
        });

        container.innerHTML = html;

        // Event listeners
        container.querySelectorAll('a[data-cat]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const catId = link.dataset.cat;
                this.filters.categoria = catId || null;
                this.currentPage = 1;
                this.loadProducts();
            });
        });
    },

    /**
     * Carga productos desde la API
     */
    async loadProducts() {
        const container = document.getElementById('products-container');
        if (!container) return;

        container.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>';

        const params = new URLSearchParams();
        if (this.filters.categoria) params.set('categoria', this.filters.categoria);
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
            }
        } catch (e) {
            container.innerHTML = '<div class="col-12 text-center py-5 text-danger">Error al cargar productos.</div>';
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
            ? '<span class="badge bg-danger stock-badge">Sin Stock</span>'
            : p.stock <= 5
            ? '<span class="badge bg-warning text-dark stock-badge">Últimas ' + p.stock + ' unid.</span>'
            : '';

        const addButton = p.sin_stock
            ? '<button class="btn btn-secondary btn-sm w-100" disabled>Sin Stock</button>'
            : `<button class="btn btn-accent btn-sm w-100 add-to-cart-btn" data-id="${p.id}" data-name="${this.escapeHtml(p.nombre)}">Agregar al Carrito</button>`;

        return `
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="product-card position-relative">
                    ${stockBadge}
                    <img src="${p.imagen_url || 'https://via.placeholder.com/400x220?text=Sin+Imagen'}"
                         class="card-img-top" alt="${this.escapeHtml(p.nombre)}"
                         onerror="this.src='https://via.placeholder.com/400x220?text=Sin+Imagen'">
                    <div class="card-body">
                        <span class="card-category">${this.escapeHtml(p.categoria_nombre || '')}</span>
                        <h5 class="card-title">${this.escapeHtml(p.nombre)}</h5>
                        <p class="card-text small text-muted">${this.escapeHtml((p.descripcion || '').substring(0, 80))}${p.descripcion && p.descripcion.length > 80 ? '...' : ''}</p>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <span class="card-price">${p.precio_formateado || App.formatPrice(p.precio)}</span>
                        </div>
                        <div class="mt-2">
                            ${addButton}
                        </div>
                        <a href="/producto.html?id=${p.id}" class="btn btn-outline-uct btn-sm w-100 mt-1">Ver Detalle</a>
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

        let html = '<nav><ul class="pagination justify-content-center">';

        html += `<li class="page-item ${this.currentPage <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${this.currentPage - 1}">Anterior</a></li>`;

        for (let i = 1; i <= pagination.total_pages; i++) {
            html += `<li class="page-item ${i === this.currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }

        html += `<li class="page-item ${this.currentPage >= pagination.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${this.currentPage + 1}">Siguiente</a></li>`;

        html += '</ul></nav>';
        html += `<div class="text-center text-muted small">Mostrando página ${this.currentPage} de ${pagination.total_pages} (${pagination.total} productos)</div>`;

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
        // Filtro de precio
        const btnFilterPrice = document.getElementById('btn-filter-price');
        if (btnFilterPrice) {
            btnFilterPrice.addEventListener('click', () => {
                this.filters.precio_min = document.getElementById('filter-price-min')?.value || null;
                this.filters.precio_max = document.getElementById('filter-price-max')?.value || null;
                this.currentPage = 1;
                this.loadProducts();
            });
        }

        // Filtro solo en stock
        const filterStock = document.getElementById('filter-stock');
        if (filterStock) {
            filterStock.addEventListener('change', () => {
                this.filters.en_stock = filterStock.checked;
                this.currentPage = 1;
                this.loadProducts();
            });
        }

        // Ordenamiento
        const sortSelect = document.getElementById('sort-select');
        if (sortSelect) {
            sortSelect.addEventListener('change', () => {
                this.filters.ordenar = sortSelect.value;
                this.currentPage = 1;
                this.loadProducts();
            });
        }

        // Búsqueda
        const searchForm = document.getElementById('search-form-catalogo');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.filters.q = document.getElementById('search-input-catalogo').value.trim();
                this.currentPage = 1;
                this.loadProducts();
            });
        }

        // Agregar al carrito (delegado)
        document.addEventListener('click', async (e) => {
            if (e.target.classList.contains('add-to-cart-btn')) {
                const btn = e.target;
                const productoId = btn.dataset.id;
                const nombre = btn.dataset.name;

                try {
                    btn.disabled = true;
                    btn.textContent = 'Agregando...';

                    const body = {
                        producto_id: parseInt(productoId),
                        cantidad: 1,
                        session_id: App.getSessionId()
                    };

                    const resp = await App.fetchAuth(`${App.apiBase}/carrito`, {
                        method: 'POST',
                        body: JSON.stringify(body)
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
                btn.textContent = 'Agregar al Carrito';
            }
        });
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
                    <img src="${product.imagen_url || 'https://via.placeholder.com/600x400?text=Sin+Imagen'}"
                         class="img-fluid rounded" alt="${this.escapeHtml(product.nombre)}"
                         onerror="this.src='https://via.placeholder.com/600x400?text=Sin+Imagen'">
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
