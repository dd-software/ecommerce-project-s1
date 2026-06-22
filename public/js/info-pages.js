/**
 * info-pages.js — Páginas institucionales (Venta Empresas, Tiendas) + menú de
 * categorías del header. Estilo PCFactory adaptado a QuadCore. Contenido estático.
 */

const Info = {
    /** #/empresas — landing B2B */
    empresas() {
        const view = document.getElementById('view-generic');
        if (!view) return;
        view.innerHTML = `
            <div class="qc-info">
                <div class="qc-info-hero">
                    <span class="qc-info-kicker"><i class="bi bi-buildings"></i> Venta Empresas</span>
                    <h1>Equipa tu empresa con QuadCore</h1>
                    <p>Precios mayoristas, facturación y un ejecutivo dedicado para compras por volumen en todo Chile.</p>
                </div>
                <div class="qc-info-grid">
                    <div class="qc-info-card"><i class="bi bi-tags"></i><h3>Precios por volumen</h3><p>Descuentos especiales según la cantidad de tu cotización.</p></div>
                    <div class="qc-info-card"><i class="bi bi-receipt"></i><h3>Facturación</h3><p>Boleta o factura electrónica, con datos de tu empresa.</p></div>
                    <div class="qc-info-card"><i class="bi bi-person-badge"></i><h3>Ejecutivo dedicado</h3><p>Un contacto directo que gestiona tus pedidos y postventa.</p></div>
                    <div class="qc-info-card"><i class="bi bi-truck"></i><h3>Despacho a todo Chile</h3><p>Coordinamos la entrega donde tu empresa lo necesite.</p></div>
                </div>
                <div class="qc-info-cta">
                    <div>
                        <h2>¿Listo para cotizar?</h2>
                        <p>Escríbenos con el detalle de lo que necesitas y te respondemos dentro de 24 h hábiles.</p>
                    </div>
                    <a href="#/contacto" class="btn btn-accent">Solicitar cotización</a>
                </div>
            </div>`;
    },

    /** #/tiendas — ubicación y horarios */
    tiendas() {
        const view = document.getElementById('view-generic');
        if (!view) return;
        const tienda = (nombre, dir, horario, mapSrc) => `
            <div class="qc-info-card qc-tienda">
                <iframe class="qc-mapframe" title="Mapa ${nombre}" loading="lazy" src="${mapSrc}"></iframe>
                <h3>${nombre}</h3>
                <p><i class="bi bi-geo-alt"></i> ${dir}</p>
                <p><i class="bi bi-clock"></i> ${horario}</p>
            </div>`;
        view.innerHTML = `
            <div class="qc-info">
                <div class="qc-info-hero">
                    <span class="qc-info-kicker"><i class="bi bi-shop"></i> Nuestras tiendas</span>
                    <h1>Visítanos y retira al instante</h1>
                    <p>Compra online y retira gratis en tienda, o acércate a ver los productos en persona.</p>
                </div>
                <div class="qc-info-grid">
                    ${tienda('QuadCore Providencia', CONTACT_INFO.address, CONTACT_INFO.schedule, 'https://www.openstreetmap.org/export/embed.html?bbox=-70.6250,-33.4305,-70.6130,-33.4225&layer=mapnik&marker=-33.4265,-70.6190')}
                    ${tienda('QuadCore Centro', 'Bandera 456, Santiago Centro', 'Lun a Vie 10–19h · Sáb 10–14h', 'https://www.openstreetmap.org/export/embed.html?bbox=-70.6590,-33.4440,-70.6470,-33.4360&layer=mapnik&marker=-33.4400,-70.6530')}
                </div>
                <div class="qc-info-cta">
                    <div><h2>Retiro en tienda gratis</h2><p>Disponible apenas tu pedido esté listo. Te avisamos por correo.</p></div>
                    <a href="#/catalogo" class="btn btn-accent">Ir al catálogo</a>
                </div>
            </div>`;
    },

    /** Rellena el menú "Categorías" del header con las categorías reales */
    async loadCategoriasMenu() {
        const menu = document.getElementById('cat-menu');
        if (!menu) return;
        try {
            const data = await (await fetch(`${App.apiBase}/catalogo/categorias`)).json();
            menu.innerHTML = (data.data || []).map(c =>
                `<li><a class="dropdown-item" href="#/catalogo?cat=${encodeURIComponent(c.slug)}">
                    <i class="bi ${Catalogo.catIcon(c.slug)}"></i>
                    <span>${Catalogo.escapeHtml(c.nombre)}</span>
                    <span class="cat-menu-count">${c.total_productos ?? 0}</span>
                </a></li>`
            ).join('');
        } catch (e) { /* el botón queda sin items */ }
    }
};

// Hacer Info global (como el resto de objetos)
window.Info = Info;