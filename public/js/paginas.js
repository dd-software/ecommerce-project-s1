const Paginas = {
    contenido: {
        despacho: {
            titulo: 'Despacho y cobertura',
            html: `
                <p class="leo-lead">En QuadCore sabemos que la espera es lo más difícil. Por eso trabajamos con las mejores empresas de logística para que tu pedido llegue rápido y con seguimiento en tiempo real a todo Chile.</p>
                <div class="leo-feature-grid">
                    <div class="leo-feature"><i class="bi bi-truck"></i><h4>Envío gratis</h4><p>En compras superiores a $50.000, el despacho es sin costo para toda la República.</p></div>
                    <div class="leo-feature"><i class="bi bi-buildings"></i><h4>Región Metropolitana</h4><p>Entregamos en 2 a 3 días hábiles. Si vives en Santiago, tu pedido llega en menos de 48 horas.</p></div>
                    <div class="leo-feature"><i class="bi bi-geo"></i><h4>Regiones</h4><p>De 3 a 6 días hábiles, dependiendo de la distancia. Hacemos envíos a todas las regiones, incluida la Isla de Pascua.</p></div>
                    <div class="leo-feature"><i class="bi bi-shop"></i><h4>Retiro en tienda</h4><p>Puedes pasar a buscar tu compra sin costo en nuestra sucursal de Av. Providencia 1234, Santiago. Te avisamos cuando esté lista.</p></div>
                </div>
                <div class="leo-highlight"><i class="bi bi-box-seam"></i> <strong>¿Compraste antes de las 15:00 h?</strong> Preparamos y despachamos tu pedido el mismo día hábil. Recibirás un correo con el número de seguimiento para que monitorees tu envío en todo momento.</div>
            `
        },
        garantia: {
            titulo: 'Garantía y devoluciones',
            html: `
                <p class="leo-lead">Todos los productos comercializados por QuadCore cuentan con <strong>garantía oficial de 12 meses</strong> contra defectos de fabricación. Tu tranquilidad es nuestra prioridad.</p>
                <h4 class="leo-subtitle">Condiciones para cambios y devoluciones</h4>
                <ul>
                    <li>Tienes <strong>10 días corridos</strong> desde la recepción del producto para solicitar un cambio o devolución.</li>
                    <li>El producto debe estar en su empaque original, sin señales de uso y con todos sus accesorios y manuales.</li>
                    <li>La garantía no cubre daños por uso indebido, golpes, caídas, manipulación no autorizada o desgaste normal.</li>
                    <li>Si el producto presenta fallas, cubrimos los costos de envío para la revisión técnica.</li>
                </ul>
                <h4 class="leo-subtitle">¿Cómo gestionar tu garantía?</h4>
                <ol class="leo-steps">
                    <li>Escríbenos a <strong>garantia@quadcore.cl</strong> con tu número de pedido y una breve descripción del problema.</li>
                    <li>Te enviaremos una etiqueta de despacho prepagada para que nos devuelvas el producto sin costo.</li>
                    <li>Nuestro equipo técnico revisará el equipo y te dará una respuesta en <strong>un máximo de 5 días hábiles</strong>.</li>
                    <li>Si corresponde a garantía, te reembolsaremos el dinero o enviaremos un producto nuevo de reemplazo, según tu preferencia.</li>
                </ol>
                <div class="leo-highlight"><i class="bi bi-headset"></i> ¿Tienes dudas? Nuestro equipo de soporte está disponible de lunes a viernes de 9:00 a 18:00 en el <strong>+56 2 2123 4567</strong> o vía chat en nuestra web.</div>
            `
        },
        'medios-pago': {
            titulo: 'Medios de pago',
            html: `
                <p class="leo-lead">En QuadCore ofrecemos diversas opciones de pago para que elijas la que mejor se adapte a ti. Todas nuestras transacciones están protegidas con cifrado de extremo a extremo.</p>
                <div class="leo-feature-grid">
                    <div class="leo-feature"><i class="bi bi-credit-card"></i><h4>Tarjetas de crédito y débito</h4><p>Aceptamos Visa, Mastercard, American Express y más a través de Webpay Plus. Puedes pagar en una cuota o hasta en <strong>3 cuotas sin interés</strong> en productos seleccionados.</p></div>
                    <div class="leo-feature"><i class="bi bi-bank"></i><h4>Transferencia bancaria</h4><p>Paga desde tu cuenta corriente o vista en cualquier banco chileno. Una vez acreditado el monto, liberamos tu pedido inmediatamente.</p></div>
                    <div class="leo-feature"><i class="bi bi-wallet2"></i><h4>Pago en efectivo</h4><p>Si prefieres, puedes realizar el depósito en efectivo en sucursales de BancoEstado o mediante Servipag.</p></div>
                </div>
                <div class="leo-highlight"><i class="bi bi-shield-lock"></i> <strong>Seguridad ante todo:</strong> QuadCore no almacena los datos de tu tarjeta. El pago se procesa directamente en la plataforma segura de Transbank, garantizando la confidencialidad de tu información financiera.</div>
            `
        },
        nosotros: {
            titulo: 'Quiénes somos',
            html: `
                <p class="leo-lead">QuadCore SpA — <em>El Corazón de la Electrónica</em>. Somos una tienda chilena apasionada por la tecnología, especializada en componentes, accesorios, herramientas y servicio técnico para PC.</p>
                <p>Nacimos en Santiago con una misión clara: <strong>hacer que armar, reparar o mejorar un computador sea fácil, transparente y al mejor precio</strong>. Trabajamos con marcas reconocidas mundialmente y contamos con un equipo técnico certificado que te acompaña antes, durante y después de tu compra.</p>
                <div class="leo-stats">
                    <div class="leo-stat"><strong>+5.000</strong><span>clientes satisfechos</span></div>
                    <div class="leo-stat"><strong>+500</strong><span>productos en stock</span></div>
                    <div class="leo-stat"><strong>12 meses</strong><span>de garantía oficial</span></div>
                    <div class="leo-stat"><strong>99%</strong><span>de entregas a tiempo</span></div>
                </div>
                <div class="leo-highlight"><i class="bi bi-heart"></i> <strong>Nuestro propósito:</strong> dar vida a la tecnología de cada persona en Chile, ofreciendo asesoría honesta, soporte real y un servicio que trasciende la venta. ¡Gracias por confiar en nosotros!</div>
            `
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