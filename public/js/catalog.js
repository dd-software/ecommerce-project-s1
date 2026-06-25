let currentModal = null;
let allProducts   = [];

function renderProducts(products, heading) {
    const container  = document.getElementById('product-list');
    const headingEl  = document.getElementById('products-heading');

    if (heading && headingEl) headingEl.textContent = heading;

    if (!products || products.length === 0) {
        container.innerHTML = '<p class="text-muted col-12 mt-3">No se encontraron productos.</p>';
        return;
    }

    container.innerHTML = products.map(product => `
        <div class="col-6 col-md-4 col-lg-3">
            <div class="product-card">
                <div class="product-img-wrapper">
                    <img src="https://picsum.photos/seed/${product.id}/400/400"
                         class="product-img w-100" alt="${product.name}">
                </div>
                <div class="product-body">
                    <div class="product-brand">${product.category_name || 'UCT Exclusivo'}</div>
                    <h5 class="product-title mb-4">${product.name}</h5>
                    <button class="btn btn-outline-uct w-100 mt-auto"
                            onclick="viewProduct(${product.id})">
                        <i class="bi bi-cart2 me-2"></i> Ver Opciones
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

async function loadProducts() {
    const container = document.getElementById('product-list');
    container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-uct-blue" role="status"></div></div>';
    try {
        const res  = await api.request('/catalog/products');
        allProducts = res.data;
        renderProducts(allProducts, 'Todos los Productos');
    } catch (e) {
        container.innerHTML = '<p class="text-danger col-12">Error al cargar el catálogo.</p>';
    }
}

async function loadCategories() {
    try {
        const res       = await api.request('/catalog/categories');
        const container = document.getElementById('category-filter');
        if (!container) return;

        const btns = [
            `<button class="btn btn-primary btn-sm" onclick="filterByCategory(null, this)">Todos</button>`,
            ...res.data.map(c =>
                `<button class="btn btn-outline-secondary btn-sm" onclick="filterByCategory(${c.id}, this)">${c.name}</button>`
            )
        ].join('');
        container.innerHTML = btns;
    } catch (e) { /* categorías son opcionales */ }
}

window.filterByCategory = async function(categoryId, btn) {
    document.querySelectorAll('#category-filter button').forEach(b => {
        b.className = 'btn btn-outline-secondary btn-sm';
    });
    btn.className = 'btn btn-primary btn-sm';

    const searchInput = document.querySelector('.search-input');
    if (searchInput) searchInput.value = '';

    const container = document.getElementById('product-list');
    container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-uct-blue" role="status"></div></div>';

    if (!categoryId) {
        renderProducts(allProducts, 'Todos los Productos');
        return;
    }

    try {
        const res = await api.request(`/catalog/products?category_id=${categoryId}`);
        renderProducts(res.data, btn.textContent.trim());
    } catch (e) {
        container.innerHTML = '<p class="text-danger col-12">Error al cargar los productos.</p>';
    }
};

window.performSearch = async function(query) {
    if (!query) {
        const firstBtn = document.querySelector('#category-filter button');
        if (firstBtn) {
            window.filterByCategory(null, firstBtn);
        } else {
            loadProducts();
        }
        return;
    }

    document.querySelectorAll('#category-filter button').forEach(b => {
        b.className = 'btn btn-outline-secondary btn-sm';
    });

    const container = document.getElementById('product-list');
    container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-uct-blue" role="status"></div></div>';

    try {
        const res = await api.request(`/catalog/products/search?q=${encodeURIComponent(query)}`);
        renderProducts(res.data, `Resultados para "${query}"`);
    } catch (e) {
        container.innerHTML = '<p class="text-danger col-12">Error al buscar productos.</p>';
    }
};

async function viewProduct(id) {
    try {
        const res     = await api.request(`/catalog/products/${id}`);
        const product = res.data;
        document.getElementById('modalTitle').innerText = product.name;

        const variantsHtml = product.variants.map(v => {
            const attrs    = JSON.stringify(v.attributes).replace(/["{}]/g, ' ').trim();
            const disabled = v.stock <= 0 ? 'disabled' : '';
            const badge    = v.stock > 0
                ? `<span class="badge text-bg-success">${v.stock} disponibles</span>`
                : `<span class="badge text-bg-danger">Agotado</span>`;
            return `
                <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                    <div>
                        <h5 class="mb-1 text-primary">$${v.price}</h5>
                        <small class="text-muted d-block">${attrs}</small>
                        ${badge}
                    </div>
                    <button class="btn btn-outline-primary" ${disabled}
                            onclick="addToCart(${v.id})">Agregar</button>
                </div>
            `;
        }).join('');

        document.getElementById('modalBody').innerHTML =
            `<p class="mb-4">${product.description}</p>${variantsHtml}`;

        if (!currentModal) {
            currentModal = new bootstrap.Modal(document.getElementById('productModal'));
        }
        currentModal.show();
    } catch(e) {}
}

async function addToCart(variantId) {
    if (!api.getToken()) {
        ui.showToast('Requiere iniciar sesión para comprar', 'warning');
        setTimeout(() => window.location.href = 'login.html', 1500);
        return;
    }
    try {
        await api.request('/cart', 'POST', { variant_id: variantId, quantity: 1 });
        ui.showToast('Producto agregado al carrito con éxito');
    } catch (e) {}
}

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    loadCategories();
});
