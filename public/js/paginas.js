/**
 * paginas.js — Páginas informativas del footer  ·  TAREA LEONARDO #3
 * Se edita solo el objeto "contenido" (+ estilos en css/leo.css). HTML puro.
 */
const Paginas = {
    contenido: {
        despacho: {
            titulo: 'Despacho y cobertura',
            html: `
                <p class="leo-lead">Enviamos a todo Chile con seguimiento en línea. Si compras antes de las 15:00 h, preparamos tu pedido el mismo día hábil.</p>
                <div class="leo-feature-grid">
                    <div class="leo-feature"><i class="bi bi-truck"></i><h4>Despacho gratis</h4><p>En compras sobre $50.000 a todo el país.</p></div>
                    <div class="leo-feature"><i class="bi bi-buildings"></i><h4>Región Metropolitana</h4><p>Entrega en 2 a 3 días hábiles.</p></div>
                    <div class="leo-feature"><i class="bi bi-geo"></i><h4>Regiones</h4><p>De 3 a 6 días hábiles según destino.</p></div>
                    <div class="leo-feature"><i class="bi bi-shop"></i><h4>Retiro en tienda</h4><p>Gratis en Av. Providencia 1234, Santiago.</p></div>
                </div>
                <div class="leo-highlight"><i class="bi bi-box-seam"></i> Recibirás un correo con el número de seguimiento apenas tu pedido sea despachado.</div>`
        },
        garantia: {
            titulo: 'Garantía y devoluciones',
            html: `
                <p class="leo-lead">Todos nuestros productos cuentan con <strong>garantía oficial de 12 meses</strong> contra fallas de fabricación.</p>
                <h4 class="leo-subtitle">Cambios y devoluciones</h4>
                <ul>
                    <li>Tienes 10 días corridos desde la recepción para solicitar un cambio o devolución.</li>
                    <li>El producto debe estar en su empaque original, con todos sus accesorios.</li>
                    <li>La garantía no cubre daños por mal uso, golpes o manipulación indebida.</li>
                </ul>
                <h4 class="leo-subtitle">¿Cómo solicitarla?</h4>
                <ol class="leo-steps">
                    <li>Escríbenos a <strong>garantia@quadcore.cl</strong> indicando tu número de pedido.</li>
                    <li>Te enviamos una etiqueta de despacho sin costo.</li>
                    <li>Revisamos el producto y resolvemos en un máximo de 5 días hábiles.</li>
                </ol>`
        },
        'medios-pago': {
            titulo: 'Medios de pago',
            html: `
                <p class="leo-lead">Compra con total seguridad. Procesamos los pagos mediante Webpay con cifrado de extremo a extremo.</p>
                <div class="leo-feature-grid">
                    <div class="leo-feature"><i class="bi bi-credit-card"></i><h4>Tarjetas</h4><p>Crédito y débito a través de Webpay Plus.</p></div>
                    <div class="leo-feature"><i class="bi bi-bank"></i><h4>Transferencia</h4><p>Acreditación rápida desde cualquier banco.</p></div>
                    <div class="leo-feature"><i class="bi bi-wallet2"></i><h4>Cuotas</h4><p>Hasta 3 cuotas sin interés en productos seleccionados.</p></div>
                </div>
                <div class="leo-highlight"><i class="bi bi-shield-lock"></i> QuadCore no almacena los datos de tu tarjeta: el pago se realiza directamente en la plataforma segura de Transbank.</div>`
        },
        nosotros: {
            titulo: 'Quiénes somos',
            html: `
                <p class="leo-lead">QuadCore SpA — <em>El Corazón de la Electrónica</em>. Tienda chilena especializada en componentes, accesorios, herramientas y servicio técnico para PC.</p>
                <p>Nacimos en Santiago con una idea simple: que armar, reparar o mejorar un computador sea fácil, transparente y al mejor precio. Trabajamos con marcas reconocidas y un equipo técnico certificado que te acompaña antes y después de tu compra.</p>
                <div class="leo-stats">
                    <div class="leo-stat"><strong>+5.000</strong><span>clientes felices</span></div>
                    <div class="leo-stat"><strong>+500</strong><span>productos en stock</span></div>
                    <div class="leo-stat"><strong>12 meses</strong><span>de garantía oficial</span></div>
                </div>
                <div class="leo-highlight"><i class="bi bi-heart"></i> Nuestra misión: dar vida a la tecnología de cada persona en Chile, con asesoría honesta y soporte real.</div>`
        }
    },

    render(slug) {
        const view = document.getElementById('view-generic');
        if (!view) return;
        const pag = this.contenido[slug];
        if (!pag) {
            view.innerHTML = '<div class="empty-state"><h5>Página no encontrada</h5></div>';
            return;
        }
        view.innerHTML = `
            <div class="leo-pagina">
                <h2 class="qc-similares-title">${pag.titulo}</h2>
                <div class="leo-pagina-body">${pag.html}</div>
            </div>`;
    }
};