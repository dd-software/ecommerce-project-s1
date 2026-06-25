/**
 * auth.js - Manejo de autenticación: login y registro
 */

const Auth = {
    /**
     * Inicializa formularios de auth
     */
    init() {
        this.initLoginForm();
        this.initRegisterForm();
    },

    /**
     * Configura formulario de login
     */
    initLoginForm() {
        const form = document.getElementById('login-form');
        if (!form) return;

        // Mostrar/ocultar contraseña
        const passInput = document.getElementById('login-password');
        const passToggle = document.getElementById('login-pass-toggle');
        passToggle?.addEventListener('click', () => {
            const show = passInput.type === 'password';
            passInput.type = show ? 'text' : 'password';
            passToggle.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });

        // Recordarme: precargar el email guardado
        const savedEmail = localStorage.getItem('qc_remember_email');
        if (savedEmail) {
            document.getElementById('login-email').value = savedEmail;
            const chk = document.getElementById('login-remember');
            if (chk) chk.checked = true;
        }

        // "¿Olvidaste tu contraseña?": envía el enlace de reset al email del campo de arriba.
        document.getElementById('login-forgot')?.addEventListener('click', async (e) => {
            e.preventDefault();
            const emailInput = document.getElementById('login-email');
            const email = emailInput.value.trim();
            if (!email) {
                App.showToast?.('Escribí tu email arriba y volvé a tocar el enlace.', 'info');
                emailInput.focus();
                return;
            }
            try {
                await fetch(`${App.apiBase}/auth/recuperar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });
            } catch (err) { /* respuesta genérica igual */ }
            App.showToast?.('Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.', 'success');
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const email = document.getElementById('login-email').value.trim();
            const password = document.getElementById('login-password').value;
            const errorDiv = document.getElementById('login-error');
            const submitBtn = document.getElementById('login-submit');

            if (errorDiv) errorDiv.classList.add('d-none');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Iniciando sesión...';
            }

            try {
                const resp = await fetch(`${App.apiBase}/auth/login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await resp.json();

                if (data.success) {
                    App.setAuth(data.data.token, data.data.usuario);

                    // Recordarme: guardar o limpiar el email
                    if (document.getElementById('login-remember')?.checked) {
                        localStorage.setItem('qc_remember_email', email);
                    } else {
                        localStorage.removeItem('qc_remember_email');
                    }

                    // Sincronizar carrito
                    const sessionId = App.getSessionId();
                    if (sessionId) {
                        try {
                            await App.fetchAuth(`${App.apiBase}/carrito/sincronizar`, {
                                method: 'POST',
                                body: JSON.stringify({ session_id: sessionId })
                            });
                        } catch (e) { /* silencioso */ }
                    }

                    // SPA: cerrar modal, actualizar UI y navegar sin recargar
                    bootstrap.Modal.getInstance(document.getElementById('loginModal'))?.hide();
                    App.updateNavbar();
                    if (typeof Carrito !== 'undefined') Carrito.loadCart();
                    App.showToast?.('¡Bienvenido de vuelta!', 'success');
                    if (data.data.usuario.rol === 'admin') {
                        location.hash = '#/admin';
                    } else if (typeof Router !== "undefined") {
                        Router.render();   // refresca la vista actual ya con sesión iniciada
                    }
                } else {
                    if (errorDiv) {
                        errorDiv.textContent = data.error?.message || 'Credenciales incorrectas.';
                        errorDiv.classList.remove('d-none');
                    }
                }
            } catch (err) {
                if (errorDiv) {
                    errorDiv.textContent = 'Error de conexión. Intenta nuevamente.';
                    errorDiv.classList.remove('d-none');
                }
            }

            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Iniciar Sesión <i class="bi bi-arrow-right"></i>';
            }
        });
    },

    /**
     * Vista #/reset?token=... — formulario para fijar nueva contraseña.
     */
    renderReset(token) {
        const view = document.getElementById('view-generic');
        if (!view) return;

        if (!token) {
            view.innerHTML = `<div class="leo-card" style="max-width:480px;margin:48px auto;text-align:center;">
                <h1 class="h4 mb-2">Enlace inválido</h1>
                <p class="text-muted mb-0">El enlace de recuperación no es válido. Pedí uno nuevo desde "¿Olvidaste tu contraseña?".</p>
            </div>`;
            return;
        }

        view.innerHTML = `
            <div class="leo-card" style="max-width:480px;margin:48px auto;">
                <h1 class="h4 mb-1">Nueva contraseña</h1>
                <p class="text-muted" style="font-size:.9rem;">Elegí una contraseña segura: mínimo 8 caracteres, una mayúscula, un número y un carácter especial.</p>
                <div id="reset-error" class="alert alert-danger d-none py-2" style="font-size:.9rem;"></div>
                <form id="reset-form">
                    <div class="mb-3">
                        <label class="form-label">Nueva contraseña</label>
                        <input type="password" id="reset-password" class="form-control" required autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Repetir contraseña</label>
                        <input type="password" id="reset-password2" class="form-control" required autocomplete="new-password">
                    </div>
                    <button type="submit" id="reset-submit" class="btn btn-accent w-100">Cambiar contraseña</button>
                </form>
            </div>`;

        const form = document.getElementById('reset-form');
        const errorDiv = document.getElementById('reset-error');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const password = document.getElementById('reset-password').value;
            const password2 = document.getElementById('reset-password2').value;
            const submitBtn = document.getElementById('reset-submit');
            errorDiv.classList.add('d-none');

            if (password !== password2) {
                errorDiv.textContent = 'Las contraseñas no coinciden.';
                errorDiv.classList.remove('d-none');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';
            try {
                const resp = await fetch(`${App.apiBase}/auth/restablecer`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token, password })
                });
                const data = await resp.json();
                if (data.success) {
                    App.showToast?.('Contraseña actualizada. Iniciá sesión con la nueva.', 'success');
                    location.hash = '#/';
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('loginModal'))?.show();
                } else {
                    errorDiv.textContent = data.error?.message || 'No se pudo cambiar la contraseña.';
                    errorDiv.classList.remove('d-none');
                }
            } catch (err) {
                errorDiv.textContent = 'Error de conexión. Intentá nuevamente.';
                errorDiv.classList.remove('d-none');
            }
            submitBtn.disabled = false;
            submitBtn.textContent = 'Cambiar contraseña';
        });
    },

    /**
     * Configura formulario de registro
     */
    initRegisterForm() {
        const form = document.getElementById('register-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const nombre = document.getElementById('reg-nombre').value.trim();
            const apellido = document.getElementById('reg-apellido').value.trim();
            const email = document.getElementById('reg-email').value.trim();
            const password = document.getElementById('reg-password').value;
            const password2 = document.getElementById('reg-password2').value;
            const errorDiv = document.getElementById('register-error');
            const submitBtn = document.getElementById('register-submit');

            if (errorDiv) errorDiv.classList.add('d-none');

            // Validaciones frontend
            if (password !== password2) {
                if (errorDiv) {
                    errorDiv.textContent = 'Las contraseñas no coinciden.';
                    errorDiv.classList.remove('d-none');
                }
                return;
            }

            if (password.length < 8) {
                if (errorDiv) {
                    errorDiv.textContent = 'La contraseña debe tener al menos 8 caracteres.';
                    errorDiv.classList.remove('d-none');
                }
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Registrando...';
            }

            try {
                const resp = await fetch(`${App.apiBase}/auth/registro`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nombre, apellido, email, password })
                });

                const data = await resp.json();

                if (data.success) {
                    App.setAuth(data.data.token, data.data.usuario);
                    bootstrap.Modal.getInstance(document.getElementById('registerModal'))?.hide();
                    App.updateNavbar();
                    if (typeof Carrito !== 'undefined') Carrito.loadCart();
                    App.showToast?.('¡Cuenta creada! Bienvenido a QuadCore', 'success');
                    if (typeof Router !== "undefined") Router.render();
                } else {
                    if (errorDiv) {
                        errorDiv.textContent = data.error?.message || 'Error al registrar.';
                        errorDiv.classList.remove('d-none');
                    }
                }
            } catch (err) {
                if (errorDiv) {
                    errorDiv.textContent = 'Error de conexión.';
                    errorDiv.classList.remove('d-none');
                }
            }

            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Crear Cuenta';
            }
        });
    }
};
