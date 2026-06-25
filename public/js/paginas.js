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
                <p>En QuadCore SpA valoramos y respetamos la privacidad de quienes visitan y compran en nuestro sitio. La presente Política describe de manera transparente cómo recopilamos, utilizamos, almacenamos y protegemos tus datos personales, en cumplimiento de la Ley N° 19.628 sobre Protección de la Vida Privada y sus modificaciones introducidas por la Ley N° 21.719 sobre protección de datos personales.</p>

                <h6>1. Responsable del tratamiento</h6>
                <p>El responsable del tratamiento de tus datos es <b>QuadCore SpA</b>, RUT 77.123.456-7, con domicilio comercial en Av. Providencia 1234, Santiago de Chile. Para cualquier consulta relacionada con tus datos personales puedes contactarnos en <b>privacidad@quadcore.cl</b>.</p>

                <h6>2. Datos que recopilamos</h6>
                <p>Recopilamos únicamente los datos necesarios para prestarte el servicio. Estos incluyen: (i) datos de identificación y contacto, como nombre, apellido, RUT cuando corresponda, correo electrónico y teléfono; (ii) datos de despacho, como dirección, comuna y región de entrega; (iii) datos de tu cuenta, como tu historial de pedidos y preferencias; y (iv) datos técnicos de navegación, como dirección IP y tipo de dispositivo, recopilados de forma agregada para mejorar el sitio.</p>
                <p>Es importante señalar que <b>no almacenamos los datos de tu tarjeta de crédito o débito</b>. Los pagos se procesan directamente en la plataforma de nuestro proveedor de pagos, que cumple con los estándares de seguridad de la industria (PCI DSS).</p>

                <h6>3. Finalidades del tratamiento</h6>
                <p>Tratamos tus datos con las siguientes finalidades: gestionar y dar seguimiento a tus compras; coordinar el despacho y la entrega de tus productos; emitir la documentación tributaria correspondiente; brindarte soporte y atención postventa; informarte sobre el estado de tus pedidos; prevenir fraudes; y cumplir las obligaciones legales y tributarias que nos resultan aplicables.</p>

                <h6>4. Base de licitud y consentimiento</h6>
                <p>El tratamiento de tus datos se funda en la ejecución del contrato de compraventa que celebras con nosotros, en el cumplimiento de obligaciones legales y, cuando corresponda, en el consentimiento que nos otorgas de forma libre e informada al registrarte o aceptar esta Política. Puedes revocar tu consentimiento en cualquier momento, sin que ello afecte la licitud del tratamiento previo.</p>

                <h6>5. Comunicación de datos a terceros</h6>
                <p>No vendemos, arrendamos ni cedemos tus datos personales a terceros con fines comerciales. Solo los compartimos cuando es estrictamente necesario para prestarte el servicio: con las empresas de transporte y logística para coordinar la entrega; con el procesador de pagos para validar la transacción; con proveedores tecnológicos que actúan como encargados de tratamiento bajo nuestras instrucciones; y con autoridades competentes cuando exista un requerimiento legal.</p>

                <h6>6. Conservación de los datos</h6>
                <p>Conservamos tus datos personales mientras mantengas una cuenta activa o sea necesario para cumplir las finalidades descritas, y posteriormente durante los plazos exigidos por la legislación tributaria, contable y de protección al consumidor. Cumplidos dichos plazos, los datos son eliminados o anonimizados de forma segura.</p>

                <h6>7. Tus derechos</h6>
                <p>De acuerdo con la legislación vigente, tienes derecho a <b>acceder</b> a tus datos, <b>rectificar</b> los que sean inexactos, <b>cancelar o suprimir</b> los que ya no sean necesarios, <b>oponerte</b> a determinados tratamientos y solicitar la <b>portabilidad</b> de tu información. Para ejercer cualquiera de estos derechos, escríbenos a <b>privacidad@quadcore.cl</b> indicando tu solicitud; responderemos dentro de los plazos legales. Si consideras que tus derechos no han sido respetados, puedes recurrir ante la autoridad de protección de datos competente.</p>

                <h6>8. Seguridad de la información</h6>
                <p>Adoptamos medidas técnicas y organizativas razonables para proteger tus datos frente a accesos no autorizados, pérdida, alteración o divulgación, incluyendo el cifrado de las comunicaciones, el control de accesos y el resguardo de nuestros sistemas. Ningún sistema es completamente infalible, pero trabajamos permanentemente para mantener un nivel de seguridad acorde a las buenas prácticas de la industria.</p>

                <h6>9. Cookies</h6>
                <p>Utilizamos cookies y tecnologías similares para recordar tu sesión, mantener tu carrito de compras y analizar el uso del sitio con fines estadísticos. Puedes configurar tu navegador para bloquear o eliminar las cookies, considerando que algunas funciones del sitio podrían verse afectadas.</p>

                <h6>10. Cambios en esta Política</h6>
                <p>Podremos actualizar esta Política para reflejar cambios legales, operativos o tecnológicos. Publicaremos siempre la versión vigente en este sitio, indicando su fecha de última actualización. Te recomendamos revisarla periódicamente.</p>

                <p class="text-muted small mb-0">Última actualización: junio de 2026.</p>
              </div></div>
            </div>
            `
        },

        'compras-devoluciones': {
            titulo: 'Política de compras y devoluciones',
            html: `
            <div class="gw-page">
              <header class="gw-head">
                <p class="gw-kicker"><i class="bi bi-bag-check"></i> Legal</p>
                <h1>Política de compras y devoluciones</h1>
                <p class="lead">Cómo se perfecciona tu compra y en qué casos puedes cambiar o devolver un producto, conforme a la Ley N° 19.496 sobre Protección de los Derechos de los Consumidores.</p>
              </header>
              <div class="gw-body"><div class="qc-terms-body">
                <p>En QuadCore queremos que compres con total confianza. Esta Política explica cómo se concreta tu compra, los medios de pago disponibles y los derechos que te asisten para cambiar, devolver o reclamar un producto, de acuerdo con la normativa chilena de protección al consumidor.</p>

                <h6>1. Proceso y perfeccionamiento de la compra</h6>
                <p>La compra se entiende perfeccionada una vez que el pago ha sido validado y aceptado, momento en el cual recibirás un correo de confirmación con el detalle de tu pedido y su número de seguimiento. Mientras el pago no sea confirmado, la operación se considera pendiente y el stock no queda reservado de forma definitiva.</p>

                <h6>2. Precios, impuestos y disponibilidad</h6>
                <p>Todos los precios se expresan en pesos chilenos (CLP) e incluyen el Impuesto al Valor Agregado (IVA), salvo que se indique expresamente lo contrario. Los precios, promociones y la disponibilidad de stock pueden variar sin previo aviso. En el evento de un error evidente de precio o de un quiebre de stock posterior a tu compra, te contactaremos para ofrecerte la corrección, la espera del reabastecimiento o el reembolso íntegro de lo pagado.</p>

                <h6>3. Medios de pago</h6>
                <p>Aceptamos los medios de pago habilitados en el checkout, procesados a través de plataformas certificadas y seguras. QuadCore no almacena los datos de tu tarjeta. La emisión de la boleta o factura electrónica se realiza conforme a la normativa tributaria vigente.</p>

                <h6>4. Despacho y entrega</h6>
                <p>Realizamos envíos a todo el territorio nacional mediante empresas de transporte autorizadas. Los plazos de entrega son referenciales y se informan antes de finalizar la compra, pudiendo variar según la comuna de destino, la disponibilidad logística o periodos de alta demanda. Recibirás el número de seguimiento para monitorear tu pedido.</p>

                <h6>5. Derecho a retracto</h6>
                <p>Conforme al artículo 3° bis de la Ley N° 19.496, dispones de un plazo de <b>10 días corridos</b>, contados desde la recepción del producto, para poner término unilateral a la compra realizada a distancia, sin expresión de causa. Para ejercerlo, el producto debe encontrarse <b>sin uso</b>, en su empaque original y con todos sus accesorios, manuales y elementos de presentación. El derecho a retracto no aplica a productos personalizados, a aquellos que por su naturaleza no puedan ser devueltos o puedan deteriorarse o caducar con rapidez, ni a contenidos digitales ya descargados, entre otras excepciones legales.</p>

                <h6>6. Garantía legal por fallas</h6>
                <p>Independiente del derecho a retracto, si dentro de los <b>6 meses</b> siguientes a la compra el producto presenta fallas o no cumple lo ofrecido, la ley te faculta para optar libremente entre la <b>reparación gratuita</b>, la <b>reposición</b> del producto o la <b>devolución de lo pagado</b> (derecho conocido como "garantía legal" o "3x3", reforzado por la Ley N° 21.398). En estos casos, QuadCore asume los costos de traslado para la revisión técnica. Adicionalmente, los productos pueden contar con la garantía voluntaria del fabricante por plazos mayores.</p>

                <h6>7. Cómo solicitar un cambio o devolución</h6>
                <p>Escríbenos a <b>devoluciones@quadcore.cl</b> indicando tu número de pedido y el motivo de la solicitud. Nuestro equipo te responderá con las instrucciones a seguir y, cuando corresponda, te enviará una etiqueta de despacho prepagada para que nos hagas llegar el producto sin costo. Una vez recibido y revisado, gestionaremos el cambio, la reparación o el reembolso según el caso.</p>

                <h6>8. Reembolsos</h6>
                <p>Cuando corresponda la devolución del dinero, el reembolso se efectúa por el mismo medio de pago utilizado en la compra y dentro de los plazos detallados en nuestra <b>Política de reembolso</b>.</p>

                <h6>9. Atención al consumidor</h6>
                <p>Ante cualquier inconveniente, nuestro canal de atención está disponible para ayudarte. Sin perjuicio de ello, podrás siempre ejercer los derechos que te reconoce la ley y, de estimarlo necesario, presentar tu reclamo ante el Servicio Nacional del Consumidor (SERNAC).</p>

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
                <p>Esta Política detalla en qué situaciones procede la devolución de tu dinero, el medio por el cual se realiza, los plazos involucrados y el monto a reintegrar. Complementa nuestra <b>Política de compras y devoluciones</b> y se aplica conforme a la Ley N° 19.496 sobre Protección de los Derechos de los Consumidores.</p>

                <h6>1. Cuándo procede un reembolso</h6>
                <p>Procede el reembolso cuando: (i) ejerces tu <b>derecho a retracto</b> dentro del plazo legal de 10 días corridos; (ii) un producto presenta una <b>falla cubierta por la garantía legal</b> y optas por la devolución del dinero en lugar de la reparación o el cambio; (iii) no podemos <b>completar tu pedido</b> por falta de stock u otra causa que nos sea atribuible; o (iv) se anula una compra antes de su despacho de común acuerdo.</p>

                <h6>2. Medio de reembolso</h6>
                <p>El reembolso se realiza siempre por el <b>mismo medio de pago</b> utilizado en la compra. Si pagaste con tarjeta de crédito o débito, el monto se reintegra a la misma tarjeta a través del procesador de pagos. Si pagaste mediante transferencia, lo depositaremos en una cuenta bancaria <b>a tu nombre</b> que nos indiques. Por motivos de seguridad, no realizamos reembolsos a cuentas o tarjetas de terceros.</p>

                <h6>3. Plazos</h6>
                <p>Una vez aprobada la solicitud y recibido el producto cuando corresponda, gestionamos el reembolso dentro de los <b>10 días hábiles</b> siguientes. El tiempo en que el dinero se ve efectivamente reflejado en tu cuenta o estado de tarjeta depende de los procesos internos de tu banco o emisor, por lo que puede tomar algunos días adicionales ajenos a nuestro control.</p>

                <h6>4. Monto a reembolsar</h6>
                <p>El reembolso corresponde al <b>valor efectivamente pagado</b> por el producto, incluidos los impuestos. Cuando la devolución se origina en una falla del producto o en un error de QuadCore, se reintegran además los <b>costos de despacho</b>. Tratándose de un retracto voluntario, el valor del producto se reembolsa íntegramente, pudiendo el costo del envío de devolución ser de cargo del cliente, según se haya informado.</p>

                <h6>5. Compras con cupones o descuentos</h6>
                <p>Si tu compra incluyó un cupón o descuento, el reembolso se calcula sobre el monto realmente pagado. Los cupones de un solo uso asociados a una compra reembolsada no se reactivan, salvo que la causa de la devolución sea atribuible a QuadCore.</p>

                <h6>6. Consultas y seguimiento</h6>
                <p>Para conocer el estado de tu reembolso, escríbenos a <b>devoluciones@quadcore.cl</b> indicando tu número de pedido. Si no estás conforme con la respuesta, puedes ejercer los derechos que te reconoce la ley y presentar tu reclamo ante el Servicio Nacional del Consumidor (SERNAC).</p>

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
                <p class="lead">Condiciones que regulan el acceso a este sitio, el uso de la cuenta y las compras realizadas en QuadCore.</p>
              </header>
              <div class="gw-body"><div class="qc-terms-body">
                <p>Los presentes Términos de uso regulan la relación entre QuadCore SpA ("QuadCore", "nosotros") y las personas que acceden, navegan o realizan compras en este sitio ("el usuario", "tú"). Al utilizar el sitio, declaras haber leído y aceptado estas condiciones. Si no estás de acuerdo con ellas, te pedimos abstenerte de utilizar la plataforma.</p>

                <h6>1. Identificación del proveedor</h6>
                <p>Este sitio es operado por <b>QuadCore SpA</b>, RUT 77.123.456-7, con domicilio en Av. Providencia 1234, Santiago de Chile. Puedes contactarnos a través de los canales publicados en la sección de contacto.</p>

                <h6>2. Cuenta de usuario</h6>
                <p>Para realizar compras puedes necesitar crear una cuenta, entregando información veraz, exacta y actualizada. Eres responsable de mantener la confidencialidad de tus credenciales y de toda actividad realizada bajo tu cuenta. Si detectas un uso no autorizado, debes notificárnoslo de inmediato. Podremos suspender o cancelar cuentas que infrinjan estos Términos o la ley.</p>

                <h6>3. Precios, productos y disponibilidad</h6>
                <p>Todos los precios se expresan en pesos chilenos (CLP) e incluyen IVA, salvo indicación expresa en contrario. Procuramos que la información de productos, imágenes, precios y especificaciones sea correcta y esté actualizada; no obstante, pueden existir errores involuntarios o variaciones de stock y precio sin previo aviso. La compra se perfecciona una vez validado y aceptado el pago.</p>

                <h6>4. Proceso de compra y pago</h6>
                <p>Al confirmar un pedido recibirás un correo con su detalle. Aceptamos los medios de pago habilitados en el checkout, procesados por plataformas certificadas que cumplen los estándares de seguridad de la industria. QuadCore no almacena los datos de tu tarjeta. Emitimos la documentación tributaria conforme a la normativa vigente.</p>

                <h6>5. Despacho y entrega</h6>
                <p>Realizamos envíos a todo el territorio nacional mediante empresas de transporte autorizadas. Los plazos son referenciales, se informan antes de finalizar la compra y pueden variar según la comuna de destino o periodos de alta demanda.</p>

                <h6>6. Garantía legal, retracto y devoluciones</h6>
                <p>Todos los productos cuentan con la garantía legal que establece la Ley N° 19.496 y, cuando corresponda, con la garantía voluntaria del fabricante. Asimismo, en las compras a distancia dispones del derecho a retracto en los términos que indica la ley. El detalle de plazos, condiciones y procedimientos se encuentra en nuestras <b>Política de compras y devoluciones</b> y <b>Política de reembolso</b>.</p>

                <h6>7. Uso permitido del sitio</h6>
                <p>Te comprometes a utilizar el sitio de forma lícita y de buena fe, absteniéndote de realizar acciones que afecten su funcionamiento, seguridad o disponibilidad, de vulnerar sus medidas de protección, o de utilizarlo con fines fraudulentos. Nos reservamos el derecho de restringir el acceso ante usos indebidos.</p>

                <h6>8. Propiedad intelectual</h6>
                <p>Los contenidos del sitio —incluyendo textos, logotipos, marcas, diseños, imágenes y código— son de propiedad de QuadCore o de sus respectivos titulares y se encuentran protegidos por la legislación aplicable. No está permitida su reproducción, distribución o uso sin autorización previa y por escrito.</p>

                <h6>9. Protección de datos personales</h6>
                <p>El tratamiento de tus datos personales se rige por nuestra <b>Política de privacidad</b>, que forma parte integrante de estos Términos. Te recomendamos revisarla para conocer cómo recopilamos, usamos y protegemos tu información.</p>

                <h6>10. Limitación de responsabilidad</h6>
                <p>QuadCore responde por sus obligaciones en los términos que establece la legislación de protección al consumidor. No seremos responsables por interrupciones del servicio derivadas de fuerza mayor, caso fortuito o causas ajenas a nuestro control razonable, sin perjuicio de los derechos que la ley reconoce a los consumidores.</p>

                <h6>11. Legislación aplicable y solución de controversias</h6>
                <p>Estos Términos se rigen por las leyes de la República de Chile. Cualquier controversia se someterá a los tribunales competentes, sin perjuicio del derecho del consumidor a recurrir al Servicio Nacional del Consumidor (SERNAC) y de las acciones que la ley le franquea.</p>

                <h6>12. Modificaciones</h6>
                <p>Podremos actualizar estos Términos para reflejar cambios operativos, comerciales o legales, publicando siempre la versión vigente en el sitio con su fecha de actualización. El uso del sitio con posterioridad a dichos cambios implica su aceptación.</p>

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