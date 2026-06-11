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

                    // Redirigir según rol
                    const redirect = new URLSearchParams(window.location.search).get('redirect');
                    if (data.data.usuario.rol === 'admin') {
                        window.location.href = '/admin.html';
                    } else {
                        window.location.href = redirect || '/';
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
                submitBtn.textContent = 'Iniciar Sesión';
            }
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
                    window.location.href = '/';
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
