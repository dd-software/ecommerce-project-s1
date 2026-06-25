/**
 * catalogo.js - Funcionalidad de catálogo de productos
 * Búsqueda, filtros, listado y detalle de productos
 */

const Catalogo = {
    currentPage: 1,
    totalPages: 1,
    filters: {},
    priceLimit: 0,        // tope del rango, se calcula con la data
    priceDraft: null,     // {min,max} borrador (histograma + conteo en vivo)
    priceBase: [],        // precios del set actual sin filtro de precio

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

        container.innerHTML = UI.loader('Cargando productos...');

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
        params.set('por_pagina', 16);
        // La búsqueda (this.filters.q) la setea el router desde #/catalogo?q=…

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
     * Carga la distribución de precios del set actual (cat/marca/búsqueda, SIN
     * el filtro de precio) para el histograma y el conteo en vivo.
     * ponytail: cálculo client-side; con ~20 productos traemos todos. Si el
     * catálogo creciera mucho, mover los buckets a un endpoint del backend.
     */
    async loadPriceDistribution() {
        const params = new URLSearchParams();
        if (this.filters.categorias?.length) params.set('categorias', this.filters.categorias.join(','));
        else if (this.filters.categoria) params.set('categoria', this.filters.categoria);
        if (this.filters.marcas?.length) params.set('marcas', this.filters.marcas.join(','));
        if (this.filters.q) params.set('q', this.filters.q);
        if (this.filters.en_stock) params.set('en_stock', '1');
        params.set('por_pagina', 300);   // trae todo el set para el histograma (exacto hasta 300 prods)
        try {
            const resp = await fetch(`${App.apiBase}/catalogo?${params.toString()}`);
            const data = await resp.json();
            this.priceBase = (data.data || []).map(p => p.precio);
        } catch (e) {
            this.priceBase = [];
        }
        // Tope = percentil 90 (no el máximo) redondeado a 10.000, fijado una vez.
        // Así el grueso de productos llena el histograma y los pocos caros no estiran
        // la escala; en el filtro, el tope equivale a "sin límite superior".
        if (!this.priceLimit) {
            const sorted = [...this.priceBase].sort((a, b) => a - b);
            const p90 = sorted.length ? sorted[Math.floor(sorted.length * 0.9)] : 300000;
            this.priceLimit = Math.max(10000, Math.ceil((p90 || 300000) / 10000) * 10000);
            this.priceDraft = { min: 0, max: this.priceLimit };
        }
        this.syncPriceControls();
        this.renderPriceHistogram();
    },

    /** Refleja priceDraft + priceLimit en sliders e inputs */
    syncPriceControls() {
        if (!this.priceDraft) return;
        const { min, max } = this.priceDraft;
        const set = (id, val) => { const el = document.getElementById(id); if (el) { el.max = this.priceLimit; el.value = val; } };
        set('price-slider-min', min);
        set('price-slider-max', max);
        set('filter-price-min', min);
        set('filter-price-max', max);
    },

    /** Pinta el histograma coloreando lo que cae en el borrador + conteo en vivo */
    renderPriceHistogram() {
        const box = document.getElementById('price-histogram');
        if (!box || !this.priceDraft) return;
        const BUCKETS = 22, step = this.priceLimit / BUCKETS;
        // el último bucket absorbe todo lo que supera el tope (productos premium)
        const counts = Array.from({ length: BUCKETS }, (_, i) => {
            const lo = i * step;
            return this.priceBase.filter(p => p >= lo && (i === BUCKETS - 1 || p < lo + step)).length;
        });
        const maxC = Math.max(1, ...counts);
        const { min, max } = this.priceDraft;
        box.innerHTML = counts.map((c, i) => {
            const inRange = (i + 1) * step >= min && i * step <= max;
            const h = Math.max(Math.round((c / maxC) * 100), 5);
            return `<span class="qc-bar${inRange ? ' on' : ''}" style="height:${h}%"></span>`;
        }).join('');
        // si el tope está al máximo = "sin límite superior" (incluye los premium)
        const noUpper = max >= this.priceLimit;
        const live = this.priceBase.filter(p => p >= min && (noUpper || p <= max)).length;
        const bf = document.getElementById('btn-filter');
        if (bf) bf.textContent = `Filtrar (${live} productos)`;
    },

    /** Edita el borrador desde slider/input (key: 'min'|'max') */
    onPriceDraft(key, raw) {
        const v = Math.max(0, Math.min(this.priceLimit, parseInt(raw) || 0));
        if (key === 'min') this.priceDraft.min = Math.min(v, this.priceDraft.max);
        else this.priceDraft.max = Math.max(v, this.priceDraft.min);
        this.syncPriceControls();
        this.renderPriceHistogram();
    },

    /** Aplica el borrador al grid (solo al pulsar "Filtrar") */
    applyPrice() {
        this.filters.precio_min = this.priceDraft.min > 0 ? this.priceDraft.min : null;
        this.filters.precio_max = this.priceDraft.max < this.priceLimit ? this.priceDraft.max : null;
        this.currentPage = 1;
        this.loadProducts();
    },

    /**
     * Mapa centralizado slug de categoría → ícono de Bootstrap Icons (la librería
     * del proyecto). Agregar una categoría nueva = una línea acá.
     */
    categoryIconMap: {
        'componentes-pc':     'bi-cpu',
        'tarjetas-video':     'bi-gpu-card',
        'almacenamiento':     'bi-hdd',
        'redes':              'bi-router',
        'notebooks':          'bi-laptop',
        'computadores':       'bi-pc-display',
        'monitores':          'bi-display',
        'celulares':          'bi-phone',
        'tv-audio':           'bi-tv',
        'consolas':           'bi-controller',
        'videojuegos':        'bi-dpad',
        'controles-gaming':   'bi-joystick',
        'accesorios':         'bi-mouse',
        'cables':             'bi-plug',
        'bolsos-fundas':      'bi-backpack',
        'sillas-escritorios': 'bi-person-workspace',
        'limpieza-cuidado':   'bi-stars',
        'herramientas':       'bi-tools',
        'repuestos':          'bi-gear-wide-connected',
        'servicios-tecnicos': 'bi-wrench-adjustable',
    },
    catIcon(slug) {
        return this.categoryIconMap[slug] || 'bi-box-seam';
    },

    /**
     * Home retail: tiles de categorías + carrusel de ofertas + carruseles por categoría.
     * (lo llama el router como Catalogo.loadFeatured)
     */
    async loadFeatured() {
        this.loadCategoryTiles();
        this.loadOfertasMosaic();
        this.loadCategorySections();
    },

    /**
     * Mosaico "Ofertas y Lanzamientos": 2 cards grandes (mayor prioridad) + 4 chicas.
     * Jerarquía: las ofertas vienen ordenadas por mayor % de descuento desde el
     * backend → las 2 primeras van grandes, las siguientes 4 chicas. Sin hardcodear.
     */
    async loadOfertasMosaic() {
        const box = document.getElementById('home-ofertas');
        if (!box) return;
        box.innerHTML = UI.loader('Cargando ofertas...');
        try {
            const data = await (await fetch(`${App.apiBase}/catalogo/ofertas`)).json();
            const ofertas = data.data || [];
            if (!ofertas.length) { 
                UI.mostrarVacio(box, {
                icono: 'bi-tag',
                titulo: 'Sin ofertas por ahora',
                descripcion: 'Pronto tendremos promociones disponibles. ¡Vuelve a consultar!',
                textoBoton: 'Ver catálogo',
                enlaceBoton: '#/catalogo',
                variante: 'inline'
            });
            }
            // Grandes = productos importantes: mayor AHORRO absoluto (no solo %).
            // Chicas = el resto de las mejores ofertas (vienen ordenadas por % desc).
            const big = [...ofertas].sort((a, b) => (b.precio_anterior - b.precio) - (a.precio_anterior - a.precio)).slice(0, 2);
            const bigIds = new Set(big.map(p => p.id));
            const small = ofertas.filter(p => !bigIds.has(p.id)).slice(0, 4);
            // Orden del DOM para el mosaico: [chica, chica, GRANDE] · [GRANDE, chica, chica]
            box.innerHTML = [
                this.offerCardSmall(small[0]), this.offerCardSmall(small[1]),
                this.offerCardBig(big[0]),
                this.offerCardBig(big[1]),
                this.offerCardSmall(small[2]), this.offerCardSmall(small[3]),
            ].join('');
        } catch (e) { box.innerHTML = ''; }
    },

    /** Bloque de precio reutilizable (oscuro, con precio actual/anterior + % acento) */
    ofertaPrecio(p) {
        return `<div class="oferta-precio">
            <div class="oferta-precio-main">
                <span class="precio-now">${p.precio_formateado || App.formatPrice(p.precio)}</span>
                ${p.precio_anterior_formateado ? `<span class="precio-old">${p.precio_anterior_formateado}</span>` : ''}
            </div>
            ${p.descuento_pct ? `<span class="oferta-pct">-${p.descuento_pct}%</span>` : ''}
        </div>`;
    },

    ofertaImg(p) {
        return p.imagen_url
            ? `<img src="${p.imagen_url}" alt="${this.escapeHtml(p.nombre)}">`
            : '<div class="qc-img-ph w-100 h-100"><i class="bi bi-box-seam"></i></div>';
    },

    offerCardSmall(p) {
        if (!p) return '';
        return `<a href="#/producto/${p.id}" class="oferta-sm">
            <div class="oferta-sm-img">${this.ofertaImg(p)}</div>
            <div class="oferta-sm-body">
                <h4>${this.escapeHtml(p.nombre)}</h4>
                <p class="oferta-desc">${this.escapeHtml(p.descripcion || '')}</p>
                ${this.ofertaPrecio(p)}
            </div>
        </a>`;
    },

    offerCardBig(p) {
        if (!p) return '';
        return `<a href="#/producto/${p.id}" class="oferta-big">
            <div class="oferta-big-info">
                <h3>${this.escapeHtml(p.nombre)}</h3>
                <p class="oferta-desc subtitle">${this.escapeHtml(p.descripcion || '')}</p>
                ${this.ofertaPrecio(p)}
            </div>
            <div class="oferta-big-img">${this.ofertaImg(p)}</div>
        </a>`;
    },

    async loadCategoryTiles() {
        const box = document.getElementById('home-categorias');
        if (!box) return;
        try {
            const data = await (await fetch(`${App.apiBase}/catalogo/categorias`)).json();
            box.innerHTML = (data.data || []).map(c => `
                <a href="#/catalogo?cat=${encodeURIComponent(c.slug)}" class="qc-cat-tile">
                    <i class="bi ${this.catIcon(c.slug)}"></i>
                    <span class="qc-cat-tile-name">${this.escapeHtml(c.nombre)}</span>
                    <small>${c.total_productos ?? 0} productos</small>
                </a>`).join('');
        } catch (e) { box.innerHTML = ''; }
    },

    /** Pinta un carrusel horizontal de productos desde un endpoint */
    async loadCarousel(containerId, url) {
        const box = document.getElementById(containerId);
        if (!box) return;
        box.innerHTML = UI.loader('Cargando...');
        try {
            const data = await (await fetch(url)).json();
            const items = data.data || [];
            box.innerHTML = items.length
                ? items.map(p => this.productCard(p)).join('')
                : '<p class="text-muted py-3">Sin productos por ahora.</p>';
        } catch (e) { box.innerHTML = ''; }
    },

    /** Un carrusel por cada categoría que tenga productos */
async loadCategorySections() {
    const box = document.getElementById('home-secciones');
    if (!box) return;
    try {
        const cats = (await (await fetch(`${App.apiBase}/catalogo/categorias`)).json()).data || [];
        const conProductos = cats.filter(c => (c.total_productos ?? 0) > 0).slice(0, 4);

        // 👇 Chequeo ANTES de renderizar
        if (conProductos.length === 0) {
            UI.mostrarVacio(box, {
                icono: 'bi-grid',
                titulo: 'Pronto más productos',
                descripcion: 'Estamos ampliando nuestro catálogo. Vuelve pronto.',
                variante: 'inline'
            });
            return;
        }

        // Renderizar solo si hay productos
        box.innerHTML = conProductos.map(c => `
            <div class="qc-section-head">
                <h2 class="section-title mb-0">${this.escapeHtml(c.nombre)}</h2>
                <a href="#/catalogo?cat=${encodeURIComponent(c.slug)}" class="qc-section-link">Ver todo →</a>
            </div>
            <div class="qc-carousel" id="home-cat-${c.id}"></div>`).join('');
        conProductos.forEach(c =>
            this.loadCarousel(`home-cat-${c.id}`, `${App.apiBase}/catalogo?categoria=${c.id}&por_pagina=10`));
    } catch (e) {
        box.innerHTML = '';
    }

    },

    /**
     * Renderiza las cards de productos
     */
    renderProducts(products) {
        const container = document.getElementById('products-container');
        if (!container) return;

        if (products.length === 0) {
            UI.mostrarVacio(container, {
                icono: 'bi-search',
                titulo: 'No se encontraron productos',
                descripcion: 'Prueba con otros términos o quita los filtros aplicados.',
                textoBoton: 'Limpiar filtros',
                enlaceBoton: '#/catalogo',
                claseBoton: 'btn-outline-uct'
            });
            return;
        }

        container.innerHTML = products.map(p => this.productCard(p)).join('');
    },

    /**
     * Genera HTML de una card de producto
     */
    productCard(p, extra = '') {
        const stockBadge = p.sin_stock
            ? '<span class="qc-badge-stock agotado">Agotado</span>'
            : '<span class="qc-badge-stock">En Stock</span>';

        const media = p.imagen_url
            ? `<img src="${p.imagen_url}" class="card-img-top" alt="${this.escapeHtml(p.nombre)}">`
            : `<div class="qc-img-ph"><i class="bi bi-cpu"></i></div>`;

        const addButton = p.sin_stock
            ? '<button class="btn btn-secondary btn-sm w-100" disabled>Sin stock</button>'
            : `<button class="btn btn-accent btn-sm w-100 add-to-cart-btn" data-id="${p.id}" data-name="${this.escapeHtml(p.nombre)}"><i class="bi bi-cart-plus"></i> Agregar al carrito</button>`;

        const ofertaBadge = p.descuento_pct ? `<span class="qc-badge-oferta">-${p.descuento_pct}%</span>` : '';
        const precioAnterior = p.precio_anterior_formateado ? `<span class="card-price-old">${p.precio_anterior_formateado}</span>` : '';

        return `
            <div class="col-6 col-lg-4 col-xl-3 mb-4">
                <div class="product-card position-relative">
                    <a href="#/producto/${p.id}" class="qc-card-media" aria-label="Ver ${this.escapeHtml(p.nombre)}">
                        ${media}
                        ${ofertaBadge}
                        ${stockBadge}
                    </a>
                    <div class="card-body">
                        <span class="card-category">${this.escapeHtml(p.marca || p.categoria_nombre || '')}</span>
                        <h5 class="card-title">${this.escapeHtml(p.nombre)}</h5>
                        <span class="card-price">${p.precio_formateado || App.formatPrice(p.precio)}</span>${precioAnterior}
                        <div class="card-actions">
                            ${addButton}
                            <div class="d-flex gap-2 mt-1">
                                <a href="#/producto/${p.id}" class="btn btn-outline-uct btn-sm flex-grow-1">Ver detalle</a>
                                <button type="button" class="btn btn-outline-uct btn-sm qc-compare-toggle${window.Compare?.isSelected(p.id) ? ' active' : ''}" data-id="${p.id}" data-name="${this.escapeHtml(p.nombre)}" title="Comparar" aria-label="Comparar"><i class="bi bi-arrow-left-right"></i></button>
                            </div>
                            ${extra}
                        </div>
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

        // Botón "Filtrar": aplica el rango de precio del borrador al grid
        document.getElementById('btn-filter')?.addEventListener('click', () => this.applyPrice());

        // Botón "Limpiar filtros"
        document.getElementById('btn-clear-filters')?.addEventListener('click', () => this.clearFilters());

        // Sliders e inputs de precio: editan el borrador (histograma + conteo en vivo)
        document.getElementById('price-slider-min')?.addEventListener('input', (e) => this.onPriceDraft('min', e.target.value));
        document.getElementById('price-slider-max')?.addEventListener('input', (e) => this.onPriceDraft('max', e.target.value));
        document.getElementById('filter-price-min')?.addEventListener('input', (e) => this.onPriceDraft('min', e.target.value));
        document.getElementById('filter-price-max')?.addEventListener('input', (e) => this.onPriceDraft('max', e.target.value));

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
                    // actualiza badge + total del header + offcanvas con la respuesta del POST (sin 2do fetch)
                    if (typeof Carrito !== 'undefined') Carrito.applyCart(data.data);
                    else { App.cartCount = data.data.items ? data.data.items.length : 0; App.updateCartBadge(); }
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
        // precio se aplica aparte vía applyPrice(); aquí se conserva lo ya aplicado
        this.filters.en_stock = document.getElementById('filter-stock')?.checked || false;
        // si el usuario filtra desde el sidebar, descartamos el deep-link de categoría única
        if (this.filters.categorias.length) this.filters.categoria = null;
    },

    applyFilters() {
        this.collectFilters();
        this.currentPage = 1;
        this.loadProducts();
        this.loadPriceDistribution();   // el set base cambió → refrescar histograma/conteo
    },

    clearFilters() {
        document.querySelectorAll('.qc-cat-cb, .qc-marca-cb').forEach(x => x.checked = false);
        const st = document.getElementById('filter-stock'); if (st) st.checked = false;
        this.filters = { ordenar: this.filters.ordenar };  // conserva el orden, limpia el resto
        this.priceDraft = { min: 0, max: this.priceLimit };
        this.syncPriceControls();
        this.currentPage = 1;
        this.loadProducts();
        this.loadPriceDistribution();
    },

    /**
     * Actualiza el contador "N resultados" y el label del botón Filtrar
     */
    updateCounts(total) {
        const rc = document.getElementById('results-count');
        if (rc) rc.textContent = `${total} resultado${total === 1 ? '' : 's'}`;
        // el botón "Filtrar (N)" lo maneja renderPriceHistogram con el conteo en vivo del borrador
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

        const img = product.imagen_url
            ? `<img src="${product.imagen_url}" class="img-fluid rounded" alt="${this.escapeHtml(product.nombre)}">`
            : `<div class="qc-img-ph qc-img-ph-lg"><i class="bi bi-cpu"></i></div>`;

        // ponytail: specs reales de las columnas que tenemos. La tabla rica del
        // mockup (núcleos, frecuencia, socket…) necesita un modelo de specs que
        // aún no existe; se agrega cuando haya datos por producto.
        const specs = [
            ['Marca', product.marca],
            ['Categoría', product.categoria_nombre],
            ['SKU', 'PROD-' + product.id],
            ['Disponibilidad', product.sin_stock ? 'Agotado' : `${product.stock} unidades`],
            ['Garantía', '12 meses oficial'],
        ].filter(([, v]) => v)
         .map(([k, v]) => `<tr><td class="qc-spec-k">${k}</td><td class="qc-spec-v">${this.escapeHtml(String(v))}</td></tr>`)
         .join('');

        const addButton = product.sin_stock
            ? '<button class="btn btn-secondary w-100" disabled>Sin Stock</button>'
            : `<button class="btn btn-accent w-100" id="btn-add-detail"><i class="bi bi-cart-plus"></i> Agregar al carrito</button>`;

        container.innerHTML = `
            <div class="qc-detail row g-4">
                <div class="col-lg-6">${img}</div>
                <div class="col-lg-6">
                    <div class="qc-detail-brand">${this.escapeHtml(product.marca || '')}</div>
                    <h1 class="qc-detail-title">${this.escapeHtml(product.nombre)}</h1>
                    <div class="qc-detail-rating text-muted small mb-2" id="detail-rating">
                        <span class="text-muted">Cargando reseñas…</span>
                    </div>
                    <div class="qc-detail-price">
                        ${product.precio_formateado}
                        ${product.precio_anterior_formateado ? `<span class="qc-detail-price-old">${product.precio_anterior_formateado}</span><span class="qc-badge-oferta">-${product.descuento_pct}%</span>` : ''}
                    </div>
                    <div class="qc-detail-stock ${product.sin_stock ? 'agotado' : ''}">
                        <i class="bi ${product.sin_stock ? 'bi-x-circle' : 'bi-check-circle'}"></i>
                        ${product.sin_stock ? 'Sin stock' : 'En Stock — disponible'}
                    </div>
                    <p class="qc-detail-desc">${this.escapeHtml(product.descripcion || 'Sin descripción')}</p>

                    <div class="qc-spec-title">Especificaciones</div>
                    <table class="qc-spec-table"><tbody>${specs}</tbody></table>

                    <ul class="qc-detail-info">
                        <li><i class="bi bi-truck"></i> Envío: <b>Gratis a todo Chile</b></li>
                        <li><i class="bi bi-clock"></i> Tiempo de entrega: <b>2-3 días hábiles</b></li>
                        <li><i class="bi bi-shield-check"></i> Garantía: <b>12 meses oficial</b></li>
                    </ul>

                    ${product.sin_stock ? '' : `
                    <div class="qc-detail-qty">
                        <span>Cantidad:</span>
                        <div class="qc-stepper">
                            <button type="button" id="qty-minus">−</button>
                            <input type="number" id="qty-detail" value="1" min="1" max="${product.stock}">
                            <button type="button" id="qty-plus">+</button>
                        </div>
                    </div>`}

                    <div class="qc-detail-actions">
                        ${addButton}
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-uct w-100" id="btn-fav"><i class="bi bi-heart"></i> Agregar a favoritos</button>
                            <button type="button" class="btn btn-outline-uct w-100 qc-compare-toggle${window.Compare?.isSelected(product.id) ? ' active' : ''}" data-id="${product.id}" data-name="${this.escapeHtml(product.nombre)}"><i class="bi bi-arrow-left-right"></i> Comparar</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="qc-reviews mt-5" id="detail-reviews"></div>
            <div class="qc-similares mt-5">
                <h2 class="qc-similares-title">Productos similares</h2>
                <div class="row" id="similares-grid"></div>
            </div>`;

        this.wireDetail(product);
        this.initFavButton(product);
        this.loadResenas(product.id);
        this.loadSimilares(product);
    },

    /** Estrellas llenas/vacías para mostrar (n = 0..5) */
    stars(n) {
        const full = Math.round(n);
        return '<span class="qc-stars">' + '★'.repeat(full) + '☆'.repeat(5 - full) + '</span>';
    },

    /** Carga rating + lista de reseñas + form (si hay sesión) */
    async loadResenas(productId) {
        const ratingEl = document.getElementById('detail-rating');
        const box = document.getElementById('detail-reviews');
        let resenas = [], resumen = { promedio: 0, total: 0 };
        try {
            const resp = await fetch(`${App.apiBase}/catalogo/${productId}/resenas`);
            const data = await resp.json();
            if (data.success) { resenas = data.data.resenas; resumen = data.data.resumen; }
        } catch (e) { /* deja vacío */ }

        if (ratingEl) {
            ratingEl.innerHTML = resumen.total > 0
                ? `${this.stars(resumen.promedio)} <b>${resumen.promedio}</b> · ${resumen.total} reseña${resumen.total === 1 ? '' : 's'}`
                : `${this.stars(0)} Sin reseñas aún`;
        }
        if (!box) return;

        const lista = resenas.length
            ? resenas.map(r => `
                <div class="qc-review">
                    <div class="qc-review-head">
                        <span class="qc-review-author">${this.escapeHtml(r.autor)}</span>
                        ${this.stars(r.calificacion)}
                        <span class="qc-review-date">${(r.created_at || '').slice(0, 10)}</span>
                    </div>
                    ${r.comentario ? `<p class="qc-review-body">${this.escapeHtml(r.comentario)}</p>` : ''}
                </div>`).join('')
            : '<p class="text-muted">Sé el primero en opinar sobre este producto.</p>';

        const form = App.token ? `
            <form class="qc-review-form" id="review-form">
                <h6>Deja tu reseña</h6>
                <label>Calificación</label>
                <select id="review-stars" class="form-select form-select-sm mb-2" style="max-width:160px">
                    <option value="5">★★★★★ (5)</option>
                    <option value="4">★★★★ (4)</option>
                    <option value="3">★★★ (3)</option>
                    <option value="2">★★ (2)</option>
                    <option value="1">★ (1)</option>
                </select>
                <textarea id="review-text" class="form-control form-control-sm mb-2" rows="3" placeholder="Cuéntanos tu experiencia (opcional)"></textarea>
                <button class="btn btn-accent btn-sm" type="submit">Publicar reseña</button>
            </form>`
            : '<p class="text-muted small">Inicia sesión para dejar una reseña.</p>';

        box.innerHTML = `<h2 class="qc-similares-title">Reseñas</h2>
            <div class="qc-reviews-list">${lista}</div>
            ${form}`;

        document.getElementById('review-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type=submit]');
            btn.disabled = true;
            try {
                const resp = await App.fetchAuth(`${App.apiBase}/catalogo/${productId}/resenas`, {
                    method: 'POST',
                    body: JSON.stringify({
                        calificacion: parseInt(document.getElementById('review-stars').value),
                        comentario: document.getElementById('review-text').value
                    })
                });
                const data = await resp.json();
                if (data.success) {
                    App.showToast('¡Gracias por tu reseña!', 'success');
                    this.loadResenas(productId);   // recarga lista + rating
                } else {
                    App.showToast(data.error?.message || 'No se pudo guardar', 'error');
                    btn.disabled = false;
                }
            } catch (err) {
                App.showToast('Error de conexión', 'error');
                btn.disabled = false;
            }
        });
    },

    /** Estado + toggle del botón favorito del detalle */
    async initFavButton(product) {
        const btn = document.getElementById('btn-fav');
        if (!btn) return;
        if (!App.token) {
            btn.addEventListener('click', () => App.showToast('Inicia sesión para guardar favoritos', 'info'));
            return;
        }
        // estado inicial: ¿ya está en favoritos?
        let fav = false;
        try {
            const data = await (await App.fetchAuth(`${App.apiBase}/favoritos`)).json();
            fav = data.success && data.data.ids.includes(product.id);
        } catch (e) { /* asume no-fav */ }

        const paint = () => {
            btn.innerHTML = fav
                ? '<i class="bi bi-heart-fill"></i> En favoritos'
                : '<i class="bi bi-heart"></i> Agregar a favoritos';
            btn.classList.toggle('active', fav);
        };
        paint();

        btn.addEventListener('click', async () => {
            btn.disabled = true;
            try {
                const opts = fav
                    ? { method: 'DELETE' }
                    : { method: 'POST', body: JSON.stringify({ producto_id: product.id }) };
                const url = fav ? `${App.apiBase}/favoritos/${product.id}` : `${App.apiBase}/favoritos`;
                const data = await (await App.fetchAuth(url, opts)).json();
                if (data.success) {
                    fav = !fav;
                    paint();
                    App.showToast(fav ? 'Agregado a favoritos' : 'Quitado de favoritos', 'success');
                }
            } catch (e) {
                App.showToast('Error de conexión', 'error');
            }
            btn.disabled = false;
        });
    },

    /** Vista #/favoritos: grid con los productos guardados del usuario */
    async loadFavoritos() {
        const view = document.getElementById('view-generic');
        if (!view) return;
        if (!App.token) {
            UI.mostrarVacio(view, {
                icono: 'bi-heart',
                titulo: 'Mis favoritos',
                descripcion: App.token ? 'Aún no tienes productos favoritos. Explora el catálogo.' : 'Inicia sesión para ver tu lista de favoritos.',
                textoBoton: 'Ir al catálogo',
                enlaceBoton: '#/catalogo'
            });
        }
        view.innerHTML = UI.loader('Cargando favoritos...');
        try {
            const data = await (await App.fetchAuth(`${App.apiBase}/favoritos`)).json();
            const productos = data.data?.productos || [];
            view.innerHTML = `<h2 class="qc-similares-title mb-3">Mis favoritos (${productos.length})</h2>
                <div class="row" id="fav-grid">${
                    productos.length
                        ? productos.map(p => this.productCard(p,
                            `<button class="btn btn-outline-danger btn-sm w-100 mt-1 fav-remove" data-id="${p.id}"><i class="bi bi-trash"></i> Quitar</button>`)).join('')
                        : '<p class="text-muted">Aún no tienes productos favoritos. <a href="#/catalogo">Explora el catálogo</a>.</p>'
                }</div>`;

            view.querySelectorAll('.fav-remove').forEach(b => b.addEventListener('click', async () => {
                b.disabled = true;
                try {
                    const r = await (await App.fetchAuth(`${App.apiBase}/favoritos/${b.dataset.id}`, { method: 'DELETE' })).json();
                    if (r.success) { this.loadFavoritos(); App.showToast('Quitado de favoritos', 'success'); }
                } catch (e) { App.showToast('Error de conexión', 'error'); b.disabled = false; }
            }));
        } catch (e) {
            view.innerHTML = '<div class="alert alert-danger">Error al cargar favoritos.</div>';
        }
    },

    /** Conecta stepper, botón agregar, favoritos y comparar del detalle */
    wireDetail(product) {
        const qtyInput = document.getElementById('qty-detail');
        const clampQty = () => {
            let v = parseInt(qtyInput.value) || 1;
            qtyInput.value = Math.min(Math.max(v, 1), product.stock || 1);
        };
        document.getElementById('qty-minus')?.addEventListener('click', () => { qtyInput.value = (parseInt(qtyInput.value) || 1) - 1; clampQty(); });
        document.getElementById('qty-plus')?.addEventListener('click', () => { qtyInput.value = (parseInt(qtyInput.value) || 1) + 1; clampQty(); });
        qtyInput?.addEventListener('change', clampQty);

        const btnDetail = document.getElementById('btn-add-detail');
        btnDetail?.addEventListener('click', async () => {
            const qty = parseInt(qtyInput.value) || 1;
            btnDetail.disabled = true;
            const original = btnDetail.innerHTML;
            btnDetail.textContent = 'Agregando...';
            try {
                const resp = await App.fetchAuth(`${App.apiBase}/carrito`, {
                    method: 'POST',
                    body: JSON.stringify({ producto_id: product.id, cantidad: qty, session_id: App.getSessionId() })
                });
                const data = await resp.json();
                if (data.success) {
                    // actualiza badge + total del header + offcanvas con la respuesta del POST (sin 2do fetch)
                    if (typeof Carrito !== 'undefined') Carrito.applyCart(data.data);
                    else { App.cartCount = data.data.items ? data.data.items.length : 0; App.updateCartBadge(); }
                    App.showToast(`${product.nombre} (x${qty}) agregado al carrito`, 'success');
                } else {
                    App.showToast(data.error?.message || 'Error', 'error');
                }
            } catch (err) {
                App.showToast('Error de conexión', 'error');
            }
            btnDetail.disabled = false;
            btnDetail.innerHTML = original;
        });

        // favoritos lo maneja initFavButton(); comparar lo maneja Compare (handler global por .qc-compare-toggle).
    },

    /** Carga 4 productos de la misma categoría (excluye el actual) */
    async loadSimilares(product) {
        const grid = document.getElementById('similares-grid');
        if (!grid || !product.id_categoria) return;
        try {
            const resp = await fetch(`${App.apiBase}/catalogo?categorias=${product.id_categoria}&por_pagina=5`);
            const data = await resp.json();
            const items = (data.data || [])
                .filter(p => p.id !== product.id)
                .slice(0, 4);
            grid.innerHTML = items.map(p => this.productCard(p)).join('')
                || '<p class="text-muted">No hay productos similares.</p>';
        } catch (e) {
            grid.innerHTML = '';
        }
    }
};
