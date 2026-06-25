const Paginas = {
    contenido: {
        despacho: {
            titulo: 'Despacho y cobertura',
            html: `
            <div class="gw-page">
              <header class="gw-head">
                <p class="gw-kicker"><i class="bi bi-truck"></i> Envíos</p>
                <h1>Despacho y cobertura</h1>
                <p class="lead">Trabajamos con empresas de logística confiables para que tu pedido llegue rápido y con seguimiento, a todo Chile.</p>
              </header>
              <div class="gw-body">
                <div class="gw-cards">
                  <div class="gw-card"><div class="ic"><i class="bi bi-truck"></i></div><h3>Envío gratis</h3><p>En compras sobre $50.000, el despacho es sin costo para todo el país.</p></div>
                  <div class="gw-card"><div class="ic"><i class="bi bi-buildings"></i></div><h3>Región Metropolitana</h3><p>Entregamos en 2 a 3 días hábiles. En Santiago, suele llegar en menos de 48 horas.</p></div>
                  <div class="gw-card"><div class="ic"><i class="bi bi-geo"></i></div><h3>Regiones</h3><p>De 3 a 6 días hábiles según la distancia. Llegamos a todas las regiones, incluida Isla de Pascua.</p></div>
                  <div class="gw-card"><div class="ic"><i class="bi bi-shop"></i></div><h3>Retiro en tienda</h3><p>Retira sin costo en Av. Providencia 1234, Santiago. Te avisamos cuando esté listo.</p></div>
                </div>
                <div class="gw-note"><span class="ic"><i class="bi bi-box-seam"></i></span><div><b>¿Compraste antes de las 15:00 h?</b> Despachamos el mismo día hábil y te enviamos el número de seguimiento por correo.</div></div>
              </div>
            </div>
            `
        },
        garantia: {
            titulo: 'Garantía y devoluciones',
            html: `
            <div class="gw-page">
              <section class="gw-hero">
                <div class="inner">
                  <span class="gw-badge"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M12 2 4 5v6c0 5 3.5 8.5 8 11 4.5-2.5 8-6 8-11V5l-8-3Z" stroke="#ffb3a6" stroke-width="2" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="#ffb3a6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Garantía oficial 12 meses</span>
                  <h1 class="gw-title">Garantías y devoluciones</h1>
                  <p class="gw-lead">Todos los productos tienen garantía oficial de 12 meses. Si algo llega con falla, gestionamos el cambio o la devolución sin costo de envío.</p>
                </div>
              </section>

              <div class="gw-wrap">
                <div class="gw-grid">

                  <section>
                    <div class="sec-head">
                      <span class="sec-ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M9 11l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><rect x="4" y="4" width="16" height="16" rx="3" stroke="currentColor" stroke-width="1.8"/></svg></span>
                      <h2 class="sec-title">Condiciones para cambios y devoluciones<small>Requisitos para que aplique</small></h2>
                    </div>
                    <div class="cond-list">
                      <div class="cond ok">
                        <span class="c-ic"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <div class="c-body"><span class="c-tag">Plazo</span>Tienes <b>10 días corridos</b> desde la recepción del producto para solicitar un cambio o devolución.</div>
                      </div>
                      <div class="cond ok">
                        <span class="c-ic"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 8 12 4l8 4v8l-8 4-8-4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="m4 8 8 4 8-4M12 12v8" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>
                        <div class="c-body"><span class="c-tag">Estado del producto</span>El producto debe estar en su <b>empaque original</b>, sin señales de uso y con todos sus accesorios y manuales.</div>
                      </div>
                      <div class="cond info">
                        <span class="c-ic"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 3 3 19h18L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 10v4m0 2.5v.3" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg></span>
                        <div class="c-body"><span class="c-tag">Exclusiones</span>La garantía <b>no cubre</b> daños por uso indebido, golpes, caídas, manipulación no autorizada o desgaste normal.</div>
                      </div>
                      <div class="cond ok">
                        <span class="c-ic"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 7h11v8H3z" stroke="currentColor" stroke-width="1.8"/><path d="M14 10h4l3 3v2h-7z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="7" cy="17" r="2" stroke="currentColor" stroke-width="1.8"/><circle cx="17" cy="17" r="2" stroke="currentColor" stroke-width="1.8"/></svg></span>
                        <div class="c-body"><span class="c-tag">Costos de envío</span>Si el producto presenta fallas, <b>cubrimos los costos de envío</b> para la revisión técnica.</div>
                      </div>
                    </div>
                  </section>

                  <section>
                    <div class="sec-head">
                      <span class="sec-ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h10M4 18h13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                      <h2 class="sec-title">¿Cómo gestionar tu garantía?<small>Cómo hacerlo, paso a paso</small></h2>
                    </div>
                    <div class="steps">
                      <div class="step"><div class="s-rail"><span class="s-num">1</span></div><div class="s-body"><span class="s-title">Escríbenos</span>Envía un correo a <b>garantia@quadcore.cl</b> con tu número de pedido y una breve descripción del problema.</div></div>
                      <div class="step"><div class="s-rail"><span class="s-num">2</span></div><div class="s-body"><span class="s-title">Recibe tu etiqueta</span>Te enviaremos una <b>etiqueta de despacho prepagada</b> para que nos devuelvas el producto sin costo.</div></div>
                      <div class="step"><div class="s-rail"><span class="s-num">3</span></div><div class="s-body"><span class="s-title">Revisión técnica</span>Nuestro equipo técnico revisará el equipo y te dará una respuesta en <b>un máximo de 5 días hábiles</b>.</div></div>
                      <div class="step"><div class="s-rail"><span class="s-num">4</span></div><div class="s-body"><span class="s-title">Resolución</span>Si corresponde a garantía, te <b>reembolsamos el dinero</b> o enviamos un <b>producto de reemplazo</b>, según prefieras.</div></div>
                    </div>
                  </section>
                </div>

                <section class="gw-support">
                  <div class="s-left">
                    <span class="s-ic"><svg width="30" height="30" viewBox="0 0 24 24" fill="none"><path d="M4 13a8 8 0 0 1 16 0" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/><rect x="3" y="13" width="4" height="7" rx="2" stroke="#fff" stroke-width="1.8"/><rect x="17" y="13" width="4" height="7" rx="2" stroke="#fff" stroke-width="1.8"/><path d="M20 18v1a3 3 0 0 1-3 3h-3" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                    <div>
                      <p class="s-k">¿Tienes dudas?</p>
                      <p class="s-t">Nuestro equipo de soporte te ayuda<small>Lunes a viernes de 9:00 a 18:00 h · o vía chat en nuestra web</small></p>
                    </div>
                  </div>
                  <div class="s-actions">
                    <a href="tel:+56221234567" class="btn-w"><svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M5 4h4l2 5-3 2a12 12 0 0 0 5 5l2-3 5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg> +56 2 2123 4567</a>
                    <a href="#/contacto" class="btn-ghost"><svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg> Chatear ahora</a>
                  </div>
                </section>
              </div>
            </div>
            `
        },
        'medios-pago': {
            titulo: 'Medios de pago',
            html: `
            <div class="gw-page">
              <header class="gw-head">
                <p class="gw-kicker"><i class="bi bi-credit-card"></i> Pagos</p>
                <h1>Medios de pago</h1>
                <p class="lead">Elige la opción que más te acomode. Todas las transacciones se procesan con cifrado de extremo a extremo.</p>
              </header>
              <div class="gw-body">
                <div class="gw-cards">
                  <div class="gw-card"><div class="ic"><i class="bi bi-credit-card"></i></div><h3>Crédito y débito</h3><p>Visa, Mastercard, American Express y más vía Webpay Plus. Hasta <b>3 cuotas sin interés</b> en productos seleccionados.</p></div>
                  <div class="gw-card"><div class="ic"><i class="bi bi-bank"></i></div><h3>Transferencia bancaria</h3><p>Paga desde tu cuenta en cualquier banco chileno. Acreditado el monto, liberamos tu pedido de inmediato.</p></div>
                  <div class="gw-card"><div class="ic"><i class="bi bi-wallet2"></i></div><h3>Efectivo</h3><p>Si prefieres, deposita en efectivo en sucursales de BancoEstado o mediante Servipag.</p></div>
                </div>
                <div class="gw-note"><span class="ic"><i class="bi bi-shield-lock"></i></span><div><b>Tus datos están seguros.</b> QuadCore no almacena los datos de tu tarjeta: el pago se procesa directamente en la plataforma de Transbank.</div></div>
              </div>
            </div>
            `
        },
        nosotros: {
            titulo: 'Quiénes somos',
            html: `
            <div class="gw-page">
              <header class="gw-head">
                <p class="gw-kicker"><i class="bi bi-heart"></i> QuadCore SpA</p>
                <h1>Quiénes somos</h1>
                <p class="lead"><em>El Corazón de la Electrónica.</em> Tienda chilena especializada en componentes, accesorios, herramientas y servicio técnico para PC.</p>
              </header>
              <div class="gw-body">
                <p class="gw-prose">Nacimos en Santiago con una misión clara: <b>hacer que armar, reparar o mejorar un computador sea fácil, transparente y al mejor precio</b>. Trabajamos con marcas reconocidas y un equipo técnico certificado que te acompaña antes, durante y después de tu compra.</p>
                <div class="gw-figs">
                  <div class="gw-fig"><div class="n">+5.000</div><div class="l">clientes satisfechos</div></div>
                  <div class="gw-fig"><div class="n">+500</div><div class="l">productos en stock</div></div>
                  <div class="gw-fig"><div class="n">12 meses</div><div class="l">de garantía oficial</div></div>
                  <div class="gw-fig"><div class="n">99%</div><div class="l">entregas a tiempo</div></div>
                </div>
                <div class="gw-note"><span class="ic"><i class="bi bi-heart"></i></span><div><b>Nuestro propósito:</b> dar vida a la tecnología de cada persona en Chile, con asesoría honesta y soporte real que trasciende la venta.</div></div>
              </div>
            </div>
            `
        },

        privacidad: {
            titulo: 'Política de privacidad',
            html: `
            <div class="gw-page">
              <header class="gw-head">
                <p class="gw-kicker"><i class="bi bi-shield-lock"></i> Legal</p>
                <h1>Política de privacidad</h1>
                <p class="lead">Cómo recopilamos, usamos y protegemos tus datos personales, conforme a la Ley N° 19.628 sobre protección de la vida privada.</p>
              </header>
              <div class="gw-body"><div class="qc-terms-body">
                <h6>1. Responsable de los datos</h6>
                <p>QuadCore SpA, RUT 77.123.456-7, con domicilio en Av. Providencia 1234, Santiago de Chile, es responsable del tratamiento de los datos personales recopilados a través de este sitio.</p>
                <h6>2. Qué datos recopilamos</h6>
                <p>Recopilamos los datos que nos entregas al registrarte o comprar: nombre, apellido, correo electrónico, dirección de despacho y datos de contacto. No almacenamos información de tu tarjeta de crédito o débito: el pago se procesa directamente en la plataforma del proveedor de pagos.</p>
                <h6>3. Para qué los usamos</h6>
                <p>Usamos tus datos exclusivamente para gestionar tus compras, coordinar el despacho, emitir documentación tributaria, brindar soporte postventa, enviarte el estado de tu pedido y cumplir obligaciones legales derivadas de la relación comercial.</p>
                <h6>4. Con quién los compartimos</h6>
                <p>No vendemos ni compartimos tus datos con terceros ajenos a la prestación del servicio, salvo con las empresas de transporte para coordinar la entrega, con el procesador de pagos para validar la transacción, o cuando sea requerido por ley.</p>
                <h6>5. Tus derechos</h6>
                <p>Puedes solicitar acceder, rectificar, eliminar u oponerte al tratamiento de tus datos escribiéndonos a <b>privacidad@quadcore.cl</b>. Responderemos tu solicitud dentro de los plazos legales.</p>
                <h6>6. Seguridad</h6>
                <p>Adoptamos medidas razonables de seguridad y cifrado para resguardar tu información frente a accesos no autorizados, pérdida o alteración.</p>
                <p class="text-muted small mb-0">Última actualización: junio de 2026.</p>
              </div></div>
            </div>
            `
        },

        'compras-devoluciones': {
            titulo: 'Compras y devoluciones',
            html: `
            <div class="gw-page">
              <header class="gw-head">
                <p class="gw-kicker"><i class="bi bi-bag-check"></i> Legal</p>
                <h1>Política de compras y devoluciones</h1>
                <p class="lead">Cómo se perfecciona tu compra y en qué casos puedes devolver un producto, conforme a la Ley N° 19.496 sobre protección de los derechos del consumidor.</p>
              </header>
              <div class="gw-body"><div class="qc-terms-body">
                <h6>1. Perfeccionamiento de la compra</h6>
                <p>La compra se entiende perfeccionada una vez que el pago ha sido validado y aceptado por QuadCore. Recibirás un correo de confirmación con el detalle de tu pedido. Los precios están expresados en pesos chilenos (CLP) e incluyen IVA.</p>
                <h6>2. Derecho a retracto</h6>
                <p>Tienes <b>10 días corridos</b> desde la recepción del producto para retractarte de la compra, siempre que el producto se encuentre sin uso, en su empaque original y con todos sus accesorios y manuales. No aplica a productos que por su naturaleza no puedan ser devueltos o puedan deteriorarse rápidamente.</p>
                <h6>3. Productos con falla</h6>
                <p>Si el producto presenta fallas de fabricación, puedes ejercer la garantía legal dentro de los plazos que establece la ley. En ese caso cubrimos los costos de envío para la revisión. Revisa el detalle en <b>Garantía y devoluciones</b>.</p>
                <h6>4. Cómo solicitar una devolución</h6>
                <p>Escríbenos a <b>devoluciones@quadcore.cl</b> con tu número de pedido y el motivo. Te enviaremos las instrucciones y, cuando corresponda, una etiqueta de despacho para que nos devuelvas el producto.</p>
                <h6>5. Condiciones</h6>
                <p>El producto debe presentarse en las condiciones descritas. Una vez recibido y revisado, gestionaremos el cambio o el reembolso según corresponda. Consulta los plazos y medios en nuestra <b>Política de reembolso</b>.</p>
                <p class="text-muted small mb-0">Última actualización: junio de 2026.</p>
              </div></div>
            </div>
            `
        },

        reembolsos: {
            titulo: 'Política de reembolso',
            html: `
            <div class="gw-page">
              <header class="gw-head">
                <p class="gw-kicker"><i class="bi bi-cash-coin"></i> Legal</p>
                <h1>Política de reembolso</h1>
                <p class="lead">Cuándo y cómo te devolvemos tu dinero cuando corresponde una devolución o anulación de compra.</p>
              </header>
              <div class="gw-body"><div class="qc-terms-body">
                <h6>1. Cuándo procede un reembolso</h6>
                <p>Procede un reembolso cuando ejerces tu derecho a retracto dentro del plazo legal, cuando un producto presenta una falla cubierta por garantía y eliges la devolución del dinero, o cuando no podemos completar tu pedido por falta de stock u otra causa atribuible a QuadCore.</p>
                <h6>2. Medio de reembolso</h6>
                <p>El reembolso se realiza por el <b>mismo medio de pago</b> utilizado en la compra. Si pagaste con tarjeta, el monto se devuelve a la misma tarjeta; si pagaste por transferencia, a la cuenta bancaria que nos indiques a tu nombre.</p>
                <h6>3. Plazos</h6>
                <p>Una vez aprobada la devolución y recibido el producto cuando corresponda, gestionamos el reembolso dentro de los <b>siguientes 10 días hábiles</b>. El tiempo en que se ve reflejado en tu cuenta o tarjeta depende de tu banco o emisor.</p>
                <h6>4. Monto</h6>
                <p>El reembolso corresponde al valor pagado por el producto. Cuando la devolución se origina en una falla o en un error de QuadCore, también se reintegran los costos de despacho. En los retractos voluntarios, el costo de envío de devolución puede ser de cargo del cliente.</p>
                <h6>5. Consultas</h6>
                <p>Para el estado de tu reembolso escríbenos a <b>devoluciones@quadcore.cl</b> indicando tu número de pedido.</p>
                <p class="text-muted small mb-0">Última actualización: junio de 2026.</p>
              </div></div>
            </div>
            `
        },

        terminos: {
            titulo: 'Términos de uso',
            html: `
            <div class="gw-page">
              <header class="gw-head">
                <p class="gw-kicker"><i class="bi bi-file-text"></i> Legal</p>
                <h1>Términos de uso</h1>
                <p class="lead">Condiciones que regulan el uso de este sitio y las compras realizadas en él.</p>
              </header>
              <div class="gw-body"><div class="qc-terms-body">
                <h6>1. Precios y disponibilidad</h6>
                <p>Todos los precios están expresados en pesos chilenos (CLP) e incluyen IVA, salvo que se indique lo contrario. La disponibilidad de productos, precios, promociones y especificaciones puede variar sin previo aviso. La compra se perfecciona una vez que el pago ha sido validado y aceptado por QuadCore.</p>
                <h6>2. Despacho y entrega</h6>
                <p>Realizamos envíos a todo el territorio nacional mediante empresas de transporte autorizadas. Los plazos de entrega son referenciales y pueden variar según la comuna de destino o periodos de alta demanda. Los costos de envío se informan antes de finalizar la compra.</p>
                <h6>3. Garantía legal y devoluciones</h6>
                <p>Todos los productos cuentan con garantía legal conforme a la legislación chilena vigente y con la garantía del fabricante cuando corresponda. Para gestionar cambios o devoluciones revisa nuestras políticas de <b>Compras y devoluciones</b> y <b>Reembolso</b>.</p>
                <h6>4. Medios de pago y seguridad</h6>
                <p>Aceptamos pagos mediante plataformas certificadas y seguras. Todas las transacciones usan protocolos de cifrado estándar de la industria. QuadCore no almacena información de tarjetas de crédito o débito.</p>
                <h6>5. Protección de datos personales</h6>
                <p>El tratamiento de tus datos se rige por nuestra <b>Política de privacidad</b>. Los datos se utilizan exclusivamente para gestionar compras, despachos, documentación tributaria, soporte postventa y obligaciones legales.</p>
                <h6>6. Uso del sitio</h6>
                <p>El usuario se compromete a usar el sitio de forma lícita y a no realizar acciones que afecten su funcionamiento o seguridad. Los contenidos, marcas y diseños del sitio son de propiedad de QuadCore y no pueden reproducirse sin autorización.</p>
                <h6>7. Aceptación</h6>
                <p>Al utilizar este sitio y completar una compra, declaras haber leído, comprendido y aceptado estos Términos de uso. QuadCore podrá actualizarlos periódicamente, publicando siempre la versión vigente en el sitio.</p>
                <p class="text-muted small mb-0">Última actualización: junio de 2026.</p>
              </div></div>
            </div>
            `
        }
    },

    render(slug) {
        const view = document.getElementById('view-generic');
        if (!view) return;
        const pag = this.contenido[slug];
        if (!pag) {
            UI.mostrarVacio(view, { icono: 'bi-exclamation-triangle', titulo: 'Página no encontrada' });
            return;
        }
        view.innerHTML = pag.html;
    }
};