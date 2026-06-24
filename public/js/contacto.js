/**
 * contacto.js — Vista de Contacto (#/contacto)  ·  TAREA LEONARDO #2
 * Solo se edita este archivo + css/leo.css. El form no tiene backend: solo muestra un toast.
 */

// Datos de contacto (definidos localmente para no depender de import)
const CONTACT_INFO = {
    address: 'Av. Providencia 1234, Santiago, Chile',
    phone: '+56 2 2123 4567',
    email: 'contacto@quadcore.cl',
    schedule: 'Lun a Vie 9:00–18:00 · Sáb 10–14h'
};

const Contacto = {
    render() {
        const view = document.getElementById('view-generic');
        if (!view) return;
        view.innerHTML = `
            <div class="leo-contacto">
                <div class="gw-page">
                    <header class="gw-head">
                        <p class="gw-kicker"><i class="bi bi-headset"></i> Atención al cliente</p>
                        <h1>Contáctanos</h1>
                        <p class="lead">¿Tienes dudas sobre un producto o necesitas servicio técnico? Escríbenos y te respondemos dentro de 24 horas hábiles.</p>
                    </header>
                </div>

                <div class="row g-4 align-items-stretch">
                    <div class="col-lg-6">
                        <div class="leo-card h-100">
                            <h3 class="leo-card-title"><i class="bi bi-chat-dots"></i> Envíanos un mensaje</h3>
                            <form id="contacto-form" class="leo-contacto-form" novalidate>
                                <div class="leo-field">
                                    <label for="c-nombre">Nombre</label>
                                    <div class="leo-input-icon">
                                        <i class="bi bi-person"></i>
                                        <input class="form-control" id="c-nombre" placeholder="Tu nombre" required>
                                    </div>
                                    <small class="leo-error">Ingresa tu nombre.</small>
                                </div>
                                <div class="leo-field">
                                    <label for="c-email">Email</label>
                                    <div class="leo-input-icon">
                                        <i class="bi bi-envelope"></i>
                                        <input class="form-control" id="c-email" type="email" placeholder="tucorreo@ejemplo.com" required>
                                    </div>
                                    <small class="leo-error">Ingresa un email válido.</small>
                                </div>
                                <div class="leo-field">
                                    <label for="c-asunto">Asunto</label>
                                    <div class="leo-input-icon">
                                        <i class="bi bi-tag"></i>
                                        <select class="form-control" id="c-asunto" required>
                                            <option value="">Selecciona un tema…</option>
                                            <option>Consulta de producto</option>
                                            <option>Servicio técnico</option>
                                            <option>Estado de mi pedido</option>
                                            <option>Garantía y devoluciones</option>
                                            <option>Otro</option>
                                        </select>
                                    </div>
                                    <small class="leo-error">Selecciona un asunto.</small>
                                </div>
                                <div class="leo-field">
                                    <label for="c-msg">Mensaje</label>
                                    <textarea class="form-control" id="c-msg" rows="4" placeholder="Cuéntanos en qué podemos ayudarte" required></textarea>
                                    <small class="leo-error">Escribe tu mensaje.</small>
                                </div>
                                <button class="btn leo-submit" type="submit"><i class="bi bi-send"></i> Enviar mensaje</button>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="leo-card leo-contacto-info h-100">
                            <h3 class="leo-card-title"><i class="bi bi-geo-alt"></i> Visítanos</h3>
                            <iframe class="qc-mapframe" title="Mapa de QuadCore en Providencia, Santiago" loading="lazy"
                                src="https://www.openstreetmap.org/export/embed.html?bbox=-70.6250,-33.4305,-70.6130,-33.4225&layer=mapnik&marker=-33.4265,-70.6190"></iframe>
                            <ul class="leo-info-list">
                                <li><i class="bi bi-geo-alt"></i><div><strong>Dirección</strong><span>${CONTACT_INFO.address}</span></div></li>
                                <li><i class="bi bi-telephone"></i><div><strong>Teléfono</strong><span>${CONTACT_INFO.phone}</span></div></li>
                                <li><i class="bi bi-envelope"></i><div><strong>Email</strong><span>${CONTACT_INFO.email}</span></div></li>
                                <li><i class="bi bi-clock"></i><div><strong>Horario</strong><span>${CONTACT_INFO.schedule}</span></div></li>
                            </ul>
                            <div class="leo-social">
                                <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                                <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                                <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                                <a href="https://wa.me/${CONTACT_INFO.phone.replace(/[^0-9]/g, '')}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;

        const form = document.getElementById('contacto-form');

        const validar = (el) => {
            const field = el.closest('.leo-field');
            if (!field) return true;
            const ok = el.checkValidity();
            field.classList.toggle('is-invalid', !ok);
            field.classList.toggle('is-valid', ok && el.value.trim() !== '');
            return ok;
        };

        form.querySelectorAll('input, select, textarea').forEach((el) => {
            el.addEventListener('blur', () => validar(el));
            el.addEventListener('input', () => {
                if (el.closest('.leo-field')?.classList.contains('is-invalid')) validar(el);
            });
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            let todoOk = true;
            form.querySelectorAll('input, select, textarea').forEach((el) => {
                if (!validar(el)) todoOk = false;
            });
            if (!todoOk) {
                App.showToast('Revisa los campos marcados en rojo.', 'error');
                return;
            }
            App.showToast('¡Gracias! Te responderemos pronto.', 'success');
            form.reset();
            form.querySelectorAll('.leo-field').forEach((f) => f.classList.remove('is-valid', 'is-invalid'));
        });
    }
};

// Hacer Contacto global
window.Contacto = Contacto;