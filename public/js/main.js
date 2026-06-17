document.addEventListener('DOMContentLoaded', () => {
    App.init();
    Catalogo.init();

    // Botón "Ver productos" en hero: scroll suave
    const btnScroll = document.getElementById('btn-scroll-products');
    if (btnScroll) {
        btnScroll.addEventListener('click', () => {
            document.getElementById('products-section').scrollIntoView({ behavior: 'smooth' });
        });
    }

    // Footer "Todos los productos" — usa el mismo filtro del catálogo
    const footerTodos = document.getElementById('footer-cat-all');
    if (footerTodos) {
        footerTodos.addEventListener('click', (e) => {
            e.preventDefault();
            Catalogo.filters.categoria = null;
            Catalogo.currentPage = 1;
            Catalogo.loadProducts();
            document.getElementById('products-section').scrollIntoView({ behavior: 'smooth' });
        });
    }

    // Sincronizar categorías a la barra de navegación superior
    const origRenderCats = Catalogo.renderCategories.bind(Catalogo);
    Catalogo.renderCategories = function(categories) {
        origRenderCats(categories);
        const nav = document.getElementById('category-nav');
        categories.forEach(c => {
            if (!c.id_padre) {
                const li = document.createElement('li');
                li.className = 'nav-item';
                li.innerHTML = `<a class="nav-link text-white text-nowrap" href="#" data-cat="${c.id}">${Catalogo.escapeHtml(c.nombre)}</a>`;
                li.querySelector('a').addEventListener('click', e => {
                    e.preventDefault();
                    document.querySelectorAll('#category-nav .nav-link').forEach(el => el.classList.remove('active'));
                    li.querySelector('a').classList.add('active');
                    Catalogo.filters.categoria = String(c.id);
                    Catalogo.currentPage = 1;
                    Catalogo.loadProducts();
                    document.getElementById('products-section').scrollIntoView({ behavior: 'smooth' });
                });
                nav.appendChild(li);
            }
        });
    };

    // Botón "Todos" en la nav de categorías
    document.getElementById('cat-all').addEventListener('click', e => {
        e.preventDefault();
        document.querySelectorAll('#category-nav .nav-link').forEach(el => el.classList.remove('active'));
        document.getElementById('cat-all').classList.add('active');
        Catalogo.filters.categoria = null;
        Catalogo.currentPage = 1;
        Catalogo.loadProducts();
    });

    // Búsqueda en tiempo real
    let searchTimer;
    document.getElementById('search-input').addEventListener('input', e => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            Catalogo.filters.q = e.target.value.trim();
            Catalogo.currentPage = 1;
            Catalogo.loadProducts();
        }, 400);
    });
    document.getElementById('search-form').addEventListener('submit', e => {
        e.preventDefault();
        clearTimeout(searchTimer);
        Catalogo.filters.q = document.getElementById('search-input').value.trim();
        Catalogo.currentPage = 1;
        Catalogo.loadProducts();
    });

    // Cargar carrito al abrir offcanvas
    document.getElementById('cartOffcanvas').addEventListener('show.bs.offcanvas', () => {
        Carrito.loadCart();
    });

    // Inicializar checkout al abrir modal
    document.getElementById('checkoutModal').addEventListener('show.bs.modal', () => {
        if (App.user) Checkout.init();
    });
});
