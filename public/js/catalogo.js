/**
 * catalogo.js - Funcionalidad de catálogo de productos
 * Búsqueda, filtros, listado y detalle de productos
 */

// Placeholder SVG local (no depende de servicios externos)
const IMG_PLACEHOLDER_CARD   = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='220'%3E%3Crect width='400' height='220' fill='%23e2e8f0'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' font-family='sans-serif' font-size='14' fill='%2394a3b8'%3ESin Imagen%3C/text%3E%3C/svg%3E";
const IMG_PLACEHOLDER_DETAIL = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='600' height='400'%3E%3Crect width='600' height='400' fill='%23e2e8f0'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' font-family='sans-serif' font-size='18' fill='%2394a3b8'%3ESin Imagen%3C/text%3E%3C/svg%3E";
const IMG_PLACEHOLDER_THUMB  = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Crect width='60' height='60' fill='%23e2e8f0'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' font-family='sans-serif' font-size='9' fill='%2394a3b8'%3EImg%3C/text%3E%3C/svg%3E";

const Catalogo = {
    currentPage: 1,
    totalPages: 1,
    filters: {},

    /**
     * Inicializa la página de catálogo
     */
    async init() {
        // Cargar productos destacados (KAN-104)
        this.loadFeatured();

        // Cargar categorías
        await this.loadCategories();

        // Cargar productos iniciales
        await this.loadProducts();

        // Event listeners de filtros
        this.initFilters();

        // Inicializar Instant Search (KAN-105)
        this.initInstantSearch();
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

        container.innerHTML = Array(8).fill(0).map(() => `
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="product-card skeleton-card">
                    <div class="skeleton skeleton-img"></div>
                    <div class="card-body">
                        <div class="skeleton skeleton-text skeleton-category"></div>
                        <div class="skeleton skeleton-text skeleton-title font-weight-bold"></div>
                        <div class="skeleton skeleton-text skeleton-description"></div>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <div class="skeleton skeleton-text skeleton-price"></div>
                        </div>
                        <div class="skeleton skeleton-button mt-2"></div>
                        <div class="skeleton skeleton-button mt-1"></div>
                    </div>
                </div>
            </div>
        `).join('');

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
            if (!resp.ok) {
                const errorData = await resp.json().catch(() => ({}));
                console.error('Error de servidor:', errorData);
                throw new Error(errorData.error?.message || 'Error en la respuesta del servidor');
            }
            const data = await resp.json();

            if (data.success) {
                this.renderProducts(data.data);
                this.renderPagination(data.meta?.pagination);
            }
        } catch (e) {
            console.error('Error cargando productos:', e);
            container.innerHTML = `<div class="col-12 text-center py-5 text-danger">Error al cargar productos: ${e.message}</div>`;
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
                    <img src="${p.imagen_url || IMG_PLACEHOLDER_CARD}"
                         class="card-img-top" alt="${this.escapeHtml(p.nombre)}"
                         onerror="this.onerror=null;this.src=IMG_PLACEHOLDER_CARD">
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
                        <a href="producto.html?id=${p.id}" class="btn btn-outline-uct btn-sm w-100 mt-1">Ver Detalle</a>
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
     * KAN-104: Carga y renderiza productos destacados
     */
    async loadFeatured() {
        const section = document.getElementById('destacados-section');
        if (!section) return;

        try {
            const resp = await fetch(`${App.apiBase}/catalogo/destacados`);
            if (!resp.ok) return;
            const data = await resp.json();

            if (data.success && data.data && data.data.length > 0) {
                this.renderFeatured(data.data);
                section.classList.remove('d-none');
            }
        } catch (e) {
            // silencioso: si destacados falla, el catálogo sigue funcionando
        }
    },

    /**
     * KAN-104: Renderiza cards de productos destacados
     */
    renderFeatured(products) {
        const container = document.getElementById('destacados-container');
        if (!container) return;

        container.innerHTML = products.slice(0, 4).map(p => {
            const addBtn = p.sin_stock
                ? `<button class="btn btn-secondary btn-sm w-100" disabled>Sin Stock</button>`
                : `<button class="btn btn-accent btn-sm w-100 add-to-cart-btn" data-id="${p.id}" data-name="${this.escapeHtml(p.nombre)}">Agregar al Carrito</button>`;

            return `
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="featured-card position-relative">
                    <span class="featured-badge"><i class="bi bi-star-fill me-1"></i>Destacado</span>
                    <img src="${p.imagen_url || 'https://via.placeholder.com/400x200?text=Sin+Imagen'}"
                         class="card-img-top" alt="${this.escapeHtml(p.nombre)}"
                         onerror="this.src='https://via.placeholder.com/400x200?text=Sin+Imagen'">
                    <div class="p-3">
                        <span class="card-category d-block mb-1">${this.escapeHtml(p.categoria_nombre || '')}</span>
                        <h6 class="fw-bold mb-1">${this.escapeHtml(p.nombre)}</h6>
                        <p class="card-price mb-3">${p.precio_formateado || App.formatPrice(p.precio)}</p>
                        <div class="d-grid gap-1">
                            ${addBtn}
                            <a href="producto.html?id=${p.id}" class="btn btn-outline-uct btn-sm">Ver Detalle</a>
                        </div>
                    </div>
                </div>
            </div>`;
        }).join('');
    },

    // ============================================================
    // KAN-105: Instant Search
    // ============================================================
    _searchDebounce: null,

    /**
     * KAN-105: Inicializa el autocompletado de búsqueda en tiempo real
     */
    initInstantSearch() {
        const input = document.getElementById('search-input-catalogo');
        const suggestionsBox = document.getElementById('search-suggestions');
        if (!input || !suggestionsBox) return;

        // Input con debounce de 320ms
        input.addEventListener('input', () => {
            clearTimeout(this._searchDebounce);
            const q = input.value.trim();

            if (q.length < 2) {
                this.hideSuggestions();
                return;
            }

            this._searchDebounce = setTimeout(() => this.fetchSuggestions(q), 320);
        });

        // Cerrar sugerencias al hacer click fuera
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !suggestionsBox.contains(e.target)) {
                this.hideSuggestions();
            }
        });

        // Cerrar con Escape
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.hideSuggestions();
        });
    },

    /**
     * KAN-105: Consulta la API y muestra las sugerencias
     */
    async fetchSuggestions(q) {
        const suggestionsBox = document.getElementById('search-suggestions');
        if (!suggestionsBox) return;

        try {
            const resp = await fetch(`${App.apiBase}/catalogo?q=${encodeURIComponent(q)}&por_pagina=5&pagina=1`);
            if (!resp.ok) return;
            const data = await resp.json();

            if (data.success && data.data) {
                this.renderSuggestions(data.data, q, data.meta?.pagination?.total || 0);
            } else {
                this.hideSuggestions();
            }
        } catch (e) {
            this.hideSuggestions();
        }
    },

    /**
     * KAN-105: Renderiza el dropdown de sugerencias
     */
    renderSuggestions(products, query, total) {
        const box = document.getElementById('search-suggestions');
        if (!box) return;

        if (products.length === 0) {
            box.innerHTML = `<div class="search-suggestion-item text-muted"><i class="bi bi-search me-2"></i>Sin resultados para "${this.escapeHtml(query)}"</div>`;
            box.classList.remove('d-none');
            return;
        }

        // Resaltar coincidencia en nombre
        const highlight = (str) => {
            const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            return str.replace(new RegExp(`(${escaped})`, 'gi'), '<mark class="bg-warning bg-opacity-50 rounded px-0">$1</mark>');
        };

        const items = products.map(p => `
            <a href="producto.html?id=${p.id}" class="search-suggestion-item">
                <img src="${p.imagen_url || 'https://via.placeholder.com/42x42?text=P'}" class="suggestion-img"
                     onerror="this.src='https://via.placeholder.com/42x42?text=P'" alt="">
                <div class="suggestion-info flex-grow-1">
                    <div class="name">${highlight(this.escapeHtml(p.nombre))}</div>
                    <div class="cat">${this.escapeHtml(p.categoria_nombre || '')}</div>
                    <div class="price">${p.precio_formateado || App.formatPrice(p.precio)}</div>
                </div>
                <i class="bi bi-arrow-right text-muted"></i>
            </a>`).join('');

        const footer = total > 5
            ? `<div class="search-suggestions-footer" id="suggestion-see-all">
                <i class="bi bi-search me-1"></i>Ver los ${total} resultados para "${this.escapeHtml(query)}"
               </div>`
            : '';

        box.innerHTML = items + footer;
        box.classList.remove('d-none');

        // Click en "ver todos" aplica búsqueda
        const seeAll = document.getElementById('suggestion-see-all');
        if (seeAll) {
            seeAll.addEventListener('click', () => {
                this.filters.q = query;
                this.currentPage = 1;
                this.hideSuggestions();
                this.loadProducts();
                document.getElementById('catalog-main')?.scrollIntoView({ behavior: 'smooth' });
            });
        }
    },

    /**
     * KAN-105: Oculta el panel de sugerencias
     */
    hideSuggestions() {
        const box = document.getElementById('search-suggestions');
        if (box) box.classList.add('d-none');
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
                    <img src="${product.imagen_url || IMG_PLACEHOLDER_DETAIL}"
                         class="img-fluid rounded" alt="${this.escapeHtml(product.nombre)}"
                         onerror="this.onerror=null;this.src=IMG_PLACEHOLDER_DETAIL">
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
